<?php

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => '无权访问']);
    exit;
}

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/PermissionManager.php';

$db = Database::getInstance();
$permissionManager = new PermissionManager($db);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'getUserPermissions':
            $userId = $_GET['user_id'] ?? 0;
            if (!$userId) {
                throw new Exception('用户ID不能为空');
            }
            
            $permissions = $permissionManager->getUserPermissions($userId);
            echo json_encode(['success' => true, 'data' => $permissions]);
            break;
            
        case 'setModulePermission':
            $userId = $_POST['user_id'] ?? 0;
            $module = $_POST['module'] ?? '';
            $allowed = ($_POST['allowed'] ?? 'true') === 'true';
            
            if (!$userId || !$module) {
                throw new Exception('参数不完整');
            }
            
            $result = $permissionManager->setModulePermission($userId, $module, $allowed);
            echo json_encode($result);
            break;
            
        case 'setModelPermission':
            $userId = $_POST['user_id'] ?? 0;
            $providerId = $_POST['provider_id'] ?? '';
            $modelId = $_POST['model_id'] ?? '';
            $allowed = ($_POST['allowed'] ?? 'true') === 'true';
            $maxTokens = $_POST['max_tokens_per_day'] ?? null;
            
            if (!$userId || !$providerId || !$modelId) {
                throw new Exception('参数不完整');
            }
            
            $result = $permissionManager->setModelPermission(
                $userId,
                $providerId,
                $modelId,
                $allowed,
                $maxTokens ? intval($maxTokens) : null
            );
            echo json_encode($result);
            break;
            
        case 'setTrainingPermission':
            $userId = $_POST['user_id'] ?? 0;
            $modelName = $_POST['model_name'] ?? '';
            $allowed = ($_POST['allowed'] ?? 'true') === 'true';
            $maxJobs = $_POST['max_training_jobs'] ?? null;
            
            if (!$userId || !$modelName) {
                throw new Exception('参数不完整');
            }
            
            $result = $permissionManager->setTrainingPermission(
                $userId,
                $modelName,
                $allowed,
                $maxJobs ? intval($maxJobs) : null
            );
            echo json_encode($result);
            break;
            
        case 'batchSetPermissions':
            $userId = $_POST['user_id'] ?? 0;
            $permissions = json_decode($_POST['permissions'] ?? '{}', true);
            
            if (!$userId || empty($permissions)) {
                throw new Exception('参数不完整');
            }
            
            $result = $permissionManager->batchSetPermissions($userId, $permissions);
            echo json_encode($result);
            break;
            
        case 'getAllModules':
            $modules = $permissionManager->getAllModules();
            echo json_encode(['success' => true, 'data' => $modules]);
            break;
            
        case 'getMyPermissions':

            $userId = $_SESSION['user']['id'] ?? 0;
            if (!$userId) {
                throw new Exception('未登录');
            }
            
            $permissions = $permissionManager->getUserPermissions($userId);
            $isAdmin = $_SESSION['user']['role'] === 'admin';
            

            $moduleAccess = [];
            foreach ($permissions['modules'] as $perm) {
                $moduleAccess[$perm['module']] = (bool)$perm['allowed'];
            }
            

            $modelAccess = [];
            foreach ($permissions['models'] as $perm) {
                $key = $perm['provider_id'] . ':' . $perm['model_id'];
                $modelAccess[$key] = [
                    'allowed' => (bool)$perm['allowed'],
                    'max_tokens' => $perm['max_tokens_per_day']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'is_admin' => $isAdmin,
                    'modules' => $moduleAccess,
                    'models' => $modelAccess,
                    'training' => $permissions['training']
                ]
            ]);
            break;
            
        default:
            throw new Exception('未知的操作类型');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
