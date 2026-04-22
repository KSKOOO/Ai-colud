<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="巨神兵AIAPI辅助平台 - 用户注册">
    <title>巨神兵AIAPI辅助平台 - 注册</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="icon" href="assets/images/logo.png" type="image/png">
    <style>
        /* 注册页面特定样式 */
        .register-container {
            max-width: 480px;
            margin: 80px auto;
            padding: 0;
        }

        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            position: relative;
            overflow: hidden;
            animation: slideIn 0.6s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .register-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }

        .register-header {
            text-align: center;
            margin-bottom: 35px;
            position: relative;
            z-index: 1;
        }

        .register-header h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 28px;
            font-weight: 700;
        }

        .register-header p {
            color: #666;
            font-size: 16px;
            margin-top: 10px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
            z-index: 1;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #666;
            font-weight: 600;
            font-size: 15px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 50px 14px 18px;
            border: 2px solid #eee;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s;
            background: #f8f9fa;
        }

        .form-group input:focus {
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
            outline: none;
        }

        .form-group .icon {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 18px;
            transition: color 0.3s;
        }

        .form-group input:focus + .icon {
            color: #667eea;
        }

        .register-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b42a0 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #666;
            border: 2px solid #eee;
        }

        .btn-secondary:hover {
            background: #e8e8e8;
            border-color: #ddd;
            transform: translateY(-3px);
        }

        .register-footer {
            text-align: center;
            margin-top: 25px;
            color: #666;
            font-size: 14px;
        }

        .register-footer a {
            color: #667eea;
            text-decoration: none;
            transition: color 0.3s;
        }

        .register-footer a:hover {
            color: #5a67d8;
            text-decoration: underline;
        }

        .error-message {
            color: #dc3545;
            margin-bottom: 25px;
            text-align: center;
            padding: 15px;
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border: 1px solid #f5c6cb;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 500;
            animation: shake 0.5s;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.1);
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* 响应式设计 */
        @media (max-width: 480px) {
            .register-container {
                margin: 40px auto;
                padding: 20px;
            }
            
            .register-card {
                padding: 30px 20px;
            }
            
            .register-header h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="register-card">
                <div class="register-header">
                    <div class="logo">
                        <img src="../assets/images/logo.png" alt="巨神兵AIAPI辅助平台" style="height: 50px;">
                    </div>
                    <h2>创建新账户</h2>
                    <p style="color: #666; font-size: 16px;">加入巨神兵AIAPI辅助平台大家庭</p>
                </div>

                <?php if (isset($error) && $error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if (isset($success) && $success): ?>
                    <div class="success-message" style="background: #d1fae5; color: #059669; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($success); ?>
                        <br><a href="?route=login" style="color: #059669; text-decoration: underline;">点击登录</a>
                    </div>
                <?php endif; ?>

                <form method="POST" action="?route=register" id="registerForm">
                    <div class="form-group">
                        <label for="username">用户名</label>
                        <div style="position: relative;">
                            <input type="text" id="username" name="username" required placeholder="请输入用户名" autocomplete="username">
                            <i class="fas fa-user icon"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password">密码</label>
                        <div style="position: relative;">
                            <input type="password" id="password" name="password" required placeholder="请输入密码" autocomplete="new-password">
                            <i class="fas fa-lock icon"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">邮箱</label>
                        <div style="position: relative;">
                            <input type="email" id="email" name="email" required placeholder="请输入邮箱" autocomplete="email">
                            <i class="fas fa-envelope icon"></i>
                        </div>
                    </div>
                    <div class="register-actions">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-user-plus"></i> 注册
                        </button>
                        <a href="?route=login" class="btn btn-secondary">
                            <i class="fas fa-sign-in-alt"></i> 登录
                        </a>
                    </div>
                </form>

                <div class="register-footer">
                    <p style="font-size: 14px; color: #666;">已有账号？<a href="?route=login">立即登录</a></p>
                </div>
            </div>
        </div>

        <footer class="footer">
            <p>&copy; <?php echo date('Y'); ?> 巨神兵AIAPI辅助平台. All rights reserved.</p>
        </footer>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 注册中...';
            submitBtn.disabled = true;
        });

        document.querySelectorAll('.form-group input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                document.querySelectorAll('.register-card').forEach(card => {
                    card.style.opacity = '1';
                });
            }, 100);
        });
    </script>
</body>
</html>