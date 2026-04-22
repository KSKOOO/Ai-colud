<?php

header('Content-Type: application/json');


error_reporting(0);
ini_set('display_errors', 0);


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']) {
    echo json_encode(['status' => 'error', 'message' => '请先登录']);
    exit;
}

require_once __DIR__ . '/../includes/UserManager.php';
require_once __DIR__ . '/../includes/Database.php';

$userManager = new UserManager();
$currentUser = $_SESSION['user'];


function formatResponse($result) {
    if (isset($result['success'])) {
        return [
            'status' => $result['success'] ? 'success' : 'error',
            'message' => $result['message'] ?? '',
            'user_id' => $result['user_id'] ?? null,
            'user' => $result['user'] ?? null
        ];
    }
    return $result;
}


$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'getCurrentUser':
        echo json_encode([
            'status' => 'success',
            'user' => [
                'id' => $currentUser['id'],
                'username' => $currentUser['username'],
                'email' => $currentUser['email'],
                'role' => $currentUser['role']
            ]
        ]);
        break;


    case 'changePassword':
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        
        $result = $userManager->changePassword($currentUser['id'], $oldPassword, $newPassword);
        echo json_encode(formatResponse($result));
        break;


    case 'updateProfile':
        $email = $_POST['email'] ?? '';
        $result = $userManager->updateUser($currentUser['id'], ['email' => $email]);
        
        if ($result['success']) {

            $_SESSION['user']['email'] = $email;
        }
        
        echo json_encode(formatResponse($result));
        break;


    

    case 'getAllUsers':
        if ($currentUser['role'] !== 'admin') {
            echo json_encode(['status' => 'error', 'message' => '权限不足']);
            break;
        }
        
        $page = intval($_GET['page'] ?? 1);
        $result = $userManager->getAllUsers($page);
        echo json_encode(['status' => 'success'] + $result);
        break;


    case 'createUser':
        if ($currentUser['role'] !== 'admin') {
            echo json_encode(['status' => 'error', 'message' => '权限不足']);
            break;
        }
        
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? 'user';
        
        $result = $userManager->createUser($username, $password, $email, $role);
        echo json_encode(formatResponse($result));
        break;


    case 'updateUser':
        if ($currentUser['role'] !== 'admin') {
            echo json_encode(['status' => 'error', 'message' => '权限不足']);
            break;
        }
        
        $userId = intval($_POST['user_id'] ?? 0);
        

        if ($userId === $currentUser['id']) {
            if (isset($_POST['role']) && $_POST['role'] !== 'admin') {
                echo json_encode(['status' => 'error', 'message' => '不能将自己的角色降级为普通用户']);
                break;
            }
            if (isset($_POST['is_active']) && intval($_POST['is_active']) === 0) {
                echo json_encode(['status' => 'error', 'message' => '不能禁用自己的账号']);
                break;
            }
        }
        
        $data = [];
        
        if (isset($_POST['email'])) $data['email'] = $_POST['email'];
        if (isset($_POST['role'])) $data['role'] = $_POST['role'];
        if (isset($_POST['is_active'])) $data['is_active'] = intval($_POST['is_active']);
        
        $result = $userManager->updateUser($userId, $data);

        $response = [
            'status' => $result['success'] ? 'success' : 'error',
            'message' => $result['message']
        ];
        echo json_encode($response);
        break;


    case 'resetPassword':
        if ($currentUser['role'] !== 'admin') {
            echo json_encode(['status' => 'error', 'message' => '权限不足']);
            break;
        }
        
        $userId = intval($_POST['user_id'] ?? 0);
        $newPassword = $_POST['new_password'] ?? '';
        
        $result = $userManager->adminResetPassword($userId, $newPassword);
        echo json_encode(formatResponse($result));
        break;


    case 'deleteUser':
        if ($currentUser['role'] !== 'admin') {
            echo json_encode(['status' => 'error', 'message' => '权限不足']);
            break;
        }
        

        $userId = intval($_POST['user_id'] ?? 0);
        if ($userId === $currentUser['id']) {
            echo json_encode(['status' => 'error', 'message' => '不能删除自己的账号']);
            break;
        }
        
        $result = $userManager->deleteUser($userId);
        echo json_encode(formatResponse($result));
        break;
    
    case 'get_usage_stats':
        try {
            $db = Database::getInstance();
            $userId = $currentUser['id'];
            
            // 检查 usage_logs 表是否存在
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
            $totalCalls = 0;
            try {
                $result = $db->query("SELECT COUNT(*) as count FROM usage_logs WHERE user_id = $userId");
                $totalCalls = $result->fetch()['count'] ?? 0;
            } catch (Exception $e) {}
            
            // 总Token数
            $totalTokens = 0;
            try {
                $result = $db->query("SELECT SUM(tokens) as total FROM usage_logs WHERE user_id = $userId");
                $totalTokens = $result->fetch()['total'] ?? 0;
            } catch (Exception $e) {}
            
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'total_calls' => (int)$totalCalls,
                    'total_tokens' => (int)$totalTokens,
                    'today_calls' => 0,
                    'month_calls' => 0
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'total_calls' => 0,
                    'total_tokens' => 0,
                    'today_calls' => 0,
                    'month_calls' => 0
                ]
            ]);
        }
        break;
    
    case 'get_profile':
        echo json_encode([
            'status' => 'success',
            'data' => [
                'id' => $currentUser['id'],
                'username' => $currentUser['username'],
                'email' => $currentUser['email'] ?? '',
                'role' => $currentUser['role'] ?? 'user',
                'created_at' => $currentUser['created_at'] ?? date('Y-m-d')
            ]
        ]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => '未知操作']);
}
?>
