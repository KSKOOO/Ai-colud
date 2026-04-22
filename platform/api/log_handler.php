<?php

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$isAdmin = $_SESSION['user']['role'] === 'admin' ?? false;
if (!$isAdmin) {
    echo json_encode(['success' => false, 'error' => '无权访问']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$logsDir = __DIR__ . '/../logs/';

try {
    switch ($action) {
        case 'get_logs':
            getLogs($logsDir);
            break;
            
        case 'get_log_content':
            $logFile = $_GET['file'] ?? '';
            getLogContent($logsDir, $logFile);
            break;
            
        case 'analyze_logs':
            analyzeLogs($logsDir);
            break;
            
        case 'clear_log':
            $logFile = $_POST['file'] ?? '';
            clearLog($logsDir, $logFile);
            break;
            
        case 'download_log':
            $logFile = $_GET['file'] ?? '';
            downloadLog($logsDir, $logFile);
            break;
            
        default:
            throw new Exception('未知的操作类型');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}


function getLogs($logsDir) {
    $logs = [];
    
    if (!is_dir($logsDir)) {
        echo json_encode(['success' => true, 'logs' => []]);
        return;
    }
    
    $files = glob($logsDir . '*.log');
    
    foreach ($files as $file) {
        $filename = basename($file);
        $size = filesize($file);
        $modified = filemtime($file);
        

        $logInfo = getLogInfo($filename);
        

        $lineCount = countLines($file);
        

        $status = detectLogStatus($file);
        
        $logs[] = [
            'filename' => $filename,
            'size' => formatBytes($size),
            'size_bytes' => $size,
            'modified' => date('Y-m-d H:i:s', $modified),
            'modified_timestamp' => $modified,
            'lines' => $lineCount,
            'type' => $logInfo['type'],
            'description' => $logInfo['description'],
            'status' => $status,
            'status_color' => getStatusColor($status)
        ];
    }
    

    usort($logs, function($a, $b) {
        return $b['modified_timestamp'] - $a['modified_timestamp'];
    });
    
    echo json_encode(['success' => true, 'logs' => $logs]);
}


function getLogContent($logsDir, $filename) {
    if (empty($filename) || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename)) {
        throw new Exception('无效的文件名');
    }
    
    $filepath = $logsDir . $filename;
    
    if (!file_exists($filepath)) {
        throw new Exception('日志文件不存在');
    }
    
    $lines = intval($_GET['lines'] ?? 100);
    $lines = min($lines, 1000);
    
    $content = tailFile($filepath, $lines);
    
    echo json_encode([
        'success' => true,
        'filename' => $filename,
        'content' => $content,
        'total_lines' => countLines($filepath)
    ]);
}


function analyzeLogs($logsDir) {
    $analysis = [
        'total_logs' => 0,
        'total_size' => 0,
        'error_count' => 0,
        'warning_count' => 0,
        'api_calls' => 0,
        'uploads' => 0,
        'recent_activity' => [],
        'error_trends' => [],
        'api_usage' => []
    ];
    
    if (!is_dir($logsDir)) {
        echo json_encode(['success' => true, 'analysis' => $analysis]);
        return;
    }
    
    $files = glob($logsDir . '*.log');
    $analysis['total_logs'] = count($files);
    
    foreach ($files as $file) {
        $filename = basename($file);
        $size = filesize($file);
        $analysis['total_size'] += $size;
        
        $content = file_get_contents($file);
        

        $errors = substr_count(strtoupper($content), 'ERROR');
        $warnings = substr_count(strtoupper($content), 'WARNING');
        $analysis['error_count'] += $errors;
        $analysis['warning_count'] += $warnings;
        

        if (strpos($filename, 'scenario_analysis') !== false) {
            $analysis['api_calls'] += substr_count($content, 'HTTP Code:');
        }
        

        if (strpos($filename, 'video_upload') !== false) {
            $analysis['uploads'] += substr_count($content, 'Upload started');
        }
        

        $lines = explode("\n", $content);
        foreach (array_slice($lines, -10) as $line) {
            if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                $analysis['recent_activity'][] = [
                    'time' => $matches[1],
                    'message' => substr($line, strlen($matches[0]) + 1),
                    'source' => $filename
                ];
            }
        }
    }
    

    usort($analysis['recent_activity'], function($a, $b) {
        return strtotime($b['time']) - strtotime($a['time']);
    });
    $analysis['recent_activity'] = array_slice($analysis['recent_activity'], 0, 20);
    

    $analysis['api_usage'] = analyzeAPIUsage($logsDir);
    

    $analysis['error_trends'] = analyzeErrorTrends($logsDir);
    
    echo json_encode(['success' => true, 'analysis' => $analysis]);
}


function clearLog($logsDir, $filename) {
    if (empty($filename) || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename)) {
        throw new Exception('无效的文件名');
    }
    
    $filepath = $logsDir . $filename;
    
    if (!file_exists($filepath)) {
        throw new Exception('日志文件不存在');
    }
    

    $backupName = $filename . '.backup.' . date('YmdHis');
    copy($filepath, $logsDir . $backupName);
    

    file_put_contents($filepath, "[" . date('Y-m-d H:i:s') . "] 日志已清空\n");
    
    echo json_encode(['success' => true, 'message' => '日志已清空', 'backup' => $backupName]);
}


function downloadLog($logsDir, $filename) {
    if (empty($filename) || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename)) {
        throw new Exception('无效的文件名');
    }
    
    $filepath = $logsDir . $filename;
    
    if (!file_exists($filepath)) {
        throw new Exception('日志文件不存在');
    }
    
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    exit;
}


function getLogInfo($filename) {
    $logTypes = [
        'scenario_analysis.log' => ['type' => 'api', 'description' => '场景分析API调用日志'],
        'video_error.log' => ['type' => 'error', 'description' => '视频处理错误日志'],
        'video_upload.log' => ['type' => 'upload', 'description' => '视频上传日志'],
        'system.log' => ['type' => 'system', 'description' => '系统运行日志'],
        'ffmpeg_' => ['type' => 'ffmpeg', 'description' => 'FFmpeg处理日志']
    ];
    
    foreach ($logTypes as $key => $info) {
        if (strpos($filename, $key) !== false) {
            return $info;
        }
    }
    
    return ['type' => 'other', 'description' => '其他日志'];
}


function detectLogStatus($filepath) {
    $content = file_get_contents($filepath);
    
    if (strpos($content, 'ERROR') !== false || strpos($content, 'Fatal') !== false) {
        return 'error';
    }
    
    if (strpos($content, 'WARNING') !== false) {
        return 'warning';
    }
    
    return 'normal';
}


function getStatusColor($status) {
    $colors = [
        'error' => '#ef4444',
        'warning' => '#f59e0b',
        'normal' => '#22c55e'
    ];
    return $colors[$status] ?? '#6b7280';
}


function countLines($filepath) {
    $lineCount = 0;
    $handle = fopen($filepath, 'r');
    while (!feof($handle)) {
        $line = fgets($handle);
        if ($line !== false) {
            $lineCount++;
        }
    }
    fclose($handle);
    return $lineCount;
}


function tailFile($filepath, $lines = 100) {
    $handle = fopen($filepath, 'r');
    $linecounter = 0;
    $pos = -2;
    $beginning = false;
    $text = [];
    
    while ($linecounter < $lines) {
        $t = " ";
        while ($t != "\n") {
            if (fseek($handle, $pos, SEEK_END) == -1) {
                $beginning = true;
                break;
            }
            $t = fgetc($handle);
            $pos--;
        }
        
        if ($beginning) {
            rewind($handle);
        }
        
        $line = fgets($handle);
        if ($line !== false) {
            array_unshift($text, $line);
        }
        
        $linecounter++;
        
        if ($beginning) {
            break;
        }
    }
    
    fclose($handle);
    return implode('', $text);
}


function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}


function analyzeAPIUsage($logsDir) {
    $usage = [];
    $analysisLog = $logsDir . 'scenario_analysis.log';
    
    if (!file_exists($analysisLog)) {
        return $usage;
    }
    
    $content = file_get_contents($analysisLog);
    

    if (preg_match_all('/Model: ([^\s]+)/', $content, $matches)) {
        $models = array_count_values($matches[1]);
        arsort($models);
        $usage['models'] = array_slice($models, 0, 10, true);
    }
    

    if (preg_match_all('/HTTP Code: (\d+)/', $content, $matches)) {
        $codes = array_count_values($matches[1]);
        $usage['http_codes'] = $codes;
    }
    
    return $usage;
}


function analyzeErrorTrends($logsDir) {
    $trends = [];
    $days = 7;
    
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $trends[$date] = ['errors' => 0, 'warnings' => 0];
    }
    
    $files = glob($logsDir . '*.log');
    
    foreach ($files as $file) {
        $handle = fopen($file, 'r');
        if (!$handle) continue;
        
        while (($line = fgets($handle)) !== false) {
            foreach ($trends as $date => &$counts) {
                if (strpos($line, "[$date") !== false) {
                    $upper = strtoupper($line);
                    if (strpos($upper, 'ERROR') !== false) {
                        $counts['errors']++;
                    }
                    if (strpos($upper, 'WARNING') !== false || strpos($upper, 'WARN') !== false) {
                        $counts['warnings']++;
                    }
                }
            }
        }
        fclose($handle);
    }
    
    return $trends;
}
