<?php

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require_once __DIR__ . '/../includes/Database.php';


$isAdmin = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
if (!$isAdmin) {
    echo json_encode(['success' => false, 'error' => '无权访问']);
    exit;
}

$action = $_GET['action'] ?? 'check_all';

try {
    switch ($action) {
        case 'check_all':
            checkAllModules();
            break;
        case 'check_module':
            $module = $_GET['module'] ?? '';
            checkSingleModule($module);
            break;
        case 'system_info':
            getSystemInfo();
            break;
        default:
            throw new Exception('未知的操作类型');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}


function checkAllModules() {
    $modules = [
        'database' => checkDatabase(),
        'ollama' => checkOllama(),
        'gpustack' => checkGPUStack(),
        'storage' => checkStorage(),
        'logs' => checkDirectory('logs'),
        'uploads' => checkDirectory('uploads'),
        'workflows' => checkWorkflows(),
        'ffmpeg' => checkFFmpeg()
    ];
    
    $healthy = 0;
    $warning = 0;
    $error = 0;
    
    foreach ($modules as $module) {
        switch ($module['status']) {
            case 'healthy':
                $healthy++;
                break;
            case 'warning':
                $warning++;
                break;
            case 'error':
                $error++;
                break;
        }
    }
    
    echo json_encode([
        'success' => true,
        'modules' => $modules,
        'summary' => [
            'total' => count($modules),
            'healthy' => $healthy,
            'warning' => $warning,
            'error' => $error,
            'overall_status' => $error > 0 ? 'error' : ($warning > 0 ? 'warning' : 'healthy')
        ],
        'checked_at' => date('Y-m-d H:i:s')
    ]);
}


function checkSingleModule($moduleName) {
    $checkers = [
        'database' => 'checkDatabase',
        'ollama' => 'checkOllama',
        'gpustack' => 'checkGPUStack',
        'storage' => 'checkStorage',
        'logs' => function() { return checkDirectory('logs'); },
        'uploads' => function() { return checkDirectory('uploads'); },
        'workflows' => 'checkWorkflows',
        'ffmpeg' => 'checkFFmpeg'
    ];
    
    if (!isset($checkers[$moduleName])) {
        throw new Exception('未知的模块: ' . $moduleName);
    }
    
    $result = $checkers[$moduleName]();
    echo json_encode([
        'success' => true,
        'module' => $moduleName,
        'result' => $result
    ]);
}


function checkDatabase() {
    try {
        $dbPath = __DIR__ . '/../data/gpustack.db';
        

        if (!file_exists($dbPath)) {
            return [
                'status' => 'warning',
                'message' => '数据库文件不存在，可能需要安装',
                'details' => ['path' => $dbPath]
            ];
        }
        

        if (!is_readable($dbPath)) {
            return [
                'status' => 'error',
                'message' => '数据库文件不可读',
                'details' => ['path' => $dbPath]
            ];
        }
        

        require_once __DIR__ . '/../config/database.php';
        $db = Database::getInstance();
        $start = microtime(true);
        $db->query("SELECT 1");
        $responseTime = round((microtime(true) - $start) * 1000, 2);
        

        $version = $db->query("SELECT sqlite_version()")->fetchColumn();
        
        return [
            'status' => 'healthy',
            'message' => '连接正常',
            'details' => [
                'version' => 'SQLite ' . $version,
                'response_time' => $responseTime . 'ms'
            ]
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => '数据库连接失败: ' . $e->getMessage(),
            'details' => null
        ];
    }
}


function checkOllama() {
    $config = require __DIR__ . '/../config/config.php';
    $url = $config['ollama_api']['base_url'] ?? 'http://localhost:11434';
    
    $ch = curl_init($url . '/api/tags');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $start = microtime(true);
    $response = curl_exec($ch);
    $responseTime = round((microtime(true) - $start) * 1000, 2);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        $modelCount = isset($data['models']) ? count($data['models']) : 0;
        
        return [
            'status' => 'healthy',
            'message' => '服务正常运行',
            'details' => [
                'url' => $url,
                'models_available' => $modelCount,
                'response_time' => $responseTime . 'ms'
            ]
        ];
    }
    
    return [
        'status' => 'warning',
        'message' => '服务未响应 (HTTP ' . $httpCode . ')',
        'details' => [
            'url' => $url,
            'response_time' => $responseTime . 'ms'
        ]
    ];
}


function checkGPUStack() {
    $config = require __DIR__ . '/../config/config.php';
    
    if (empty($config['gpustack_api']['enabled'])) {
        return [
            'status' => 'disabled',
            'message' => '已禁用',
            'details' => null
        ];
    }
    
    $url = $config['gpustack_api']['base_url'] ?? '';
    if (empty($url)) {
        return [
            'status' => 'disabled',
            'message' => '未配置',
            'details' => null
        ];
    }
    
    $ch = curl_init($url . '/v1/models');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $apiKey = $config['gpustack_api']['api_key'] ?? '';
    if ($apiKey) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $apiKey]);
    }
    
    $start = microtime(true);
    $response = curl_exec($ch);
    $responseTime = round((microtime(true) - $start) * 1000, 2);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return [
            'status' => 'healthy',
            'message' => 'API连接正常',
            'details' => [
                'url' => $url,
                'response_time' => $responseTime . 'ms'
            ]
        ];
    }
    
    return [
        'status' => 'warning',
        'message' => 'API未响应 (HTTP ' . $httpCode . ')',
        'details' => [
            'url' => $url,
            'response_time' => $responseTime . 'ms'
        ]
    ];
}


function checkStorage() {
    $checks = [
        'storage' => checkDirectory('storage'),
        'logs' => checkDirectory('logs'),
        'uploads' => checkDirectory('uploads')
    ];
    
    $issues = [];
    foreach ($checks as $name => $check) {
        if ($check['status'] !== 'healthy') {
            $issues[] = $name . ': ' . $check['message'];
        }
    }
    
    if (empty($issues)) {
        return [
            'status' => 'healthy',
            'message' => '所有目录正常',
            'details' => $checks
        ];
    }
    
    return [
        'status' => 'warning',
        'message' => implode(', ', $issues),
        'details' => $checks
    ];
}


function checkDirectory($name) {
    $paths = [
        'storage' => __DIR__ . '/../storage/',
        'logs' => __DIR__ . '/../logs/',
        'uploads' => __DIR__ . '/../uploads/'
    ];
    
    if (!isset($paths[$name])) {
        return ['status' => 'error', 'message' => '未知目录'];
    }
    
    $dir = $paths[$name];
    
    if (!is_dir($dir)) {
        return ['status' => 'error', 'message' => '目录不存在'];
    }
    
    if (!is_writable($dir)) {
        return ['status' => 'error', 'message' => '目录不可写'];
    }
    
    $freeSpace = disk_free_space($dir);
    $totalSpace = disk_total_space($dir);
    $usedPercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;
    
    if ($usedPercent > 95) {
        return [
            'status' => 'error',
            'message' => '存储空间严重不足 (' . round($usedPercent, 1) . '% 已用)',
            'details' => ['used_percent' => round($usedPercent, 1)]
        ];
    }
    
    if ($usedPercent > 90) {
        return [
            'status' => 'warning',
            'message' => '存储空间不足 (' . round($usedPercent, 1) . '% 已用)',
            'details' => ['used_percent' => round($usedPercent, 1)]
        ];
    }
    
    return [
        'status' => 'healthy',
        'message' => '正常 (' . round($usedPercent, 1) . '% 已用)',
        'details' => ['used_percent' => round($usedPercent, 1)]
    ];
}


function checkWorkflows() {
    $requiredFiles = [
        'WorkflowEngine.php',
        'ComfyUIWorkflowEngine.php',
        'AIProviderManager.php',
        'AIProviderCaller.php'
    ];
    
    $libDir = __DIR__ . '/../lib/';
    $missing = [];
    
    foreach ($requiredFiles as $file) {
        if (!file_exists($libDir . $file)) {
            $missing[] = $file;
        }
    }
    
    if (empty($missing)) {
        return [
            'status' => 'healthy',
            'message' => '引擎文件完整',
            'details' => ['files_checked' => count($requiredFiles)]
        ];
    }
    
    return [
        'status' => 'error',
        'message' => '缺失文件: ' . implode(', ', $missing),
        'details' => ['missing' => $missing]
    ];
}


function checkFFmpeg() {
    $ffmpegPath = trim(shell_exec('where ffmpeg 2>nul') ?: shell_exec('which ffmpeg 2>/dev/null'));
    
    if (empty($ffmpegPath)) {
        return [
            'status' => 'warning',
            'message' => '未安装或不在PATH中',
            'details' => null
        ];
    }
    
    $version = shell_exec('ffmpeg -version 2>&1 | head -1');
    preg_match('/version\s+([\d\.]+)/i', $version, $matches);
    $versionNum = $matches[1] ?? 'unknown';
    
    return [
        'status' => 'healthy',
        'message' => '已安装 (v' . $versionNum . ')',
        'details' => [
            'path' => $ffmpegPath,
            'version' => $versionNum
        ]
    ];
}


function getSystemInfo() {
    $info = [
        'php' => [
            'version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_file_uploads' => ini_get('max_file_uploads')
        ],
        'server' => [
            'os' => PHP_OS,
            'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'php_sapi' => php_sapi_name()
        ],
        'disk' => [
            'free' => disk_free_space(__DIR__),
            'total' => disk_total_space(__DIR__),
            'used_percent' => round(((disk_total_space(__DIR__) - disk_free_space(__DIR__)) / disk_total_space(__DIR__)) * 100, 2)
        ],
        'extensions' => get_loaded_extensions()
    ];
    
    echo json_encode(['success' => true, 'info' => $info]);
}
