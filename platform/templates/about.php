<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>关于 - 巨神兵AIAPI辅助平台</title>
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
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            width: 100%;
        }

        /* 头部导航 */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.6);
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
            font-weight: 700;
            color: #4c51bf;
        }

        .logo img {
            height: 36px;
        }

        .nav {
            display: flex;
            gap: 8px;
        }

        .nav-item {
            padding: 8px 12px;
            border-radius: 8px;
            color: #64748b;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
            white-space: nowrap;
            font-size: 13px;
        }

        .nav-item:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .nav-item.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        /* 主内容区 */
        .main-card {
            background: white;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 24px;
            text-align: center;
        }

        .about-content {
            color: #4a5568;
            line-height: 1.8;
        }

        .about-content p {
            margin-bottom: 16px;
        }

        .features-list {
            background: #f8fafc;
            border-radius: 16px;
            padding: 24px;
            margin: 24px 0;
        }

        .features-list h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 16px;
        }

        .features-list ul {
            list-style: none;
        }

        .features-list li {
            padding: 8px 0;
            padding-left: 28px;
            position: relative;
        }

        .features-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #4c51bf;
            font-weight: bold;
        }

        .contact-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 16px;
            padding: 24px;
            margin-top: 24px;
        }

        .contact-section h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .contact-section p {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 头部导航 -->
        <header class="header">
            <a href="?route=home" class="logo" style="text-decoration: none; display: flex; align-items: center; gap: 10px; min-width: 240px; flex-shrink: 0;">
                <img src="assets/images/logo.png" alt="巨神兵AIAI" style="height: 32px; width: auto; flex-shrink: 0;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <span style="display: flex; align-items: center; gap: 8px; color: #4c51bf; white-space: nowrap;">
                    <span style="font-size: 18px; font-weight: 700; white-space: nowrap;">巨神兵AIAPI辅助平台</span>
                </span>
            </a>
            <nav class="nav" style="display: flex; gap: 6px; align-items: center; flex-wrap: nowrap;">
                <a href="?route=home" class="nav-item">
                    <i class="fas fa-home"></i> <span>首页</span>
                </a>
                <?php if (isset($_SESSION['user']) && $_SESSION['user']['logged_in']): ?>
                    <a href="?route=chat" class="nav-item">
                        <i class="fas fa-comments"></i> <span>聊天</span>
                    </a>
                    <a href="?route=workflows_comfyui" class="nav-item">
                        <i class="fas fa-project-diagram"></i> <span>工作流</span>
                    </a>
                    <a href="?route=agents" class="nav-item">
                        <i class="fas fa-robot"></i> <span>智能体</span>
                    </a>
                    <a href="?route=logout" class="nav-item">
                        <i class="fas fa-sign-out-alt"></i> <span>退出</span>
                    </a>
                <?php else: ?>
                    <a href="?route=login" class="nav-item">
                        <i class="fas fa-sign-in-alt"></i> <span>登录</span>
                    </a>
                <?php endif; ?>
                <a href="templates/openclaw.php" class="nav-item lobster-btn" target="_blank" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 50%, #c44569 100%); color: white; border: none; font-weight: 600;">
                    <span style="font-size: 18px; margin-right: 4px;">🦞</span> <span>养龙虾</span>
                </a>
                <a href="?route=about" class="nav-item active">
                    <i class="fas fa-info-circle"></i> <span>关于</span>
                </a>
            </nav>
        </header>

        <!-- 主内容 -->
        <div class="main-card">
            <h1 class="page-title">关于巨神兵AIAPI辅助平台</h1>
            
            <div class="about-content">
                <p>巨神兵AIAPI辅助平台是一个基于先进AI技术的智能服务平台，致力于为用户提供强大的AI能力，助力工作效率提升。</p>
                
                <div class="features-list">
                    <h3>平台特点</h3>
                    <ul>
                        <li>基于最新的AI大模型技术</li>
                        <li>支持多种AI模型自由选择</li>
                        <li>实时对话交互，快速响应</li>
                        <li>可视化工作流编辑器</li>
                        <li>企业级安全保障</li>
                    </ul>
                </div>
                
                <div class="contact-section">
                    <h3>联系我们</h3>
                    <p><i class="fas fa-envelope" style="margin-right: 8px;"></i> 1293724438@qq.com</p>
                     <p><i class="fas fa-envelope" style="margin-right: 8px;"></i> 巨神兵AI出品</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
