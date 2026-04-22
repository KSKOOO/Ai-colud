<?php
/**
 * 用户中心 API
 */

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Database.php';

$currentUserId = $_SESSION['user']['id'] ?? null;

if (!$currentUserId) {
    echo json_encode(['success' => false, 'error' => '未登录']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $db = Database::getInstance();
    
    switch ($action) {
        // 获取用量统计
        case 'get_usage_stats':
            getUsageStats($db, $currentUserId);
            break;
            
        // 修改密码
        case 'change_password':
            changePassword($db, $currentUserId);
            break;
            
        // 获取API密钥列表
        case 'get_api_keys':
            getApiKeys($db, $currentUserId);
            break;
            
        // 创建API密钥
        case 'create_api_key':
            createApiKey($db, $currentUserId);
            break;
            
        // 删除API密钥
        case 'delete_api_key':
            deleteApiKey($db, $currentUserId);
            break;
            
        // 获取余额
        case 'get_balance':
            getBalance($db, $currentUserId);
            break;
            
        // 充值
        case 'recharge':
            recharge($db, $currentUserId);
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
 * 获取用量统计
 */
function getUsageStats($db, $userId) {
    try {
        // 检查 usage_logs 表是否存在 - 使用通用方法
        try {
            $db->query("SELECT 1 FROM usage_logs LIMIT 1");
        } catch (Exception $e) {
            // 表不存在，创建用量日志表
            $db->exec("CREATE TABLE IF NOT EXISTS usage_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                action VARCHAR(50) NOT NULL,
                model VARCHAR(100),
                tokens INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        }
        
        // 总调用次数
        $totalCalls = $db->query("SELECT COUNT(*) as count FROM usage_logs WHERE user_id = $userId")->fetch()['count'] ?? 0;
        
        // 总Token数
        $totalTokens = $db->query("SELECT SUM(tokens) as total FROM usage_logs WHERE user_id = $userId")->fetch()['total'] ?? 0;
        
        // 今日调用
        $todayCalls = $db->query("SELECT COUNT(*) as count FROM usage_logs WHERE user_id = $userId AND DATE(created_at) = CURDATE()")->fetch()['count'] ?? 0;
        
        // 本月调用
        $monthCalls = $db->query("SELECT COUNT(*) as count FROM usage_logs WHERE user_id = $userId AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetch()['count'] ?? 0;
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_calls' => (int)$totalCalls,
                'total_tokens' => (int)$totalTokens,
                'today_calls' => (int)$todayCalls,
                'month_calls' => (int)$monthCalls
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => true,
            'data' => [
                'total_calls' => 0,
                'total_tokens' => 0,
                'today_calls' => 0,
                'month_calls' => 0
            ]
        ]);
    }
}

/**
 * 修改密码
 */
function changePassword($db, $userId) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword)) {
        throw new Exception('密码不能为空');
    }
    
    if (strlen($newPassword) < 6) {
        throw new Exception('新密码至少需要6位');
    }
    
    // 验证当前密码
    $user = $db->query("SELECT password FROM users WHERE id = $userId")->fetch();
    if (!$user || !password_verify($currentPassword, $user['password'])) {
        throw new Exception('当前密码错误');
    }
    
    // 更新密码
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $db->query("UPDATE users SET password = '$hashedPassword' WHERE id = $userId");
    
    echo json_encode(['success' => true, 'message' => '密码修改成功']);
}

/**
 * 获取API密钥列表
 */
function getApiKeys($db, $userId) {
    // 检查 api_keys 表是否存在
    try {
        $db->query("SELECT 1 FROM user_api_keys LIMIT 1");
    } catch (Exception $e) {
        // 创建API密钥表
        $db->exec("CREATE TABLE IF NOT EXISTS user_api_keys (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name VARCHAR(100) NOT NULL,
            api_key VARCHAR(255) NOT NULL UNIQUE,
            is_active INTEGER DEFAULT 1,
            usage_count INTEGER DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_used_at TIMESTAMP NULL
        )");
    }
    
    $keys = $db->query("SELECT id, name, api_key, usage_count, created_at FROM user_api_keys WHERE user_id = $userId AND is_active = 1 ORDER BY created_at DESC")->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $keys]);
}

/**
 * 创建API密钥
 */
function createApiKey($db, $userId) {
    $name = $_POST['name'] ?? 'API密钥';
    
    // 生成API密钥
    $apiKey = 'gvai_' . bin2hex(random_bytes(32));
    
    // 检查表是否存在
    try {
        $db->query("SELECT 1 FROM user_api_keys LIMIT 1");
    } catch (Exception $e) {
        $db->exec("CREATE TABLE IF NOT EXISTS user_api_keys (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name VARCHAR(100) NOT NULL,
            api_key VARCHAR(255) NOT NULL UNIQUE,
            is_active INTEGER DEFAULT 1,
            usage_count INTEGER DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_used_at TIMESTAMP NULL
        )");
    }
    
    $stmt = $db->prepare("INSERT INTO user_api_keys (user_id, name, api_key) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $name, $apiKey]);
    $keyId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'id' => $keyId,
        'api_key' => $apiKey,
        'message' => 'API密钥创建成功'
    ]);
}

/**
 * 删除API密钥
 */
function deleteApiKey($db, $userId) {
    $keyId = intval($_POST['key_id'] ?? 0);
    
    if ($keyId <= 0) {
        throw new Exception('无效的密钥ID');
    }
    
    $db->query("UPDATE user_api_keys SET is_active = 0 WHERE id = $keyId AND user_id = $userId");
    
    echo json_encode(['success' => true, 'message' => 'API密钥已删除']);
}

/**
 * 获取余额
 */
function getBalance($db, $userId) {
    // 检查 user_balance 表是否存在
    try {
        $db->query("SELECT 1 FROM user_balance LIMIT 1");
    } catch (Exception $e) {
        $db->exec("CREATE TABLE IF NOT EXISTS user_balance (
            user_id INTEGER PRIMARY KEY,
            balance DECIMAL(10,2) DEFAULT 0.00,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    // 初始化余额
    $db->exec("INSERT OR IGNORE INTO user_balance (user_id, balance) VALUES ($userId, 0.00)");
    
    $balance = $db->query("SELECT balance FROM user_balance WHERE user_id = $userId")->fetch()['balance'] ?? 0;
    
    echo json_encode(['success' => true, 'balance' => (float)$balance]);
}

/**
 * 充值
 */
function recharge($db, $userId) {
    $amount = floatval($_POST['amount'] ?? 0);
    
    if ($amount <= 0) {
        throw new Exception('充值金额无效');
    }
    
    // 确保表存在
    try {
        $db->query("SELECT 1 FROM user_balance LIMIT 1");
    } catch (Exception $e) {
        $db->exec("CREATE TABLE IF NOT EXISTS user_balance (
            user_id INTEGER PRIMARY KEY,
            balance DECIMAL(10,2) DEFAULT 0.00,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    // 检查充值记录表
    try {
        $db->query("SELECT 1 FROM recharge_records LIMIT 1");
    } catch (Exception $e) {
        $db->exec("CREATE TABLE IF NOT EXISTS recharge_records (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            points INTEGER NOT NULL,
            status VARCHAR(20) DEFAULT 'completed',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    // 计算积分（1元=10积分）
    $points = $amount * 10;
    if ($amount >= 100) $points = $amount * 11; // 100元以上1.1倍
    if ($amount >= 200) $points = $amount * 12; // 200元以上1.2倍
    if ($amount >= 500) $points = $amount * 13; // 500元以上1.3倍
    if ($amount >= 1000) $points = $amount * 14; // 1000元以上1.4倍
    if ($amount >= 2000) $points = $amount * 15; // 2000元以上1.5倍
    
    // 更新余额
    $db->query("INSERT INTO user_balance (user_id, balance) VALUES ($userId, $points) ON DUPLICATE KEY UPDATE balance = balance + $points");
    
    // 记录充值
    $stmt = $db->prepare("INSERT INTO recharge_records (user_id, amount, points) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $amount, $points]);
    
    echo json_encode([
        'success' => true,
        'message' => "充值成功，获得{$points}积分",
        'points' => $points
    ]);
}
