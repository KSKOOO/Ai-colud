<?php
/**
 * 日志查看 API
 */

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/Database.php';

$currentUserId = $_SESSION['user']['id'] ?? null;
$isAdmin = ($_SESSION['user']['role'] ?? '') === 'admin';

if (!$currentUserId || !$isAdmin) {
    echo json_encode(['success' => false, 'error' => '权限不足']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $db = Database::getInstance();
    
    switch ($action) {
        case 'get_stats':
            getStats($db);
            break;
            
        case 'get_logs':
            getLogs($db);
            break;
            
        default:
            throw new Exception('未知的操作类型');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * 获取统计信息
 */
function getStats($db) {
    // 今日日志数
    $todayLogs = rand(100, 500);
    
    // 错误日志数
    $errorLogs = rand(0, 20);
    
    // API调用次数
    $apiCalls = rand(1000, 5000);
    
    // 活跃用户数
    $activeUsers = rand(10, 100);
    
    // 变化率（模拟数据）
    $logsChange = rand(-20, 50);
    $usersChange = rand(-10, 30);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'today_logs' => $todayLogs,
            'error_logs' => $errorLogs,
            'api_calls' => $apiCalls,
            'active_users' => $activeUsers,
            'logs_change' => $logsChange,
            'users_change' => $usersChange
        ]
    ]);
}

/**
 * 获取日志列表
 */
function getLogs($db) {
    $level = $_GET['level'] ?? '';
    $source = $_GET['source'] ?? '';
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';
    $keyword = $_GET['keyword'] ?? '';
    $page = intval($_GET['page'] ?? 1);
    $pageSize = 20;
    
    // 模拟日志数据
    $logs = [];
    $levels = ['info', 'warning', 'error', 'debug'];
    $sources = ['api', 'auth', 'database', 'system'];
    
    for ($i = 0; $i < $pageSize; $i++) {
        $logLevel = $levels[array_rand($levels)];
        $logSource = $sources[array_rand($sources)];
        
        $logs[] = [
            'id' => $i + 1,
            'level' => $logLevel,
            'source' => $logSource,
            'message' => getLogMessage($logLevel, $logSource),
            'user_name' => '用户' . rand(1, 100),
            'ip' => '192.168.1.' . rand(1, 255),
            'created_at' => date('Y-m-d H:i:s', time() - rand(0, 86400))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $logs,
        'total_pages' => 10,
        'current_page' => $page
    ]);
}

function getLogMessage($level, $source) {
    $messages = [
        'api' => [
            'info' => 'API请求成功完成',
            'warning' => 'API响应时间较长',
            'error' => 'API请求失败，错误码500',
            'debug' => 'API调试信息：请求参数验证通过'
        ],
        'auth' => [
            'info' => '用户登录成功',
            'warning' => '登录尝试次数过多',
            'error' => '用户认证失败',
            'debug' => 'Token验证通过'
        ],
        'database' => [
            'info' => '数据库查询完成',
            'warning' => '数据库连接池接近上限',
            'error' => '数据库查询失败',
            'debug' => 'SQL执行时间：0.05s'
        ],
        'system' => [
            'info' => '系统运行正常',
            'warning' => '内存使用率超过80%',
            'error' => '系统异常退出',
            'debug' => '内存使用情况：正常'
        ]
    ];
    
    return $messages[$source][$level] ?? '系统日志消息';
}
