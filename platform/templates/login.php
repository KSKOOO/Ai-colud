<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="巨神兵AIAPI辅助平台 - 用户登录">
    <title>巨神兵AIAPI辅助平台 - 登录</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="icon" href="assets/images/logo.png" type="image/png">
    <style>
        /* 登录页面优化样式 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 50%, #2563eb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* 动态背景装饰 - 简化版 */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, rgba(255,255,255,0.05) 0%, transparent 80%);
            z-index: 0;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .login-container {
            max-width: 440px;
            margin: 0 auto;
            padding: 0;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.2),
                0 8px 20px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.6);
            padding: 48px 42px;
            position: relative;
            overflow: hidden;
            animation: cardSlideIn 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        @keyframes cardSlideIn {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* 卡片顶部渐变边框 */
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            border-radius: 24px 24px 0 0;
        }

        /* 装饰性光晕效果 */
        .login-card::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
            pointer-events: none;
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }

        .login-header .logo {
            margin-bottom: 24px;
            animation: logoFloat 3s ease-in-out infinite;
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .login-header .logo img {
            height: 56px;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.15));
        }

        .login-header h2 {
            color: #1a1a2e;
            margin-bottom: 12px;
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }

        .login-header p {
            color: #64748b;
            font-size: 15px;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 28px;
            position: relative;
            z-index: 1;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #475569;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .form-group input {
            width: 100%;
            padding: 16px 52px 16px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            font-size: 15px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #f8fafc;
            color: #1e293b;
            font-weight: 500;
        }

        .form-group input::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        .form-group input:focus {
            border-color: #667eea;
            background: white;
            box-shadow: 
                0 0 0 4px rgba(102, 126, 234, 0.1),
                0 4px 12px rgba(102, 126, 234, 0.15);
            outline: none;
            transform: translateY(-2px);
        }

        .form-group .icon {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 18px;
            transition: all 0.3s;
            pointer-events: none;
        }

        .form-group input:focus + .icon {
            color: #667eea;
            transform: translateY(-50%) scale(1.1);
        }

        /* 记住密码选项 */
        .remember-me {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 28px;
            position: relative;
            z-index: 1;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #667eea;
            cursor: pointer;
        }

        .remember-me label {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            margin-bottom: 0;
            text-transform: none;
            letter-spacing: normal;
        }

        .login-actions {
            display: flex;
            gap: 16px;
            margin-top: 32px;
            position: relative;
            z-index: 1;
        }

        .btn {
            flex: 1;
            padding: 16px;
            border: none;
            border-radius: 14px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            letter-spacing: 0.5px;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn:active::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            color: white;
            box-shadow: 
                0 6px 20px rgba(59, 130, 246, 0.3),
                0 2px 6px rgba(59, 130, 246, 0.2);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-3px);
            box-shadow: 
                0 12px 30px rgba(59, 130, 246, 0.4),
                0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 2px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            border-color: #cbd5e1;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .login-footer {
            text-align: center;
            margin-top: 32px;
            color: #64748b;
            font-size: 14px;
            position: relative;
            z-index: 1;
        }

        .login-footer a {
            color: #3b82f6;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .login-footer a:hover {
            color: #2563eb;
            transform: translateY(-2px);
            display: inline-block;
        }

        .login-footer a i {
            transition: transform 0.3s;
        }

        .login-footer a:hover i {
            transform: translateX(3px);
        }

        /* 错误消息优化 */
        .error-message {
            color: #991b1b;
            margin-bottom: 30px;
            text-align: center;
            padding: 18px 20px;
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 2px solid #fecaca;
            border-radius: 16px;
            font-size: 14px;
            font-weight: 600;
            animation: errorShake 0.6s cubic-bezier(0.36, 0.07, 0.19, 0.97);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15);
            position: relative;
            z-index: 1;
        }

        .error-message::before {
            content: '⚠️';
            display: block;
            font-size: 20px;
            margin-bottom: 8px;
        }

        @keyframes errorShake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); }
            20%, 40%, 60%, 80% { transform: translateX(4px); }
        }

        /* 响应式设计优化 */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }

            .login-card {
                padding: 36px 28px;
                border-radius: 20px;
            }

            .login-header h2 {
                font-size: 26px;
            }

            .user-info {
                top: 16px;
                right: 16px;
                padding: 12px 16px;
            }

            .user-info .user-avatar {
                width: 36px;
                height: 36px;
                font-size: 16px;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 0;
            }

            .login-card {
                padding: 32px 24px;
                border-radius: 18px;
            }

            .login-header h2 {
                font-size: 24px;
            }

            .login-header .logo img {
                height: 48px;
            }

            .login-actions {
                flex-direction: column;
            }


            .btn {
                width: 100%;
            }
        }

        /* 深色模式支持 */
        @media (prefers-color-scheme: dark) {
            .login-card {
                background: rgba(30, 41, 59, 0.95);
                border-color: rgba(255, 255, 255, 0.1);
            }

            .login-header h2 {
                color: #f8fafc;
            }

            .login-header p {
                color: #94a3b8;
            }

            .form-group label {
                color: #cbd5e1;
            }

            .form-group input {
                background: rgba(30, 41, 59, 0.8);
                border-color: #475569;
                color: #f1f5f9;
            }

            .form-group input::placeholder {
                color: #64748b;
            }

            .btn-secondary {
                background: #374151;
                color: #d1d5db;
                border-color: #4b5563;
            }

            .login-footer {
                color: #94a3b8;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <div class="logo">
                        <img src="../assets/images/logo.png" alt="巨神兵AIAPI辅助平台">
                    </div>
                    <h2>欢迎回来</h2>
                    <p>登录您的账户继续使用AI服务</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="?route=login" id="loginForm" autocomplete="off">
                    <div class="form-group">
                        <label for="username">用户名</label>
                        <div class="input-wrapper">
                            <input type="text" id="username" name="username" required 
                                   placeholder="请输入您的用户名" 
                                   autocomplete="username"
                                   aria-label="用户名">
                            <i class="fas fa-user icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">密码</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" required 
                                   placeholder="请输入您的密码" 
                                   autocomplete="current-password"
                                   aria-label="密码">
                            <i class="fas fa-lock icon"></i>
                        </div>
                    </div>

                    <div class="login-actions" style="flex-direction: row-reverse; justify-content: space-between; margin-bottom: 24px;">
                        <a href="?route=register" class="btn btn-secondary">
                            <i class="fas fa-user-plus"></i> 注册账户
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-sign-in-alt"></i> 立即登录
                        </button>
                    </div>

                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">记住我的登录状态</label>
                    </div>
                </form>

                <div class="login-footer">
                    <a href="#">
                        <i class="fas fa-key"></i>
                        忘记密码？
                    </a>
                </div>
            </div>
        </div>

        <footer class="footer">
            <p>&copy; <?php echo date('Y'); ?> 巨神兵AIAPI辅助平台. All rights reserved.</p>
        </footer>
    </div>

    <script>
        // 表单提交处理
        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');

        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // 验证输入
                if (!validateForm()) {
                    return;
                }

                // 显示加载状态
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 登录中...';
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.7';

                // 模拟API延迟（实际使用时可以移除）
                setTimeout(() => {
                    loginForm.submit();
                }, 800);
            });
        }

        // 表单验证
        function validateForm() {
            let isValid = true;
            
            // 验证用户名
            if (usernameInput.value.trim().length < 3) {
                showInputError(usernameInput, '用户名至少需要3个字符');
                isValid = false;
            } else {
                clearInputError(usernameInput);
            }

            // 验证密码
            if (passwordInput.value.length < 6) {
                showInputError(passwordInput, '密码至少需要6个字符');
                isValid = false;
            } else {
                clearInputError(passwordInput);
            }

            return isValid;
        }

        // 显示输入框错误
        function showInputError(input, message) {
            const wrapper = input.parentElement;
            let errorDiv = wrapper.querySelector('.input-error');
            
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'input-error';
                errorDiv.style.cssText = `
                    color: #ef4444;
                    font-size: 12px;
                    margin-top: 6px;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 4px;
                    animation: fadeInDown 0.3s ease;
                `;
                wrapper.appendChild(errorDiv);
            }
            
            errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
            input.style.borderColor = '#ef4444';
            input.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
        }

        // 清除输入框错误
        function clearInputError(input) {
            const wrapper = input.parentElement;
            const errorDiv = wrapper.querySelector('.input-error');
            
            if (errorDiv) {
                errorDiv.remove();
            }
            
            input.style.borderColor = '';
            input.style.boxShadow = '';
        }


        // 增强输入框交互效果
        document.querySelectorAll('.input-wrapper').forEach(wrapper => {
            const input = wrapper.querySelector('input');
            const icon = wrapper.querySelector('.icon');
            
            input.addEventListener('focus', function() {
                wrapper.style.transform = 'scale(1.02)';
                wrapper.style.transition = 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            });
            
            input.addEventListener('blur', function() {
                wrapper.style.transform = 'scale(1)';
            });
        });

        // 添加密码显示切换功能
        const passwordWrapper = passwordInput?.parentElement;
        if (passwordWrapper) {
            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.className = 'password-toggle';
            toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
            toggleBtn.style.cssText = `
                position: absolute;
                right: 52px;
                top: 50%;
                transform: translateY(-50%);
                background: none;
                border: none;
                color: #94a3b8;
                cursor: pointer;
                font-size: 16px;
                padding: 0;
                transition: all 0.3s;
                z-index: 2;
            `;
            
            passwordWrapper.appendChild(toggleBtn);
            
            toggleBtn.addEventListener('click', function() {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                toggleBtn.innerHTML = type === 'password' 
                    ? '<i class="fas fa-eye"></i>' 
                    : '<i class="fas fa-eye-slash"></i>';
            });
            
            toggleBtn.addEventListener('mouseenter', function() {
                this.style.color = '#667eea';
            });
            
            toggleBtn.addEventListener('mouseleave', function() {
                this.style.color = '#94a3b8';
            });
        }

        // 实时输入验证
        if (usernameInput) {
            usernameInput.addEventListener('input', function() {
                if (this.value.trim().length >= 3) {
                    clearInputError(this);
                }
            });
        }

        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                if (this.value.length >= 6) {
                    clearInputError(this);
                }
            });
        }

        // 页面加载优化动画
        document.addEventListener('DOMContentLoaded', function() {
            const loginCard = document.querySelector('.login-card');
            
            // 延迟显示登录卡片
            setTimeout(() => {
                if (loginCard) {
                    loginCard.style.opacity = '1';
                }
            }, 200);

            // 为输入框添加动画延迟
            const inputs = document.querySelectorAll('.form-group');
            inputs.forEach((input, index) => {
                input.style.opacity = '0';
                input.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    input.style.opacity = '1';
                    input.style.transform = 'translateY(0)';
                }, 300 + (index * 100));
            });
        });

        // 添加键盘快捷键
        document.addEventListener('keydown', function(e) {
            // ESC键重置表单
            if (e.key === 'Escape' && loginForm) {
                loginForm.reset();
                document.querySelectorAll('.input-error').forEach(error => error.remove());
                document.querySelectorAll('input').forEach(input => {
                    input.style.borderColor = '';
                    input.style.boxShadow = '';
                });
            }
        });

        // 添加淡入动画样式
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeInDown {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .form-group {
                opacity: 0;
                transform: translateY(20px);
                transition: opacity 0.5s ease, transform 0.5s ease;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>