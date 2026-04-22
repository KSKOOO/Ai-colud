<?php
/**
 * 系统设置 API
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
    echo json_encode(['success' => false, 'error' => '权限不足']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'save_settings':
            saveSettings();
            break;
            
        case 'get_settings':
            getSettings();
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
 * 保存设置
 */
function saveSettings() {
    $section = $_POST['section'] ?? '';
    
    if (empty($section)) {
        throw new Exception('设置分类不能为空');
    }
    
    // 读取现有配置
    $configPath = __DIR__ . '/../config/config.php';
    $config = require $configPath;
    
    // 根据分类更新配置
    switch ($section) {
        case 'general':
            if (isset($_POST['app_name'])) {
                $config['app']['name'] = sanitizeInput($_POST['app_name']);
            }
            if (isset($_POST['app_version'])) {
                $config['app']['version'] = sanitizeInput($_POST['app_version']);
            }
            if (isset($_POST['app_debug'])) {
                $config['app']['debug'] = $_POST['app_debug'] === 'on' || $_POST['app_debug'] === '1';
            }
            break;
            
        case 'api':
            if (isset($_POST['api_timeout'])) {
                $config['api']['timeout'] = intval($_POST['api_timeout']);
            }
            if (isset($_POST['api_retry'])) {
                $config['api']['retry'] = intval($_POST['api_retry']);
            }
            if (isset($_POST['rate_limit'])) {
                $config['api']['rate_limit'] = intval($_POST['rate_limit']);
            }
            break;
            
        case 'security':
            if (isset($_POST['session_timeout'])) {
                $config['security']['session_timeout'] = intval($_POST['session_timeout']);
            }
            if (isset($_POST['max_login_attempts'])) {
                $config['security']['max_login_attempts'] = intval($_POST['max_login_attempts']);
            }
            if (isset($_POST['password_min_length'])) {
                $config['security']['password_min_length'] = intval($_POST['password_min_length']);
            }
            break;
    }
    
    // 保存配置到文件
    $configContent = "<?php\nreturn " . var_export($config, true) . ";\n";
    
    if (file_put_contents($configPath, $configContent, LOCK_EX)) {
        echo json_encode(['success' => true, 'message' => '设置保存成功']);
    } else {
        throw new Exception('保存配置文件失败');
    }
}

/**
 * 获取设置
 */
function getSettings() {
    $config = require __DIR__ . '/../config/config.php';
    echo json_encode(['success' => true, 'data' => $config]);
}

/**
 * 清理输入
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
