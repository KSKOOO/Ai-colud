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
    <title>用户协议 - 巨神兵API辅助平台API辅助平台</title>
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
            line-height: 1.8;
            color: #4a5568;
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

        /* 主内容区 */
        .main-content {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            border-radius: 20px;
            padding: 48px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 8px;
            text-align: center;
        }

        .update-time {
            text-align: center;
            color: #718096;
            font-size: 14px;
            margin-bottom: 40px;
        }

        .section {
            margin-bottom: 32px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
        }

        .section-content {
            color: #4a5568;
            line-height: 1.8;
        }

        .section-content ul {
            margin-left: 24px;
            margin-top: 12px;
        }

        .section-content li {
            margin-bottom: 8px;
        }

        .contact-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 16px;
            padding: 24px;
            margin-top: 40px;
        }

        .contact-section h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .contact-section a {
            color: white;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 头部导航 -->
        <header class="header">
            <a href="?route=home" class="logo" style="text-decoration: none; display: flex; align-items: center; gap: 10px;">
                <img src="assets/images/logo.png" alt="巨神兵API辅助平台AI" style="height: 32px;" onerror="this.style.display='none';">
                <span style="color: #4c51bf;">巨神兵API辅助平台API辅助平台</span>
            </a>
            <nav class="nav">
                <a href="?route=home" class="nav-item">
                    <i class="fas fa-home"></i> <span>首页</span>
                </a>
                <a href="?route=about" class="nav-item">
                    <i class="fas fa-info-circle"></i> <span>关于</span>
                </a>
            </nav>
        </header>

        <!-- 主内容 -->
        <div class="main-content">
            <h1 class="page-title">用户协议</h1>
            <p class="update-time">最后更新日期：2026年3月</p>

            <div class="section">
                <h2 class="section-title">1. 协议接受</h2>
                <div class="section-content">
                    <p>欢迎使用巨神兵API辅助平台API辅助平台！请您在使用本平台前仔细阅读本用户协议（以下简称"本协议"）。通过下载、安装、注册或使用本平台，即表示您已阅读、理解并同意接受本协议的所有条款。如果您不同意本协议的任何内容，请立即停止使用本平台。</p>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">2. 服务说明</h2>
                <div class="section-content">
                    <p>巨神兵API辅助平台API辅助平台是一款基于人工智能技术的智能服务平台，提供以下服务：</p>
                    <ul>
                        <li>AI智能对话和问答</li>
                        <li>文本生成和内容创作</li>
                        <li>代码辅助和编程支持</li>
                        <li>智能体创建和管理</li>
                        <li>多模型切换和对比</li>
                        <li>可视化工作流编辑</li>
                    </ul>
                    <p>本平台支持本地部署和在线API两种模式，用户可根据需求选择合适的使用方式。</p>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">3. 账户注册与安全</h2>
                <div class="section-content">
                    <p>3.1 您需要注册账户才能使用本平台的全部功能。注册时您需要提供真实、准确、完整的个人信息，并及时更新以保持信息的有效性。</p>
                    <p>3.2 您有责任保护账户安全，妥善保管登录密码。如发现账户异常，请立即通知我们。</p>
                    <p>3.3 禁止转让、借用或共享账户。每个用户只能拥有一个主账户。</p>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">4. 使用规范</h2>
                <div class="section-content">
                    <p>您同意在使用本平台时遵守以下规范：</p>
                    <ul>
                        <li>遵守所有适用的法律法规</li>
                        <li>尊重他人的知识产权和隐私权</li>
                        <li>不生成、传播违法、有害、侵权内容</li>
                        <li>不进行任何形式的网络攻击或滥用</li>
                        <li>不干扰或破坏本平台的正常运行</li>
                        <li>不使用自动化工具批量访问API</li>
                    </ul>
                    <p>违反上述规范可能导致账户暂停或终止。</p>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">5. 知识产权</h2>
                <div class="section-content">
                    <p>5.1 本平台的界面设计、代码、文档等知识产权归巨神兵API辅助平台有限公司所有。</p>
                    <p>5.2 您使用本平台生成的内容，其知识产权归您所有。但请注意：</p>
                    <ul>
                        <li>AI生成内容可能受第三方知识产权约束</li>
                        <li>请勿将生成内容用于非法或侵权用途</li>
                        <li>商业使用前建议进行知识产权审查</li>
                    </ul>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">6. 服务变更与中断</h2>
                <div class="section-content">
                    <p>6.1 我们保留随时修改、暂停或终止部分或全部服务的权利，会尽可能提前通知用户。</p>
                    <p>6.2 以下情况可能导致服务中断：</p>
                    <ul>
                        <li>系统维护或升级</li>
                        <li>不可抗力因素</li>
                        <li>违反本协议导致的账户封禁</li>
                    </ul>
                    <p>对于非我们原因导致的服务中断，我们不承担责任。</p>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">7. 免责声明</h2>
                <div class="section-content">
                    <p>7.1 AI生成内容的准确性和适用性无法完全保证，仅供参考，不构成专业建议。</p>
                    <p>7.2 本地部署模式下，数据安全由用户自行负责。请妥善保管服务器访问权限。</p>
                    <p>7.3 因用户自身原因（如密码泄露、设备丢失）造成的损失，我们不承担责任。</p>
                    <p>7.4 在法律允许的最大范围内，我们对间接、附带、特殊损失不承担责任。</p>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">8. 协议修改</h2>
                <div class="section-content">
                    <p>我们可能会不时修改本协议。修改后的协议将在本平台内公布，重大变更我们会通过适当方式通知您。继续使用本平台即表示您接受修改后的协议。如您不同意修改内容，请停止使用本平台。</p>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">9. 争议解决</h2>
                <div class="section-content">
                    <p>9.1 本协议的订立、执行和解释均适用中华人民共和国法律。</p>
                    <p>9.2 如发生争议，双方应友好协商解决；协商不成的，任何一方可向被告所在地人民法院提起诉讼。</p>
                </div>
            </div>

            <div class="contact-section">
                <h3>联系我们</h3>
                <p>如果您对本用户协议有任何疑问或建议，请通过以下方式联系我们：</p>
                <p style="margin-top: 12px;"><i class="fas fa-envelope" style="margin-right: 8px;"></i> 邮箱：<a href="mailto:1293724438@qq.com">1293724438@qq.com</a></p>
            </div>
        </div>
    </div>
</body>
</html>
