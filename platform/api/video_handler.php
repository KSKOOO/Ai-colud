<?php

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);


function videoErrorLog($message) {
    $logFile = __DIR__ . '/../logs/video_error.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    @file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    error_log($message);
}


set_error_handler(function($errno, $errstr, $errfile, $errline) {

    if ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED) {
        return true;
    }
    if ($errno === E_NOTICE || $errno === E_WARNING) {
        return true;
    }
    videoErrorLog("ERROR [$errno]: $errstr in $errfile on line $errline");
    return false;
});

set_exception_handler(function($e) {
    videoErrorLog("EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '服务器内部错误：' . $e->getMessage()]);
    exit;
});


$debug = isset($_GET['debug']) && $_GET['debug'] === '1';

videoErrorLog("=== Video handler started ===");


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']) {
    echo json_encode(['success' => false, 'error' => '请先登录']);
    exit;
}


try {
    @set_time_limit(0);
    @ini_set('max_execution_time', '0');
    @ini_set('max_input_time', '0');
} catch (Exception $e) {

}


@ini_set('output_buffering', 'off');
@ini_set('implicit_flush', 'on');

require_once __DIR__ . '/../includes/Database.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userId = $_SESSION['user']['id'];

try {
    $db = Database::getInstance();
    
    switch ($action) {
        case 'generate':

            handleVideoGenerate($db, $userId);
            break;
            
        case 'list':

            handleVideoList($db, $userId);
            break;
            
        case 'view':

            handleVideoView($db);
            break;
            
        case 'delete':

            handleVideoDelete($db, $userId);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => '未知的操作类型']);
    }
    
} catch (Exception $e) {
    videoErrorLog("Video handler error: " . $e->getMessage());
    $errorMsg = $debug ? '服务器错误: ' . $e->getMessage() : '服务器错误，请稍后重试';
    echo json_encode(['success' => false, 'error' => $errorMsg]);
}


function handleVideoGenerate($db, $userId) {
    $prompt = $_POST['prompt'] ?? '';
    $ratio = $_POST['ratio'] ?? '16:9';
    $duration = intval($_POST['duration'] ?? 5);
    $provider = $_POST['provider'] ?? '';
    $model = $_POST['model'] ?? '';
    
    if (empty($prompt)) {
        echo json_encode(['success' => false, 'error' => '请输入视频描述']);
        return;
    }
    

    $duration = min(max($duration, 5), 60);
    

    $videoId = uniqid('vid_');
    $timestamp = time();
    

    $outputDir = __DIR__ . '/../uploads/videos/';
    if (!is_dir($outputDir)) {
        $created = @mkdir($outputDir, 0755, true);
        if (!$created) {
            error_log("Failed to create output directory: $outputDir");
            echo json_encode(['success' => false, 'error' => '无法创建视频输出目录']);
            return;
        }
    }
    

    if (!is_writable($outputDir)) {
        videoErrorLog("Output directory not writable: $outputDir");
        echo json_encode(['success' => false, 'error' => '视频输出目录不可写']);
        return;
    }
    
    $outputFileName = 'generated_video_' . $timestamp . '_' . $videoId . '.mp4';
    $outputPath = $outputDir . $outputFileName;
    $webPath = 'uploads/videos/' . $outputFileName;
    

    $resolution = parseResolution($ratio);
    

    $result = generateVideoWithAI($prompt, $resolution, $duration, $outputPath, $provider, $model);
    
    if ($result['success']) {

        if (!file_exists($outputPath)) {
            echo json_encode([
                'success' => false,
                'error' => '视频文件生成失败：文件不存在'
            ]);
            return;
        }
        
        $fileSize = filesize($outputPath);
        if ($fileSize < 1024) {
            echo json_encode([
                'success' => false,
                'error' => '视频文件生成失败：文件太小 (' . $fileSize . ' 字节)'
            ]);
            return;
        }

        // 获取实际视频时长
        $actualDuration = getActualVideoDuration($outputPath);
        if ($actualDuration <= 0) {
            $actualDuration = $duration;
        }

        try {
            $db->insert('generated_videos', [
                'user_id' => $userId,
                'video_id' => $videoId,
                'prompt' => $prompt,
                'file_path' => $outputPath,
                'web_path' => $webPath,
                'resolution' => $resolution['width'] . 'x' . $resolution['height'],
                'duration' => $actualDuration,
                'file_size' => $fileSize,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {

            error_log("Save video record failed: " . $e->getMessage());
        }

        // 记录用量统计
        try {
            require_once __DIR__ . '/../lib/UsageTracker.php';
            $usageTracker = new UsageTracker();
            // 视频生成按秒数估算Token (每秒约100 tokens)
            $estimatedTokens = $actualDuration * 100;
            $usageTracker->recordUsage(
                $userId,
                'video_generate',
                $provider ?: 'wanx',
                intval($estimatedTokens * 0.3),  // 输入约30%
                intval($estimatedTokens * 0.7),  // 输出约70%
                ['prompt' => $prompt, 'duration' => $actualDuration, 'resolution' => $resolution]
            );
        } catch (Exception $e) {
            error_log("Record video usage failed: " . $e->getMessage());
        }
        

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = dirname($scriptName);
        

        $basePath = dirname($scriptDir);
        $basePath = str_replace('\\', '/', $basePath);
        

        $videoUrl = rtrim($protocol . '://' . $host . $basePath, '/') . '/' . ltrim($webPath, '/');
        

        if (!file_exists($outputPath)) {
            throw new Exception('视频文件未生成');
        }
        
        $fileSize = filesize($outputPath);
        if ($fileSize < 1024) {
            throw new Exception('视频文件无效（太小）');
        }
        
        $response = [
            'success' => true,
            'video' => [
                'id' => $videoId,
                'url' => $videoUrl,
                'prompt' => $prompt,
                'resolution' => $resolution['width'] . 'x' . $resolution['height'],
                'duration' => $duration,
                'created_at' => '刚刚'
            ]
        ];
        

        if (isset($result['source'])) {
            $response['source'] = $result['source'];
        }
        
        echo json_encode($response);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? '视频生成失败'
        ]);
    }
}


function generateVideoWithAI($prompt, $resolution, $duration, $outputPath, $providerId, $model) {

    require_once __DIR__ . '/../lib/AIProviderManager.php';
    $providerManager = new AIProviderManager();
    

    $provider = null;
    $isQwen = false;
    
    if ($providerId) {

        $provider = $providerManager->getProvider($providerId);
        

        if (!$provider) {
            $allProviders = $providerManager->getProviders(true);
            foreach ($allProviders as $id => $p) {
                if ($p['type'] === $providerId) {
                    $provider = $p;
                    $providerId = $id;
                    break;
                }
            }
        }
        

        if ($provider && isset($provider['type']) && $provider['type'] === 'qwen') {
            $isQwen = true;
        }
    }
    

    videoErrorLog("Video generation - Input: " . ($providerId ?: 'none') . ", Found provider: " . ($provider ? 'yes' : 'no') . ", Is Qwen: " . ($isQwen ? 'yes' : 'no'));
    

    if ($isQwen) {
        videoErrorLog("Using Wanx API for video generation");
        $result = generateVideoWithQwenWanx($prompt, $resolution, $duration, $outputPath, $providerId);
        videoErrorLog("Wanx result: " . json_encode(['success' => $result['success'] ?? false]));
        return $result;
    }
    

    return [
        'success' => false,
        'error' => '当前选择的提供商不支持视频生成，请选择通义千问(Wanx)提供商'
    ];
}


function generateVideoWithQwenWanx($prompt, $resolution, $duration, $outputPath, $providerId) {
    try {

        require_once __DIR__ . '/../lib/AIProviderManager.php';
        $providerManager = new AIProviderManager();
        $provider = $providerManager->getProvider($providerId);
        
        if (!$provider) {
            return ['success' => false, 'error' => '提供商配置不存在'];
        }
        
        $apiKey = $provider['config']['api_key'] ?? '';
        if (empty($apiKey)) {
            return ['success' => false, 'error' => '未配置API密钥'];
        }
        

        $apiUrl = 'https://dashscope.aliyuncs.com/api/v1/services/aigc/video-generation/video-synthesis';
        

        $width = $resolution['width'];
        $height = $resolution['height'];
        

        $supportedSizes = [
            '1280*720' => ['width' => 1280, 'height' => 720],
            '854*480' => ['width' => 854, 'height' => 480],
            '768*768' => ['width' => 768, 'height' => 768],
            '720*1280' => ['width' => 720, 'height' => 1280],
            '480*480' => ['width' => 480, 'height' => 480],
            '1024*768' => ['width' => 1024, 'height' => 768],
            '1280*768' => ['width' => 1280, 'height' => 768],
            '768*1024' => ['width' => 768, 'height' => 1024],
            '1024*1024' => ['width' => 1024, 'height' => 1024],
            '384*384' => ['width' => 384, 'height' => 384],
            '854*640' => ['width' => 854, 'height' => 640],
        ];
        
        $targetSize = '1280*720';
        $minDiff = PHP_INT_MAX;
        foreach ($supportedSizes as $size => $dims) {
            $diff = abs($dims['width'] - $width) + abs($dims['height'] - $height);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $targetSize = $size;
            }
        }
        

        $requestBody = [
            'model' => 'wanx2.1-t2v-turbo',
            'input' => [
                'prompt' => $prompt
            ],
            'parameters' => [
                'size' => $targetSize,

                'seed' => rand(1, 999999)
            ]
        ];
        

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            'X-DashScope-Async: enable'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            return ['success' => false, 'error' => 'API请求失败: ' . $curlError];
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['message'] ?? $errorData['error']['message'] ?? 'API返回错误: ' . $httpCode;
            return ['success' => false, 'error' => $errorMsg];
        }
        
        $data = json_decode($response, true);
        if (!$data || !isset($data['output']['task_id'])) {
            return ['success' => false, 'error' => 'API响应格式错误'];
        }
        
        $taskId = $data['output']['task_id'];
        

        $videoUrl = null;
        $maxAttempts = 150;
        $attempt = 0;
        
        videoErrorLog("Starting task polling for task: $taskId");
        
        while ($attempt < $maxAttempts) {
            sleep(2);
            $attempt++;
            

            if ($attempt % 15 === 0) {
                videoErrorLog("Polling attempt $attempt/$maxAttempts for task: $taskId");
            }
            
            $statusUrl = 'https://dashscope.aliyuncs.com/api/v1/tasks/' . $taskId;
            $ch = curl_init($statusUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            
            $statusResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                videoErrorLog("Task status query failed: HTTP $httpCode");
                continue;
            }
            
            $statusData = json_decode($statusResponse, true);
            if (!$statusData) {
                videoErrorLog("Failed to decode status response");
                continue;
            }
            
            $taskStatus = $statusData['output']['task_status'] ?? '';
            videoErrorLog("Task status: $taskStatus (attempt $attempt)");
            
            if ($taskStatus === 'SUCCEEDED') {
                $videoUrl = $statusData['output']['video_url'] ?? null;
                videoErrorLog("Task completed, video URL: " . ($videoUrl ? 'available' : 'missing'));
                break;
            } elseif ($taskStatus === 'FAILED') {
                $errorMsg = $statusData['output']['message'] ?? '视频生成失败';
                videoErrorLog("Task failed: $errorMsg");
                return ['success' => false, 'error' => $errorMsg];
            }

        }
        
        if (!$videoUrl) {
            videoErrorLog("Task polling timeout after $attempt attempts");
            return ['success' => false, 'error' => '等待视频生成超时（可能需要更长时间，请稍后检查视频列表）'];
        }
        

        $tempVideoPath = $outputPath . '.tmp';
        $ch = curl_init($videoUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        
        $videoData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        
        if (!$videoData || strlen($videoData) < 1024) {
            return ['success' => false, 'error' => '下载视频失败: 数据为空或太小 (' . strlen($videoData) . ' bytes)'];
        }
        
        if ($httpCode !== 200) {
            return ['success' => false, 'error' => '下载视频失败 (HTTP ' . $httpCode . ')'];
        }
        

        file_put_contents($tempVideoPath, $videoData);
        $savedSize = filesize($tempVideoPath);
        videoErrorLog("Video saved to temp file: $tempVideoPath, size: $savedSize bytes");
        

        $mimeType = detectVideoMimeType($tempVideoPath);
        videoErrorLog("Downloaded video MIME type: " . $mimeType . ", HTTP Content-Type: " . ($contentType ?? 'unknown'));
        

        if ($mimeType === 'video/mp4') {
            rename($tempVideoPath, $outputPath);
            @unlink($tempVideoPath);
            videoErrorLog("Video is already MP4, moved to: $outputPath");
        } else {

            videoErrorLog("Converting video from $mimeType to MP4");
            $convertResult = convertToMP4($tempVideoPath, $outputPath);
            @unlink($tempVideoPath);
            
            if (!$convertResult['success']) {
                videoErrorLog("Video conversion failed: " . $convertResult['error']);
                return ['success' => false, 'error' => '视频格式转换失败: ' . $convertResult['error']];
            }
            videoErrorLog("Video conversion successful");
        }
        

        if (file_exists($outputPath)) {
            $finalSize = filesize($outputPath);
            videoErrorLog("Final video file: $outputPath, size: $finalSize bytes");
            if ($finalSize > 1024) {
                return ['success' => true, 'source' => 'wanx'];
            }
        }
        
        return ['success' => false, 'error' => '保存视频文件失败'];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => '万相视频生成异常: ' . $e->getMessage()];
    }
}


function detectVideoMimeType($filePath) {

    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            return $mimeType;
        }
    }
    

    $handle = fopen($filePath, 'rb');
    if ($handle) {
        $bytes = fread($handle, 12);
        fclose($handle);
        

        $signatures = [
            'video/mp4' => [['ftyp', 4], ['isom', 4], ['mp42', 4], ['avc1', 4]],
            'video/webm' => [['\x1A\x45\xDF\xA3', 0]],
            'video/avi' => [['RIFF', 0], ['AVI ', 8]],
            'video/quicktime' => [['ftypqt', 4], ['moov', 4]],
        ];
        
        foreach ($signatures as $mime => $sigs) {
            $match = true;
            foreach ($sigs as $sig) {
                if (substr($bytes, $sig[1], strlen($sig[0])) !== $sig[0]) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                return $mime;
            }
        }
        

        if (substr($bytes, 4, 4) === 'ftyp') {
            return 'video/mp4';
        }
        if (substr($bytes, 0, 4) === "\x1A\x45\xDF\xA3") {
            return 'video/webm';
        }
    }
    

    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $extMap = [
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'avi' => 'video/avi',
        'mov' => 'video/quicktime',
    ];
    
    return $extMap[$ext] ?? 'application/octet-stream';
}


function convertToMP4($inputPath, $outputPath) {
    $ffmpegPath = getFFmpegPath();
    

    $testCmd = sprintf('"%s" -version 2>&1', $ffmpegPath);
    $testOutput = [];
    $testReturnCode = 0;
    @exec($testCmd, $testOutput, $testReturnCode);
    
    if ($testReturnCode !== 0) {
        videoErrorLog("FFmpeg not available at: $ffmpegPath");

        copy($inputPath, $outputPath);
        return ['success' => true, 'note' => 'FFmpeg不可用，直接保存原始文件'];
    }
    

    $cmd = sprintf(
        '"%s" -i "%s" -c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p ' .
        '-c:a aac -b:a 128k -movflags +faststart -y "%s" 2>&1',
        $ffmpegPath,
        $inputPath,
        $outputPath
    );
    
    $output = [];
    $returnCode = 0;
    @exec($cmd, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($outputPath) && filesize($outputPath) > 1024) {
        return ['success' => true];
    }
    

    videoErrorLog("FFmpeg conversion failed, copying original file");
    copy($inputPath, $outputPath);
    
    if (file_exists($outputPath) && filesize($outputPath) > 1024) {
        return ['success' => true, 'note' => '转换失败，使用原始文件'];
    }
    
    $error = implode("\n", array_slice($output, -5));
    return ['success' => false, 'error' => $error];
}


function isFFmpegAvailable() {
    $ffmpegPath = getFFmpegPath();
    $output = [];
    $returnCode = 0;
    @exec('"' . $ffmpegPath . '" -version 2>&1', $output, $returnCode);
    return $returnCode === 0 && !empty($output);
}


function createPlaceholderVideo($prompt, $resolution, $duration, $outputPath, $ffmpegPath) {
    $width = $resolution['width'];
    $height = $resolution['height'];
    

    $visualType = selectVisualEffect($prompt);
    

    $result = generateFFmpegVisualVideo($visualType, $width, $height, $duration, $outputPath, $ffmpegPath);
    
    if ($result['success'] && file_exists($outputPath) && filesize($outputPath) > 1024) {
        return ['success' => true];
    }
    

    return generateTestVideo($width, $height, $duration, $outputPath, $ffmpegPath);
}


function generateFFmpegVisualVideo($type, $width, $height, $duration, $outputPath, $ffmpegPath) {

    $filters = [

        'nature' => 'smptebars=duration=' . $duration . ':size=' . $width . 'x' . $height,

        'space' => 'testsrc=duration=' . $duration . ':size=' . $width . 'x' . $height . ':rate=30',

        'fire' => 'rgbtestsrc=duration=' . $duration . ':size=' . $width . 'x' . $height . ':rate=30',

        'water' => 'mandelbrot=s=' . $width . 'x' . $height . ':duration=' . $duration,

        'technology' => 'life=s=' . $width . 'x' . $height . ':duration=' . $duration,

        'city' => 'allrgb=s=' . $width . 'x' . $height . ':duration=' . $duration,

        'abstract' => 'smptebars=duration=' . $duration . ':size=' . $width . 'x' . $height,
    ];
    
    $filter = $filters[$type] ?? $filters['abstract'];
    

    $cmd = sprintf(
        '"%s" -f lavfi -i "%s" -vf "format=yuv420p" -c:v libx264 -preset ultrafast -crf 28 -pix_fmt yuv420p -movflags +faststart -y "%s" 2>&1',
        $ffmpegPath,
        $filter,
        $outputPath
    );
    
    $output = [];
    $returnCode = 0;
    exec($cmd, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($outputPath) && filesize($outputPath) > 1024) {
        return ['success' => true];
    }
    
    $error = implode("\n", array_slice($output, -5));
    return ['success' => false, 'error' => $error];
}


function generateTestVideo($width, $height, $duration, $outputPath, $ffmpegPath) {
    $cmd = sprintf(
        '"%s" -f lavfi -i testsrc=duration=%d:size=%dx%d:rate=30 -vf "format=yuv420p" -c:v libx264 -preset ultrafast -crf 28 -pix_fmt yuv420p -movflags +faststart -y "%s" 2>&1',
        $ffmpegPath,
        $duration,
        $width,
        $height,
        $outputPath
    );
    
    $output = [];
    $returnCode = 0;
    exec($cmd, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($outputPath) && filesize($outputPath) > 1024) {
        return ['success' => true];
    }
    
    return ['success' => false, 'error' => '视频生成失败'];
}


function selectVisualEffect($prompt) {
    $prompt = strtolower($prompt);
    

    $keywords = [
        'nature' => ['nature', 'natural', 'forest', 'tree', 'leaf', 'grass', 'flower', 'mountain', 'river', 'ocean', 'sea', 'sky', 'cloud', '自然', '森林', '树', '花', '山', '河', '海', '天空', '云'],
        'abstract' => ['abstract', 'geometric', 'pattern', 'shape', 'line', 'wave', '抽象', '几何', '图案', '形状', '线条', '波浪'],
        'space' => ['space', 'star', 'galaxy', 'universe', 'planet', 'cosmic', '宇宙', '星空', '银河', '星球', '太空'],
        'fire' => ['fire', 'flame', 'burn', 'hot', '火山', '火焰', '燃烧', '火'],
        'water' => ['water', 'liquid', 'flow', 'rain', 'snow', '水', '液体', '流动', '雨', '雪'],
        'city' => ['city', 'urban', 'building', 'street', 'cityscape', '城市', '建筑', '街道', '都市'],
        'technology' => ['technology', 'tech', 'digital', 'computer', 'code', 'network', '科技', '数字', '电脑', '代码', '网络'],
    ];
    
    foreach ($keywords as $type => $words) {
        foreach ($words as $word) {
            if (strpos($prompt, $word) !== false) {
                return $type;
            }
        }
    }
    
    return 'abstract';
}


function generateVisualVideo($type, $width, $height, $duration, $outputPath, $ffmpegPath) {

    $colorConfigs = [
        'nature' => ['c1' => '2ecc71', 'c2' => '3498db', 'c3' => '1abc9c'],
        'space' => ['c1' => '1a1a2e', 'c2' => '16213e', 'c3' => '0f3460'],
        'fire' => ['c1' => 'e74c3c', 'c2' => 'e67e22', 'c3' => 'f39c12'],
        'water' => ['c1' => '3498db', 'c2' => '2980b9', 'c3' => '5dade2'],
        'technology' => ['c1' => '000000', 'c2' => '1a1a1a', 'c3' => '00ff00'],
        'city' => ['c1' => '2c3e50', 'c2' => '34495e', 'c3' => '7f8c8d'],
        'abstract' => ['c1' => '9b59b6', 'c2' => 'e74c3c', 'c3' => 'f1c40f'],
    ];
    
    $colors = $colorConfigs[$type] ?? $colorConfigs['abstract'];
    

    $filterChains = [
        'nature' => sprintf(
            'testsrc=duration=%d:size=%dx%d:rate=30,' .
            'geq=lum=\'128+64*sin(X/40+T)*cos(Y/40-T)\':cb=128:cr=128,' .
            'hue=H=t*5+30:s=1.8,format=yuv420p',
            $duration, $width, $height
        ),
        'space' => sprintf(
            'noise=alls=10:allf=t+s:duration=%d,s=%dx%d,' .
            'geq=lum=\'if(gt(random(0)*255,250),255,20+random(0)*30)\':cb=128:cr=128,' .
            'gblur=sigma=0.8,hue=H=220:s=0.5,format=yuv420p',
            $duration, $width, $height
        ),
        'fire' => sprintf(
            'testsrc=duration=%d:size=%dx%d:rate=30,' .
            'geq=lum=\'200-50*sin((X+Y)/30+T*4)+random(0)*20\':cb=128:cr=128,' .
            'hue=H=20+sin(T)*15:s=2.5,format=yuv420p',
            $duration, $width, $height
        ),
        'water' => sprintf(
            'testsrc=duration=%d:size=%dx%d:rate=30,' .
            'geq=lum=\'128+64*sin((X+Y)/25+T*3)\':cb=\'128+30*sin(X/20-T)\':cr=\'128+30*cos(Y/20+T)\',' .
            'hue=H=200:s=1.5,format=yuv420p',
            $duration, $width, $height
        ),
        'technology' => sprintf(
            'testsrc=duration=%d:size=%dx%d:rate=30,' .
            'geq=lum=\'if(mod(X+T*50,100)<2,200,if(mod(Y+T*30,80)<2,150,20))\':cb=128:cr=128,' .
            'hue=H=120:s=0.8,format=yuv420p',
            $duration, $width, $height
        ),
        'city' => sprintf(
            'testsrc=duration=%d:size=%dx%d:rate=30,' .
            'geq=lum=\'if(gt(sin(X/50+T)*100+cos(Y/30)*50,80),180,40)\':cb=128:cr=128,' .
            'hue=H=40:s=0.2,format=yuv420p',
            $duration, $width, $height
        ),
        'abstract' => sprintf(
            'testsrc=duration=%d:size=%dx%d:rate=30,' .
            'geq=lum=\'128+64*sin(sqrt((X-%d)^2+(Y-%d)^2)/30-T*3)\':cb=128:cr=128,' .
            'hue=H=t*30:s=2,format=yuv420p',
            $duration, $width, $height, $width/2, $height/2
        ),
    ];
    
    $filter = $filterChains[$type] ?? $filterChains['abstract'];
    

    $cmd = sprintf(
        '"%s" -f lavfi -i "%s" ' .
        '-c:v libx264 -pix_fmt yuv420p -movflags +faststart -y "%s" 2>&1',
        $ffmpegPath,
        $filter,
        $outputPath
    );
    
    $output = [];
    $returnCode = 0;
    exec($cmd, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($outputPath) && filesize($outputPath) > 1024) {
        return ['success' => true];
    }
    

    return ['success' => false];
}


function generateSimpleVisualVideo($width, $height, $duration, $outputPath, $ffmpegPath) {

    $effects = [

        sprintf('testsrc=duration=%d:size=%dx%d:rate=30,hue=H=t*30:s=2', $duration, $width, $height),

        sprintf('noise=alls=20:allf=t+s:duration=%d,s=%dx%d,geq=lum=\'128+64*sin(X/30+T)*cos(Y/30+T)\':cb=128:cr=128,hue=H=t*15', $duration, $width, $height),

        sprintf('smptebars=duration=%d:size=%dx%d,geq=lum=\'lum(X,Y)*0.8\':cb=128:cr=128,hue=H=t*20', $duration, $width, $height),

        sprintf('testsrc=duration=%d:size=%dx%d:rate=30,format=gray,geq=lum=\'255*(1+sin((X+Y)/20+T*3))/2\',hue=H=t*30:s=1.5', $duration, $width, $height),
    ];
    
    $filter = $effects[array_rand($effects)];
    
    $cmd = sprintf(
        '"%s" -f lavfi -i "%s" ' .
        '-c:v libx264 -pix_fmt yuv420p -movflags +faststart -y "%s" 2>&1',
        $ffmpegPath,
        $filter,
        $outputPath
    );
    
    $output = [];
    $returnCode = 0;
    exec($cmd, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($outputPath) && filesize($outputPath) > 1024) {
        return ['success' => true];
    }
    
    return [
        'success' => false,
        'error' => '简单视觉效果生成失败: ' . implode("\n", array_slice($output, -3))
    ];
}


function generateSubtitleVideo($prompt, $width, $height, $duration, $outputPath, $ffmpegPath) {

    $title = mb_substr($prompt, 0, 60);
    if (mb_strlen($prompt) > 60) {
        $title .= '...';
    }
    

    $title = str_replace([':', "'", '"', '\\', '%'], ['\\:', "\\'", '\\"', '\\\\', '\\%'], $title);
    

    $drawtext = sprintf(
        "drawtext=text='%s':fontcolor=white@0.95:fontsize=%d:" .
        "x=(w-text_w)/2:y=(h*0.75):box=1:boxcolor=black@0.5:boxborderw=15:" .
        "shadowcolor=black@0.8:shadowx=3:shadowy=3",
        $title,
        max(24, min(48, $width / 40))
    );
    

    $cmd = sprintf(
        '"%s" -f lavfi -i "color=black@%d:s=%dx%d:d=%d,format=yuva420p" ' .
        '-vf "%s,format=yuva420p" ' .
        '-c:v libx264 -pix_fmt yuva420p -movflags +faststart -y "%s" 2>&1',
        $ffmpegPath,
        0,
        $width,
        $height,
        $duration,
        $drawtext,
        $outputPath
    );
    
    $output = [];
    $returnCode = 0;
    exec($cmd, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($outputPath) && filesize($outputPath) > 1024) {
        return ['success' => true];
    }
    
    return ['success' => false];
}


function mergeVideoLayers($visualPath, $subtitlePath, $outputPath, $ffmpegPath) {
    $cmd = sprintf(
        '"%s" -i "%s" -i "%s" ' .
        '-filter_complex "[0:v][1:v]overlay=0:0:format=auto,format=yuv420p" ' .
        '-c:v libx264 -pix_fmt yuv420p -movflags +faststart -y "%s" 2>&1',
        $ffmpegPath,
        $visualPath,
        $subtitlePath,
        $outputPath
    );
    
    $output = [];
    $returnCode = 0;
    exec($cmd, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($outputPath) && filesize($outputPath) > 1024) {
        return ['success' => true];
    }
    
    return ['success' => false];
}


function convertImageToVideo($imageData, $resolution, $duration, $outputPath, $ffmpegPath) {

    $tempImage = sys_get_temp_dir() . '/ai_gen_' . uniqid() . '.png';
    

    if (strpos($imageData, 'data:image') === 0) {
        $imageData = substr($imageData, strpos($imageData, ',') + 1);
    }
    
    file_put_contents($tempImage, base64_decode($imageData));
    
    if (!file_exists($tempImage)) {
        return ['success' => false, 'error' => '图片保存失败'];
    }
    
    $width = $resolution['width'];
    $height = $resolution['height'];
    

    $cmd = sprintf(
        '"%s" -loop 1 -i "%s" -c:v libx264 -t %d -pix_fmt yuv420p -vf "scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2:black,zoompan=z=\'min(zoom+0.0015,1.3)\':d=%d:s=%dx%d,format=yuv420p" -movflags +faststart -y "%s" 2>&1',
        $ffmpegPath,
        $tempImage,
        $duration,
        $width,
        $height,
        $width,
        $height,
        $duration * 25,
        $width,
        $height,
        $outputPath
    );
    
    $output = [];
    $returnCode = 0;
    exec($cmd, $output, $returnCode);
    

    @unlink($tempImage);
    
    if ($returnCode === 0 && file_exists($outputPath) && filesize($outputPath) > 1024) {
        return ['success' => true];
    }
    
    return [
        'success' => false,
        'error' => '视频转换失败: ' . implode("\n", array_slice($output, -5))
    ];
}


function getFFmpegPath() {

    $builtinPaths = [
        __DIR__ . '/../bin/ffmpeg/ffmpeg.exe',
        __DIR__ . '/../bin/ffmpeg/ffmpeg',
        __DIR__ . '/../bin/ffmpeg.exe',
    ];
    
    foreach ($builtinPaths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    

    $configPath = __DIR__ . '/../config/ffmpeg.php';
    if (file_exists($configPath)) {
        $config = include $configPath;
        if (!empty($config['ffmpeg_path']) && file_exists($config['ffmpeg_path'])) {
            return $config['ffmpeg_path'];
        }
    }
    

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $paths = [
            'C:\ffmpeg\bin\ffmpeg.exe',
            'D:\ffmpeg\bin\ffmpeg.exe',
            'E:\ffmpeg\bin\ffmpeg.exe',
        ];
    } else {
        $paths = [
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            '/opt/ffmpeg/bin/ffmpeg',
        ];
    }
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    

    return 'ffmpeg';
}


function getFFprobePath() {
    $builtinPaths = [
        __DIR__ . '/../bin/ffmpeg/ffprobe.exe',
        __DIR__ . '/../bin/ffmpeg/ffprobe',
        __DIR__ . '/../bin/ffprobe.exe',
    ];

    foreach ($builtinPaths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }

    // 尝试从ffmpeg.exe推断ffprobe
    $ffmpegPath = getFFmpegPath();
    if (file_exists($ffmpegPath)) {
        $ffprobePath = str_replace('ffmpeg.exe', 'ffprobe.exe', $ffmpegPath);
        $ffprobePath = str_replace('ffmpeg', 'ffprobe', $ffprobePath);
        if (file_exists($ffprobePath)) {
            return $ffprobePath;
        }
    }

    // 尝试系统路径
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $paths = [
            'C:\ffmpeg\bin\ffprobe.exe',
            'D:\ffmpeg\bin\ffprobe.exe',
            'E:\ffmpeg\bin\ffprobe.exe',
        ];
    } else {
        $paths = [
            '/usr/bin/ffprobe',
            '/usr/local/bin/ffprobe',
            '/opt/ffmpeg/bin/ffprobe',
        ];
    }

    foreach ($paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }

    return 'ffprobe';
}


function getActualVideoDuration($videoPath) {
    $ffprobePath = getFFprobePath();
    
    $output = [];
    $cmd = sprintf('"%s" -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "%s" 2>&1', $ffprobePath, $videoPath);
    @exec($cmd, $output, $returnCode);
    
    if ($returnCode === 0 && !empty($output[0])) {
        $duration = floatval($output[0]);
        // 向上取整
        return ceil($duration);
    }
    
    // 如果ffprobe失败,返回0,调用方会使用默认值
    return 0;
}


function parseResolution($ratio) {

    $resolutions = [
        '16:9' => ['width' => 1280, 'height' => 720],
        '9:16' => ['width' => 720, 'height' => 1280],
        '1:1' => ['width' => 768, 'height' => 768],
        '4:3' => ['width' => 1024, 'height' => 768],
    ];
    
    return $resolutions[$ratio] ?? $resolutions['16:9'];
}


function handleVideoList($db, $userId) {
    try {
        $videos = $db->fetchAll(
            "SELECT video_id as id, prompt, web_path, resolution, duration, created_at 
            FROM generated_videos 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC LIMIT 20",
            ['user_id' => $userId]
        );
        

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = dirname($scriptName);
        $basePath = dirname($scriptDir);
        $basePath = str_replace('\\', '/', $basePath);
        
        foreach ($videos as &$video) {
            $video['url'] = rtrim($protocol . '://' . $host . $basePath, '/') . '/' . ltrim($video['web_path'], '/');
        }
        
        echo json_encode(['success' => true, 'videos' => $videos]);
    } catch (Exception $e) {

        echo json_encode(['success' => true, 'videos' => []]);
    }
}


function handleVideoView($db) {
    $videoId = $_GET['id'] ?? '';
    
    if (empty($videoId)) {
        echo json_encode(['success' => false, 'error' => '视频ID不能为空']);
        return;
    }
    
    try {
        $video = $db->fetch(
            "SELECT * FROM generated_videos WHERE video_id = :video_id",
            ['video_id' => $videoId]
        );
        
        if ($video) {

            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            $scriptDir = dirname($scriptName);
            $basePath = dirname($scriptDir);
            $basePath = str_replace('\\', '/', $basePath);
            
            $video['url'] = rtrim($protocol . '://' . $host . $basePath, '/') . '/' . ltrim($video['web_path'], '/');
            
            echo json_encode(['success' => true, 'video' => $video]);
        } else {
            echo json_encode(['success' => false, 'error' => '视频不存在']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => '查询失败']);
    }
}


function handleVideoDelete($db, $userId) {
    $videoId = $_POST['id'] ?? '';
    
    if (empty($videoId)) {
        echo json_encode(['success' => false, 'error' => '视频ID不能为空']);
        return;
    }
    
    try {
        $video = $db->fetch(
            "SELECT * FROM generated_videos WHERE video_id = :video_id AND user_id = :user_id",
            ['video_id' => $videoId, 'user_id' => $userId]
        );
        
        if ($video) {

            if (file_exists($video['file_path'])) {
                unlink($video['file_path']);
            }
            

            $db->delete('generated_videos', 'video_id = :video_id', ['video_id' => $videoId]);
            
            echo json_encode(['success' => true, 'message' => '视频已删除']);
        } else {
            echo json_encode(['success' => false, 'error' => '视频不存在或无权删除']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => '删除失败']);
    }
}
