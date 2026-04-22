<?php
/**
 * 系统设置 - 整合所有系统管理功能
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']) {
    header('Location: ?route=login');
    exit;
}

$isAdmin = ($_SESSION['user']['role'] ?? '') === 'admin';
$config = require __DIR__ . '/../config/config.php';

// 设置项
$settings = [
    'general' => [
        'title' => '通用设置',
        'icon' => 'fa-cog',
        'items' => [
            ['key' => 'app_name', 'label' => '平台名称', 'type' => 'text', 'value' => $config['app']['name'] ?? '巨神兵AIAPI辅助平台'],
            ['key' => 'app_version', 'label' => '版本号', 'type' => 'text', 'value' => $config['app']['version'] ?? '1.0.0'],
            ['key' => 'app_debug', 'label' => '调试模式', 'type' => 'toggle', 'value' => $config['app']['debug'] ?? false],
        ]
    ],
    'api' => [
        'title' => 'API设置',
        'icon' => 'fa-plug',
        'items' => [
            ['key' => 'api_timeout', 'label' => 'API超时时间(秒)', 'type' => 'number', 'value' => $config['api']['timeout'] ?? 30],
            ['key' => 'api_retry', 'label' => '重试次数', 'type' => 'number', 'value' => $config['api']['retry'] ?? 3],
            ['key' => 'rate_limit', 'label' => '请求频率限制(次/分钟)', 'type' => 'number', 'value' => $config['api']['rate_limit'] ?? 60],
        ]
    ],
    'security' => [
        'title' => '安全设置',
        'icon' => 'fa-shield-alt',
        'items' => [
            ['key' => 'session_timeout', 'label' => '会话超时(分钟)', 'type' => 'number', 'value' => $config['security']['session_timeout'] ?? 120],
            ['key' => 'max_login_attempts', 'label' => '最大登录尝试次数', 'type' => 'number', 'value' => $config['security']['max_login_attempts'] ?? 5],
            ['key' => 'password_min_length', 'label' => '密码最小长度', 'type' => 'number', 'value' => $config['security']['password_min_length'] ?? 6],
        ]
    ],
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统设置 - <?php echo $config['app']['name'] ?? '巨神兵AIAPI辅助平台'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            color: #1a202c;
        }
        
        /* 顶部导航 */
        .top-nav {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
            font-weight: 700;
            color: #4c51bf;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 8px;
        }
        
        .nav-link {
            padding: 10px 20px;
            border-radius: 10px;
            color: #64748b;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .nav-link:hover {
            background: #f3f4f6;
            color: #4c51bf;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px;
        }
        
        .page-header {
            margin-bottom: 32px;
        }
        
        .page-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 8px;
        }
        
        .page-header p {
            color: #64748b;
            font-size: 14px;
        }
        
        /* 菜单网格 */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .menu-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 20px;
            border: 1px solid #e2e8f0;
        }
        
        .menu-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .menu-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            background: #ede9fe;
            color: #4c51bf;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .menu-info h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #1f2937;
        }
        
        .menu-info p {
            font-size: 13px;
            color: #64748b;
        }
        
        /* 设置表单 */
        .settings-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
        }
        
        .settings-card h2 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #1a202c;
        }
        
        .settings-card h2 i {
            color: #4c51bf;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"] {
            width: 100%;
            max-width: 400px;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            background: #f8fafc;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4c51bf;
            background: white;
        }
        
        /* 开关样式 */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 28px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #e5e7eb;
            transition: .4s;
            border-radius: 28px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(22px);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #4c51bf;
            color: white;
        }
        
        .btn-primary:hover {
            background: #4338ca;
        }
        
        /* 开关样式 */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #e2e8f0;
            transition: .3s;
            border-radius: 24px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: #4c51bf;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(20px);
        }
        
        /* 权限提示 */
        .permission-alert {
            background: #fee2e2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            color: #dc2626;
        }
        
        .permission-alert i {
            font-size: 48px;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
    <!-- 顶部导航 -->
    <header class="top-nav">
        <a href="?route=home" class="logo">
            <i class="fas fa-robot"></i> <?php echo $config['app']['name'] ?? '巨神兵AIAPI辅助平台'; ?>
        </a>
        <nav class="nav-links">
            <a href="?route=home" class="nav-link">
                <i class="fas fa-home"></i> 返回前台
            </a>
            <a href="?route=admin" class="nav-link">
                <i class="fas fa-user-shield"></i> 管理后台
            </a>
            <a href="?route=logout" class="nav-link">
                <i class="fas fa-sign-out-alt"></i> 退出
            </a>
        </nav>
    </header>
    
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-cogs" style="color: #4c51bf;"></i> 系统设置</h1>
            <p>管理系统配置、日志查看、设备状态和巡检</p>
        </div>
        
        <?php if (!$isAdmin): ?>
        <div class="permission-alert">
            <i class="fas fa-lock"></i>
            <h3>权限不足</h3>
            <p>只有管理员可以访问系统设置</p>
        </div>
        <?php else: ?>
        
        <!-- 快速访问菜单 -->
        <div class="menu-grid">
            <a href="?route=logs_viewer" class="menu-card">
                <div class="menu-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="menu-info">
                    <h3>日志查看</h3>
                    <p>查看系统日志，AI分析日志内容</p>
                </div>
            </a>
            
            <a href="?route=system_status" class="menu-card">
                <div class="menu-icon">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <div class="menu-info">
                    <h3>系统状态</h3>
                    <p>设备状态监控，一键巡检</p>
                </div>
            </a>
            
            <a href="?route=user_center" class="menu-card">
                <div class="menu-icon">
                    <i class="fas fa-user-cog"></i>
                </div>
                <div class="menu-info">
                    <h3>用户中心</h3>
                    <p>个人设置、API密钥、充值管理</p>
                </div>
            </a>
        </div>
        
        <!-- 设置表单 -->
        <?php foreach ($settings as $sectionKey => $section): ?>
        <div class="settings-card">
            <h2><i class="fas <?php echo $section['icon']; ?>"></i> <?php echo $section['title']; ?></h2>
            <form onsubmit="saveSettings('<?php echo $sectionKey; ?>', event)">
                <?php foreach ($section['items'] as $item): ?>
                <div class="form-group">
                    <label><?php echo $item['label']; ?></label>
                    <?php if ($item['type'] === 'toggle'): ?>
                    <label class="toggle-switch">
                        <input type="checkbox" name="<?php echo $item['key']; ?>" <?php echo $item['value'] ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <?php else: ?>
                    <input type="<?php echo $item['type']; ?>" name="<?php echo $item['key']; ?>" value="<?php echo htmlspecialchars($item['value']); ?>">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 保存<?php echo $section['title']; ?>
                </button>
            </form>
        </div>
        <?php endforeach; ?>
        
        <?php endif; ?>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function saveSettings(section, event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            formData.append('section', section);
            
            $.ajax({
                url: 'api/settings_api.php?action=save_settings',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('设置保存成功！');
                    } else {
                        alert('保存失败：' + (response.error || '未知错误'));
                    }
                },
                error: function() {
                    alert('保存失败，请重试');
                }
            });
        }
    </script>
</body>
</html>
