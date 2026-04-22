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
    <title>巨神兵API辅助平台API辅助平台</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
            min-height: 100vh;
            background-attachment: fixed;
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
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
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
            gap: 6px;
            align-items: center;
            flex-wrap: nowrap;
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
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .nav-item.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        /* 养龙虾按钮特殊样式 */
        .nav-item.lobster-btn {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 50%, #c44569 100%);
            color: white;
            border: none;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(238, 90, 111, 0.4);
            animation: pulse 2s infinite;
        }

        .nav-item.lobster-btn:hover {
            background: linear-gradient(135deg, #ff5252 0%, #f44336 50%, #c62828 100%);
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 6px 20px rgba(238, 90, 111, 0.5);
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 4px 15px rgba(238, 90, 111, 0.4); }
            50% { box-shadow: 0 4px 25px rgba(238, 90, 111, 0.6); }
        }

        /* 主内容区 */
        .main-card {
            background: white;
            padding: 32px;
            border-radius: 0 0 24px 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            width: 100%;
            box-sizing: border-box;
        }

        /* Hero区域 */
        .hero {
            text-align: center;
            padding: 80px 20px 60px;
            color: #1a202c;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.03) 0%, rgba(118, 75, 162, 0.03) 100%);
            border-radius: 20px;
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .hero h2 {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #c44569 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
            z-index: 1;
            letter-spacing: -0.5px;
        }

        .hero p {
            font-size: 18px;
            color: #64748b;
            max-width: 600px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
            line-height: 1.6;
        }

        /* 功能卡片 */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 28px;
            margin-top: 40px;
        }

        .feature-card {
            background: white;
            border: 1px solid rgba(226, 232, 240, 0.6);
            border-radius: 20px;
            padding: 32px 24px;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            border-color: transparent;
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.2);
        }

        .feature-card:hover::before {
            transform: scaleX(1);
        }

        .feature-icon {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            margin: 0 auto 20px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .feature-card h3 {
            font-size: 20px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 12px;
        }

        .feature-card p {
            color: #64748b;
            font-size: 14px;
            line-height: 1.7;
        }

        /* 登录提示 */
        .login-prompt {
            text-align: center;
            padding: 50px 40px;
            background: #f8fafc;
            border-radius: 20px;
            margin-top: 50px;
            transition: all 0.3s ease;
        }

        .login-prompt:hover {
            border-color: #667eea;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.1);
        }

        .login-prompt h3 {
            font-size: 26px;
            color: #1a202c;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .login-prompt p {
            color: #64748b;
            margin-bottom: 28px;
            font-size: 15px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        .btn-secondary {
            background: white;
            color: #4a5568;
            border: 2px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #f7fafc;
            border-color: #cbd5e0;
            transform: translateY(-2px);
        }

        /* 主按钮 */
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 32px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.1) inset;
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .btn-gradient::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.2) inset;
        }

        .btn-gradient:hover::before {
            left: 100%;
        }

        .btn-gradient:active {
            transform: translateY(0);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.1) inset;
        }

        .btn-gradient i {
            font-size: 16px;
            transition: transform 0.3s ease;
        }

        .btn-gradient:hover i {
            transform: translateX(3px) translateY(-1px);
        }

        /* 白色变体 - 用于深色背景 */
        .btn-gradient.btn-white {
            background: white;
            color: #667eea;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-gradient.btn-white:hover {
            background: #f7fafc;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
        }

        .btn-gradient.btn-white::before {
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
        }

        /* 移动端响应式设计 */
        @media screen and (max-width: 768px) {
            body {
                padding: 10px;
            }

            .header {
                flex-direction: column;
                gap: 12px;
                padding: 12px 16px;
            }

            .logo {
                font-size: 16px;
            }

            .logo img {
                height: 28px;
            }

            .nav {
                flex-wrap: wrap;
                justify-content: center;
                gap: 4px;
            }

            .nav-item {
                padding: 8px 12px;
                font-size: 13px;
            }

            .main-card {
                padding: 20px 16px;
            }

            .hero {
                padding: 30px 10px;
            }

            .hero h2 {
                font-size: 24px;
            }

            .hero p {
                font-size: 14px;
            }

            .features-grid {
                grid-template-columns: 1fr;
                gap: 16px;
                margin-top: 24px;
            }

            .feature-card {
                padding: 20px;
            }

            .feature-icon {
                width: 56px;
                height: 56px;
                font-size: 24px;
            }
        }

        @media screen and (max-width: 480px) {
            .nav-item span {
                display: none;
            }

            .nav-item i {
                font-size: 16px;
            }

            .hero h2 {
                font-size: 20px;
            }

            .btn-gradient {
                padding: 12px 24px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 头部导航 -->
        <header class="header">
            <a href="?route=home" class="logo" style="text-decoration: none; display: flex; align-items: center; gap: 10px; min-width: 240px; flex-shrink: 0;">
                <img src="assets/images/logo.png" alt="Logo" style="height: 36px; width: auto; object-fit: contain; flex-shrink: 0;" 
                     onerror="this.style.display='none'; document.getElementById('logo-fallback').style.display='flex';">
                <span id="logo-fallback" style="display: none; align-items: center; gap: 8px; color: #4c51bf; white-space: nowrap;">
                    <i class="fas fa-robot" style="font-size: 28px;"></i>
                    <span style="font-size: 20px; font-weight: 700; white-space: nowrap;">巨神兵API辅助平台API辅助平台</span>
                </span>
                <span id="logo-text" style="display: flex; align-items: center; gap: 8px; color: #4c51bf; white-space: nowrap;">
                    <span style="font-size: 20px; font-weight: 700; white-space: nowrap;">巨神兵API辅助平台API辅助平台</span>
                </span>
            </a>
            <nav class="nav">
                <a href="?route=home" class="nav-item active">
                    <i class="fas fa-home"></i> <span>首页</span>
                </a>
                <?php
                // 获取当前用户权限
                $userPermissions = null;
                if (isset($_SESSION['user']['id']) && isset($permissionManager)) {
                    $userPermissions = $permissionManager->getUserPermissions($_SESSION['user']['id']);
                }
                $isAdmin = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
                
                // 检查模块权限的辅助函数
                function canAccessModule($module, $userPermissions, $isAdmin) {
                    if ($isAdmin) return true;
                    if (!$userPermissions) return true;
                    foreach ($userPermissions['modules'] as $perm) {
                        if ($perm['module'] === $module) {
                            return $perm['allowed'] == 1;
                        }
                    }
                    return true; // 默认允许
                }
                ?>
                <a href="?route=scenarios" class="nav-item" data-require-module="scenarios" style="<?php echo isset($_SESSION['user']['id']) && !canAccessModule('scenarios', $userPermissions, $isAdmin) ? 'display:none;' : ''; ?>">
                    <i class="fas fa-magic"></i> <span>场景演示</span>
                </a>
                <?php if (isset($_SESSION['user']) && $_SESSION['user']['logged_in']): ?>
                    <?php if (canAccessModule('chat', $userPermissions, $isAdmin)): ?>
                    <a href="?route=chat" class="nav-item">
                        <i class="fas fa-comments"></i> <span>聊天</span>
                    </a>
                    <?php endif; ?>
                    <?php if (canAccessModule('workflows', $userPermissions, $isAdmin)): ?>
                    <a href="?route=workflows_comfyui" class="nav-item">
                        <i class="fas fa-project-diagram"></i> <span>工作流</span>
                    </a>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['user']) && $_SESSION['user']['logged_in']): ?>
                    <a href="?route=agents" class="nav-item">
                        <i class="fas fa-robot"></i> <span>智能体</span>
                    </a>
                    <a href="?route=user_center" class="nav-item">
                        <i class="fas fa-user-circle"></i> <span>用户中心</span>
                    </a>
                    <?php endif; ?>
                    <?php if ($isAdmin): ?>
                    <a href="?route=admin" class="nav-item">
                        <i class="fas fa-user-shield"></i> <span>管理</span>
                    </a>
                    <?php endif; ?>
                    <a href="?route=logout" class="nav-item">
                        <i class="fas fa-sign-out-alt"></i> <span>退出</span>
                    </a>
                <?php else: ?>
                    <a href="?route=login" class="nav-item">
                        <i class="fas fa-sign-in-alt"></i> <span>登录</span>
                    </a>
                <?php endif; ?>
                <a href="templates/openclaw.php" class="nav-item lobster-btn" target="_blank" title="AI智能体训练系统">
                    <span style="font-size: 20px; margin-right: 4px;">🦞</span> <span>养龙虾</span>
                </a>
                <a href="?route=about" class="nav-item">
                    <i class="fas fa-info-circle"></i> <span>关于</span>
                </a>
            </nav>
        </header>

        <!-- 主内容 -->
        <div class="main-card">
            <div class="hero">
                <h2>欢迎使用巨神兵API辅助平台出品API辅助平台</h2>
                <p>基于本地AI模型，提供智能对话、内容生成、代码辅助等功能</p>
            </div>

            <!-- 场景演示入口 -->
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 24px; padding: 50px 40px; margin-bottom: 40px; text-align: center; position: relative; overflow: hidden; box-shadow: 0 20px 40px rgba(102, 126, 234, 0.3);">
                <div style="position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%); animation: rotate 30s linear infinite;"></div>
                <h3 style="font-size: 28px; margin-bottom: 16px; color: white; font-weight: 700; position: relative; z-index: 1;">
                    <i class="fas fa-magic" style="margin-right: 10px;"></i> 
                    探索AI场景演示中心
                </h3>
                <p style="color: rgba(255,255,255,0.9); margin-bottom: 28px; max-width: 600px; margin-left: auto; margin-right: auto; font-size: 16px; line-height: 1.6; position: relative; z-index: 1;">
                    涵盖AI员工、智能剪辑、抖音&小红书文案生成等16+行业场景，一键体验AI赋能传统行业
                </p>
                <a href="?route=scenarios" class="btn btn-gradient btn-white" style="position: relative; z-index: 1;">
                    <i class="fas fa-rocket"></i> 
                    立即体验场景演示
                </a>
            </div>

            <div style="text-align: center; margin-bottom: 30px;">
                <h3 style="font-size: 24px; color: #1a202c; margin-bottom: 8px; font-weight: 700;">核心功能</h3>
                <p style="color: #64748b; font-size: 15px;">探索AI赋能的无限可能</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card" onclick="location.href='<?php echo isset($_SESSION['user']) && $_SESSION['user']['logged_in'] ? '?route=agents' : '?route=login'; ?>'">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);">
                        <i class="fas fa-magic"></i>
                    </div>
                    <h3>智能体制作</h3>
                    <p>像搭积木一样创建专属AI智能体，支持部署分享</p>
                </div>
                <div class="feature-card" onclick="location.href='<?php echo isset($_SESSION['user']) && $_SESSION['user']['logged_in'] ? '?route=chat' : '?route=login'; ?>'">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3>智能对话</h3>
                    <p>与AI助手进行自然语言对话，获取信息和帮助</p>
                </div>
                <div class="feature-card" onclick="location.href='<?php echo isset($_SESSION['user']) && $_SESSION['user']['logged_in'] ? '?route=chat' : '?route=login'; ?>'">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <i class="fas fa-code"></i>
                    </div>
                    <h3>代码辅助</h3>
                    <p>代码生成、调试和优化，提高开发效率</p>
                </div>
                <div class="feature-card" onclick="location.href='<?php echo isset($_SESSION['user']) && $_SESSION['user']['logged_in'] ? '?route=workflows_comfyui' : '?route=login'; ?>'">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #38a169 0%, #2f855a 100%);">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <h3>工作流管理</h3>
                    <p>创建和管理AI工作流，自动化处理任务</p>
                </div>
            </div>

            <?php if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']): ?>
            <div class="login-prompt" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border: 2px dashed #cbd5e1;">
                <div style="font-size: 48px; margin-bottom: 16px;">🔐</div>
                <h3>登录体验完整功能</h3>
                <p>登录后可以开始与AI助手对话，选择不同模型，保存对话历史等</p>
                <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                    <a href="?route=login" class="btn btn-primary" style="padding: 14px 32px; font-size: 15px;">
                        <i class="fas fa-sign-in-alt"></i> 立即登录
                    </a>
                    <a href="?route=register" class="btn btn-secondary" style="padding: 14px 32px; font-size: 15px; margin-left: 0;">
                        <i class="fas fa-user-plus"></i> 注册账号
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- 底部版权信息 -->
        <div class="footer" style="text-align: center; padding: 40px 20px; margin-top: 40px; background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%); border-radius: 20px 20px 0 0;">
            <div style="max-width: 1200px; margin: 0 auto;">
                <div style="display: flex; justify-content: center; gap: 30px; margin-bottom: 20px; flex-wrap: wrap;">
                    <a href="?route=about" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; transition: color 0.2s;">关于我们</a>
                    <a href="?route=privacy" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; transition: color 0.2s;">隐私政策</a>
                    <a href="?route=terms" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; transition: color 0.2s;">用户协议</a>
                    <a href="mailto:1293724438@qq.com" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; transition: color 0.2s;">联系我们</a>
                </div>
                <p style="color: rgba(255,255,255,0.5); font-size: 13px; margin-bottom: 8px;">巨神兵API辅助平台出品 © 2026</p>
                <p style="color: rgba(255,255,255,0.4); font-size: 12px;">红尾鵟版 SaaS 1.0.0</p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // 后台静默检测Ollama状态（不在页面显示）
        function checkOllamaStatus() {
            $.ajax({
                url: 'api/api_handler.php',
                method: 'GET',
                data: { request: 'models' },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        console.log(`✅ Ollama已连接，检测到 ${response.total_models} 个模型`);
                        if (response.model_details) {
                            console.log('📦 模型列表:', response.model_details);
                        }
                    } else if (response.status === 'warning') {
                        console.warn('⚠️ Ollama已连接但未安装模型:', response.message);
                    } else {
                        console.error('❌ Ollama连接失败:', response.message);
                    }
                },
            });
        }

        // 页面加载时后台静默检测
        $(document).ready(function() {
            checkOllamaStatus();
        });
    </script>
</body>
</html>
