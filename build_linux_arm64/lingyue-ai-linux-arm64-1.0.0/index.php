<?php
// 显示错误信息（调试用，生产环境请关闭）
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';

session_start();

$route = isset($_GET['route']) ? $_GET['route'] : 'home';
$pageTitle = '巨神兵API辅助平台API辅助平台';

// 检查是否在正确的目录运行
if (!file_exists(__DIR__ . '/templates/home.php')) {
    die('错误：模板文件不存在，请检查服务器根目录配置');
}

$dbFile = __DIR__ . '/data/database.sqlite';

// 检查是否需要安装（更严格的检查）
$needsInstall = true;
if (file_exists($dbFile) && filesize($dbFile) > 0) {
    try {
        require_once __DIR__ . '/includes/Database.php';
        $db = Database::getInstance();
        // 检查users表是否存在
        $tableExists = $db->fetch("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        if ($tableExists) {
            $needsInstall = false;
        }
    } catch (Exception $e) {
        // 数据库问题，标记为需要安装
        $needsInstall = true;
    }
}

if ($needsInstall && $route !== 'install') {
    header('Location: install.php');
    exit;
}

if ($route === 'api') {
    include __DIR__ . '/api/api_handler.php';
    exit;
}

$db = null;
$permManager = null;
if (!$needsInstall) {
    require_once __DIR__ . '/includes/Database.php';
    require_once __DIR__ . '/includes/PermissionManager.php';
    try {
        $db = Database::getInstance();
        $permManager = new PermissionManager($db);
    } catch (Exception $e) {
        if ($route !== 'install') {
            die('数据库连接失败: ' . $e->getMessage());
        }
    }
}

$isLoggedIn = isset($_SESSION['user']) && $_SESSION['user']['logged_in'];
$isAdmin = $isLoggedIn && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';

function checkModulePermission($module) {
    global $permManager, $isAdmin, $isLoggedIn;
    if (!$isLoggedIn) return false;
    if ($isAdmin) return true;
    if (!$permManager) return true;
    return $permManager->hasModulePermission($_SESSION['user']['id'], $module);
}

$protectedRoutes = ['chat', 'agents', 'workflows_comfyui', 'user_center', 'recharge', 'scenarios', 'admin', 'agent_editor', 'settings'];

// agent_chat 支持通过token外部访问，单独处理
if ($route === 'agent_chat') {
    // 如果有token参数，允许外部访问
    $agentToken = $_GET['token'] ?? '';
    if (empty($agentToken) && !$isLoggedIn) {
        header('Location: ?route=login&redirect=' . urlencode($route));
        exit;
    }
} elseif (in_array($route, $protectedRoutes) && !$isLoggedIn) {
    header('Location: ?route=login&redirect=' . urlencode($route));
    exit;
}

switch ($route) {
    case 'home':
        include __DIR__ . '/templates/home.php';
        break;
        
    case 'about':
        $pageTitle = '关于我们';
        include __DIR__ . '/templates/about.php';
        break;
        
    case 'privacy':
        $pageTitle = '隐私政策';
        include __DIR__ . '/templates/privacy.php';
        break;
        
    case 'terms':
        $pageTitle = '用户协议';
        include __DIR__ . '/templates/terms.php';
        break;
        
    case 'chat':
        if (!checkModulePermission('chat')) {
            header('Location: ?route=home&error=permission_denied');
            exit;
        }
        $pageTitle = 'AI聊天';
        include __DIR__ . '/templates/chat.php';
        break;
        
    case 'agents':
        if (!checkModulePermission('agents')) {
            header('Location: ?route=home&error=permission_denied');
            exit;
        }
        $pageTitle = '智能体';
        include __DIR__ . '/templates/agents.php';
        break;
        
    case 'agent_editor':
        if (!checkModulePermission('agents')) {
            header('Location: ?route=home&error=permission_denied');
            exit;
        }
        $pageTitle = '编辑智能体';
        include __DIR__ . '/templates/agent_editor.php';
        break;
        
    case 'agent_chat':
        // 如果有token参数，允许外部访问，不检查权限
        $agentToken = $_GET['token'] ?? '';
        if (empty($agentToken) && !checkModulePermission('agents')) {
            header('Location: ?route=home&error=permission_denied');
            exit;
        }
        $pageTitle = '智能体对话';
        include __DIR__ . '/templates/agent_chat.php';
        break;
        
    case 'workflows_comfyui':
        if (!checkModulePermission('workflows')) {
            header('Location: ?route=home&error=permission_denied');
            exit;
        }
        $pageTitle = 'ComfyUI工作流';
        include __DIR__ . '/templates/workflows_comfyui.php';
        break;
        
    case 'scenarios':
        if (!checkModulePermission('scenarios')) {
            header('Location: ?route=home&error=permission_denied');
            exit;
        }
        $pageTitle = '场景演示';
        include __DIR__ . '/templates/scenarios.php';
        break;
        
    case 'user_center':
        if (!checkModulePermission('user_center')) {
            header('Location: ?route=home&error=permission_denied');
            exit;
        }
        $pageTitle = '用户中心';
        include __DIR__ . '/templates/user_center.php';
        break;
        
    case 'recharge':
        if (!checkModulePermission('user_center')) {
            header('Location: ?route=home&error=permission_denied');
            exit;
        }
        $pageTitle = '充值中心';
        include __DIR__ . '/templates/recharge.php';
        break;
        
    case 'admin':
        if (!$isAdmin) {
            header('Location: ?route=home&error=admin_only');
            exit;
        }
        $pageTitle = '后台管理';
        include __DIR__ . '/templates/admin.php';
        break;
        
    case 'settings':
        if (!$isAdmin) {
            header('Location: ?route=home&error=admin_only');
            exit;
        }
        $pageTitle = '系统设置';
        include __DIR__ . '/templates/settings.php';
        break;
        
    case 'login':
        if ($isLoggedIn) {
            header('Location: ?route=home');
            exit;
        }
        // 处理登录表单提交
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $error = '';
            
            if ($username && $password && $db) {
                $user = $db->fetch(
                    "SELECT * FROM users WHERE username = :username AND is_active = 1",
                    [':username' => $username]
                );
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    $db->exec(
                        "UPDATE users SET last_login = datetime('now') WHERE id = :id",
                        [':id' => $user['id']]
                    );
                    
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'logged_in' => true
                    ];
                    
                    $redirect = $_GET['redirect'] ?? 'home';
                    header('Location: ?route=' . $redirect);
                    exit;
                } else {
                    $error = '用户名或密码错误';
                }
            } else {
                $error = '请填写用户名和密码';
            }
        }
        $pageTitle = '登录';
        include __DIR__ . '/templates/login.php';
        break;
        
    case 'register':
        if ($isLoggedIn) {
            header('Location: ?route=home');
            exit;
        }
        // 处理注册表单提交
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $email = $_POST['email'] ?? '';
            $error = '';
            
            if ($username && $password && $email && $db) {
                $existing = $db->fetch(
                    "SELECT id FROM users WHERE username = :username",
                    [':username' => $username]
                );
                if ($existing) {
                    $error = '用户名已存在';
                } else {
                    $existingEmail = $db->fetch(
                        "SELECT id FROM users WHERE email = :email",
                        [':email' => $email]
                    );
                    if ($existingEmail) {
                        $error = '邮箱已被注册';
                    } else {
                        try {
                            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                            $userId = $db->insert('users', [
                                'username' => $username,
                                'password_hash' => $passwordHash,
                                'email' => $email,
                                'role' => 'user',
                                'is_active' => 1
                            ]);
                            
                            $_SESSION['user'] = [
                                'id' => $userId,
                                'username' => $username,
                                'email' => $email,
                                'role' => 'user',
                                'logged_in' => true
                            ];
                            
                            header('Location: ?route=home');
                            exit;
                        } catch (Exception $e) {
                            $error = '注册失败：' . $e->getMessage();
                        }
                    }
                }
            } else {
                $error = '请填写所有必填字段';
            }
        }
        $pageTitle = '注册';
        include __DIR__ . '/templates/register.php';
        break;
        
    case 'logout':
        session_unset();
        session_destroy();
        header('Location: ?route=home');
        exit;
        
    case 'logs_viewer':
        if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']) {
            header('Location: ?route=login');
            exit;
        }
        $pageTitle = '日志查看';
        include __DIR__ . '/templates/logs_viewer.php';
        break;
        
    case 'system_status':
        if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']) {
            header('Location: ?route=login');
            exit;
        }
        $pageTitle = '系统状态';
        include __DIR__ . '/templates/system_status.php';
        break;
        
    case 'system_settings':
        if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']) {
            header('Location: ?route=login');
            exit;
        }
        $pageTitle = '系统设置';
        include __DIR__ . '/templates/system_settings.php';
        break;
        
    default:
        // 尝试加载指定的模板，如果不存在则加载首页
        $templateFile = __DIR__ . '/templates/' . $route . '.php';
        if (file_exists($templateFile)) {
            include $templateFile;
        } else {
            // 404 页面未找到，加载首页
            header('HTTP/1.0 404 Not Found');
            $pageTitle = '页面未找到';
            include __DIR__ . '/templates/home.php';
        }
        break;
}
?>
