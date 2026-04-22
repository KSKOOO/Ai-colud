<?php
/**
 * 系统状态 Handler API
 * 提供系统状态监控和巡检功能
 */

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUserId = $_SESSION['user']['id'] ?? null;
$isAdmin = ($_SESSION['user']['role'] ?? '') === 'admin';

if (!$currentUserId || !$isAdmin) {
    echo json_encode(['status' => 'error', 'message' => '权限不足']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'getStatus':
            getSystemStatus();
            break;
            
        case 'getDiskSpace':
            getDiskSpace();
            break;
            
        case 'inspect':
            runSystemInspect();
            break;
            
        default:
            echo json_encode(['status' => 'error', 'message' => '未知的操作类型']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

/**
 * 获取系统状态
 */
function getSystemStatus() {
    $dbStatus = 'connected';
    try {
        require_once __DIR__ . '/../includes/Database.php';
        $db = Database::getInstance();
    } catch (Exception $e) {
        $dbStatus = 'disconnected';
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'php_version' => PHP_VERSION,
            'server_os' => PHP_OS,
            'database' => [
                'connected' => $dbStatus === 'connected'
            ]
        ]
    ]);
}

/**
 * 获取磁盘空间
 */
function getDiskSpace() {
    $free = disk_free_space('.');
    $total = disk_total_space('.');
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'free' => $free,
            'total' => $total,
            'used' => $total - $free
        ]
    ]);
}

/**
 * 运行系统巡检
 */
function runSystemInspect() {
    $checks = [];
    $warnings = [];
    $errors = [];
    
    // 1. PHP版本检查
    $phpVersion = PHP_VERSION;
    $phpOk = version_compare($phpVersion, '7.4.0', '>=');
    $checks[] = [
        'name' => 'PHP版本',
        'passed' => $phpOk,
        'warning' => !$phpOk,
        'message' => $phpOk ? "PHP {$phpVersion} 符合要求" : "PHP {$phpVersion} 建议升级到7.4以上"
    ];
    if (!$phpOk) {
        $warnings[] = 'PHP版本建议升级';
    }
    
    // 2. 扩展检查
    $requiredExtensions = ['pdo', 'gd', 'curl', 'mbstring', 'json', 'openssl', 'zip', 'fileinfo'];
    foreach ($requiredExtensions as $ext) {
        $installed = extension_loaded($ext);
        $checks[] = [
            'name' => "{$ext}扩展",
            'passed' => $installed,
            'warning' => false,
            'message' => $installed ? '已安装' : '未安装'
        ];
        if (!$installed) {
            $errors[] = "{$ext}扩展未安装";
        }
    }
    
    // 3. 磁盘空间检查
    $freeSpace = disk_free_space('.');
    $totalSpace = disk_total_space('.');
    $diskPercent = ($totalSpace - $freeSpace) / $totalSpace * 100;
    $diskOk = $diskPercent < 90;
    $diskWarning = $diskPercent >= 80 && $diskPercent < 90;
    $checks[] = [
        'name' => '磁盘空间',
        'passed' => $diskOk,
        'warning' => $diskWarning,
        'message' => "已使用 " . round($diskPercent, 1) . "%，剩余 " . formatBytes($freeSpace)
    ];
    if (!$diskOk) {
        $errors[] = '磁盘空间不足';
    } elseif ($diskWarning) {
        $warnings[] = '磁盘空间即将不足';
    }
    
    // 4. 内存限制检查
    $memoryLimit = ini_get('memory_limit');
    $memoryBytes = returnBytes($memoryLimit);
    $memoryOk = $memoryBytes >= 128 * 1024 * 1024;
    $checks[] = [
        'name' => '内存限制',
        'passed' => $memoryOk,
        'warning' => !$memoryOk,
        'message' => "当前限制: {$memoryLimit}" . ($memoryOk ? '' : '，建议至少128M')
    ];
    if (!$memoryOk) {
        $warnings[] = '内存限制较低';
    }
    
    // 5. 上传限制检查
    $uploadLimit = ini_get('upload_max_filesize');
    $checks[] = [
        'name' => '上传限制',
        'passed' => true,
        'warning' => false,
        'message' => "最大上传: {$uploadLimit}"
    ];
    
    // 6. 执行时间检查
    $maxExecution = ini_get('max_execution_time');
    $executionOk = $maxExecution >= 30 || $maxExecution == 0;
    $checks[] = [
        'name' => '执行时间限制',
        'passed' => $executionOk,
        'warning' => !$executionOk,
        'message' => "最大执行时间: {$maxExecution}秒"
    ];
    
    // 7. 数据库连接检查
    $dbConnected = false;
    try {
        require_once __DIR__ . '/../includes/Database.php';
        $db = Database::getInstance();
        $dbConnected = true;
    } catch (Exception $e) {
        $dbConnected = false;
    }
    $checks[] = [
        'name' => '数据库连接',
        'passed' => $dbConnected,
        'warning' => false,
        'message' => $dbConnected ? '连接正常' : '连接失败'
    ];
    if (!$dbConnected) {
        $errors[] = '数据库连接失败';
    }
    
    // 确定总体状态
    $overallStatus = 'success';
    if (count($errors) > 0) {
        $overallStatus = 'error';
    } elseif (count($warnings) > 0) {
        $overallStatus = 'warning';
    }
    
    echo json_encode([
        'status' => $overallStatus,
        'checks' => $checks,
        'warnings' => $warnings,
        'errors' => $errors,
        'message' => count($errors) > 0 ? '发现 ' . count($errors) . ' 个问题' : (count($warnings) > 0 ? '发现 ' . count($warnings) . ' 个警告' : '系统运行正常')
    ]);
}

/**
 * 格式化字节
 */
function formatBytes($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024 * 1024) return round($bytes / 1024, 2) . ' KB';
    if ($bytes < 1024 * 1024 * 1024) return round($bytes / (1024 * 1024), 2) . ' MB';
    return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
}

/**
 * 将PHP内存格式转换为字节
 */
function returnBytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    $val = (int)$val;
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}
