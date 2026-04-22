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
    <title>隐私政策 - 巨神兵API辅助平台API辅助平台</title>
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
            <h1 class="page-title">隐私政策</h1>
            <p class="update-time">最后更新日期：2026年3月</p>

            <div class="section">
                <h2 class="section-title">1. 引言</h2>
                <div class="section-content">
                    <p>巨神兵API辅助平台API辅助平台（以下简称"我们"或"本平台"）非常重视用户的隐私保护。本隐私政策说明了我们如何收集、使用、存储和保护您的个人信息。请您在使用本平台前仔细阅读本政策。</p>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">2. 信息收集</h2>
                <div class="section-content">
                    <p>我们可能收集以下信息：</p>
                    <ul>
                        <li>账户信息：用户名、邮箱地址等注册信息</li>
                        <li>使用数据：对话记录、功能使用频率、Token消耗量</li>
                        <li>设备信息：设备型号、操作系统版本、IP地址</li>
                        <li>日志信息：访问时间、使用时长、错误日志</li>
                    </ul>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">3. 信息使用</h2>
                <div class="section-content">
                    <p>我们使用收集的信息用于：</p>
                    <ul>
                        <li>提供、维护和改进本平台服务</li>
                        <li>处理您的请求和反馈</li>
                        <li>发送服务通知和更新信息</li>
                        <li>防止欺诈和滥用行为</li>
                        <li>进行数据分析和研究以改善用户体验</li>
                    </ul>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">4. 本地部署说明</h2>
                <div class="section-content">
                    <p>巨神兵API辅助平台API辅助平台采用本地部署架构：</p>
                    <ul>
                        <li>AI模型运行在您的本地服务器或设备上</li>
                        <li>对话数据主要存储在本地数据库</li>
                        <li>不会将您的私人数据上传到第三方云服务</li>
                        <li>您对自己的数据拥有完全控制权</li>
                    </ul>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">5. 数据安全</h2>
                <div class="section-content">
                    <p>我们采取以下措施保护您的数据：</p>
                    <ul>
                        <li>使用行业标准的加密技术保护数据传输</li>
                        <li>实施严格的访问控制和身份验证</li>
                        <li>定期进行安全审计和漏洞扫描</li>
                        <li>建立数据备份和恢复机制</li>
                    </ul>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">6. 用户权利</h2>
                <div class="section-content">
                    <p>您拥有以下权利：</p>
                    <ul>
                        <li>访问和查看您的个人信息</li>
                        <li>更正不准确的个人信息</li>
                        <li>删除您的账户和相关数据</li>
                        <li>导出您的数据副本</li>
                        <li>随时撤回同意（可能影响服务使用）</li>
                    </ul>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">7. 政策更新</h2>
                <div class="section-content">
                    <p>我们可能会不时更新本隐私政策。更新后的政策将在本平台内公布，重大变更我们会通过适当方式通知您。继续使用本平台即表示您同意更新后的政策。</p>
                </div>
            </div>

            <div class="contact-section">
                <h3>联系我们</h3>
                <p>如果您对本隐私政策有任何疑问或建议，请通过以下方式联系我们：</p>
                <p style="margin-top: 12px;"><i class="fas fa-envelope" style="margin-right: 8px;"></i> 邮箱：<a href="mailto:1293724438@qq.com">1293724438@qq.com</a></p>
            </div>
        </div>
    </div>
</body>
</html>
