<?php

header('Content-Type: application/json');


error_reporting(E_ALL);
ini_set('display_errors', 0);


set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo json_encode(['success' => false, 'error' => "PHP错误: $errstr 在 $errfile:$errline"]);
    exit;
});

set_exception_handler(function($e) {
    echo json_encode(['success' => false, 'error' => '异常: ' . $e->getMessage()]);
    exit;
});


require_once __DIR__ . '/../includes/Database.php';


try {
    $db = Database::getInstance();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => '数据库连接失败: ' . $e->getMessage()]);
    exit;
}

session_start();

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'error' => '未登录']);
    exit;
}

$userId = $_SESSION['user']['id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'getKeys':
            try {
                $stmt = $db->prepare("SELECT id, name, api_key, permissions, created_at, last_used_at, usage_count, quota_remaining, enabled FROM api_keys WHERE user_id = ? ORDER BY created_at DESC");
                $stmt->execute([$userId]);
                $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'keys' => $keys]);
            } catch (PDOException $e) {

                if ($e->getCode() == '42S02' || $e->getCode() == 'HY000') {
                    echo json_encode(['success' => true, 'keys' => []]);
                } else {
                    throw $e;
                }
            }
            break;
            
        case 'createKey':
            $name = $_POST['name'] ?? 'API Key';
            $permissions = $_POST['permissions'] ?? 'all';
            $quota = intval($_POST['quota'] ?? 100000);
            

            $apiKey = 'pk_' . bin2hex(random_bytes(32));
            
            try {

                $createTableSQL = "CREATE TABLE IF NOT EXISTS api_keys (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    name TEXT NOT NULL,
                    api_key TEXT NOT NULL UNIQUE,
                    permissions TEXT DEFAULT 'all',
                    quota_remaining INTEGER DEFAULT NULL,
                    usage_count INTEGER DEFAULT 0,
                    enabled INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    last_used_at DATETIME DEFAULT NULL,
                    expires_at DATETIME DEFAULT NULL
                )";
                
                $db->exec($createTableSQL);
                

                $db->exec("CREATE INDEX IF NOT EXISTS idx_api_key ON api_keys(api_key)");
                $db->exec("CREATE INDEX IF NOT EXISTS idx_user_id ON api_keys(user_id)");
                
                $stmt = $db->prepare("INSERT INTO api_keys (user_id, name, api_key, permissions, quota_remaining, created_at) VALUES (?, ?, ?, ?, ?, datetime('now'))");
                $stmt->execute([$userId, $name, $apiKey, $permissions, $quota]);
                
                echo json_encode([
                    'success' => true,
                    'key' => [
                        'id' => $db->lastInsertId(),
                        'name' => $name,
                        'api_key' => $apiKey,
                        'permissions' => $permissions
                    ],
                    'message' => 'API Key创建成功，请妥善保存，此密钥只显示一次'
                ]);
            } catch (PDOException $e) {
                echo json_encode([
                    'success' => false, 
                    'error' => '数据库错误: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'revokeKey':
            $keyId = intval($_POST['key_id'] ?? 0);
            
            $stmt = $db->prepare("UPDATE api_keys SET enabled = 0 WHERE id = ? AND user_id = ?");
            $stmt->execute([$keyId, $userId]);
            
            echo json_encode(['success' => true, 'message' => 'API Key已停用']);
            break;
            
        case 'deleteKey':
            $keyId = intval($_POST['key_id'] ?? 0);
            
            $stmt = $db->prepare("DELETE FROM api_keys WHERE id = ? AND user_id = ?");
            $stmt->execute([$keyId, $userId]);
            
            echo json_encode(['success' => true, 'message' => 'API Key已删除']);
            break;
            
        case 'updateQuota':
            $keyId = intval($_POST['key_id'] ?? 0);
            $quota = intval($_POST['quota'] ?? 100000);
            
            $stmt = $db->prepare("UPDATE api_keys SET quota_remaining = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$quota, $keyId, $userId]);
            
            echo json_encode(['success' => true, 'message' => '配额已更新']);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => '未知操作']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
