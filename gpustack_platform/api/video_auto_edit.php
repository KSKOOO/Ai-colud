<?php
/**
 * 视频自动剪辑API
 * 智能分析并自动剪辑视频，生成可直接发布的成品
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']) {
    echo json_encode(['success' => false, 'error' => '请先登录']);
    exit;
}

require_once __DIR__ . '/../lib/VideoAutoEditor.php';

$userId = $_SESSION['user']['id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'auto_edit':
            handleAutoEdit($userId);
            break;
            
        case 'get_config':
            handleGetConfig();
            break;
            
        case 'update_config':
            handleUpdateConfig();
            break;
            
        case 'preview_analysis':
            handlePreviewAnalysis();
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => '未知操作']);
    }
} catch (Exception $e) {
    error_log("Video auto edit error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * 自动剪辑视频
 */
function handleAutoEdit($userId) {
    if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => '请上传视频文件']);
        return;
    }
    
    $file = $_FILES['video'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $validExts = ['mp4', 'mov', 'avi', 'mkv', 'webm'];
    
    if (!in_array($ext, $validExts)) {
        echo json_encode(['success' => false, 'error' => '不支持的文件格式']);
        return;
    }
    
    // 获取参数
    $targetDuration = intval($_POST['target_duration'] ?? 30);
    $style = $_POST['style'] ?? 'auto'; // auto, fast, slow, cinematic
    
    // 根据风格调整配置
    $config = getStyleConfig($style);
    $config['target_duration_medium'] = $targetDuration;
    
    // 保存上传的视频
    $uploadDir = __DIR__ . '/../uploads/videos/source/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $inputFileName = 'input_' . time() . '_' . uniqid() . '.' . $ext;
    $inputPath = $uploadDir . $inputFileName;
    
    if (!move_uploaded_file($file['tmp_name'], $inputPath)) {
        echo json_encode(['success' => false, 'error' => '文件保存失败']);
        return;
    }
    
    // 创建输出路径
    $outputDir = __DIR__ . '/../uploads/videos/edited/';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    $outputFileName = 'edited_' . time() . '_' . uniqid() . '.mp4';
    $outputPath = $outputDir . $outputFileName;
    $webPath = 'uploads/videos/edited/' . $outputFileName;
    
    // 执行自动剪辑
    $editor = new VideoAutoEditor($config);
    $result = $editor->autoEdit($inputPath, $outputPath, [
        'target_duration' => $targetDuration,
    ]);
    
    if ($result['success']) {
        // 保存到数据库
        $db = Database::getInstance();
        $db->insert('edited_videos', [
            'user_id' => $userId,
            'original_name' => $file['name'],
            'input_path' => $inputPath,
            'output_path' => $outputPath,
            'web_path' => $webPath,
            'duration' => $result['duration'],
            'clips_count' => $result['clips_count'],
            'style' => $style,
            'config' => json_encode($config),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        // 构建响应
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $downloadUrl = $protocol . '://' . $host . '/' . $webPath;
        
        echo json_encode([
            'success' => true,
            'message' => '视频自动剪辑完成',
            'video' => [
                'url' => $downloadUrl,
                'duration' => $result['duration'],
                'clips_count' => $result['clips_count'],
                'file_size' => formatFileSize(filesize($outputPath)),
            ],
            'clips' => $result['clips'] ?? [],
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? '剪辑失败',
        ]);
    }
}

/**
 * 获取当前配置
 */
function handleGetConfig() {
    $editor = new VideoAutoEditor();
    echo json_encode([
        'success' => true,
        'config' => $editor->getConfig(),
    ]);
}

/**
 * 更新配置（微调模式）
 */
function handleUpdateConfig() {
    $config = $_POST['config'] ?? [];
    
    // 验证配置项
    $allowedKeys = [
        'scene_threshold',
        'min_clip_duration',
        'max_clip_duration',
        'motion_weight',
        'audio_weight',
        'scene_weight',
        'transition_type',
        'auto_stabilize',
        'auto_color_grade',
        'auto_audio_normalize',
    ];
    
    $filteredConfig = [];
    foreach ($config as $key => $value) {
        if (in_array($key, $allowedKeys)) {
            $filteredConfig[$key] = $value;
        }
    }
    
    // 保存到session
    $_SESSION['video_edit_config'] = $filteredConfig;
    
    echo json_encode([
        'success' => true,
        'message' => '配置已更新',
        'config' => $filteredConfig,
    ]);
}

/**
 * 预览分析结果
 */
function handlePreviewAnalysis() {
    if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => '请上传视频文件']);
        return;
    }
    
    $file = $_FILES['video'];
    $tempPath = $file['tmp_name'];
    
    $editor = new VideoAutoEditor();
    
    // 只分析，不剪辑
    $analysis = $editor->analyzeVideo($tempPath);
    
    echo json_encode([
        'success' => true,
        'analysis' => [
            'duration' => $analysis['duration'],
            'metadata' => $analysis['metadata'],
            'scene_changes_count' => count($analysis['scene_changes']),
            'key_frames_count' => count($analysis['key_frames']),
        ],
    ]);
}

/**
 * 获取风格配置
 */
function getStyleConfig($style) {
    $configs = [
        'auto' => [], // 使用默认配置
        
        'fast' => [
            'scene_threshold' => 0.2,
            'min_clip_duration' => 2,
            'max_clip_duration' => 8,
            'motion_weight' => 0.5,
            'transition_duration' => 0.3,
            'target_duration_short' => 15,
        ],
        
        'slow' => [
            'scene_threshold' => 0.4,
            'min_clip_duration' => 5,
            'max_clip_duration' => 20,
            'motion_weight' => 0.2,
            'transition_duration' => 1.0,
            'target_duration_medium' => 45,
        ],
        
        'cinematic' => [
            'scene_threshold' => 0.35,
            'min_clip_duration' => 4,
            'max_clip_duration' => 15,
            'motion_weight' => 0.3,
            'audio_weight' => 0.3,
            'scene_weight' => 0.4,
            'transition_type' => 'dissolve',
            'transition_duration' => 0.8,
            'auto_color_grade' => true,
            'auto_stabilize' => true,
        ],
    ];
    
    return $configs[$style] ?? $configs['auto'];
}

/**
 * 格式化文件大小
 */
function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
