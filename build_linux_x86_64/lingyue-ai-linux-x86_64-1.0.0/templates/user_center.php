<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']) {
    header('Location: ?route=login');
    exit;
}

$config = require __DIR__ . '/../config/config.php';
$userId = $_SESSION['user']['id'] ?? 0;
$userName = $_SESSION['user']['username'] ?? '';
$userRole = $_SESSION['user']['role'] ?? 'user';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户中心 - <?php echo $config['app']['name'] ?? '巨神兵API辅助平台API辅助平台'; ?></title>
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

        .header {
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

        .nav {
            display: flex;
            gap: 8px;
        }

        .nav-item {
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

        .nav-item:hover {
            background: #f3f4f6;
            color: #4c51bf;
        }

        .admin-container {
            display: flex;
            min-height: calc(100vh - 73px);
        }

        .sidebar {
            width: 260px;
            background: white;
            border-right: 1px solid #e2e8f0;
            padding: 24px 0;
        }

        .sidebar-logo {
            padding: 0 24px 24px;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 16px;
        }

        .sidebar-logo img {
            width: 64px;
            height: 64px;
            object-fit: contain;
            border-radius: 12px;
            margin-bottom: 12px;
            display: block;
        }

        .sidebar-logo .logo-icon {
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            color: white;
            font-size: 32px;
        }

        .sidebar-logo .platform-name {
            font-size: 16px;
            font-weight: 600;
            color: #1a202c;
        }

        .sidebar-logo .platform-version {
            font-size: 12px;
            color: #64748b;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-item {
            padding: 12px 24px;
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s;
            border-left: 3px solid transparent;
            text-decoration: none;
        }

        .sidebar-item:hover {
            background: #f8fafc;
            color: #4c51bf;
        }

        .sidebar-item.active {
            background: #ede9fe;
            color: #4c51bf;
            border-left-color: #4c51bf;
        }

        .sidebar-item i {
            width: 20px;
            text-align: center;
        }

        .main-content {
            flex: 1;
            padding: 32px;
            overflow-y: auto;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 8px;
        }

        .page-desc {
            color: #64748b;
            margin-bottom: 32px;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: #4c51bf;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
        }

        .user-info h2 {
            font-size: 20px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 4px;
        }

        .user-info p {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .user-role {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            background: #ede9fe;
            color: #4c51bf;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #4c51bf;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 13px;
            color: #64748b;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: #4c51bf;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #4c51bf;
            color: white;
        }

        .btn-primary:hover {
            background: #434190;
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .api-key-list {
            margin-top: 16px;
        }

        .api-key-item {
            background: #f8fafc;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .api-key-info {
            flex: 1;
        }

        .api-key-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .api-key-value {
            font-family: monospace;
            font-size: 13px;
            color: #6b7280;
            background: #e5e7eb;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .api-key-meta {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 4px;
        }

        .api-key-actions {
            display: flex;
            gap: 8px;
        }

        .recharge-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .recharge-option {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .recharge-option:hover,
        .recharge-option.selected {
            border-color: #4c51bf;
            background: #ede9fe;
        }

        .recharge-option .amount {
            font-size: 24px;
            font-weight: 700;
            color: #4c51bf;
        }

        .recharge-option .points {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
        }

        .balance-display {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            margin-bottom: 24px;
        }

        .balance-display .amount {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .balance-display .label {
            font-size: 14px;
            opacity: 0.9;
        }

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 16px 24px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            display: none;
            align-items: center;
            gap: 12px;
            z-index: 9999;
        }

        .toast.show {
            display: flex;
        }

        .toast.success {
            border-left: 4px solid #22c55e;
        }

        .toast.error {
            border-left: 4px solid #ef4444;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 24px;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .recharge-options {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- 顶部导航 -->
    <header class="header">
        <a href="?route=home" class="logo" style="text-decoration: none; display: flex; align-items: center; gap: 10px;">
            <img src="assets/images/logo.png" alt="Logo" style="height: 32px; width: auto; object-fit: contain;"
                 onerror="this.style.display='none'; document.getElementById('header-logo-fallback').style.display='flex';">
            <span id="header-logo-fallback" style="display: none; align-items: center; gap: 8px; color: #4c51bf;">
                <i class="fas fa-robot" style="font-size: 28px;"></i>
            </span>
            <span style="font-size: 20px; font-weight: 700; color: #4c51bf;">巨神兵API辅助平台API辅助平台</span>
        </a>
        <nav class="nav">
            <a href="?route=home" class="nav-item">
                <i class="fas fa-home"></i> 返回前台
            </a>
            <a href="?route=logout" class="nav-item">
                <i class="fas fa-sign-out-alt"></i> 退出
            </a>
        </nav>
    </header>

    <div class="admin-container">
        <!-- 侧边栏 -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="assets/images/logo.png" alt="Logo" 
                     style="width: 64px; height: 64px; object-fit: contain; border-radius: 12px; margin-bottom: 12px;"
                     onerror="this.style.display='none'; document.getElementById('sidebar-logo-fallback').style.display='flex';">
                <div id="sidebar-logo-fallback" class="logo-icon" style="display: none;">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="platform-name">用户中心</div>
                <div class="platform-version">v<?php echo $config['app']['version'] ?? '1.0.0'; ?></div>
            </div>
            <ul class="sidebar-menu">
                <li class="sidebar-item active" onclick="showPage('overview')">
                    <i class="fas fa-user-circle"></i> 账户概览
                </li>
                <li class="sidebar-item" onclick="showPage('password')">
                    <i class="fas fa-lock"></i> 修改密码
                </li>
                <li class="sidebar-item" onclick="showPage('apikeys')">
                    <i class="fas fa-key"></i> API密钥
                </li>
                <li class="sidebar-item" onclick="showPage('recharge')">
                    <i class="fas fa-wallet"></i> 账户充值
                </li>
            </ul>
        </aside>

        <!-- 主内容区 -->
        <main class="main-content">
            <!-- 账户概览页面 -->
            <div id="page-overview" class="page-content">
                <h1 class="page-title">账户概览</h1>
                <p class="page-desc">查看您的账户信息和使用统计</p>

                <!-- 用户信息卡片 -->
                <div class="card">
                    <div class="user-profile">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-info">
                            <h2><?php echo htmlspecialchars($userName); ?></h2>
                            <p>用户ID: <?php echo $userId; ?></p>
                            <span class="user-role">
                                <i class="fas fa-shield-alt"></i> 
                                <?php echo $userRole === 'admin' ? '管理员' : '普通用户'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 用量统计 -->
                <div class="card">
                    <div class="card-title"><i class="fas fa-chart-pie"></i> 用量统计</div>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value" id="totalCalls">0</div>
                            <div class="stat-label">总调用次数</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="totalTokens">0</div>
                            <div class="stat-label">总Token数</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="todayCalls">0</div>
                            <div class="stat-label">今日调用</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="monthCalls">0</div>
                            <div class="stat-label">本月调用</div>
                        </div>
                    </div>
                    <button class="btn btn-secondary" onclick="loadUsageStats()">
                        <i class="fas fa-sync-alt"></i> 刷新数据
                    </button>
                </div>

                <!-- 账户余额 -->
                <div class="card">
                    <div class="card-title"><i class="fas fa-wallet"></i> 账户余额</div>
                    <div class="balance-display">
                        <div class="amount" id="currentBalance">¥0.00</div>
                        <div class="label">当前可用余额</div>
                    </div>
                    <button class="btn btn-primary" onclick="showPage('recharge')">
                        <i class="fas fa-plus"></i> 立即充值
                    </button>
                </div>
            </div>

            <!-- 修改密码页面 -->
            <div id="page-password" class="page-content" style="display: none;">
                <h1 class="page-title">修改密码</h1>
                <p class="page-desc">更新您的账户登录密码</p>

                <div class="card" style="max-width: 500px;">
                    <form id="passwordForm">
                        <div class="form-group">
                            <label class="form-label">当前密码</label>
                            <input type="password" class="form-input" id="currentPassword" placeholder="请输入当前密码" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">新密码</label>
                            <input type="password" class="form-input" id="newPassword" placeholder="请输入新密码（至少6位）" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label class="form-label">确认新密码</label>
                            <input type="password" class="form-input" id="confirmPassword" placeholder="请再次输入新密码" required minlength="6">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 修改密码
                        </button>
                    </form>
                </div>
            </div>

            <!-- API密钥页面 -->
            <div id="page-apikeys" class="page-content" style="display: none;">
                <h1 class="page-title">API密钥管理</h1>
                <p class="page-desc">管理您的API访问密钥</p>

                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <div class="card-title" style="margin: 0;"><i class="fas fa-key"></i> 我的API密钥</div>
                        <button class="btn btn-primary btn-sm" onclick="createApiKey()">
                            <i class="fas fa-plus"></i> 创建新密钥
                        </button>
                    </div>
                    <div class="api-key-list" id="apiKeyList">
                        <p style="color: #6b7280; text-align: center; padding: 40px;">
                            <i class="fas fa-spinner fa-spin"></i> 加载中...
                        </p>
                    </div>
                </div>
            </div>

            <!-- 充值页面 -->
            <div id="page-recharge" class="page-content" style="display: none;">
                <h1 class="page-title">账户充值</h1>
                <p class="page-desc">充值积分以使用更多服务</p>

                <div class="card" style="max-width: 600px;">
                    <div class="card-title"><i class="fas fa-coins"></i> 选择充值金额</div>
                    <div class="recharge-options">
                        <div class="recharge-option" data-amount="50" onclick="selectRecharge(this)">
                            <div class="amount">¥50</div>
                            <div class="points">500积分</div>
                        </div>
                        <div class="recharge-option selected" data-amount="100" onclick="selectRecharge(this)">
                            <div class="amount">¥100</div>
                            <div class="points">1100积分</div>
                        </div>
                        <div class="recharge-option" data-amount="200" onclick="selectRecharge(this)">
                            <div class="amount">¥200</div>
                            <div class="points">2400积分</div>
                        </div>
                        <div class="recharge-option" data-amount="500" onclick="selectRecharge(this)">
                            <div class="amount">¥500</div>
                            <div class="points">6500积分</div>
                        </div>
                        <div class="recharge-option" data-amount="1000" onclick="selectRecharge(this)">
                            <div class="amount">¥1000</div>
                            <div class="points">14000积分</div>
                        </div>
                        <div class="recharge-option" data-amount="2000" onclick="selectRecharge(this)">
                            <div class="amount">¥2000</div>
                            <div class="points">30000积分</div>
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="recharge()" style="width: 100%;">
                        <i class="fas fa-credit-card"></i> 立即充值
                    </button>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Toast提示 -->
    <div class="toast" id="toast">
        <i class="fas fa-check-circle"></i>
        <span id="toastMessage"></span>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let selectedRechargeAmount = 100;
        
        $(document).ready(function() {
            loadUsageStats();
            loadApiKeys();
            loadBalance();
        });
        
        function showPage(page) {
            $('.sidebar-item').removeClass('active');
            $(`.sidebar-item:contains('${getPageName(page)}')`).addClass('active');
            $('.page-content').hide();
            $(`#page-${page}`).show();
        }
        
        function getPageName(page) {
            const names = {
                'overview': '账户概览',
                'password': '修改密码',
                'apikeys': 'API密钥',
                'recharge': '账户充值'
            };
            return names[page] || '';
        }
        
        function loadUsageStats() {
            $.ajax({
                url: 'api/user_center_api.php?action=get_usage_stats',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#totalCalls').text(response.data.total_calls || 0);
                        $('#totalTokens').text(formatNumber(response.data.total_tokens || 0));
                        $('#todayCalls').text(response.data.today_calls || 0);
                        $('#monthCalls').text(response.data.month_calls || 0);
                    }
                },
                error: function() {
                    showToast('加载用量统计失败', 'error');
                }
            });
        }
        
        function loadApiKeys() {
            $.ajax({
                url: 'api/user_center_api.php?action=get_api_keys',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        renderApiKeys(response.data || []);
                    }
                },
                error: function() {
                    $('#apiKeyList').html('<p style="color: #ef4444; text-align: center;">加载失败</p>');
                }
            });
        }
        
        function renderApiKeys(keys) {
            if (keys.length === 0) {
                $('#apiKeyList').html('<p style="color: #6b7280; text-align: center; padding: 40px;"><i class="fas fa-key" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>暂无API密钥</p>');
                return;
            }
            
            let html = '';
            keys.forEach(function(key) {
                const maskedKey = key.api_key.substring(0, 8) + '...' + key.api_key.substring(key.api_key.length - 4);
                html += `
                    <div class="api-key-item">
                        <div class="api-key-info">
                            <div class="api-key-name">${key.name || 'API密钥'}</div>
                            <div class="api-key-value">${maskedKey}</div>
                            <div class="api-key-meta">
                                创建于: ${key.created_at} | 调用: ${key.usage_count || 0}次
                            </div>
                        </div>
                        <div class="api-key-actions">
                            <button class="btn btn-secondary btn-sm" onclick="copyApiKey('${key.api_key}')">
                                <i class="fas fa-copy"></i> 复制
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteApiKey(${key.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            $('#apiKeyList').html(html);
        }
        
        function createApiKey() {
            const name = prompt('请输入API密钥名称（如：测试环境、生产环境）：');
            if (!name) return;
            
            $.ajax({
                url: 'api/user_center_api.php?action=create_api_key',
                type: 'POST',
                data: { name: name },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast('API密钥创建成功！请复制保存', 'success');
                        loadApiKeys();
                    } else {
                        showToast(response.error || '创建失败', 'error');
                    }
                },
                error: function() {
                    showToast('创建失败，请重试', 'error');
                }
            });
        }
        
        function copyApiKey(key) {
            navigator.clipboard.writeText(key).then(function() {
                showToast('API密钥已复制到剪贴板', 'success');
            });
        }
        
        function deleteApiKey(keyId) {
            if (!confirm('确定要删除这个API密钥吗？删除后将无法恢复。')) return;
            
            $.ajax({
                url: 'api/user_center_api.php?action=delete_api_key',
                type: 'POST',
                data: { key_id: keyId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast('API密钥已删除', 'success');
                        loadApiKeys();
                    } else {
                        showToast(response.error || '删除失败', 'error');
                    }
                }
            });
        }
        
        function loadBalance() {
            $.ajax({
                url: 'api/user_center_api.php?action=get_balance',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#currentBalance').text('¥' + (response.balance || 0).toFixed(2));
                    }
                }
            });
        }
        
        function selectRecharge(element) {
            document.querySelectorAll('.recharge-option').forEach(function(opt) {
                opt.classList.remove('selected');
            });
            element.classList.add('selected');
            selectedRechargeAmount = element.dataset.amount;
        }
        
        function recharge() {
            $.ajax({
                url: 'api/user_center_api.php?action=recharge',
                type: 'POST',
                data: { amount: selectedRechargeAmount },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast('充值成功！', 'success');
                        loadBalance();
                    } else {
                        showToast(response.error || '充值失败', 'error');
                    }
                },
                error: function() {
                    showToast('充值失败，请重试', 'error');
                }
            });
        }
        
        $('#passwordForm').on('submit', function(e) {
            e.preventDefault();
            
            const currentPassword = $('#currentPassword').val();
            const newPassword = $('#newPassword').val();
            const confirmPassword = $('#confirmPassword').val();
            
            if (newPassword !== confirmPassword) {
                showToast('两次输入的新密码不一致', 'error');
                return;
            }
            
            if (newPassword.length < 6) {
                showToast('新密码至少需要6位', 'error');
                return;
            }
            
            $.ajax({
                url: 'api/user_center_api.php?action=change_password',
                type: 'POST',
                data: {
                    current_password: currentPassword,
                    new_password: newPassword
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast('密码修改成功！', 'success');
                        $('#passwordForm')[0].reset();
                    } else {
                        showToast(response.error || '密码修改失败', 'error');
                    }
                },
                error: function() {
                    showToast('请求失败，请重试', 'error');
                }
            });
        });
        
        function showToast(message, type) {
            const toast = $('#toast');
            toast.removeClass('success error').addClass(type);
            toast.find('#toastMessage').text(message);
            toast.addClass('show');
            
            setTimeout(function() {
                toast.removeClass('show');
            }, 3000);
        }
        
        function formatNumber(num) {
            if (num >= 10000) {
                return (num / 10000).toFixed(1) + '万';
            }
            return num.toString();
        }
    </script>
</body>
</html>
