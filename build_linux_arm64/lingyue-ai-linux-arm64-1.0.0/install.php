<?php
/**
 * 巨神兵API辅助平台API辅助平台 - 安装程序
 */

session_start();

$configFile = __DIR__ . '/config/database.php';
$dataDir = __DIR__ . '/data';
$dbFile = $dataDir . '/database.sqlite';

// 检查是否已经安装（检查数据库文件是否存在且包含users表）
$isInstalled = false;
if (file_exists($dbFile) && filesize($dbFile) > 0) {
    try {
        require_once __DIR__ . '/includes/Database.php';
        $db = Database::getInstance();
        // 检查users表是否存在
        $tableExists = $db->fetch("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        if ($tableExists) {
            $isInstalled = true;
        }
    } catch (Exception $e) {
        // 数据库连接失败，可能需要重新安装
        $isInstalled = false;
    }
}

if ($isInstalled) {
    // 已安装，重定向到首页
    header('Location: index.php?route=home');
    exit;
}

$error = '';
$success = '';

// 处理安装请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminUsername = $_POST['admin_username'] ?? 'admin';
    $adminPassword = $_POST['admin_password'] ?? '';
    $adminEmail = $_POST['admin_email'] ?? 'admin@example.com';
    
    if (strlen($adminPassword) < 6) {
        $error = '管理员密码至少需要6个字符';
    } else {
        try {
            // 创建数据目录
            if (!is_dir($dataDir)) {
                mkdir($dataDir, 0755, true);
            }
            
            // 创建数据库文件
            if (!file_exists($dbFile)) {
                touch($dbFile);
                chmod($dbFile, 0666);
            }
            
            // 初始化数据库
            require_once __DIR__ . '/includes/Database.php';
            $db = Database::getInstance();
            
            // 创建默认管理员用户
            $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
            $db->exec("INSERT INTO users (username, password_hash, email, role, is_active) 
                      VALUES (:username, :password, :email, 'admin', 1)", [
                ':username' => $adminUsername,
                ':password' => $passwordHash,
                ':email' => $adminEmail
            ]);
            
            // 初始化权限表
            require_once __DIR__ . '/includes/PermissionManager.php';
            $permManager = new PermissionManager($db);
            
            $success = '安装成功！请使用管理员账号登录。';
            
            // 自动登录
            $user = $db->fetch("SELECT * FROM users WHERE username = :username", [':username' => $adminUsername]);
            if ($user) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'logged_in' => true
                ];
                header('Location: index.php');
                exit;
            }
        } catch (Exception $e) {
            $error = '安装失败: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装 - 巨神兵API辅助平台API辅助平台</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .install-box {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo i {
            font-size: 48px;
            color: #667eea;
        }

        h1 {
            font-size: 24px;
            color: #1a202c;
            margin-bottom: 8px;
            text-align: center;
        }

        .subtitle {
            color: #64748b;
            text-align: center;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            transition: border-color 0.2s;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-install {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-install:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #d1fae5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .info-box {
            background: #f3f4f6;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #4b5563;
        }

        .info-box i {
            color: #667eea;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="install-box">
        <div class="logo">
            <i class="fas fa-robot"></i>
        </div>
        <h1>巨神兵API辅助平台API辅助平台</h1>
        <p class="subtitle">系统安装向导</p>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php else: ?>
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                欢迎使用巨神兵API辅助平台API辅助平台！请设置管理员账号以完成安装。
            </div>

            <form method="post">
                <div class="form-group">
                    <label for="admin_username">管理员账号</label>
                    <input type="text" id="admin_username" name="admin_username" value="admin" required>
                </div>

                <div class="form-group">
                    <label for="admin_email">管理员邮箱</label>
                    <input type="email" id="admin_email" name="admin_email" value="admin@example.com" required>
                </div>

                <div class="form-group">
                    <label for="admin_password">管理员密码</label>
                    <input type="password" id="admin_password" name="admin_password" placeholder="至少6位密码" required>
                </div>

                <button type="submit" class="btn-install">
                    <i class="fas fa-rocket"></i> 开始安装
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
