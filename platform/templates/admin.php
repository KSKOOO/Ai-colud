<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 简单的管理员权限检查
if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']) {
    header('Location: ?route=login');
    exit;
}

// 加载当前配置
$config = require __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台管理 - 巨神兵AIAPI辅助平台</title>
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

        /* 头部导航 */
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
        }

        .logo img {
            width: 36px;
            height: 36px;
            object-fit: contain;
            border-radius: 8px;
        }

        .sidebar-logo {
            padding: 0 24px 24px;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 16px;
        }

        .sidebar-logo img {
            width: 48px;
            height: 48px;
            object-fit: contain;
            border-radius: 12px;
            margin-bottom: 8px;
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

        /* 主布局 */
        .admin-container {
            display: flex;
            min-height: calc(100vh - 73px);
        }

        /* 侧边栏 */
        .sidebar {
            width: 260px;
            background: white;
            border-right: 1px solid #e2e8f0;
            padding: 24px 0;
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

        /* 下拉菜单样式 */
        .sidebar-dropdown {
            position: relative;
        }

        .sidebar-dropdown .dropdown-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }

        .sidebar-dropdown .dropdown-toggle i:first-child {
            width: 20px;
            text-align: center;
        }

        .sidebar-dropdown .dropdown-toggle .fa-chevron-down {
            width: auto;
            font-size: 12px;
            transition: transform 0.3s ease;
        }

        .sidebar-dropdown.open .dropdown-toggle .fa-chevron-down {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            list-style: none;
            background: #f8fafc;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .sidebar-dropdown.open .dropdown-menu {
            max-height: 300px;
        }

        .dropdown-item {
            padding: 10px 24px 10px 56px;
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .dropdown-item:hover {
            background: #ede9fe;
            color: #4c51bf;
        }

        .dropdown-item.active {
            background: #ede9fe;
            color: #4c51bf;
            border-left-color: #4c51bf;
        }

        /* 主内容区 */
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

        /* 卡片 */
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

        /* 表单 */
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

        .form-select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            background: white;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-hint {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 4px;
        }

        /* 按钮 */
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

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        /* 模型列表 */
        .model-list {
            display: grid;
            gap: 16px;
        }

        .model-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            background: #f8fafc;
            border-radius: 12px;
            border: 2px solid transparent;
            transition: all 0.2s;
        }

        .model-item:hover {
            border-color: #4c51bf;
        }

        .model-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .model-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }

        .model-icon.local {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .model-icon.online {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        }

        .model-details h4 {
            font-size: 16px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 4px;
        }

        .model-details p {
            font-size: 13px;
            color: #64748b;
        }

        .model-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .model-badge.local {
            background: #d1fae5;
            color: #059669;
        }

        .model-badge.online {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .model-actions {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        /* 标签页 */
        .tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 24px;
            border-bottom: 2px solid #e2e8f0;
        }

        .tab {
            padding: 12px 20px;
            color: #64748b;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
        }

        .tab:hover {
            color: #4c51bf;
        }

        .tab.active {
            color: #4c51bf;
            border-bottom-color: #4c51bf;
            font-weight: 500;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* 开关 */
        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
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

        .slider:before {
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

        input:checked + .slider {
            background-color: #4c51bf;
        }

        input:checked + .slider:before {
            transform: translateX(20px);
        }

        /* 状态指示器 */
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-dot.online {
            background: #10b981;
        }

        .status-dot.offline {
            background: #ef4444;
        }

        .status-dot.warning {
            background: #f59e0b;
        }

        /* Toast */
        .toast {
            position: fixed;
            top: 80px;
            right: 24px;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            font-size: 14px;
            z-index: 1000;
            animation: slideIn 0.3s ease;
        }

        .toast.success {
            background: #10b981;
        }

        .toast.error {
            background: #ef4444;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* 模态框 */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 24px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            color: #64748b;
            cursor: pointer;
        }

        /* 移动端响应式设计 */
        @media screen and (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 12px;
                padding: 12px 16px;
                height: auto;
            }

            .logo {
                font-size: 16px;
            }

            .nav {
                flex-wrap: wrap;
                justify-content: center;
                gap: 4px;
            }

            .nav-item {
                padding: 6px 10px;
                font-size: 12px;
            }

            .admin-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                padding: 16px 0;
                border-right: none;
                border-bottom: 1px solid #e2e8f0;
            }

            .sidebar-menu {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                padding: 0 16px;
            }

            .sidebar-item {
                border-left: none;
                border-radius: 8px;
                padding: 8px 12px;
            }

            .sidebar-item.active {
                border-left-color: transparent;
            }

            .main-content {
                padding: 20px 16px;
            }

            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .stat-card {
                padding: 16px;
            }

            .content-section {
                padding: 16px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .data-table {
                font-size: 13px;
            }

            .data-table th,
            .data-table td {
                padding: 10px 8px;
            }

            .action-btns {
                flex-wrap: wrap;
                gap: 4px;
            }

            .btn-sm {
                padding: 4px 8px;
                font-size: 11px;
            }
        }

        @media screen and (max-width: 480px) {
            .nav-item span {
                display: none;
            }

            .stats-cards {
                grid-template-columns: 1fr;
            }

            .search-box input {
                font-size: 16px; /* 防止iOS缩放 */
            }

            .form-group input,
            .form-group select,
            .form-group textarea {
                font-size: 16px; /* 防止iOS缩放 */
            }

            .modal-content {
                width: 95%;
                margin: 20px auto;
            }
        }
    </style>
</head>
<body>
    <!-- 头部导航 -->
    <header class="header">
        <a href="?route=home" class="logo" style="text-decoration: none;">
            <?php if (file_exists(__DIR__ . '/../assets/images/logo.png')): ?>
                <img src="assets/images/logo.png?v=<?php echo filemtime(__DIR__ . '/../assets/images/logo.png'); ?>" alt="Logo">
            <?php else: ?>
                <i class="fas fa-robot" style="font-size: 28px;"></i>
            <?php endif; ?>
            <?php echo $config['app']['name'] ?? '巨神兵AIAPI辅助平台'; ?>
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
                <?php if (file_exists(__DIR__ . '/../assets/images/logo.png')): ?>
                    <img src="assets/images/logo.png?v=<?php echo filemtime(__DIR__ . '/../assets/images/logo.png'); ?>" alt="Logo">
                <?php else: ?>
                    <i class="fas fa-robot" style="font-size: 48px; color: #4c51bf;"></i>
                <?php endif; ?>
                <div class="platform-name"><?php echo $config['app']['name'] ?? '巨神兵AIAPI辅助平台'; ?></div>
                <div class="platform-version">v<?php echo $config['app']['version'] ?? '1.0.0'; ?></div>
            </div>
            <ul class="sidebar-menu">
                <li class="sidebar-item active" onclick="showPage('models')">
                    <i class="fas fa-brain"></i> 模型管理
                </li>
                <li class="sidebar-item" onclick="showPage('users')">
                    <i class="fas fa-users"></i> 用户管理
                </li>
                <li class="sidebar-item" onclick="showPage('usage')">
                    <i class="fas fa-chart-bar"></i> 用量统计
                </li>
                <li class="sidebar-item" onclick="showPage('ai-services')">
                    <i class="fas fa-network-wired"></i> AI服务管理
                </li>
                <li class="sidebar-item" onclick="showPage('knowledge')">
                    <i class="fas fa-book"></i> 知识库管理
                </li>
                <li class="sidebar-item" onclick="window.open('templates/openclaw.php', '_blank')">
                    <i class="fas fa-robot"></i> 🦞 养龙虾
                </li>
                <li class="sidebar-item" onclick="showPage('storage')">
                    <i class="fas fa-hdd"></i> 存储管理
                </li>
                <li class="sidebar-item" onclick="showPage('billing')">
                    <i class="fas fa-credit-card"></i> 计费管理
                </li>
                <li class="sidebar-dropdown" id="systemDropdown">
                    <div class="sidebar-item dropdown-toggle" onclick="toggleDropdown('systemDropdown')">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <i class="fas fa-cog"></i> 系统设置
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <ul class="dropdown-menu">
                        <li class="dropdown-item" onclick="showSubPage('system', 'settings')">
                            <i class="fas fa-sliders-h"></i> 基础设置
                        </li>
                        <li class="dropdown-item" onclick="showSubPage('system', 'status')">
                            <i class="fas fa-heartbeat"></i> 系统状态
                        </li>
                        <li class="dropdown-item" onclick="showSubPage('system', 'logs')">
                            <i class="fas fa-file-alt"></i> 日志查看
                        </li>
                    </ul>
                </li>
            </ul>
        </aside>

        <!-- 主内容区 -->
        <main class="main-content">
            <!-- 模型管理页面 -->
            <div id="page-models" class="page-content">
                <h1 class="page-title">模型管理</h1>
                <p class="page-desc">管理本地Ollama模型和在线API模型</p>

                <div class="tabs">
                    <div class="tab active" onclick="switchTab('local')">本地模型</div>
                    <div class="tab" onclick="switchTab('online')">在线API</div>
                </div>

                <!-- 本地模型 -->
                <div id="tab-local" class="tab-content active">
                    <div class="card">
                        <div class="card-title">
                            <i class="fas fa-server"></i> Ollama本地模型
                            <span class="status-indicator" style="margin-left: auto;">
                                <span class="status-dot online"></span>
                                <span id="ollamaStatus">已连接</span>
                            </span>
                        </div>
                        <div id="localModelList" class="model-list">
                            <!-- 动态加载 -->
                        </div>
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                            <button class="btn btn-success" onclick="refreshOllamaModels()">
                                <i class="fas fa-sync-alt"></i> 刷新模型列表
                            </button>
                            <span class="form-hint" style="margin-left: 12px;">从Ollama服务自动获取已安装的模型</span>
                        </div>
                    </div>
                </div>

                <!-- 在线API -->
                <div id="tab-online" class="tab-content">
                    <!-- 云端API模型 -->
                    <div class="card" style="margin-bottom: 20px;">
                        <div class="card-title">
                            <i class="fas fa-server" style="color: #10b981;"></i> 云端API模型
                            <span style="margin-left: 12px; font-size: 13px; color: #64748b;">从AI服务管理的提供商自动获取</span>
                        </div>
                        <div id="cloudModelList" class="model-list">
                            <!-- 动态加载 -->
                        </div>
                    </div>
                    
                    <!-- 手动添加的模型 -->
                    <div class="card">
                        <div class="card-title">
                            <i class="fas fa-cloud"></i> 自定义在线模型
                            <button class="btn btn-success btn-sm" style="margin-left: auto;" onclick="showAddModelModal()">
                                <i class="fas fa-plus"></i> 添加模型
                            </button>
                        </div>
                        <div id="onlineModelList" class="model-list">
                            <!-- 动态加载 -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- 用户管理页面 -->
            <div id="page-users" class="page-content" style="display: none;">
                <h1 class="page-title">用户管理</h1>
                <p class="page-desc">管理系统用户账号和权限</p>

                <div class="card">
                    <div class="card-title">
                        <i class="fas fa-users"></i> 用户列表
                        <button class="btn btn-success btn-sm" style="margin-left: auto;" onclick="showAddUserModal()">
                            <i class="fas fa-plus"></i> 添加用户
                        </button>
                    </div>
                    <div id="userList" class="model-list">
                        <!-- 动态加载 -->
                    </div>
                    <div id="userPagination" style="display: flex; justify-content: center; gap: 8px; margin-top: 20px;">
                        <!-- 分页 -->
                    </div>
                </div>
            </div>

            <!-- 用量统计页面 -->
            <div id="page-usage" class="page-content" style="display: none;">
                <h1 class="page-title">用量统计</h1>
                <p class="page-desc">查看用户API使用量和Token消耗统计</p>

                <!-- 日期筛选 -->
                <div class="card" style="margin-bottom: 20px;">
                    <div class="card-title"><i class="fas fa-filter"></i> 时间筛选</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">开始日期</label>
                            <input type="date" class="form-input" id="usageStartDate" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">结束日期</label>
                            <input type="date" class="form-input" id="usageEndDate" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button class="btn btn-primary" onclick="loadUsageStats()">
                                <i class="fas fa-search"></i> 查询
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 系统总体统计 -->
                <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px;">
                        <div class="stat-title" style="font-size: 14px; opacity: 0.9;">总请求数</div>
                        <div class="stat-value" id="totalRequests" style="font-size: 32px; font-weight: 600; margin-top: 8px;">0</div>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 20px; border-radius: 12px;">
                        <div class="stat-title" style="font-size: 14px; opacity: 0.9;">活跃用户数</div>
                        <div class="stat-value" id="activeUsers" style="font-size: 32px; font-weight: 600; margin-top: 8px;">0</div>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 20px; border-radius: 12px;">
                        <div class="stat-title" style="font-size: 14px; opacity: 0.9;">总Token数</div>
                        <div class="stat-value" id="totalTokens" style="font-size: 32px; font-weight: 600; margin-top: 8px;">0</div>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 20px; border-radius: 12px;">
                        <div class="stat-title" style="font-size: 14px; opacity: 0.9;">预估费用</div>
                        <div class="stat-value" id="totalCost" style="font-size: 32px; font-weight: 600; margin-top: 8px;">¥0.00</div>
                    </div>
                </div>

                <!-- 用户用量列表 -->
                <div class="card">
                    <div class="card-title">
                        <i class="fas fa-users"></i> 用户用量统计
                    </div>
                    <div id="usersUsageList" style="overflow-x: auto;">
                        <table class="data-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8fafc;">
                                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">用户名</th>
                                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">邮箱</th>
                                    <th style="padding: 12px; text-align: center; border-bottom: 2px solid #e2e8f0;">请求数</th>
                                    <th style="padding: 12px; text-align: center; border-bottom: 2px solid #e2e8f0;">输入Token</th>
                                    <th style="padding: 12px; text-align: center; border-bottom: 2px solid #e2e8f0;">输出Token</th>
                                    <th style="padding: 12px; text-align: center; border-bottom: 2px solid #e2e8f0;">总Token</th>
                                    <th style="padding: 12px; text-align: center; border-bottom: 2px solid #e2e8f0;">活跃天数</th>
                                    <th style="padding: 12px; text-align: right; border-bottom: 2px solid #e2e8f0;">费用</th>
                                    <th style="padding: 12px; text-align: center; border-bottom: 2px solid #e2e8f0;">操作</th>
                                </tr>
                            </thead>
                            <tbody id="usersUsageTableBody">
                                <!-- 动态加载 -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 每日趋势图表 -->
                <div class="card" style="margin-top: 20px;">
                    <div class="card-title"><i class="fas fa-chart-line"></i> 每日用量趋势</div>
                    <div id="dailyUsageChart" style="height: 300px; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                        加载中...
                    </div>
                </div>
            </div>

            <!-- 用户用量详情模态框 -->
            <div id="userUsageDetailModal" class="modal">
                <div class="modal-content" style="max-width: 900px; max-height: 80vh; overflow-y: auto;">
                    <div class="modal-header">
                        <h3 class="modal-title"><i class="fas fa-chart-bar"></i> 用户用量详情</h3>
                        <button class="modal-close" onclick="closeUserUsageDetailModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div id="userUsageDetailContent">
                            <!-- 动态加载 -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- 系统设置页面 -->
            <div id="page-system" class="page-content" style="display: none;">
                <h1 class="page-title">系统设置</h1>
                <p class="page-desc">配置平台基本信息和功能开关</p>

                <div class="card">
                    <div class="card-title"><i class="fas fa-cog"></i> 基本设置</div>
                    
                    <!-- LOGO上传 -->
                    <div class="form-group">
                        <label class="form-label">平台LOGO</label>
                        <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 12px;">
                            <div id="logoPreview" style="width: 80px; height: 80px; border: 2px dashed #e2e8f0; border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; background: #f8fafc;">
                                <?php if (file_exists(__DIR__ . '/../assets/images/logo.png')): ?>
                                    <img src="assets/images/logo.png?v=<?php echo filemtime(__DIR__ . '/../assets/images/logo.png'); ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                <?php else: ?>
                                    <i class="fas fa-image" style="font-size: 32px; color: #cbd5e1;"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <input type="file" id="logoUpload" accept="image/png,image/jpeg,image/svg+xml" style="display: none;">
                                <button class="btn btn-secondary" onclick="$('#logoUpload').click()">
                                    <i class="fas fa-upload"></i> 选择图片
                                </button>
                                <span class="form-hint" style="display: block; margin-top: 8px;">支持 PNG、JPG、SVG 格式，建议尺寸 200x200px</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">平台名称</label>
                            <input type="text" class="form-input" id="appName" value="<?php echo $config['app']['name'] ?? '巨神兵AIAPI辅助平台'; ?>">
                            <span class="form-hint">显示在网站标题和导航栏的平台名称</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">版本号</label>
                            <input type="text" class="form-input" id="appVersion" value="<?php echo $config['app']['version'] ?? '1.0.0'; ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="display: flex; align-items: center; gap: 12px;">
                            调试模式
                            <label class="switch">
                                <input type="checkbox" id="debugMode" <?php echo ($config['app']['debug'] ?? true) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </label>
                        <span class="form-hint">开启后将显示详细的错误信息（生产环境建议关闭）</span>
                    </div>
                    <div style="margin-top: 20px;">
                        <button class="btn btn-primary" onclick="saveSystemConfig()">
                            <i class="fas fa-save"></i> 保存设置
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-title"><i class="fas fa-database"></i> 数据管理</div>
                    <div style="display: flex; gap: 12px;">
                        <button class="btn btn-secondary" onclick="clearCache()">
                            <i class="fas fa-broom"></i> 清理缓存
                        </button>
                        <button class="btn btn-secondary" onclick="exportConfig()">
                            <i class="fas fa-download"></i> 导出配置
                        </button>
                        <button class="btn btn-secondary" onclick="importConfig()">
                            <i class="fas fa-upload"></i> 导入配置
                        </button>
                    </div>
                </div>
            </div>

            <!-- 系统状态页面 -->
            <div id="page-status" class="page-content" style="display: none;">
                <h1 class="page-title">系统状态</h1>
                <p class="page-desc">服务器状态监控和一键巡检</p>

                <!-- 状态概览 -->
                <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <div class="stat-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div style="font-size: 14px; color: #64748b; margin-bottom: 8px;">PHP版本</div>
                        <div style="font-size: 24px; font-weight: 600; color: #1a202c;"><?php echo PHP_VERSION; ?></div>
                    </div>
                    <div class="stat-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div style="font-size: 14px; color: #64748b; margin-bottom: 8px;">服务器系统</div>
                        <div style="font-size: 24px; font-weight: 600; color: #1a202c;"><?php echo PHP_OS; ?></div>
                    </div>
                    <div class="stat-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div style="font-size: 14px; color: #64748b; margin-bottom: 8px;">数据库状态</div>
                        <div style="font-size: 24px; font-weight: 600; color: #10b981;">正常</div>
                    </div>
                    <div class="stat-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div style="font-size: 14px; color: #64748b; margin-bottom: 8px;">磁盘空间</div>
                        <div style="font-size: 24px; font-weight: 600; color: #1a202c;" id="diskSpace">计算中...</div>
                    </div>
                </div>

                <!-- 扩展检查 -->
                <div class="card">
                    <div class="card-title"><i class="fas fa-puzzle-piece"></i> PHP扩展检查</div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px;" id="extensionList">
                        <?php
                        $extensions = ['pdo', 'pdo_sqlite', 'gd', 'curl', 'mbstring', 'json', 'openssl', 'zip', 'fileinfo'];
                        foreach ($extensions as $ext) {
                            $loaded = extension_loaded($ext);
                            $color = $loaded ? '#10b981' : '#ef4444';
                            $icon = $loaded ? 'fa-check-circle' : 'fa-times-circle';
                            echo "<div style='background: #f8fafc; padding: 12px; border-radius: 8px; text-align: center; border: 1px solid " . ($loaded ? '#d1fae5' : '#fee2e2') . ";'>
                                    <i class='fas $icon' style='color: $color; font-size: 20px; margin-bottom: 6px; display: block;'></i>
                                    <span style='font-size: 13px; color: #374151;'>$ext</span>
                                  </div>";
                        }
                        ?>
                    </div>
                </div>

                <!-- 一键巡检 -->
                <div class="card" style="margin-top: 24px;">
                    <div class="card-title"><i class="fas fa-stethoscope"></i> 系统巡检</div>
                    <p style="color: #64748b; margin-bottom: 16px;">全面检查系统运行状态，发现潜在问题</p>
                    <button class="btn btn-primary" onclick="runSystemInspect()" style="width: 100%; padding: 16px; font-size: 16px;">
                        <i class="fas fa-play"></i> 开始一键巡检
                    </button>
                    <div id="inspectResult" style="margin-top: 20px; padding: 20px; border-radius: 12px; display: none;"></div>
                </div>
            </div>

            <!-- 日志查看页面 -->
            <div id="page-logs" class="page-content" style="display: none;">
                <h1 class="page-title">日志查看</h1>
                <p class="page-desc">查看系统运行日志，AI分析日志内容</p>

                <!-- 筛选器 -->
                <div class="card" style="margin-bottom: 24px;">
                    <div class="card-title"><i class="fas fa-filter"></i> 日志筛选</div>
                    <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 150px;">
                            <label style="display: block; font-size: 13px; color: #64748b; margin-bottom: 6px;">日志级别</label>
                            <select class="form-select" id="logLevelFilter" onchange="loadSystemLogsData()">
                                <option value="">全部</option>
                                <option value="error">错误</option>
                                <option value="warning">警告</option>
                                <option value="info">信息</option>
                                <option value="debug">调试</option>
                            </select>
                        </div>
                        <div style="flex: 1; min-width: 150px;">
                            <label style="display: block; font-size: 13px; color: #64748b; margin-bottom: 6px;">日期范围</label>
                            <input type="date" class="form-input" id="logDateFilter" onchange="loadSystemLogsData()">
                        </div>
                        <div style="flex: 2; min-width: 200px;">
                            <label style="display: block; font-size: 13px; color: #64748b; margin-bottom: 6px;">关键词搜索</label>
                            <input type="text" class="form-input" id="logSearchFilter" placeholder="输入关键词搜索..." onkeyup="if(event.key==='Enter')loadSystemLogsData()">
                        </div>
                        <div style="display: flex; align-items: flex-end;">
                            <button class="btn btn-primary" onclick="loadSystemLogsData()">
                                <i class="fas fa-search"></i> 查询
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 日志列表 -->
                <div class="card">
                    <div class="card-title" style="display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fas fa-list"></i> 日志列表</span>
                        <div style="display: flex; gap: 8px;">
                            <button class="btn btn-secondary btn-sm" onclick="analyzeLogsWithAI()">
                                <i class="fas fa-brain"></i> AI分析
                            </button>
                            <button class="btn btn-secondary btn-sm" onclick="exportLogs()">
                                <i class="fas fa-download"></i> 导出
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="clearLogs()">
                                <i class="fas fa-trash"></i> 清空
                            </button>
                        </div>
                    </div>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8fafc;">
                                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0; font-size: 13px; color: #64748b;">时间</th>
                                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0; font-size: 13px; color: #64748b;">级别</th>
                                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0; font-size: 13px; color: #64748b;">来源</th>
                                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0; font-size: 13px; color: #64748b;">消息</th>
                                </tr>
                            </thead>
                            <tbody id="logsTableBody">
                                <tr>
                                    <td colspan="4" style="padding: 40px; text-align: center; color: #94a3b8;">
                                        <i class="fas fa-info-circle" style="font-size: 24px; margin-bottom: 12px; display: block;"></i>
                                        点击查询按钮加载日志
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- 分页 -->
                    <div id="logsPagination" style="display: flex; justify-content: center; gap: 8px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                        <!-- 动态加载 -->
                    </div>
                </div>
            </div>

            <!-- 知识库管理页面 -->
            <div id="page-knowledge" class="page-content" style="display: none;">
                <h1 class="page-title">知识库管理</h1>
                <p class="page-desc">管理训练文档和知识库，用于模型微调</p>

                <div class="card">
                    <div class="card-title">
                        <i class="fas fa-upload"></i> 上传训练文档
                        <span class="form-hint" style="margin-left: auto;">支持 TXT、DOC、DOCX、PDF 格式</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">文档上传</label>
                        <input type="file" id="trainingFile" accept=".txt,.doc,.docx,.pdf" class="form-input">
                        <span class="form-hint">选择要添加到知识库的训练文档</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">文档名称</label>
                        <input type="text" class="form-input" id="docName" placeholder="输入文档名称">
                    </div>
                    <div class="form-group">
                        <label class="form-label">分类标签</label>
                        <input type="text" class="form-input" id="docTags" placeholder="例如: 产品说明, 技术文档 (用逗号分隔)">
                    </div>
                    <div class="form-group">
                        <label class="form-label">文档描述</label>
                        <textarea class="form-input" id="docDescription" rows="3" placeholder="输入文档描述信息..."></textarea>
                    </div>
                    <div style="margin-top: 20px;">
                        <button class="btn btn-primary" onclick="uploadTrainingDoc()">
                            <i class="fas fa-upload"></i> 上传到知识库
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-title">
                        <i class="fas fa-database"></i> 知识库列表
                        <button class="btn btn-success btn-sm" style="margin-left: auto;" onclick="trainModelWithKnowledge()">
                            <i class="fas fa-brain"></i> 开始训练模型
                        </button>
                    </div>
                    <div id="knowledgeBaseList" class="model-list">
                        <div style="color: #64748b; text-align: center; padding: 40px;">
                            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                            知识库为空，请先上传文档
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-title"><i class="fas fa-cogs"></i> 训练配置</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">目标模型</label>
                            <select class="form-select" id="trainTargetModel">
                                <option value="">正在加载可用模型...</option>
                            </select>
                            <span class="form-hint">从已安装的本地或在线模型中选择</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">训练轮数 (Epochs)</label>
                            <input type="number" class="form-input" id="trainEpochs" value="3" min="1" max="10">
                            <span class="form-hint">建议值：3-5轮</span>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">学习率</label>
                            <input type="number" class="form-input" id="trainLearningRate" value="0.0001" step="0.0001" min="0.00001" max="0.01">
                            <span class="form-hint">建议值：0.0001-0.001</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">批次大小</label>
                            <input type="number" class="form-input" id="trainBatchSize" value="4" min="1" max="32">
                            <span class="form-hint">根据显存调整，建议4-8</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="display: flex; align-items: center; gap: 12px;">
                            增量训练
                            <label class="switch">
                                <input type="checkbox" id="incrementalTraining" checked>
                                <span class="slider"></span>
                            </label>
                        </label>
                        <span class="form-hint">开启后将在现有模型基础上继续训练，而非从头开始</span>
                    </div>
                </div>

                <!-- 训练进度面板 -->
                <div class="card" id="trainingProgressCard" style="display: none;">
                    <div class="card-title">
                        <i class="fas fa-spinner fa-spin"></i> 训练进度
                        <span id="trainingStatus" style="margin-left: auto; color: #4c51bf; font-weight: 500;">准备中...</span>
                    </div>
                    <div class="progress-container" style="margin: 20px 0;">
                        <div class="progress-bar-wrapper" style="background: #e2e8f0; border-radius: 10px; height: 20px; overflow: hidden;">
                            <div class="progress-bar" id="trainingProgressBar" style="background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); height: 100%; width: 0%; border-radius: 10px; transition: width 0.3s ease;"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 8px; font-size: 13px; color: #64748b;">
                            <span id="trainingProgressText">0%</span>
                            <span id="trainingTimeElapsed">已用时: 00:00:00</span>
                        </div>
                    </div>
                    <div id="trainingLogContainer" style="background: #1a202c; color: #e2e8f0; padding: 12px; border-radius: 8px; font-family: monospace; font-size: 12px; max-height: 150px; overflow-y: auto;">
                        <div style="color: #64748b;">等待训练开始...</div>
                    </div>
                    <div style="margin-top: 16px; display: flex; gap: 12px;">
                        <button class="btn btn-danger" id="btnStopTraining" onclick="stopTraining()">
                            <i class="fas fa-stop"></i> 停止训练
                        </button>
                        <button class="btn btn-secondary" onclick="viewTrainingDetails()">
                            <i class="fas fa-info-circle"></i> 查看详情
                        </button>
                    </div>
                </div>

                <!-- 训练历史 -->
                <div class="card">
                    <div class="card-title">
                        <i class="fas fa-history"></i> 训练历史
                        <div style="margin-left: auto; display: flex; gap: 8px;">
                            <button class="btn btn-warning btn-sm" onclick="viewAllErrorLogs()">
                                <i class="fas fa-exclamation-triangle"></i> 错误日志
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="showClearTasksModal()">
                                <i class="fas fa-trash-alt"></i> 清除任务
                            </button>
                            <button class="btn btn-secondary btn-sm" onclick="loadTrainingHistory()">
                                <i class="fas fa-sync-alt"></i> 刷新
                            </button>
                        </div>
                    </div>
                    <div id="trainingHistoryList" class="model-list">
                        <div style="color: #64748b; text-align: center; padding: 40px;">
                            <i class="fas fa-history" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                            暂无训练记录
                        </div>
                    </div>
                </div>
            </div>

            <!-- 存储管理页面 -->
            <div id="page-storage" class="page-content" style="display: none;">
                <h1 class="page-title">存储管理</h1>
                <p class="page-desc">管理模型和文件的存储位置，支持本地存储、云存储和自定义存储</p>

                <!-- 存储概览 -->
                <div class="card">
                    <div class="card-title"><i class="fas fa-chart-pie"></i> 存储概览</div>
                    <div class="form-row">
                        <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px;">
                            <div style="font-size: 14px; opacity: 0.9;">当前存储</div>
                            <div style="font-size: 24px; font-weight: 700; margin: 8px 0;" id="currentStorageType">本地存储</div>
                            <div style="font-size: 13px; opacity: 0.8;" id="currentStorageStatus">运行正常</div>
                        </div>
                        <div class="stat-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 20px; border-radius: 12px;">
                            <div style="font-size: 14px; opacity: 0.9;">已用空间</div>
                            <div style="font-size: 24px; font-weight: 700; margin: 8px 0;" id="storageUsed">0 MB</div>
                            <div style="font-size: 13px; opacity: 0.8;" id="storageTotal">共 0 MB</div>
                        </div>
                        <div class="stat-card" style="background: linear-gradient(135deg, #fc4a1a 0%, #f7b733 100%); color: white; padding: 20px; border-radius: 12px;">
                            <div style="font-size: 14px; opacity: 0.9;">存储位置</div>
                            <div style="font-size: 18px; font-weight: 600; margin: 8px 0;" id="storageLocation">本地</div>
                            <div style="font-size: 13px; opacity: 0.8;">模型文件存储位置</div>
                        </div>
                    </div>
                </div>

                <!-- 存储配置 -->
                <div class="card">
                    <div class="card-title">
                        <i class="fas fa-cog"></i> 存储配置
                        <button class="btn btn-success btn-sm" style="margin-left: auto;" onclick="showAddStorageModal()">
                            <i class="fas fa-plus"></i> 添加存储
                        </button>
                    </div>
                    
                    <div id="storageList" class="model-list">
                        <!-- 动态加载存储配置列表 -->
                    </div>
                </div>

                <!-- 存储类型说明 -->
                <div class="card">
                    <div class="card-title"><i class="fas fa-info-circle"></i> 支持的存储类型</div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                        <div style="padding: 16px; background: #f8fafc; border-radius: 8px;">
                            <div style="font-weight: 600; color: #1a202c; margin-bottom: 8px;"><i class="fas fa-desktop" style="color: #4c51bf;"></i> 本地存储</div>
                            <div style="font-size: 13px; color: #64748b;">使用服务器本地磁盘存储模型文件</div>
                        </div>
                        <div style="padding: 16px; background: #f8fafc; border-radius: 8px;">
                            <div style="font-weight: 600; color: #1a202c; margin-bottom: 8px;"><i class="fab fa-aws" style="color: #ff9900;"></i> Amazon S3</div>
                            <div style="font-size: 13px; color: #64748b;">AWS S3 对象存储服务</div>
                        </div>
                        <div style="padding: 16px; background: #f8fafc; border-radius: 8px;">
                            <div style="font-weight: 600; color: #1a202c; margin-bottom: 8px;"><i class="fas fa-cloud" style="color: #00c1de;"></i> 阿里云OSS</div>
                            <div style="font-size: 13px; color: #64748b;">阿里云对象存储服务</div>
                        </div>
                        <div style="padding: 16px; background: #f8fafc; border-radius: 8px;">
                            <div style="font-weight: 600; color: #1a202c; margin-bottom: 8px;"><i class="fas fa-cloud" style="color: #006eff;"></i> 腾讯云COS</div>
                            <div style="font-size: 13px; color: #64748b;">腾讯云对象存储服务</div>
                        </div>
                        <div style="padding: 16px; background: #f8fafc; border-radius: 8px;">
                            <div style="font-weight: 600; color: #1a202c; margin-bottom: 8px;"><i class="fas fa-database" style="color: #c72e49;"></i> MinIO</div>
                            <div style="font-size: 13px; color: #64748b;">高性能开源对象存储</div>
                        </div>
                        <div style="padding: 16px; background: #f8fafc; border-radius: 8px;">
                            <div style="font-weight: 600; color: #1a202c; margin-bottom: 8px;"><i class="fas fa-server" style="color: #16a34a;"></i> 自定义存储</div>
                            <div style="font-size: 13px; color: #64748b;">支持自托管的S3兼容存储</div>
                        </div>
                        <div style="padding: 16px; background: #f8fafc; border-radius: 8px;">
                            <div style="font-weight: 600; color: #1a202c; margin-bottom: 8px;"><i class="fas fa-network-wired" style="color: #7c3aed;"></i> IP-SAN</div>
                            <div style="font-size: 13px; color: #64748b;">iSCSI 企业级块存储</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI服务管理页面 (API配置 + 提供商管理合并) -->
            <div id="page-ai-services" class="page-content" style="display: none;">
                <h1 class="page-title">AI服务管理</h1>
                <p class="page-desc">统一管理本地Ollama、GPUStack和在线API提供商，支持14+种AI服务</p>

                <!-- 统计概览 -->
                <div class="card">
                    <div class="card-title"><i class="fas fa-chart-pie"></i> 服务概览</div>
                    <div class="form-row" id="providerStats">
                        <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px;">
                            <div style="font-size: 14px; opacity: 0.9;">总提供商</div>
                            <div style="font-size: 32px; font-weight: 700; margin: 8px 0;" id="totalProviders">0</div>
                            <div style="font-size: 13px; opacity: 0.8;">已配置</div>
                        </div>
                        <div class="stat-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 20px; border-radius: 12px;">
                            <div style="font-size: 14px; opacity: 0.9;">在线服务</div>
                            <div style="font-size: 32px; font-weight: 700; margin: 8px 0;" id="onlineProviders">0</div>
                            <div style="font-size: 13px; opacity: 0.8;">云端API</div>
                        </div>
                        <div class="stat-card" style="background: linear-gradient(135deg, #fc4a1a 0%, #f7b733 100%); color: white; padding: 20px; border-radius: 12px;">
                            <div style="font-size: 14px; opacity: 0.9;">本地服务</div>
                            <div style="font-size: 32px; font-weight: 700; margin: 8px 0;" id="localProviders">0</div>
                            <div style="font-size: 13px; opacity: 0.8;">私有化部署</div>
                        </div>
                        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 20px; border-radius: 12px;">
                            <div style="font-size: 14px; opacity: 0.9;">当前活动</div>
                            <div style="font-size: 20px; font-weight: 700; margin: 8px 0;" id="activeProviderName">-</div>
                            <div style="font-size: 13px; opacity: 0.8;">默认提供商</div>
                        </div>
                    </div>
                </div>

                <!-- 快速配置：Ollama本地服务 -->
                <div class="card">
                    <div class="card-title"><i class="fas fa-server"></i> Ollama本地服务快速配置</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">服务地址</label>
                            <input type="text" class="form-input" id="quickOllamaUrl" value="<?php echo $config['ollama_api']['base_url'] ?? 'http://localhost:11434'; ?>">
                            <span class="form-hint">Ollama服务的完整URL地址</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">默认模型</label>
                            <input type="text" class="form-input" id="quickOllamaModel" value="<?php echo $config['ollama_api']['default_model'] ?? 'llama2'; ?>">
                            <span class="form-hint">系统默认使用的模型名称</span>
                        </div>
                    </div>
                    <div style="margin-top: 20px; display: flex; gap: 12px;">
                        <button class="btn btn-primary" onclick="quickSetupOllama()">
                            <i class="fas fa-bolt"></i> 一键添加为提供商
                        </button>
                        <button class="btn btn-secondary" onclick="testQuickOllama()">
                            <i class="fas fa-plug"></i> 测试连接
                        </button>
                    </div>
                </div>

                <!-- 快速配置：GPUStack -->
                <div class="card">
                    <div class="card-title">
                        <i class="fas fa-microchip"></i> GPUStack快速配置
                        <label class="switch" style="margin-left: auto;">
                            <input type="checkbox" id="quickGpustackEnabled" <?php echo ($config['gpustack_api']['enabled'] ?? false) ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">API地址</label>
                            <input type="text" class="form-input" id="quickGpustackUrl" value="<?php echo $config['gpustack_api']['base_url'] ?? ''; ?>" placeholder="https://api.gpustack.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">API密钥</label>
                            <input type="password" class="form-input" id="quickGpustackKey" value="<?php echo $config['gpustack_api']['api_key'] ?? ''; ?>" placeholder="可选">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">默认模型</label>
                            <input type="text" class="form-input" id="quickGpustackModel" value="<?php echo $config['gpustack_api']['default_model'] ?? ''; ?>" placeholder="例如: llama2">
                        </div>
                    </div>
                    <div style="margin-top: 20px; display: flex; gap: 12px;">
                        <button class="btn btn-primary" onclick="quickSetupGpustack()">
                            <i class="fas fa-bolt"></i> 一键添加为提供商
                        </button>
                        <button class="btn btn-secondary" onclick="saveGpuStackConfigQuick()">
                            <i class="fas fa-save"></i> 仅保存配置
                        </button>
                    </div>
                </div>

                <!-- 提供商列表 -->
                <div class="card">
                    <div class="card-title">
                        <i class="fas fa-network-wired"></i> API提供商列表
                        <div style="margin-left: auto; display: flex; gap: 8px;">
                            <button class="btn btn-primary btn-sm" onclick="showAddProviderModal()">
                                <i class="fas fa-plus"></i> 添加提供商
                            </button>
                        </div>
                    </div>
                    <div id="providersList" class="model-list">
                        <div style="color: #64748b; text-align: center; padding: 40px;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 32px; margin-bottom: 16px; display: block;"></i>
                            加载中...
                        </div>
                    </div>
                </div>

                <!-- API密钥管理 -->
                <div class="card">
                    <div class="card-title">
                        <i class="fas fa-key"></i> API密钥管理
                        <div style="margin-left: auto; display: flex; gap: 8px;">
                            <button type="button" class="btn btn-primary btn-sm" id="createApiKeyBtn">
                                <i class="fas fa-plus"></i> 创建密钥
                            </button>
                        </div>
                    </div>
                    <div id="apiKeysList" class="model-list">
                        <div style="color: #64748b; text-align: center; padding: 40px;">
                            <i class="fas fa-key" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                            点击「创建密钥」生成用于外部API调用的密钥
                        </div>
                    </div>
                </div>

                <!-- API文档 -->
                <div class="card">
                    <div class="card-title"><i class="fas fa-book"></i> API文档</div>
                    <div style="background: #f8fafc; padding: 20px; border-radius: 8px; font-family: monospace; font-size: 13px;">
                        <p style="margin-bottom: 12px; font-weight: 600;">基础URL:</p>
                        <code style="background: #e2e8f0; padding: 8px 12px; border-radius: 4px; display: block; margin-bottom: 16px;">
                            <?php echo 'http://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['PHP_SELF'])) . '/api/external_api.php?path='; ?>
                        </code>
                        
                        <p style="margin-bottom: 12px; font-weight: 600;">认证方式:</p>
                        <code style="background: #e2e8f0; padding: 8px 12px; border-radius: 4px; display: block; margin-bottom: 16px;">
                            Header: X-API-Key: your_api_key_here
                        </code>
                        
                        <p style="margin-bottom: 12px; font-weight: 600;">支持的接口:</p>
                        <ul style="margin-left: 20px; line-height: 2;">
                            <li><code>models</code> - 获取模型列表</li>
                            <li><code>chat/completions</code> - 对话接口 (OpenAI兼容)</li>
                            <li><code>finetuned/chat</code> - 训练模型对话</li>
                            <li><code>workflow/run</code> - 执行工作流</li>
                            <li><code>files/upload</code> - 上传文件</li>
                            <li><code>knowledge/query</code> - 知识库查询</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- 计费管理页面 -->
            <div id="page-billing" class="page-content" style="display: none;">
                <h1 class="page-title">计费管理</h1>
                <p class="page-desc">配置平台计费规则和查看使用统计</p>

                <div class="card">
                    <div class="card-title"><i class="fas fa-credit-card"></i> 计费设置</div>
                    <div class="form-group">
                        <label class="form-label" style="display: flex; align-items: center; gap: 12px;">
                            启用计费功能
                            <label class="switch">
                                <input type="checkbox" id="billingEnabled" <?php echo ($config['billing']['enabled'] ?? false) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </label>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">免费额度 (Token/月)</label>
                            <input type="number" class="form-input" id="freeQuota" value="<?php echo $config['billing']['free_quota'] ?? 100000; ?>" min="0" step="1000">
                            <span class="form-hint">每个用户每月的免费使用额度</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">超出单价 (元/千Token)</label>
                            <input type="number" class="form-input" id="tokenPrice" value="<?php echo $config['billing']['price_per_1k'] ?? 0.02; ?>" min="0" step="0.001">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">计费周期</label>
                        <select class="form-select" id="billingCycle">
                            <option value="monthly" <?php echo ($config['billing']['cycle'] ?? 'monthly') === 'monthly' ? 'selected' : ''; ?>>按月计费</option>
                            <option value="quarterly" <?php echo ($config['billing']['cycle'] ?? '') === 'quarterly' ? 'selected' : ''; ?>>按季度计费</option>
                            <option value="yearly" <?php echo ($config['billing']['cycle'] ?? '') === 'yearly' ? 'selected' : ''; ?>>按年计费</option>
                        </select>
                    </div>
                    <div style="margin-top: 20px;">
                        <button class="btn btn-primary" onclick="saveBillingConfig()">
                            <i class="fas fa-save"></i> 保存计费设置
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-title"><i class="fas fa-chart-bar"></i> 使用统计</div>
                    <div class="form-row">
                        <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px;">
                            <div style="font-size: 14px; opacity: 0.9;">本月总消耗</div>
                            <div style="font-size: 32px; font-weight: 700; margin: 8px 0;">¥<span id="totalCost">0.00</span></div>
                            <div style="font-size: 13px; opacity: 0.8;"><span id="totalTokens">0</span> Token</div>
                        </div>
                        <div class="stat-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 20px; border-radius: 12px;">
                            <div style="font-size: 14px; opacity: 0.9;">活跃用户数</div>
                            <div style="font-size: 32px; font-weight: 700; margin: 8px 0;" id="activeUsers">0</div>
                            <div style="font-size: 13px; opacity: 0.8;">本月有使用记录</div>
                        </div>
                        <div class="stat-card" style="background: linear-gradient(135deg, #fc4a1a 0%, #f7b733 100%); color: white; padding: 20px; border-radius: 12px;">
                            <div style="font-size: 14px; opacity: 0.9;">平均单次消耗</div>
                            <div style="font-size: 32px; font-weight: 700; margin: 8px 0;">¥<span id="avgCost">0.00</span></div>
                            <div style="font-size: 13px; opacity: 0.8;">每次对话</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-title">
                        <i class="fas fa-list-alt"></i> 用户账单记录
                        <button class="btn btn-secondary btn-sm" style="margin-left: auto;" onclick="exportBillingReport()">
                            <i class="fas fa-download"></i> 导出报表
                        </button>
                    </div>
                    <div id="billingRecords" class="model-list">
                        <div style="color: #64748b; text-align: center; padding: 40px;">
                            <i class="fas fa-receipt" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                            暂无账单记录
                        </div>
                    </div>
                </div>
            </div>

            <!-- 日志查看页面 -->
            <div id="page-logs" class="page-content" style="display: none;">
                <h1 class="page-title">日志查看</h1>
                <p class="page-desc">查看系统运行日志和错误信息</p>

                <div class="card">
                    <div class="card-title">
                        <i class="fas fa-file-alt"></i> 系统日志
                        <button class="btn btn-secondary btn-sm" style="margin-left: auto;" onclick="refreshLogs()">
                            <i class="fas fa-sync-alt"></i> 刷新
                        </button>
                    </div>
                    <div id="logContainer" style="background: #1a202c; color: #e2e8f0; padding: 16px; border-radius: 8px; font-family: monospace; font-size: 13px; max-height: 400px; overflow-y: auto;">
                        <div style="color: #64748b;">日志加载中...</div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- 添加用户模态框 -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">添加新用户</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="form-group">
                <label class="form-label">用户名</label>
                <input type="text" class="form-input" id="newUsername" placeholder="3-20位字母数字下划线">
            </div>
            <div class="form-group">
                <label class="form-label">密码</label>
                <input type="password" class="form-input" id="newUserPassword" placeholder="至少6位字符">
            </div>
            <div class="form-group">
                <label class="form-label">邮箱</label>
                <input type="email" class="form-input" id="newUserEmail" placeholder="user@example.com">
            </div>
            <div class="form-group">
                <label class="form-label">角色</label>
                <select class="form-select" id="newUserRole">
                    <option value="user">普通用户</option>
                    <option value="admin">管理员</option>
                </select>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button class="btn btn-primary" onclick="addUser()">
                    <i class="fas fa-plus"></i> 创建用户
                </button>
                <button class="btn btn-secondary" onclick="closeModal()">取消</button>
            </div>
        </div>
    </div>

    <!-- 权限分配模态框 -->
    <div id="permissionModal" class="modal">
        <div class="modal-content" style="max-width: 800px; max-height: 80vh; overflow-y: auto;">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-key"></i> 权限分配</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <input type="hidden" id="permissionUserId">
            <div style="margin-bottom: 16px;">
                <span style="color: #64748b;">用户：</span>
                <strong id="permissionUsername" style="color: #1a202c;"></strong>
                <span id="permissionAdminBadge" style="display: none; margin-left: 8px; background: #4c51bf; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px;">管理员</span>
            </div>
            
            <!-- 权限标签页 -->
            <div style="border-bottom: 1px solid #e5e7eb; margin-bottom: 20px;">
                <button type="button" class="perm-tab active" data-tab="modules" style="padding: 10px 20px; border: none; background: none; cursor: pointer; border-bottom: 2px solid #4c51bf; color: #4c51bf; font-weight: 600;">模块权限</button>
                <button type="button" class="perm-tab" data-tab="models" style="padding: 10px 20px; border: none; background: none; cursor: pointer; color: #64748b;">模型权限</button>
                <button type="button" class="perm-tab" data-tab="training" style="padding: 10px 20px; border: none; background: none; cursor: pointer; color: #64748b;">训练权限</button>
            </div>
            
            <!-- 模块权限 -->
            <div id="permTab-modules" class="perm-content">
                <div class="form-group">
                    <label class="form-label" style="display: flex; align-items: center; justify-content: space-between;">
                        <span>模块访问权限</span>
                        <small style="color: #64748b; font-weight: normal;">关闭后用户将无法访问该模块</small>
                    </label>
                    <div id="modulePermissions" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-top: 12px;">
                        <!-- 动态生成 -->
                    </div>
                </div>
            </div>
            
            <!-- 模型权限 -->
            <div id="permTab-models" class="perm-content" style="display: none;">
                <div class="form-group">
                    <label class="form-label">模型使用权限</label>
                    <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 12px; margin-bottom: 12px;">
                        <p style="margin: 0; font-size: 13px; color: #0369a1;">
                            <i class="fas fa-info-circle"></i> 勾选允许用户使用的AI模型，未勾选的模型将不会出现在用户的选择列表中
                        </p>
                    </div>
                    <div id="modelPermissions" style="max-height: 300px; overflow-y: auto;">
                        <!-- 动态生成 -->
                    </div>
                </div>
            </div>
            
            <!-- 训练权限 -->
            <div id="permTab-training" class="perm-content" style="display: none;">
                <div class="form-group">
                    <label class="form-label">模型训练权限</label>
                    <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 12px; margin-bottom: 12px;">
                        <p style="margin: 0; font-size: 13px; color: #166534;">
                            <i class="fas fa-info-circle"></i> 配置用户可训练的模型类型和数量限制
                        </p>
                    </div>
                    <div id="trainingPermissions">
                        <!-- 动态生成 -->
                    </div>
                </div>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 24px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                <button class="btn btn-primary" onclick="savePermissions()">
                    <i class="fas fa-save"></i> 保存权限设置
                </button>
                <button class="btn btn-secondary" onclick="closeModal()">取消</button>
            </div>
        </div>
    </div>

    <!-- 编辑用户模态框 -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">编辑用户</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <input type="hidden" id="editUserId">
            <div class="form-group">
                <label class="form-label">用户名</label>
                <input type="text" class="form-input" id="editUsername" readonly style="background: #f3f4f6;">
            </div>
            <div class="form-group">
                <label class="form-label">邮箱</label>
                <input type="email" class="form-input" id="editUserEmail" placeholder="user@example.com">
            </div>
            <div class="form-group">
                <label class="form-label">角色</label>
                <select class="form-select" id="editUserRole">
                    <option value="user">普通用户</option>
                    <option value="admin">管理员</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">账号状态</label>
                <select class="form-select" id="editUserStatus">
                    <option value="1">正常</option>
                    <option value="0">禁用</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">新密码 <small style="color: #64748b;">(留空表示不修改)</small></label>
                <input type="password" class="form-input" id="editUserNewPassword" placeholder="输入新密码">
            </div>
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button class="btn btn-primary" onclick="saveUserEdit()">
                    <i class="fas fa-save"></i> 保存修改
                </button>
                <button class="btn btn-secondary" onclick="closeModal()">取消</button>
            </div>
        </div>
    </div>

    <!-- 创建API密钥模态框 -->
    <div id="createApiKeyModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-key"></i> 创建API密钥</h3>
                <button class="modal-close" onclick="closeApiKeyModal()">&times;</button>
            </div>
            
            <div id="apiKeyForm">
                <div class="form-group">
                    <label class="form-label">密钥名称</label>
                    <input type="text" class="form-input" id="apiKeyName" placeholder="例如：我的应用">
                </div>
                
                <div class="form-group">
                    <label class="form-label">配额限制 (Token数)</label>
                    <input type="number" class="form-input" id="apiKeyQuota" value="100000" min="0" step="1000">
                    <span class="form-hint">0表示无限制</span>
                </div>
                
                <div style="display: flex; gap: 12px; margin-top: 24px;">
                    <button class="btn btn-primary" onclick="createApiKey()">
                        <i class="fas fa-plus"></i> 创建密钥
                    </button>
                    <button class="btn btn-secondary" onclick="closeApiKeyModal()">取消</button>
                </div>
            </div>
            
            <div id="newApiKeyDisplay" style="display: none; margin-top: 20px; padding: 16px; background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px;">
                <div style="color: #166534; font-weight: 600; margin-bottom: 8px;">
                    <i class="fas fa-check-circle"></i> 密钥创建成功！
                </div>
                <div style="font-size: 13px; color: #64748b; margin-bottom: 12px;">
                    请立即复制并保存此密钥，它只会显示一次：
                </div>
                <div style="display: flex; gap: 8px;">
                    <code id="newApiKeyValue" style="flex: 1; background: #dcfce7; padding: 12px; border-radius: 6px; word-break: break-all; font-size: 12px; user-select: all; cursor: text;"></code>
                    <button type="button" class="btn btn-secondary" id="copyApiKeyBtn" onclick="copyApiKeyWithFeedback(this)" title="复制到剪贴板" style="min-width: 60px;">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <div id="copyFeedback" style="margin-top: 8px; font-size: 12px; color: #166534; display: none;">
                    <i class="fas fa-check"></i> 已复制！
                </div>
            </div>
        </div>
    </div>

    <!-- 添加/编辑API提供商模态框 -->
    <div id="providerModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title" id="providerModalTitle"><i class="fas fa-server"></i> 添加API提供商</h3>
                <button class="modal-close" onclick="closeProviderModal()">&times;</button>
            </div>
            <input type="hidden" id="providerId">
            
            <div class="form-group">
                <label class="form-label">提供商类型</label>
                <select class="form-select" id="providerType" onchange="onProviderTypeChange()">
                    <optgroup label="本地部署">
                        <option value="ollama">Ollama</option>
                        <option value="llamacpp">llama.cpp</option>
                        <option value="vllm">vLLM</option>
                        <option value="xinference">Xinference</option>
                        <option value="gpustack">GPUStack</option>
                    </optgroup>
                    <optgroup label="在线服务">
                        <option value="openai">OpenAI</option>
                        <option value="azure_openai">Azure OpenAI</option>
                        <option value="anthropic">Anthropic Claude</option>
                        <option value="gemini">Google Gemini</option>
                        <option value="deepseek">DeepSeek</option>
                        <option value="hunyuan">腾讯混元</option>
                        <option value="zhipu">智谱AI</option>
                        <option value="qwen">通义千问</option>
                        <option value="moonshot">Moonshot</option>
                    </optgroup>
                    <optgroup label="其他">
                        <option value="custom_openai">自定义OpenAI</option>
                    </optgroup>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">显示名称</label>
                <input type="text" class="form-input" id="providerName" placeholder="例如: 我的OpenAI">
            </div>
            
            <div class="form-group">
                <label class="form-label">API地址</label>
                <input type="text" class="form-input" id="providerBaseUrl" placeholder="http://localhost:11434">
                <span class="form-hint" id="providerUrlHint">Ollama默认地址: http://localhost:11434</span>
            </div>
            
            <div class="form-group" id="providerApiKeyGroup">
                <label class="form-label">API密钥</label>
                <input type="password" class="form-input" id="providerApiKey" placeholder="sk-...">
                <span class="form-hint">在线服务需要API密钥，本地服务通常不需要</span>
            </div>
            
            <!-- 腾讯混元特有配置 -->
            <div id="hunyuanConfig" style="display: none; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 16px; background: #f8fafc;">
                <div style="font-weight: 600; color: #1a202c; margin-bottom: 12px;">
                    <i class="fab fa-qq" style="color: #00a1e9;"></i> 腾讯云认证配置
                </div>
                <div class="form-group">
                    <label class="form-label">SecretId</label>
                    <input type="text" class="form-input" id="hunyuanSecretId" placeholder="AKIDxxxxxxxxxxxxxxxxxxxx">
                    <span class="form-hint">腾讯云账号的 SecretId，<a href="https://console.cloud.tencent.com/cam/capi" target="_blank">在控制台获取</a></span>
                </div>
                <div class="form-group">
                    <label class="form-label">SecretKey</label>
                    <input type="password" class="form-input" id="hunyuanSecretKey" placeholder="xxxxxxxxxxxxxxxxxxxxxxxx">
                    <span class="form-hint">腾讯云账号的 SecretKey，用于API签名</span>
                </div>
                <div class="form-group">
                    <label class="form-label">地域</label>
                    <select class="form-select" id="hunyuanRegion">
                        <option value="ap-guangzhou">广州 (ap-guangzhou)</option>
                        <option value="ap-beijing">北京 (ap-beijing)</option>
                        <option value="ap-shanghai">上海 (ap-shanghai)</option>
                        <option value="ap-nanjing">南京 (ap-nanjing)</option>
                        <option value="ap-chengdu">成都 (ap-chengdu)</option>
                        <option value="ap-hongkong">香港 (ap-hongkong)</option>
                    </select>
                    <span class="form-hint">选择离您最近的地域以获得最佳性能</span>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">默认模型</label>
                    <input type="text" class="form-input" id="providerDefaultModel" placeholder="llama2">
                </div>
                <div class="form-group">
                    <label class="form-label">超时时间(秒)</label>
                    <input type="number" class="form-input" id="providerTimeout" value="120" min="10" max="600">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">温度(Temperature)</label>
                    <input type="number" class="form-input" id="providerTemperature" value="0.7" min="0" max="2" step="0.1">
                </div>
                <div class="form-group">
                    <label class="form-label">最大Token</label>
                    <input type="number" class="form-input" id="providerMaxTokens" value="2048" min="100" max="32000" step="100">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group" style="display: flex; align-items: center; gap: 12px;">
                    <label class="switch">
                        <input type="checkbox" id="providerEnabled" checked>
                        <span class="slider"></span>
                    </label>
                    <span>启用此提供商</span>
                </div>
                <div class="form-group" style="display: flex; align-items: center; gap: 12px;">
                    <label class="switch">
                        <input type="checkbox" id="providerIsDefault">
                        <span class="slider"></span>
                    </label>
                    <span>设为默认提供商</span>
                </div>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button class="btn btn-primary" onclick="saveProvider()">
                    <i class="fas fa-save"></i> 保存
                </button>
                <button class="btn btn-secondary" onclick="testProviderConnection()">
                    <i class="fas fa-plug"></i> 测试连接
                </button>
                <button class="btn btn-secondary" onclick="closeProviderModal()">取消</button>
            </div>
        </div>
    </div>

    <!-- 添加/编辑存储模态框 -->
    <div id="storageModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title" id="storageModalTitle"><i class="fas fa-hdd"></i> 添加存储</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <input type="hidden" id="storageId">
            
            <div class="form-group">
                <label class="form-label">存储名称</label>
                <input type="text" class="form-input" id="storageName" placeholder="例如: 本地存储、阿里云OSS">
            </div>
            
            <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">存储类型</label>
                        <select class="form-select" id="storageType" onchange="onStorageTypeChange()">
                            <option value="local">本地存储</option>
                            <option value="s3">Amazon S3</option>
                            <option value="oss">阿里云OSS</option>
                            <option value="cos">腾讯云COS</option>
                            <option value="minio">MinIO</option>
                            <option value="custom">自定义S3兼容存储</option>
                            <option value="ipsan">IP-SAN (iSCSI)</option>
                        </select>
                    </div>
                <div class="form-group">
                    <label class="form-label" style="display: flex; align-items: center; gap: 12px;">
                        设为默认
                        <label class="switch">
                            <input type="checkbox" id="storageIsDefault">
                            <span class="slider"></span>
                        </label>
                    </label>
                    <span class="form-hint">设为默认后，新模型将自动使用此存储</span>
                </div>
            </div>
            
            <!-- 本地存储配置 -->
            <div id="localStorageFields">
                <div class="form-group">
                    <label class="form-label">存储路径</label>
                    <input type="text" class="form-input" id="localPath" placeholder="例如: /storage/models/ 或 D:\\Models\\">
                    <span class="form-hint">模型文件将存储在此目录下，请确保有足够的磁盘空间</span>
                </div>
            </div>
            
                <!-- 云存储配置 -->
            <div id="cloudStorageFields" style="display: none;">
                <div class="form-group">
                    <label class="form-label">服务端点 (Endpoint)</label>
                    <input type="text" class="form-input" id="cloudEndpoint" placeholder="例如: https://s3.amazonaws.com 或 https://oss-cn-beijing.aliyuncs.com">
                    <span class="form-hint">S3 API 端点地址，自定义存储请填写自托管地址</span>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">存储桶 (Bucket)</label>
                        <input type="text" class="form-input" id="cloudBucket" placeholder="存储桶名称">
                    </div>
                    <div class="form-group">
                        <label class="form-label">区域 (Region)</label>
                        <input type="text" class="form-input" id="cloudRegion" placeholder="例如: us-east-1, cn-beijing">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Access Key</label>
                        <input type="text" class="form-input" id="cloudAccessKey" placeholder="访问密钥ID">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Secret Key</label>
                        <input type="password" class="form-input" id="cloudSecretKey" placeholder="访问密钥密码">
                    </div>
                </div>
                
                <!-- 自定义存储额外配置 -->
                <div id="customStorageFields" style="display: none;">
                    <div class="form-group">
                        <label class="form-label" style="display: flex; align-items: center; gap: 12px;">
                            使用 Path Style
                            <label class="switch">
                                <input type="checkbox" id="customPathStyle">
                                <span class="slider"></span>
                            </label>
                        </label>
                        <span class="form-hint">某些自托管存储（如MinIO）需要开启此选项</span>
                    </div>
                </div>
            </div>

            <!-- IP-SAN 配置 -->
            <div id="ipsanStorageFields" style="display: none;">
                <div class="alert" style="background: #eff6ff; border: 1px solid #3b82f6; color: #1e40af; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 13px;">
                    <i class="fas fa-info-circle"></i> IP-SAN (iSCSI) 配置用于连接企业级存储设备。确保服务器已安装 iSCSI initiator 工具。
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Target IP 地址</label>
                        <input type="text" class="form-input" id="ipsanIp" placeholder="例如: 192.168.1.100">
                        <span class="form-hint">iSCSI Target 服务器 IP</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">端口</label>
                        <input type="number" class="form-input" id="ipsanPort" value="3260" min="1" max="65535">
                        <span class="form-hint">默认端口 3260</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Target IQN</label>
                    <input type="text" class="form-input" id="ipsanIqn" placeholder="例如: iqn.2024-01.com.vendor:storage.target01">
                    <span class="form-hint">iSCSI Qualified Name，可从存储管理员获取</span>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">用户名 (CHAP)</label>
                        <input type="text" class="form-input" id="ipsanUsername" placeholder="单向CHAP认证用户名">
                        <span class="form-hint">如使用CHAP认证请填写</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">密码 (CHAP)</label>
                        <input type="password" class="form-input" id="ipsanPassword" placeholder="CHAP认证密码">
                        <span class="form-hint">单向CHAP认证密码</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">本地挂载路径</label>
                    <input type="text" class="form-input" id="ipsanMountPath" placeholder="例如: /mnt/ipsan/models/">
                    <span class="form-hint">iSCSI 卷将挂载到此目录，请确保目录存在且为空</span>
                </div>
                
                <div class="form-group">
                    <label class="form-label" style="display: flex; align-items: center; gap: 12px;">
                        双向CHAP认证 (Mutual CHAP)
                        <label class="switch">
                            <input type="checkbox" id="ipsanMutualChap">
                            <span class="slider"></span>
                        </label>
                    </label>
                    <span class="form-hint">开启后需要配置反向认证用户名和密码</span>
                </div>
                
                <div id="ipsanMutualChapFields" style="display: none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">反向认证用户名</label>
                            <input type="text" class="form-input" id="ipsanMutualUsername" placeholder="反向CHAP用户名">
                        </div>
                        <div class="form-group">
                            <label class="form-label">反向认证密码</label>
                            <input type="password" class="form-input" id="ipsanMutualPassword" placeholder="反向CHAP密码">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">备注说明</label>
                <textarea class="form-input" id="storageNotes" rows="2" placeholder="可选，添加关于此存储的备注"></textarea>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button class="btn btn-primary" onclick="saveStorage()">
                    <i class="fas fa-save"></i> 保存
                </button>
                <button class="btn btn-secondary" onclick="testStorageConnection()">
                    <i class="fas fa-plug"></i> 测试连接
                </button>
                <button class="btn btn-secondary" onclick="closeModal()">取消</button>
            </div>
        </div>
    </div>

    <!-- 清除任务模态框 -->
    <div id="clearTasksModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i> 清除训练任务</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="form-group">
                <label class="form-label">选择要清除的任务类型</label>
                <select class="form-select" id="clearTaskType">
                    <option value="completed">已完成的任务</option>
                    <option value="failed">失败的任务</option>
                    <option value="stopped">已停止的任务</option>
                    <option value="all" style="color: #ef4444;">所有任务（包括正在运行的）</option>
                </select>
            </div>
            <div class="alert" style="background: #fef2f2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 13px;">
                <i class="fas fa-info-circle"></i> 此操作不可恢复，请谨慎操作！
            </div>
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button class="btn btn-danger" onclick="confirmClearTasks()">
                    <i class="fas fa-trash-alt"></i> 确认清除
                </button>
                <button class="btn btn-secondary" onclick="closeModal()">取消</button>
            </div>
        </div>
    </div>

    <!-- 训练日志查看模态框 -->
    <div id="trainingLogModal" class="modal">
        <div class="modal-content" style="max-width: 900px; max-height: 80vh; display: flex; flex-direction: column;">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-file-alt"></i> 训练日志查看</h3>
                <button class="modal-close" onclick="closeTrainingLogModal()">&times;</button>
            </div>
            <div style="padding: 20px; overflow-y: auto; flex: 1;">
                <div id="trainingLogInfo" style="margin-bottom: 16px; padding: 12px; background: #f8fafc; border-radius: 8px;">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; font-size: 13px;">
                        <div><strong>任务ID:</strong> <span id="logTaskId">-</span></div>
                        <div><strong>模型:</strong> <span id="logModel">-</span></div>
                        <div><strong>状态:</strong> <span id="logStatus">-</span></div>
                        <div><strong>Epochs:</strong> <span id="logEpochs">-</span></div>
                        <div><strong>学习率:</strong> <span id="logLR">-</span></div>
                        <div><strong>创建时间:</strong> <span id="logCreated">-</span></div>
                    </div>
                </div>
                
                <!-- 日志筛选 -->
                <div style="margin-bottom: 16px; display: flex; gap: 8px;">
                    <button class="btn btn-sm" onclick="filterLogs('all')" id="filterAll" style="background: #4c51bf; color: white;">全部</button>
                    <button class="btn btn-sm btn-secondary" onclick="filterLogs('info')" id="filterInfo">信息</button>
                    <button class="btn btn-sm btn-secondary" onclick="filterLogs('error')" id="filterError">错误</button>
                    <button class="btn btn-sm btn-secondary" onclick="filterLogs('warning')" id="filterWarning">警告</button>
                    <button class="btn btn-sm btn-secondary" onclick="downloadLog()" style="margin-left: auto;">
                        <i class="fas fa-download"></i> 下载日志
                    </button>
                </div>
                
                <!-- 日志内容 -->
                <div id="trainingLogContent" style="background: #1a202c; color: #e2e8f0; padding: 16px; border-radius: 8px; font-family: 'Consolas', 'Monaco', monospace; font-size: 13px; line-height: 1.6; max-height: 400px; overflow-y: auto;">
                    <div style="color: #64748b; text-align: center;">加载中...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 系统错误日志模态框 -->
    <div id="systemErrorLogModal" class="modal">
        <div class="modal-content" style="max-width: 1000px; max-height: 85vh; display: flex; flex-direction: column;">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i> 系统错误日志</h3>
                <button class="modal-close" onclick="closeSystemErrorLogModal()">&times;</button>
            </div>
            <div style="padding: 20px; overflow-y: auto; flex: 1;">
                <!-- 统计信息 -->
                <div style="margin-bottom: 16px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
                    <div style="padding: 12px; background: #fee2e2; border-radius: 8px; text-align: center;">
                        <div style="font-size: 24px; font-weight: 600; color: #dc2626;" id="errorCountTotal">0</div>
                        <div style="font-size: 12px; color: #991b1b;">总错误数</div>
                    </div>
                    <div style="padding: 12px; background: #fef3c7; border-radius: 8px; text-align: center;">
                        <div style="font-size: 24px; font-weight: 600; color: #d97706;" id="errorCountToday">0</div>
                        <div style="font-size: 12px; color: #92400e;">今日错误</div>
                    </div>
                    <div style="padding: 12px; background: #dbeafe; border-radius: 8px; text-align: center;">
                        <div style="font-size: 24px; font-weight: 600; color: #2563eb;" id="failedTaskCount">0</div>
                        <div style="font-size: 12px; color: #1e40af;">失败任务</div>
                    </div>
                    <div style="padding: 12px; background: #dcfce7; border-radius: 8px; text-align: center;">
                        <div style="font-size: 24px; font-weight: 600; color: #16a34a;" id="successRate">0%</div>
                        <div style="font-size: 12px; color: #166534;">成功率</div>
                    </div>
                </div>
                
                <!-- 失败任务列表 -->
                <div style="margin-bottom: 20px;">
                    <h4 style="margin-bottom: 12px; color: #1a202c;"><i class="fas fa-tasks"></i> 近期失败任务</h4>
                    <div id="failedTasksList" style="max-height: 200px; overflow-y: auto;">
                        <div style="color: #64748b; text-align: center; padding: 20px;">暂无失败任务</div>
                    </div>
                </div>
                
                <!-- 错误日志详情 -->
                <div>
                    <h4 style="margin-bottom: 12px; color: #1a202c;"><i class="fas fa-bug"></i> 错误日志详情</h4>
                    <div id="systemErrorLogContent" style="background: #1a202c; color: #e2e8f0; padding: 16px; border-radius: 8px; font-family: 'Consolas', 'Monaco', monospace; font-size: 12px; line-height: 1.6; max-height: 300px; overflow-y: auto;">
                        <div style="color: #64748b; text-align: center;">加载中...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 添加模型模态框 -->
    <div id="addModelModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">添加在线模型</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="form-group">
                <label class="form-label">模型名称</label>
                <input type="text" class="form-input" id="newModelName" placeholder="例如: GPT-4">
            </div>
            <div class="form-group">
                <label class="form-label">模型ID</label>
                <input type="text" class="form-input" id="newModelId" placeholder="例如: gpt-4">
            </div>
            <div class="form-group">
                <label class="form-label">所属API</label>
                <select class="form-select" id="newModelApi">
                    <option value="">加载中...</option>
                </select>
                <span class="form-hint">从AI服务管理的提供商自动同步</span>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button class="btn btn-primary" onclick="addOnlineModel()">
                    <i class="fas fa-plus"></i> 添加
                </button>
                <button class="btn btn-secondary" onclick="closeModal()">取消</button>
            </div>
        </div>
    </div>

    <!-- 编辑模型模态框 -->
    <div id="editModelModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">编辑在线模型</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <input type="hidden" id="editModelOldId">
            <div class="form-group">
                <label class="form-label">模型名称</label>
                <input type="text" class="form-input" id="editModelName" placeholder="例如: GPT-4">
            </div>
            <div class="form-group">
                <label class="form-label">模型ID</label>
                <input type="text" class="form-input" id="editModelId" placeholder="例如: gpt-4">
            </div>
            <div class="form-group">
                <label class="form-label">所属API</label>
                <select class="form-select" id="editModelApi">
                    <option value="">加载中...</option>
                </select>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button class="btn btn-primary" onclick="updateOnlineModel()">
                    <i class="fas fa-save"></i> 保存
                </button>
                <button class="btn btn-secondary" onclick="closeModal()">取消</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // 页面切换
        // 下拉菜单切换
        function toggleDropdown(dropdownId) {
            const dropdown = document.getElementById(dropdownId);
            dropdown.classList.toggle('open');
        }

        // 显示子页面（下拉菜单中的页面）
        function showSubPage(parent, subPage) {
            // 更新下拉菜单样式
            $('.dropdown-item').removeClass('active');
            $(event.target).closest('.dropdown-item').addClass('active');
            
            // 隐藏所有页面
            $('.page-content').hide();
            
            // 根据子页面显示对应内容
            if (subPage === 'settings') {
                $('#page-system').show();
            } else if (subPage === 'status') {
                $('#page-status').show();
                loadSystemStatusData();
            } else if (subPage === 'logs') {
                $('#page-logs').show();
                loadSystemLogsData();
            }
        }

        function disableLegacyLocalModelUI() {
            $('#tab-local').hide().removeClass('active');
            $('#tab-online').addClass('active');

            const modelTabs = $('#page-models .tabs .tab');
            if (modelTabs.length > 0) {
                $(modelTabs[0]).hide().removeClass('active');
                if (modelTabs.length > 1) {
                    $(modelTabs[1]).addClass('active');
                }
            }

            const quickOllamaCard = $('#quickOllamaUrl').closest('.card');
            if (quickOllamaCard.length) {
                quickOllamaCard.hide();
            }

            $('#providerType option[value="ollama"]').remove();
            $('#localProviders').closest('.stat-card').hide();
        }

        function showPage(page) {
            $('.sidebar-item').removeClass('active');
            $(`.sidebar-item:contains('${getPageName(page)}')`).addClass('active');
            $('.page-content').hide();
            $(`#page-${page}`).show();
            disableLegacyLocalModelUI();

            if (page === 'models') {
                loadOnlineModels();
            } else if (page === 'users') {
                loadUsers(1);
            } else if (page === 'usage') {
                loadUsageStats();
            } else if (page === 'knowledge') {
                loadKnowledgeBase();
            } else if (page === 'storage') {
                loadStorageList();
                loadStorageOverview();
            } else if (page === 'ai-services') {
                loadProviders();
                loadProviderStats();
                setTimeout(() => {
                    loadApiKeys();
                }, 100);
            } else if (page === 'billing') {
                loadBillingStats();
            } else if (page === 'logs') {
                refreshLogs();
            }
        }

        function getPageName(page) {
            const names = {
                'models': '模型管理',
                'users': '用户管理',
                'usage': '用量统计',
                'ai-services': 'AI服务管理',
                'knowledge': '知识库管理',
                'storage': '存储管理',
                'billing': '计费管理',
                'system': '系统设置',
                'logs': '日志查看'
            };
            return names[page] || '';
        }

        // ========== 用量统计功能 ==========
        
        // 加载系统用量统计
        function loadUsageStats() {
            const startDate = $('#usageStartDate').val();
            const endDate = $('#usageEndDate').val();
            
            // 加载系统总体统计
            $.get('api/admin_handler.php?action=getSystemUsageStats&start_date=' + startDate + '&end_date=' + endDate, function(response) {
                if (response.status === 'success') {
                    const stats = response.data.summary;
                    $('#totalRequests').text((stats.total_requests || 0).toLocaleString());
                    $('#activeUsers').text((stats.active_users || 0).toLocaleString());
                    $('#totalTokens').text((stats.total_tokens || 0).toLocaleString());
                    $('#totalCost').text('¥' + parseFloat(stats.total_cost || 0).toFixed(2));
                    
                    // 渲染每日趋势
                    renderDailyUsageChart(response.data.daily_stats);
                }
            });
            
            // 加载用户用量列表
            $.get('api/admin_handler.php?action=getAllUsersUsage&start_date=' + startDate + '&end_date=' + endDate, function(response) {
                if (response.status === 'success') {
                    renderUsersUsageTable(response.data);
                }
            });
        }
        
        // 渲染用户用量表格
        function renderUsersUsageTable(users) {
            console.log('renderUsersUsageTable called with users:', users);
            const tbody = $('#usersUsageTableBody');
            tbody.empty();
            
            if (!users || users.length === 0) {
                tbody.append('<tr><td colspan="9" style="text-align: center; padding: 40px; color: #94a3b8;">暂无数据</td></tr>');
                return;
            }
            
            users.forEach(user => {
                console.log('Rendering user:', user.username, 'user_id:', user.user_id);
                const row = `
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 12px;">${user.username || '-'}</td>
                        <td style="padding: 12px;">${user.email || '-'}</td>
                        <td style="padding: 12px; text-align: center;">${(user.total_requests || 0).toLocaleString()}</td>
                        <td style="padding: 12px; text-align: center;">${(user.total_input_tokens || 0).toLocaleString()}</td>
                        <td style="padding: 12px; text-align: center;">${(user.total_output_tokens || 0).toLocaleString()}</td>
                        <td style="padding: 12px; text-align: center; font-weight: 600;">${(user.total_tokens || 0).toLocaleString()}</td>
                        <td style="padding: 12px; text-align: center;">${user.active_days || 0}</td>
                        <td style="padding: 12px; text-align: right;">¥${parseFloat(user.total_cost || 0).toFixed(4)}</td>
                        <td style="padding: 12px; text-align: center;">
                            <button class="btn btn-secondary btn-sm" onclick='showUserUsageDetail(${user.user_id}, "${user.username}")'>
                                <i class="fas fa-chart-bar"></i> 详情
                            </button>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        }
        
        // 渲染每日用量趋势
        function renderDailyUsageChart(dailyStats) {
            const container = $('#dailyUsageChart');
            
            if (!dailyStats || dailyStats.length === 0) {
                container.html('<div style="color: #94a3b8;">暂无数据</div>');
                return;
            }
            
            // 反转数据，按时间正序显示
            const sortedStats = [...dailyStats].reverse();
            const maxTokens = Math.max(...sortedStats.map(s => s.tokens || 0));
            
            // 创建简单的柱状图
            let html = '<div style="display: flex; align-items: flex-end; justify-content: space-between; height: 250px; padding: 20px; gap: 4px;">';
            
            sortedStats.forEach(stat => {
                const height = maxTokens > 0 ? (stat.tokens / maxTokens * 200) : 0;
                const date = new Date(stat.date).toLocaleDateString('zh-CN', { month: 'short', day: 'numeric' });
                
                html += `
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; min-width: 30px;">
                        <div style="background: linear-gradient(to top, #667eea, #764ba2); width: 100%; max-width: 40px; border-radius: 4px 4px 0 0; transition: all 0.3s;"
                             title="${date}: ${(stat.tokens || 0).toLocaleString()} Token\n请求: ${stat.request_count || 0}\n活跃用户: ${stat.active_users || 0}"
                             onmouseover="this.style.opacity='0.8'" 
                             onmouseout="this.style.opacity='1'"
                             style="height: ${height}px; background: linear-gradient(to top, #667eea, #764ba2); width: 100%; max-width: 40px; border-radius: 4px 4px 0 0;">
                        </div>
                        <div style="font-size: 10px; color: #64748b; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; text-align: center;">${date}</div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.html(html);
        }
        
        // 显示用户用量详情
        function showUserUsageDetail(userId, username) {
            console.log('showUserUsageDetail called:', userId, username);
            const startDate = $('#usageStartDate').val();
            const endDate = $('#usageEndDate').val();
            
            $('#userUsageDetailModal').addClass('show');
            $('#userUsageDetailContent').html('<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>');
            
            // 加载用户统计
            $.get('api/admin_handler.php?action=getUserUsageStats&user_id=' + userId + '&start_date=' + startDate + '&end_date=' + endDate, function(response) {
                console.log('getUserUsageStats response:', response);
                if (response.status === 'success') {
                    renderUserUsageDetail(userId, username, response.data);
                } else {
                    $('#userUsageDetailContent').html('<div style="text-align: center; padding: 40px; color: #ef4444;">加载失败: ' + (response.message || '未知错误') + '</div>');
                }
            }).fail(function(xhr, status, error) {
                console.error('getUserUsageStats error:', error);
                $('#userUsageDetailContent').html('<div style="text-align: center; padding: 40px; color: #ef4444;">请求失败: ' + error + '</div>');
            });
        }
        
        // 渲染用户用量详情
        function renderUserUsageDetail(userId, username, data) {
            const summary = data.summary;
            
            let html = `
                <div style="margin-bottom: 24px;">
                    <h4 style="margin-bottom: 16px; color: #1a202c;">${username} 的用量统计</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px;">
                        <div style="background: #f8fafc; padding: 16px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 24px; font-weight: 600; color: #667eea;">${(summary.total_requests || 0).toLocaleString()}</div>
                            <div style="font-size: 12px; color: #64748b;">总请求数</div>
                        </div>
                        <div style="background: #f8fafc; padding: 16px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 24px; font-weight: 600; color: #667eea;">${(summary.total_tokens || 0).toLocaleString()}</div>
                            <div style="font-size: 12px; color: #64748b;">总Token数</div>
                        </div>
                        <div style="background: #f8fafc; padding: 16px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 24px; font-weight: 600; color: #667eea;">${summary.active_days || 0}</div>
                            <div style="font-size: 12px; color: #64748b;">活跃天数</div>
                        </div>
                        <div style="background: #f8fafc; padding: 16px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 24px; font-weight: 600; color: #667eea;">¥${parseFloat(summary.total_cost || 0).toFixed(4)}</div>
                            <div style="font-size: 12px; color: #64748b;">预估费用</div>
                        </div>
                    </div>
                </div>
            `;
            
            // 按模型统计
            if (data.by_model && data.by_model.length > 0) {
                html += `
                    <div style="margin-bottom: 24px;">
                        <h5 style="margin-bottom: 12px; color: #1a202c;">按模型统计</h5>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8fafc;">
                                    <th style="padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0;">模型</th>
                                    <th style="padding: 10px; text-align: center; border-bottom: 1px solid #e2e8f0;">请求数</th>
                                    <th style="padding: 10px; text-align: center; border-bottom: 1px solid #e2e8f0;">Token数</th>
                                    <th style="padding: 10px; text-align: right; border-bottom: 1px solid #e2e8f0;">费用</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                data.by_model.forEach(model => {
                    html += `
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px;">${model.model || '-'}</td>
                            <td style="padding: 10px; text-align: center;">${(model.request_count || 0).toLocaleString()}</td>
                            <td style="padding: 10px; text-align: center;">${(model.tokens || 0).toLocaleString()}</td>
                            <td style="padding: 10px; text-align: right;">¥${parseFloat(model.cost || 0).toFixed(4)}</td>
                        </tr>
                    `;
                });
                
                html += '</tbody></table></div>';
            }
            
            // 按日期统计
            if (data.by_date && data.by_date.length > 0) {
                html += `
                    <div>
                        <h5 style="margin-bottom: 12px; color: #1a202c;">每日用量</h5>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8fafc;">
                                    <th style="padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0;">日期</th>
                                    <th style="padding: 10px; text-align: center; border-bottom: 1px solid #e2e8f0;">请求数</th>
                                    <th style="padding: 10px; text-align: center; border-bottom: 1px solid #e2e8f0;">Token数</th>
                                    <th style="padding: 10px; text-align: right; border-bottom: 1px solid #e2e8f0;">费用</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                data.by_date.forEach(date => {
                    html += `
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px;">${date.date}</td>
                            <td style="padding: 10px; text-align: center;">${(date.request_count || 0).toLocaleString()}</td>
                            <td style="padding: 10px; text-align: center;">${(date.tokens || 0).toLocaleString()}</td>
                            <td style="padding: 10px; text-align: right;">¥${parseFloat(date.cost || 0).toFixed(4)}</td>
                        </tr>
                    `;
                });
                
                html += '</tbody></table></div>';
            }
            
            $('#userUsageDetailContent').html(html);
        }
        
        // 关闭用户用量详情模态框
        function closeUserUsageDetailModal() {
            console.log('Closing user usage detail modal');
            $('#userUsageDetailModal').removeClass('show');
        }
        
        // 点击模态框背景关闭
        $('#userUsageDetailModal').on('click', function(e) {
            if (e.target === this) {
                closeUserUsageDetailModal();
            }
        });
        
        // 加载用户列表
        let currentUserPage = 1;
        function loadUsers(page) {
            currentUserPage = page;
            $.get('api/user_handler.php?action=getAllUsers&page=' + page, function(response) {
                const container = $('#userList');
                container.empty();

                if (response.status === 'success' && response.users.length > 0) {
                    response.users.forEach(user => {
                        const roleBadge = user.role === 'admin' 
                            ? '<span class="model-badge online"><i class="fas fa-shield-alt"></i> 管理员</span>'
                            : '<span class="model-badge local"><i class="fas fa-user"></i> 普通用户</span>';
                        
                        const statusBadge = user.is_active 
                            ? '<span style="color: #10b981;"><i class="fas fa-check-circle"></i> 正常</span>'
                            : '<span style="color: #ef4444;"><i class="fas fa-ban"></i> 禁用</span>';

                        container.append(
                            '<div class="model-item">' +
                                '<div class="model-info">' +
                                    '<div class="model-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">' +
                                        '<i class="fas fa-user"></i>' +
                                    '</div>' +
                                    '<div class="model-details">' +
                                        '<h4>' + user.username + '</h4>' +
                                        '<p>' + user.email + ' | 注册: ' + new Date(user.created_at).toLocaleDateString() + '</p>' +
                                    '</div>' +
                                    roleBadge +
                                '</div>' +
                                '<div class="model-actions">' +
                                    statusBadge +
                                    '<button class="btn btn-primary btn-sm" onclick="managePermissions(' + user.id + ', \'' + user.username + '\')" style="margin-left: 8px;" title="权限分配">' +
                                        '<i class="fas fa-key"></i>' +
                                    '</button>' +
                                    '<button class="btn btn-secondary btn-sm" onclick="editUser(' + user.id + ')" style="margin-left: 8px;" title="编辑用户">' +
                                        '<i class="fas fa-edit"></i>' +
                                    '</button>' +
                                    '<button class="btn btn-danger btn-sm" onclick="deleteUser(' + user.id + ')" style="margin-left: 8px;" title="删除用户">' +
                                        '<i class="fas fa-trash"></i>' +
                                    '</button>' +
                                '</div>' +
                            '</div>'
                        );
                    });

                    // 分页
                    renderPagination(response.total_pages, response.page);
                } else {
                    container.html('<div style="color: #64748b; text-align: center; padding: 40px;">暂无用户</div>');
                }
            });
        }

        // 渲染分页
        function renderPagination(totalPages, currentPage) {
            const container = $('#userPagination');
            let html = '';
            
            for (let i = 1; i <= totalPages; i++) {
                const active = i === currentPage ? 'background: #4c51bf; color: white;' : 'background: #f3f4f6; color: #4a5568;';
                html += '<button onclick="loadUsers(' + i + ')" style="padding: 8px 14px; border: none; border-radius: 6px; cursor: pointer; ' + active + '">' + i + '</button>';
            }
            
            container.html(html);
        }

        // 显示添加用户模态框
        function showAddUserModal() {
            $('#addUserModal').addClass('show');
        }

        // 添加用户
        function addUser() {
            const data = {
                action: 'createUser',
                username: $('#newUsername').val(),
                password: $('#newUserPassword').val(),
                email: $('#newUserEmail').val(),
                role: $('#newUserRole').val()
            };

            $.post('api/user_handler.php', data, function(response) {
                if (response.status === 'success') {
                    closeModal();
                    loadUsers(currentUserPage);
                    showToast('用户已创建', 'success');
                    // 清空表单
                    $('#newUsername, #newUserPassword, #newUserEmail').val('');
                } else {
                    showToast('创建失败: ' + response.message, 'error');
                }
            });
        }

        // 当前编辑的用户数据
        let currentEditUser = null;

        // 编辑用户
        function editUser(userId) {
            // 从当前列表中找到用户数据
            $.get('api/user_handler.php?action=getAllUsers&page=' + currentUserPage, function(response) {
                if (response.status === 'success' && response.users.length > 0) {
                    const user = response.users.find(u => u.id === userId);
                    if (user) {
                        currentEditUser = user;
                        // 填充表单
                        $('#editUserId').val(user.id);
                        $('#editUsername').val(user.username);
                        $('#editUserEmail').val(user.email);
                        $('#editUserRole').val(user.role);
                        $('#editUserStatus').val(user.is_active ? '1' : '0');
                        $('#editUserNewPassword').val('');
                        
                        // 显示模态框
                        $('#editUserModal').addClass('show');
                    } else {
                        showToast('未找到用户信息', 'error');
                    }
                }
            });
        }

        // 保存用户编辑
        function saveUserEdit() {
            const userId = $('#editUserId').val();
            const email = $('#editUserEmail').val().trim();
            const role = $('#editUserRole').val();
            const isActive = $('#editUserStatus').val();
            const newPassword = $('#editUserNewPassword').val();

            // 验证邮箱
            if (!email || !email.includes('@')) {
                showToast('请输入有效的邮箱地址', 'error');
                return;
            }

            // 构建更新数据
            const updateData = {
                action: 'updateUser',
                user_id: userId,
                email: email,
                role: role,
                is_active: isActive
            };

            // 先更新基本信息
            $.post('api/user_handler.php', updateData, function(response) {
                if (response.status === 'success') {
                    // 如果有新密码，则重置密码
                    if (newPassword && newPassword.length >= 6) {
                        $.post('api/user_handler.php', {
                            action: 'resetPassword',
                            user_id: userId,
                            new_password: newPassword
                        }, function(pwdResponse) {
                            if (pwdResponse.status === 'success') {
                                showToast('用户信息及密码已更新', 'success');
                            } else {
                                showToast('用户信息已更新，但密码重置失败: ' + pwdResponse.message, 'warning');
                            }
                            closeModal();
                            loadUsers(currentUserPage);
                        });
                    } else {
                        showToast('用户信息已更新', 'success');
                        closeModal();
                        loadUsers(currentUserPage);
                    }
                } else {
                    showToast('更新失败: ' + response.message, 'error');
                }
            }).fail(function() {
                showToast('请求失败，请检查网络连接', 'error');
            });
        }

        // 删除用户
        function deleteUser(userId) {
            if (!confirm('确定要删除这个用户吗？此操作不可恢复！')) return;

            $.post('api/user_handler.php', {
                action: 'deleteUser',
                user_id: userId
            }, function(response) {
                if (response.status === 'success') {
                    loadUsers(currentUserPage);
                    showToast('用户已删除', 'success');
                } else {
                    showToast('删除失败: ' + response.message, 'error');
                }
            });
        }

        // 标签页切换
        function switchTab(tab) {
            if (tab === 'local') {
                tab = 'online';
            }
            $('.tab').removeClass('active');
            $(`.tab:contains('${tab === 'local' ? '本地模型' : '在线API'}')`).addClass('active');
            $('.tab-content').removeClass('active');
            $(`#tab-${tab}`).addClass('active');
        }

        // 加载本地模型
        function loadLocalModels() {
            $.get('api/api_handler.php?request=models', function(response) {
                const container = $('#localModelList');
                container.empty();

                if (response.status === 'success' && response.models) {
                    Object.entries(response.models).forEach(([id, model]) => {
                        // 支持新的数据结构（对象）和旧的数据结构（字符串）
                        let modelName, parameterSize, quantization, modelSize;
                        
                        if (typeof model === 'object' && model !== null) {
                            // 新数据结构：模型详细信息对象
                            modelName = model.name || id;
                            parameterSize = model.parameter_size || '未知';
                            quantization = model.quantization || '未知';
                            modelSize = model.size || '未知';
                        } else {
                            // 旧数据结构：模型名称字符串
                            modelName = model;
                            parameterSize = '未知';
                            quantization = '未知';
                            modelSize = '未知';
                        }
                        
                        const isDefault = modelName.includes('⭐');
                        container.append(
                            '<div class="model-item">' +
                                '<div class="model-info">' +
                                    '<div class="model-icon local"><i class="fas fa-server"></i></div>' +
                                    '<div class="model-details">' +
                                        '<h4>' + modelName.replace(' ⭐', '') + '</h4>' +
                                        '<p>参数量: ' + parameterSize + ' | 量化: ' + quantization + ' | 大小: ' + modelSize + '</p>' +
                                    '</div>' +
                                    '<span class="model-badge local"><i class="fas fa-home"></i> 本地</span>' +
                                '</div>' +
                                '<div class="model-actions">' +
                                    (isDefault ? '<span style="color: #4c51bf; font-size: 13px;"><i class="fas fa-check-circle"></i> 默认</span>' : '') +
                                '</div>' +
                            '</div>'
                        );
                    });
                } else {
                    container.html('<div style="color: #64748b; text-align: center; padding: 40px;">未检测到本地模型</div>');
                }
            }).fail(function() {
                container.html('<div style="color: #ef4444; text-align: center; padding: 40px;">加载模型列表失败，请检查Ollama服务</div>');
            });
        }

        // 加载在线模型
        function loadOnlineModels() {
            console.log('Loading online models...');
            
            // 加载云端API模型
            loadCloudModels();
            
            // 加载手动添加的模型
            loadCustomOnlineModels();
        }
        
        // 加载云端API模型
        function loadCloudModels() {
            const container = $('#cloudModelList');
            container.html('<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>');
            
            $.get('api/providers_handler.php?action=get_providers&enabled=1', function(response) {
                console.log('Cloud models response:', response);
                container.empty();
                
                if (response.success && response.data && response.data.length > 0) {
                    const providers = response.data;
                    let hasCloudModels = false;
                    
                    // 过滤掉本地提供商类型（ollama, llamacpp, vllm等）
                    const localTypes = ['ollama', 'llamacpp', 'vllm', 'xinference', 'gpustack'];
                    
                    providers.forEach(provider => {
                        // 跳过本地部署的提供商
                        if (localTypes.includes(provider.type)) {
                            return;
                        }
                        
                        const models = provider.models || [];
                        if (models.length > 0) {
                            hasCloudModels = true;
                            models.forEach(modelId => {
                                const isDefault = provider.is_default ? '<span style="color: #f59e0b; margin-left: 8px;"><i class="fas fa-star"></i> 默认</span>' : '';
                                container.append(
                                    '<div class="model-item">' +
                                        '<div class="model-info">' +
                                            '<div class="model-icon online" style="background: linear-gradient(135deg, #10b981, #059669);"><i class="fas fa-cloud"></i></div>' +
                                            '<div class="model-details">' +
                                                '<h4>' + modelId + isDefault + '</h4>' +
                                                '<p>来源: ' + (provider.name || provider.id) + ' (' + (provider.type || 'unknown') + ')</p>' +
                                            '</div>' +
                                            '<span class="model-badge online" style="background: linear-gradient(135deg, #10b981, #059669);"><i class="fas fa-server"></i> 云端API</span>' +
                                        '</div>' +
                                        '<div class="model-actions">' +
                                            '<span style="color: #64748b; font-size: 13px;">自动获取</span>' +
                                        '</div>' +
                                    '</div>'
                                );
                            });
                        }
                    });
                    
                    if (!hasCloudModels) {
                        container.html('<div style="color: #64748b; text-align: center; padding: 40px;">暂无云端API模型<br><small>请在"AI服务管理"中添加云端提供商并点击"刷新模型列表"</small></div>');
                    }
                } else {
                    container.html('<div style="color: #64748b; text-align: center; padding: 40px;">暂无云端API提供商<br><small>请在"AI服务管理"中添加云端提供商（如腾讯混元、DeepSeek、OpenAI等）</small></div>');
                }
            }).fail(function() {
                container.html('<div style="color: #ef4444; text-align: center; padding: 40px;">加载云端API模型失败</div>');
            });
        }
        
        // 加载手动添加的在线模型
        function loadCustomOnlineModels() {
            const container = $('#onlineModelList');
            container.html('<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>');
            
            $.get('api/admin_handler.php?action=getOnlineModels', function(response) {
                console.log('Custom models response:', response);
                container.empty();
                
                if (response.status === 'success' && response.models && response.models.length > 0) {
                    response.models.forEach(model => {
                        container.append(
                            '<div class="model-item">' +
                                '<div class="model-info">' +
                                    '<div class="model-icon online"><i class="fas fa-cloud"></i></div>' +
                                    '<div class="model-details">' +
                                        '<h4>' + model.name + '</h4>' +
                                        '<p>ID: ' + model.id + ' | API: ' + model.api_type + '</p>' +
                                    '</div>' +
                                    '<span class="model-badge online"><i class="fas fa-globe"></i> 在线</span>' +
                                '</div>' +
                                '<div class="model-actions">' +
                                    '<button class="btn btn-secondary btn-sm" onclick="editOnlineModel(\'' + model.id + '\')">' +
                                        '<i class="fas fa-edit"></i>' +
                                    '</button>' +
                                    '<button class="btn btn-danger btn-sm" onclick="deleteOnlineModel(\'' + model.id + '\')">' +
                                        '<i class="fas fa-trash"></i>' +
                                    '</button>' +
                                '</div>' +
                            '</div>'
                        );
                    });
                } else {
                    container.html('<div style="color: #64748b; text-align: center; padding: 40px;">暂无自定义模型<br><small>点击右上角添加自定义模型</small></div>');
                }
            }).fail(function() {
                container.html('<div style="color: #ef4444; text-align: center; padding: 40px;">加载自定义模型失败</div>');
            });
        }

        // 刷新Ollama模型
        function refreshOllamaModels() {
            const btn = event.target.closest('button');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 刷新中...';

            $.get('api/api_handler.php?request=models', function(response) {
                loadLocalModels();
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sync-alt"></i> 刷新模型列表';
                showToast('模型列表已更新', 'success');
            });
        }

        // 保存Ollama配置
        function saveOllamaConfig() {
            const data = {
                action: 'saveOllamaConfig',
                base_url: $('#ollamaUrl').val(),
                default_model: $('#ollamaDefaultModel').val(),
                temperature: parseFloat($('#ollamaTemp').val()),
                max_tokens: parseInt($('#ollamaMaxTokens').val())
            };

            $.post('api/admin_handler.php', data, function(response) {
                if (response.status === 'success') {
                    showToast('配置已保存', 'success');
                } else {
                    showToast('保存失败: ' + response.message, 'error');
                }
            });
        }

        // 测试Ollama连接
        function testOllamaConnection() {
            $.get('api/api_handler.php?request=testOllama', function(response) {
                if (response.status === 'success') {
                    showToast('连接成功！', 'success');
                } else {
                    showToast('连接失败: ' + response.message, 'error');
                }
            });
        }

        // 保存在线API配置
        function saveOnlineApiConfig() {
            const data = {
                action: 'saveOnlineApiConfig',
                api_type: $('#onlineApiType').val(),
                api_key: $('#onlineApiKey').val(),
                base_url: $('#onlineApiUrl').val()
            };

            $.post('api/admin_handler.php', data, function(response) {
                if (response.status === 'success') {
                    showToast('配置已保存', 'success');
                } else {
                    showToast('保存失败: ' + response.message, 'error');
                }
            });
        }

        // 保存GPUStack配置
        function saveGpuStackConfig() {
            const data = {
                action: 'saveGpuStackConfig',
                enabled: $('#gpustackEnabled').is(':checked'),
                base_url: $('#gpustackUrl').val(),
                api_key: $('#gpustackApiKey').val(),
                default_model: $('#gpustackDefaultModel').val(),
                temperature: parseFloat($('#gpustackTemp').val()),
                max_tokens: parseInt($('#gpustackMaxTokens').val())
            };

            $.post('api/admin_handler.php', data, function(response) {
                if (response.status === 'success') {
                    showToast('GPUStack配置已保存', 'success');
                } else {
                    showToast('保存失败: ' + response.message, 'error');
                }
            });
        }

        // 测试GPUStack连接
        function testGpuStackConnection() {
            $.get('api/admin_handler.php?action=testGpuStackConnection', function(response) {
                if (response.status === 'success') {
                    showToast(response.message, 'success');
                } else {
                    showToast('连接失败: ' + response.message, 'error');
                }
            });
        }

        // ========== AI服务管理：快速配置功能 ==========

        // 加载Ollama配置到快速配置区域
        function loadOllamaConfig() {
            // 配置已直接通过PHP输出到input的value中
            // 此函数用于页面加载时的额外操作
        }

        // 快速测试Ollama连接
        function testQuickOllama() {
            const url = $('#quickOllamaUrl').val();
            $.ajax({
                url: url + '/api/tags',
                method: 'GET',
                timeout: 10000,
                success: function() {
                    showToast('Ollama连接成功！', 'success');
                },
                error: function() {
                    showToast('连接失败，请检查Ollama是否运行', 'error');
                }
            });
        }

        // 一键添加Ollama为提供商
        function quickSetupOllama() {
            const url = $('#quickOllamaUrl').val();
            const model = $('#quickOllamaModel').val();

            if (!url) {
                showToast('请输入Ollama服务地址', 'error');
                return;
            }

            const data = {
                action: 'add_provider',
                id: 'ollama_quick_' + Date.now(),
                type: 'ollama',
                name: '本地Ollama (' + url.replace(/https?:\/\//, '') + ')',
                base_url: url,
                api_key: '',
                default_model: model || 'llama2',
                temperature: 0.7,
                max_tokens: 2048,
                timeout: 120,
                enabled: 1,
                is_default: 0
            };

            $.post('api/providers_handler.php', data, function(response) {
                if (response.success) {
                    showToast('Ollama已添加为API提供商！', 'success');
                    loadProviders();
                    loadProviderStats();
                } else {
                    showToast('添加失败: ' + (response.error || '未知错误'), 'error');
                }
            });
        }

        // 一键添加GPUStack为提供商
        function quickSetupGpustack() {
            const url = $('#quickGpustackUrl').val();
            const key = $('#quickGpustackKey').val();
            const model = $('#quickGpustackModel').val();
            const enabled = $('#quickGpustackEnabled').is(':checked');

            if (!url) {
                showToast('请输入GPUStack API地址', 'error');
                return;
            }

            const data = {
                action: 'add_provider',
                id: 'gpustack_quick_' + Date.now(),
                type: 'gpustack',
                name: 'GPUStack (' + url.replace(/https?:\/\//, '').split('/')[0] + ')',
                base_url: url,
                api_key: key,
                default_model: model || '',
                temperature: 0.7,
                max_tokens: 2048,
                timeout: 120,
                enabled: enabled ? 1 : 0,
                is_default: 0
            };

            $.post('api/providers_handler.php', data, function(response) {
                if (response.success) {
                    showToast('GPUStack已添加为API提供商！', 'success');
                    loadProviders();
                    loadProviderStats();
                } else {
                    showToast('添加失败: ' + (response.error || '未知错误'), 'error');
                }
            });
        }

        // 仅保存GPUStack配置（向后兼容）
        function saveGpuStackConfigQuick() {
            const data = {
                action: 'saveGpuStackConfig',
                enabled: $('#quickGpustackEnabled').is(':checked'),
                base_url: $('#quickGpustackUrl').val(),
                api_key: $('#quickGpustackKey').val(),
                default_model: $('#quickGpustackModel').val(),
                temperature: 0.7,
                max_tokens: 2048
            };

            $.post('api/admin_handler.php', data, function(response) {
                if (response.status === 'success') {
                    showToast('GPUStack配置已保存', 'success');
                } else {
                    showToast('保存失败: ' + response.message, 'error');
                }
            });
        }

        // 模态框
        function showAddModelModal() {
            // 加载云端API提供商列表
            loadProviderOptions('newModelApi');
            $('#addModelModal').addClass('show');
        }
        
        // 加载提供商选项到下拉框
        function loadProviderOptions(selectId, selectedValue) {
            const select = $('#' + selectId);
            select.html('<option value="">加载中...</option>');
            
            $.get('api/providers_handler.php?action=get_providers&enabled=1', function(response) {
                if (response.success && response.data) {
                    const localTypes = ['ollama', 'llamacpp', 'vllm', 'xinference', 'gpustack'];
                    let options = '<option value="">请选择API提供商</option>';
                    
                    response.data.forEach(provider => {
                        // 跳过本地提供商
                        if (localTypes.includes(provider.type)) {
                            return;
                        }
                        
                        const selected = provider.id === selectedValue ? 'selected' : '';
                        options += '<option value="' + provider.id + '" ' + selected + '>' + 
                                   (provider.name || provider.id) + ' (' + provider.type + ')</option>';
                    });
                    
                    // 添加自定义选项
                    options += '<option value="custom">自定义</option>';
                    
                    select.html(options);
                } else {
                    select.html('<option value="">加载失败</option>');
                }
            }).fail(function() {
                select.html('<option value="">加载失败</option>');
            });
        }

        function showAddUserModal() {
            $('#addUserModal').addClass('show');
        }

        // ========== 权限管理功能 ==========
        
        let currentPermissionUserId = null;
        let currentPermissionData = null;
        let availableModels = [];
        
        // 打开权限管理模态框
        function managePermissions(userId, username) {
            currentPermissionUserId = userId;
            $('#permissionUserId').val(userId);
            $('#permissionUsername').text(username);
            
            // 显示管理员标记（如果是管理员）
            $.get('api/user_handler.php?action=getAllUsers&page=1', function(response) {
                if (response.status === 'success') {
                    const user = response.users.find(u => u.id === userId);
                    if (user && user.role === 'admin') {
                        $('#permissionAdminBadge').show();
                    } else {
                        $('#permissionAdminBadge').hide();
                    }
                }
            });
            
            // 加载可用模型列表
            loadAvailableModels().then(() => {
                // 加载用户当前权限
                loadUserPermissions(userId);
            });
            
            // 重置标签页
            $('.perm-tab').removeClass('active').css({ 'border-bottom': 'none', 'color': '#64748b' });
            $('.perm-tab[data-tab="modules"]').addClass('active').css({ 'border-bottom': '2px solid #4c51bf', 'color': '#4c51bf' });
            $('.perm-content').hide();
            $('#permTab-modules').show();
            
            $('#permissionModal').addClass('show');
        }
        
        // 加载可用模型列表
        function loadAvailableModels() {
            return new Promise((resolve) => {
                // 使用新的API获取所有可用模型（本地+在线）
                $.get('api/get_available_models.php', function(response) {
                    if (response.success && response.models && response.models.length > 0) {
                        availableModels = response.models;
                        console.log('加载了 ' + response.count + ' 个模型');
                    } else {
                        // 如果API不可用，使用默认模型列表
                        availableModels = [
                            { provider_id: 'ollama', 'model_id': 'llama2', name: 'Ollama - Llama 2', type: 'ollama' },
                            { provider_id: 'ollama', 'model_id': 'mistral', name: 'Ollama - Mistral', type: 'ollama' },
                            { provider_id: 'openai', 'model_id': 'gpt-3.5-turbo', name: 'OpenAI - GPT-3.5', type: 'openai' },
                            { provider_id: 'openai', 'model_id': 'gpt-4', name: 'OpenAI - GPT-4', type: 'openai' }
                        ];
                    }
                    resolve();
                }).fail(function(xhr) {
                    console.error('加载模型失败:', xhr.responseText);
                    // 使用默认模型列表
                    availableModels = [
                        { provider_id: 'ollama', 'model_id': 'llama2', name: 'Ollama - Llama 2', type: 'ollama' },
                        { provider_id: 'ollama', 'model_id': 'mistral', name: 'Ollama - Mistral', type: 'ollama' }
                    ];
                    resolve();
                });
            });
        }
        
        // 加载用户权限
        function loadUserPermissions(userId) {
            $.get('api/permission_handler.php?action=getUserPermissions&user_id=' + userId, function(response) {
                if (response.success) {
                    currentPermissionData = response.data;
                    renderModulePermissions(response.data.modules);
                    renderModelPermissions(response.data.models);
                    renderTrainingPermissions(response.data.training);
                } else {
                    showToast('加载权限失败: ' + response.error, 'error');
                }
            });
        }
        
        // 渲染模块权限
        function renderModulePermissions(modules) {
            const modulesList = [
                { id: 'chat', name: 'AI聊天', icon: 'fa-comments', desc: '与AI进行对话' },
                { id: 'scenarios', name: '场景演示', icon: 'fa-magic', desc: '使用场景化AI功能' },
                { id: 'workflow', name: '工作流', icon: 'fa-project-diagram', desc: '创建AI工作流' },
                { id: 'training', name: '模型训练', icon: 'fa-graduation-cap', desc: '训练自定义模型' }
            ];
            
            // 构建已设置的权限映射
            const moduleMap = {};
            modules.forEach(m => {
                moduleMap[m.module] = m.allowed == 1;
            });
            
            let html = '';
            modulesList.forEach(mod => {
                const isAllowed = moduleMap.hasOwnProperty(mod.id) ? moduleMap[mod.id] : true;
                html += `
                    <div style="display: flex; align-items: center; padding: 12px; background: ${isAllowed ? '#f0fdf4' : '#fef2f2'}; border: 1px solid ${isAllowed ? '#86efac' : '#fecaca'}; border-radius: 8px;">
                        <input type="checkbox" id="mod_${mod.id}" class="module-perm-check" data-module="${mod.id}" ${isAllowed ? 'checked' : ''} 
                            style="width: 18px; height: 18px; margin-right: 12px; cursor: pointer;">
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: #1a202c;">
                                <i class="fas ${mod.icon}" style="color: ${isAllowed ? '#22c55e' : '#ef4444'}; margin-right: 6px;"></i>
                                ${mod.name}
                            </div>
                            <div style="font-size: 12px; color: #64748b;">${mod.desc}</div>
                        </div>
                    </div>
                `;
            });
            
            $('#modulePermissions').html(html);
        }
        
        // 渲染模型权限
        function renderModelPermissions(models) {
            if (availableModels.length === 0) {
                $('#modelPermissions').html('<div style="text-align: center; padding: 20px; color: #64748b;">暂无可用的AI模型</div>');
                return;
            }
            
            // 构建已设置的权限映射
            const modelMap = {};
            models.forEach(m => {
                const key = m.provider_id + ':' + m.model_id;
                modelMap[key] = {
                    allowed: m.allowed == 1,
                    maxTokens: m.max_tokens_per_day
                };
            });
            
            // 按提供商类型分组（本地 vs 云端）
            const localTypes = ['ollama', 'llamacpp', 'vllm', 'xinference', 'gpustack'];
            const localModels = [];
            const cloudModels = [];
            
            availableModels.forEach(model => {
                const type = model.type || '';
                if (localTypes.includes(type)) {
                    localModels.push(model);
                } else {
                    cloudModels.push(model);
                }
            });
            
            let html = '';
            
            // 添加"全选/全不选"按钮
            html += `
                <div style="display: flex; gap: 10px; margin-bottom: 16px;">
                    <button type="button" class="btn btn-sm btn-secondary" onclick="selectAllModels(true)">
                        <i class="fas fa-check-square"></i> 全选
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="selectAllModels(false)">
                        <i class="fas fa-square"></i> 全不选
                    </button>
                </div>
            `;
            
            // 本地模型分组
            if (localModels.length > 0) {
                html += renderModelGroup('本地模型', localModels, modelMap, 'desktop', '#10b981');
            }
            
            // 云端模型分组
            if (cloudModels.length > 0) {
                html += renderModelGroup('云端API模型', cloudModels, modelMap, 'cloud', '#3b82f6');
            }
            
            $('#modelPermissions').html(html);
        }
        
        // 渲染模型分组
        function renderModelGroup(title, models, modelMap, icon, color) {
            let html = `
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid ${color};">
                        <i class="fas fa-${icon}" style="color: ${color};"></i>
                        <span style="font-weight: 600; color: #1a202c;">${title}</span>
                        <span style="background: ${color}; color: white; padding: 2px 8px; border-radius: 10px; font-size: 12px;">${models.length}</span>
                    </div>
                    <div style="display: grid; gap: 8px;">
            `;
            
            models.forEach(model => {
                const key = model.provider_id + ':' + model.model_id;
                const perm = modelMap[key] || { allowed: true };
                const isAllowed = perm.allowed;
                
                html += `
                    <div style="display: flex; align-items: center; padding: 12px; background: ${isAllowed ? '#f0fdf4' : '#f9fafb'}; border: 1px solid ${isAllowed ? '#86efac' : '#e5e7eb'}; border-radius: 8px; transition: all 0.2s;">
                        <input type="checkbox" id="model_${key.replace(/:/g, '_')}" class="model-perm-check" 
                            data-provider="${model.provider_id}" data-model="${model.model_id}" 
                            ${isAllowed ? 'checked' : ''} style="width: 18px; height: 18px; margin-right: 12px; cursor: pointer;">
                        <div style="flex: 1;">
                            <div style="font-weight: 500; color: #1a202c; font-size: 14px;">${model.name || model.model_id}</div>
                            <div style="font-size: 11px; color: #64748b; margin-top: 2px;">${model.provider_id} / ${model.model_id}</div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <label style="font-size: 12px; color: #64748b; white-space: nowrap;">日限额:</label>
                            <input type="number" class="model-token-limit" data-key="${key}" 
                                value="${perm.maxTokens || ''}" placeholder="无限制"
                                style="width: 70px; padding: 4px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;">
                        </div>
                    </div>
                `;
            });
            
            html += '</div></div>';
            return html;
        }
        
        // 渲染训练权限
        function renderTrainingPermissions(training) {
            const trainingTypes = [
                { id: '*', name: '所有模型', desc: '允许训练任何类型的模型' },
                { id: 'llama', name: 'Llama 系列', desc: 'Llama2, Llama3 等' },
                { id: 'qwen', name: '通义千问', desc: 'Qwen 系列模型' },
                { id: 'chatglm', name: 'ChatGLM', desc: 'ChatGLM 系列模型' },
                { id: 'baichuan', name: '百川', desc: 'Baichuan 系列模型' }
            ];
            
            const trainingMap = {};
            training.forEach(t => {
                trainingMap[t.model_name] = {
                    allowed: t.allowed == 1,
                    maxJobs: t.max_training_jobs
                };
            });
            
            let html = '';
            trainingTypes.forEach(type => {
                const perm = trainingMap[type.id] || { allowed: type.id === '*' ? false : true };
                const isAllowed = perm.allowed;
                
                html += `
                    <div style="display: flex; align-items: center; padding: 12px; margin-bottom: 12px; background: ${isAllowed ? '#f0fdf4' : '#f9fafb'}; border: 1px solid ${isAllowed ? '#86efac' : '#e5e7eb'}; border-radius: 8px;">
                        <input type="checkbox" id="train_${type.id}" class="training-perm-check" 
                            data-model="${type.id}" ${isAllowed ? 'checked' : ''} style="margin-right: 12px;">
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: #1a202c;">${type.name}</div>
                            <div style="font-size: 12px; color: #64748b;">${type.desc}</div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <label style="font-size: 12px; color: #64748b;">最大任务数:</label>
                            <input type="number" class="training-job-limit" data-model="${type.id}" 
                                value="${perm.maxJobs || ''}" placeholder="无限制"
                                style="width: 70px; padding: 4px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;">
                        </div>
                    </div>
                `;
            });
            
            $('#trainingPermissions').html(html);
        }
        
        // 全选/全不选模型
        function selectAllModels(select) {
            $('.model-perm-check').prop('checked', select);
        }
        
        // 保存权限设置
        function savePermissions() {
            if (!currentPermissionUserId) return;
            
            const permissions = {
                modules: {},
                models: [],
                training: []
            };
            
            // 收集模块权限
            $('.module-perm-check').each(function() {
                const module = $(this).data('module');
                permissions.modules[module] = $(this).is(':checked');
            });
            
            // 收集模型权限
            $('.model-perm-check').each(function() {
                const providerId = $(this).data('provider');
                const modelId = $(this).data('model');
                const key = providerId + ':' + modelId;
                const maxTokens = $(`.model-token-limit[data-key="${key}"]`).val();
                
                permissions.models.push({
                    provider_id: providerId,
                    model_id: modelId,
                    allowed: $(this).is(':checked'),
                    max_tokens_per_day: maxTokens ? parseInt(maxTokens) : null
                });
            });
            
            // 收集训练权限
            $('.training-perm-check').each(function() {
                const modelName = $(this).data('model');
                const maxJobs = $(`.training-job-limit[data-model="${modelName}"]`).val();
                
                permissions.training.push({
                    model_name: modelName,
                    allowed: $(this).is(':checked'),
                    max_training_jobs: maxJobs ? parseInt(maxJobs) : null
                });
            });
            
            // 发送保存请求
            $.post('api/permission_handler.php', {
                action: 'batchSetPermissions',
                user_id: currentPermissionUserId,
                permissions: JSON.stringify(permissions)
            }, function(response) {
                if (response.success) {
                    showToast('权限设置已保存', 'success');
                    closeModal();
                } else {
                    showToast('保存失败: ' + (response.error || '未知错误'), 'error');
                }
            }).fail(function() {
                showToast('请求失败，请检查网络连接', 'error');
            });
        }
        
        // 权限标签页切换
        $(document).on('click', '.perm-tab', function() {
            const tab = $(this).data('tab');
            
            $('.perm-tab').removeClass('active').css({ 'border-bottom': 'none', 'color': '#64748b' });
            $(this).addClass('active').css({ 'border-bottom': '2px solid #4c51bf', 'color': '#4c51bf' });
            
            $('.perm-content').hide();
            $('#permTab-' + tab).show();
        });

        function closeModal() {
            $('.modal').removeClass('show');
        }

        // API提供商类型默认配置
        const providerTypeDefaults = {
            'ollama': { url: 'http://localhost:11434', model: 'llama2', requiresKey: false },
            'llamacpp': { url: 'http://localhost:8080', model: 'llama-2-7b', requiresKey: false },
            'vllm': { url: 'http://localhost:8000/v1', model: '', requiresKey: false },
            'xinference': { url: 'http://localhost:9997/v1', model: '', requiresKey: false },
            'gpustack': { url: 'http://localhost:8080', model: '', requiresKey: false },
            'openai': { url: 'https://api.openai.com/v1', model: 'gpt-3.5-turbo', requiresKey: true },
            'azure_openai': { url: 'https://{resource}.openai.azure.com', model: 'gpt-35-turbo', requiresKey: true },
            'anthropic': { url: 'https://api.anthropic.com/v1', model: 'claude-3-sonnet-20240229', requiresKey: true },
            'gemini': { url: 'https://generativelanguage.googleapis.com/v1', model: 'gemini-pro', requiresKey: true },
            'deepseek': { url: 'https://api.deepseek.com/v1', model: 'deepseek-chat', requiresKey: true },
            'hunyuan': { url: 'https://hunyuan.tencentcloudapi.com/v1', model: 'hunyuan-pro', requiresKey: true },
            'zhipu': { url: 'https://open.bigmodel.cn/api/paas/v4', model: 'glm-4', requiresKey: true },
            'qwen': { url: 'https://dashscope.aliyuncs.com/api/v1', model: 'qwen-turbo', requiresKey: true },
            'moonshot': { url: 'https://api.moonshot.cn/v1', model: 'moonshot-v1-8k', requiresKey: true },
            'custom_openai': { url: 'http://localhost:8000/v1', model: '', requiresKey: true }
        };

        // ========== API密钥管理功能 ==========

        // 加载API密钥列表
        function loadApiKeys() {
            $.get('api/apikey_handler.php?action=getKeys', function(response) {
                const container = $('#apiKeysList');
                
                if (!response.success) {
                    container.html('<div style="color: #ef4444; text-align: center; padding: 40px;">加载失败</div>');
                    return;
                }
                
                const keys = response.keys || [];
                
                if (keys.length === 0) {
                    container.html(`
                        <div style="color: #64748b; text-align: center; padding: 40px;">
                            <i class="fas fa-key" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                            暂无API密钥<br>
                            <button class="btn btn-primary" style="margin-top: 16px;" onclick="showCreateApiKeyModal()">
                                <i class="fas fa-plus"></i> 创建第一个密钥
                            </button>
                        </div>
                    `);
                    return;
                }
                
                let html = '';
                keys.forEach(key => {
                    const statusBadge = key.enabled 
                        ? '<span style="background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 12px; font-size: 12px;">启用</span>'
                        : '<span style="background: #fee2e2; color: #991b1b; padding: 4px 12px; border-radius: 12px; font-size: 12px;">停用</span>';
                    
                    html += `
                        <div class="model-item" style="display: flex; align-items: center; justify-content: space-between; padding: 16px; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 12px; background: white;">
                            <div>
                                <div style="font-weight: 600; color: #1a202c;">${key.name}</div>
                                <div style="font-size: 13px; color: #64748b; margin-top: 4px;">
                                    权限: ${key.permissions} | 
                                    剩余配额: ${key.quota_remaining !== null ? key.quota_remaining.toLocaleString() : '无限制'} |
                                    使用次数: ${key.usage_count}
                                </div>
                                <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">
                                    创建: ${key.created_at} | 最后使用: ${key.last_used_at || '从未'}
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                ${statusBadge}
                                <button class="btn btn-danger btn-sm" onclick="deleteApiKey(${key.id}, '${key.name}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                });
                
                container.html(html);
            });
        }

        // 显示创建API密钥模态框
        function showCreateApiKeyModal() {
            console.log('showCreateApiKeyModal called');
            const modal = document.getElementById('createApiKeyModal');
            console.log('Modal element:', modal ? 'found' : 'not found');
            if (modal) {
                modal.classList.add('show');
                document.getElementById('apiKeyName').value = '';
                document.getElementById('apiKeyQuota').value = 100000;
                document.getElementById('newApiKeyDisplay').style.display = 'none';
            }
        }

        // 关闭API密钥模态框
        function closeApiKeyModal() {
            const modal = document.getElementById('createApiKeyModal');
            if (modal) {
                modal.classList.remove('show');
            }
        }

        // 创建API密钥
        function createApiKey() {
            const nameInput = document.getElementById('apiKeyName');
            const quotaInput = document.getElementById('apiKeyQuota');
            const name = nameInput.value.trim();
            const quota = quotaInput.value;
            
            console.log('Creating API key:', { name, quota });
            
            if (!name) {
                showToast('请输入密钥名称', 'error');
                return;
            }
            
            // 显示加载状态
            const btn = document.querySelector('#apiKeyForm .btn-primary');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 创建中...';
            btn.disabled = true;
            
            $.post('api/apikey_handler.php', {
                action: 'createKey',
                name: name,
                quota: quota
            }, function(response) {
                console.log('API key creation response:', response);
                
                // 恢复按钮状态
                btn.innerHTML = originalText;
                btn.disabled = false;
                
                if (response && response.success) {
                    document.getElementById('newApiKeyDisplay').style.display = 'block';
                    document.getElementById('newApiKeyValue').textContent = response.key.api_key;
                    loadApiKeys();
                    showToast('API密钥创建成功', 'success');
                } else {
                    const errorMsg = response && response.error ? response.error : '未知错误';
                    showToast('创建失败: ' + errorMsg, 'error');
                }
            }).fail(function(xhr, status, error) {
                console.error('API key creation failed:', {xhr, status, error});
                btn.innerHTML = originalText;
                btn.disabled = false;
                
                // 尝试解析错误响应
                let errorMsg = '请求失败';
                try {
                    const responseText = xhr.responseText;
                    if (responseText) {
                        // 如果返回的是HTML，提取其中的错误信息
                        if (responseText.indexOf('<') === 0) {
                            errorMsg = '服务器返回HTML错误页面，可能是PHP语法错误或服务器配置问题';
                        } else {
                            const jsonResponse = JSON.parse(responseText);
                            errorMsg = jsonResponse.error || error;
                        }
                    }
                } catch (e) {
                    errorMsg = error || '网络错误';
                }
                
                showToast('请求失败: ' + errorMsg, 'error');
            });
        }

        // 复制API密钥（带按钮反馈）
        function copyApiKeyWithFeedback(btn) {
            copyApiKey().then(success => {
                if (success) {
                    // 显示成功反馈
                    const originalContent = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-check"></i>';
                    btn.style.background = '#10b981';
                    btn.style.color = 'white';
                    btn.style.borderColor = '#10b981';
                    
                    // 显示反馈文字
                    const feedback = document.getElementById('copyFeedback');
                    if (feedback) {
                        feedback.style.display = 'block';
                    }
                    
                    // 2秒后恢复
                    setTimeout(() => {
                        btn.innerHTML = originalContent;
                        btn.style.background = '';
                        btn.style.color = '';
                        btn.style.borderColor = '';
                        if (feedback) {
                            feedback.style.display = 'none';
                        }
                    }, 2000);
                }
            });
        }
        
        // 复制API密钥
        async function copyApiKey() {
            const keyElement = document.getElementById('newApiKeyValue');
            if (!keyElement) {
                showToast('找不到密钥元素', 'error');
                return false;
            }
            
            const key = keyElement.textContent || keyElement.innerText || '';
            if (!key.trim()) {
                showToast('密钥为空', 'error');
                return false;
            }
            
            console.log('Attempting to copy key:', key.substring(0, 20) + '...');
            
            // 方法1: 现代 Clipboard API（需要HTTPS）
            if (navigator.clipboard && window.isSecureContext) {
                try {
                    await navigator.clipboard.writeText(key);
                    showToast('密钥已复制到剪贴板', 'success');
                    console.log('Copied using Clipboard API');
                    return true;
                } catch (err) {
                    console.error('Clipboard API failed:', err);
                }
            }
            
            // 方法2: 降级方案 - execCommand
            try {
                const textArea = document.createElement('textarea');
                textArea.value = key;
                textArea.style.cssText = 'position:fixed;top:0;left:0;opacity:0;pointer-events:none;';
                
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                const successful = document.execCommand('copy');
                document.body.removeChild(textArea);
                
                if (successful) {
                    showToast('密钥已复制到剪贴板', 'success');
                    console.log('Copied using execCommand');
                    return true;
                }
            } catch (err) {
                console.error('execCommand failed:', err);
            }
            
            // 方法3: 手动选择
            return fallbackManualCopy(key);
        }
        
        // 手动复制降级方案
        function fallbackManualCopy(text) {
            const keyElement = document.getElementById('newApiKeyValue');
            if (!keyElement) {
                showToast('无法复制，请手动选择密钥文本', 'warning');
                return false;
            }
            
            try {
                // 创建选区
                const range = document.createRange();
                range.selectNodeContents(keyElement);
                
                const selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);
                
                // 在非安全上下文中尝试 Clipboard API
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(() => {
                        showToast('密钥已复制到剪贴板', 'success');
                        selection.removeAllRanges();
                    }).catch(() => {
                        showToast('请按 Ctrl+C 复制选中的密钥', 'warning');
                        setTimeout(() => selection.removeAllRanges(), 5000);
                    });
                    return true;
                } else {
                    showToast('请按 Ctrl+C 复制选中的密钥', 'warning');
                    setTimeout(() => selection.removeAllRanges(), 5000);
                    return false;
                }
            } catch (err) {
                console.error('Manual copy failed:', err);
                showToast('复制失败，请手动选择密钥文本复制', 'error');
                return false;
            }
        }

        // 删除API密钥
        function deleteApiKey(keyId, keyName) {
            if (!confirm(`确定要删除密钥 "${keyName}" 吗？此操作不可恢复。`)) return;
            
            $.post('api/apikey_handler.php', {
                action: 'deleteKey',
                key_id: keyId
            }, function(response) {
                if (response.success) {
                    loadApiKeys();
                    showToast('密钥已删除', 'success');
                } else {
                    showToast('删除失败: ' + (response.error || '未知错误'), 'error');
                }
            });
        }

        // 显示添加提供商模态框
        function showAddProviderModal() {
            $('#providerId').val('');
            $('#providerModalTitle').html('<i class="fas fa-server"></i> 添加API提供商');
            $('#providerType').val('ollama').trigger('change');
            $('#providerName').val('');
            $('#providerApiKey').val('');
            $('#providerEnabled').prop('checked', true);
            $('#providerIsDefault').prop('checked', false);
            $('#providerModal').addClass('show');
        }

        // 编辑提供商
        function editProvider(providerId) {
            $.get('api/providers_handler.php?action=get_provider&provider_id=' + providerId, function(response) {
                if (response.success) {
                    const p = response.data;
                    $('#providerId').val(p.id);
                    $('#providerModalTitle').html('<i class="fas fa-edit"></i> 编辑API提供商');
                    $('#providerType').val(p.type).trigger('change');
                    $('#providerName').val(p.name);
                    $('#providerBaseUrl').val(p.config.base_url);
                    $('#providerApiKey').val(''); // 清空密码框，需要重新输入才更新
                    $('#providerDefaultModel').val(p.config.default_model);
                    $('#providerTimeout').val(p.config.timeout);
                    $('#providerTemperature').val(p.config.temperature);
                    $('#providerMaxTokens').val(p.config.max_tokens);
                    $('#providerEnabled').prop('checked', p.enabled);
                    $('#providerIsDefault').prop('checked', p.is_default);
                    
                    // 加载腾讯混元特有字段
                    if (p.type === 'hunyuan') {
                        $('#hunyuanSecretId').val(p.config.secret_id || '');
                        $('#hunyuanSecretKey').val(''); // 清空密码框
                        $('#hunyuanRegion').val(p.config.region || 'ap-guangzhou');
                    }
                    
                    $('#providerModal').addClass('show');
                } else {
                    showToast('加载失败: ' + (response.error || '未知错误'), 'error');
                }
            });
        }

        // 关闭提供商模态框
        function closeProviderModal() {
            $('#providerModal').removeClass('show');
        }

        // 提供商类型改变时更新默认值
        function onProviderTypeChange() {
            const type = $('#providerType').val();
            const defaults = providerTypeDefaults[type];
            if (defaults) {
                $('#providerBaseUrl').val(defaults.url);
                $('#providerDefaultModel').val(defaults.model);
                $('#providerUrlHint').text('默认地址: ' + defaults.url);
                
                if (defaults.requiresKey) {
                    $('#providerApiKeyGroup').show();
                } else {
                    $('#providerApiKeyGroup').hide();
                    $('#providerApiKey').val('');
                }
                
                // 显示/隐藏腾讯混元特有配置
                if (type === 'hunyuan') {
                    $('#hunyuanConfig').show();
                    $('#providerApiKeyGroup label').text('API密钥 (可选)');
                    $('#providerApiKeyGroup .form-hint').html('如果使用腾讯云原生API，请填写下方的 SecretId 和 SecretKey');
                } else {
                    $('#hunyuanConfig').hide();
                    $('#providerApiKeyGroup label').text('API密钥');
                    $('#providerApiKeyGroup .form-hint').text('在线服务需要API密钥，本地服务通常不需要');
                }
            }
        }

        // 保存提供商
        function saveProvider() {
            const id = $('#providerId').val();
            const data = {
                action: id ? 'update_provider' : 'add_provider',
                provider_id: id,
                type: $('#providerType').val(),
                name: $('#providerName').val().trim(),
                base_url: $('#providerBaseUrl').val().trim(),
                api_key: $('#providerApiKey').val().trim(),
                default_model: $('#providerDefaultModel').val().trim(),
                timeout: $('#providerTimeout').val(),
                temperature: $('#providerTemperature').val(),
                max_tokens: $('#providerMaxTokens').val(),
                enabled: $('#providerEnabled').is(':checked') ? 1 : 0,
                is_default: $('#providerIsDefault').is(':checked') ? 1 : 0
            };

            if (!data.name) {
                showToast('请输入提供商名称', 'error');
                return;
            }
            if (!data.base_url) {
                showToast('请输入API地址', 'error');
                return;
            }

            // 如果是编辑且API密钥为空，则不提交API密钥字段
            if (id && !data.api_key) {
                delete data.api_key;
            }
            
            // 添加腾讯混元特有字段
            if (data.type === 'hunyuan') {
                const secretId = $('#hunyuanSecretId').val().trim();
                const secretKey = $('#hunyuanSecretKey').val().trim();
                const region = $('#hunyuanRegion').val();
                
                if (secretId) data.secret_id = secretId;
                if (secretKey) data.secret_key = secretKey;
                if (region) data.region = region;
            }

            $.post('api/providers_handler.php', data, function(response) {
                if (response.success) {
                    showToast(id ? '提供商已更新' : '提供商已添加', 'success');
                    closeProviderModal();
                    loadProviders();
                    loadProviderStats();
                } else {
                    showToast('保存失败: ' + (response.error || '未知错误'), 'error');
                }
            });
        }

        // 测试提供商连接
        function testProviderConnection() {
            const baseUrl = $('#providerBaseUrl').val().trim();
            const apiKey = $('#providerApiKey').val().trim();
            const type = $('#providerType').val();

            if (!baseUrl) {
                showToast('请先输入API地址', 'error');
                return;
            }
            
            // 腾讯混元需要检查SecretId和SecretKey
            if (type === 'hunyuan') {
                const secretId = $('#hunyuanSecretId').val().trim();
                const secretKey = $('#hunyuanSecretKey').val().trim();
                if (!secretId || !secretKey) {
                    showToast('腾讯混元需要提供 SecretId 和 SecretKey', 'error');
                    return;
                }
            }

            showToast('正在测试连接...', 'info');

            // 创建临时测试配置
            const testData = {
                action: 'add_provider',
                id: 'temp_test_' + Date.now(),
                type: type,
                name: 'Test',
                base_url: baseUrl,
                api_key: apiKey,
                enabled: 0,
                is_default: 0
            };
            
            // 添加腾讯混元特有字段
            if (type === 'hunyuan') {
                testData.secret_id = $('#hunyuanSecretId').val().trim();
                testData.secret_key = $('#hunyuanSecretKey').val().trim();
                testData.region = $('#hunyuanRegion').val();
            }

            $.post('api/providers_handler.php', testData, function(addResponse) {
                if (addResponse.success) {
                    const tempId = addResponse.data.id;
                    $.get('api/providers_handler.php?action=test_provider&provider_id=' + tempId, function(testResponse) {
                        // 删除临时提供商
                        $.post('api/providers_handler.php', { action: 'delete_provider', provider_id: tempId });

                        if (testResponse.success) {
                            const modelCount = testResponse.model_count || 0;
                            showToast(`连接成功! 发现 ${modelCount} 个模型`, 'success');
                        } else {
                            showToast('连接失败: ' + (testResponse.message || testResponse.error), 'error');
                        }
                    });
                } else {
                    showToast('测试失败: ' + (addResponse.error || '未知错误'), 'error');
                }
            });
        }

        // 添加在线模型
        function addOnlineModel() {
            const data = {
                action: 'addOnlineModel',
                name: $('#newModelName').val(),
                id: $('#newModelId').val(),
                api_type: $('#newModelApi').val()
            };
            
            console.log('Adding online model:', data);

            $.post('api/admin_handler.php', data, function(response) {
                console.log('addOnlineModel response:', response);
                if (response.status === 'success') {
                    closeModal();
                    loadOnlineModels();
                    showToast('模型已添加', 'success');
                } else {
                    showToast('添加失败: ' + response.message, 'error');
                }
            }).fail(function(xhr, status, error) {
                console.error('addOnlineModel error:', error);
                showToast('请求失败: ' + error, 'error');
            });
        }

        // 删除在线模型
        function deleteOnlineModel(modelId) {
            if (!confirm('确定要删除这个模型吗？')) return;

            $.post('api/admin_handler.php', {
                action: 'deleteOnlineModel',
                model_id: modelId
            }, function(response) {
                if (response.status === 'success') {
                    loadOnlineModels();
                    showToast('模型已删除', 'success');
                }
            });
        }

        // 编辑在线模型
        let currentEditingModelId = null;
        function editOnlineModel(modelId) {
            // 从当前列表中找到模型数据
            $.get('api/admin_handler.php?action=getOnlineModels', function(response) {
                if (response.status === 'success') {
                    const models = response.models || [];
                    const model = models.find(m => m.id === modelId);
                    
                    if (model) {
                        currentEditingModelId = modelId;
                        $('#editModelOldId').val(modelId);
                        $('#editModelName').val(model.name);
                        $('#editModelId').val(model.id);
                        
                        // 加载提供商选项并选中当前值
                        loadProviderOptions('editModelApi', model.api_type);
                        
                        $('#editModelModal').addClass('show');
                    }
                }
            });
        }

        // 更新在线模型
        function updateOnlineModel() {
            const data = {
                action: 'updateOnlineModel',
                old_id: $('#editModelOldId').val(),
                name: $('#editModelName').val(),
                id: $('#editModelId').val(),
                api_type: $('#editModelApi').val()
            };

            $.post('api/admin_handler.php', data, function(response) {
                if (response.status === 'success') {
                    closeModal();
                    loadOnlineModels();
                    showToast('模型已更新', 'success');
                } else {
                    showToast('更新失败: ' + response.message, 'error');
                }
            }).fail(function(xhr, status, error) {
                showToast('请求失败: ' + error, 'error');
            });
        }

        // LOGO上传预览
        $('#logoUpload').on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#logoPreview').html('<img src="' + e.target.result + '" style="max-width: 100%; max-height: 100%; object-fit: contain;">');
                };
                reader.readAsDataURL(file);
            }
        });

        // 保存系统配置
        function saveSystemConfig() {
            const formData = new FormData();
            formData.append('action', 'saveSystemConfig');
            formData.append('name', $('#appName').val());
            formData.append('version', $('#appVersion').val());
            formData.append('debug', $('#debugMode').is(':checked'));

            // 如果有新LOGO，添加到表单
            const logoFile = $('#logoUpload')[0].files[0];
            if (logoFile) {
                formData.append('logo', logoFile);
            }

            $.ajax({
                url: 'api/admin_handler.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status === 'success') {
                        showToast('设置已保存，刷新页面后生效', 'success');
                        // 清空文件输入
                        $('#logoUpload').val('');
                    } else {
                        showToast('保存失败: ' + response.message, 'error');
                    }
                },
                error: function() {
                    showToast('保存失败，请检查网络连接', 'error');
                }
            });
        }

        // ========== 数据管理功能 ==========

        // 清理缓存
        function clearCache() {
            if (!confirm('确定要清理系统缓存吗？这将清除所有临时文件和缓存数据。')) {
                return;
            }
            
            $.post('api/admin_handler.php', { action: 'clearCache' }, function(response) {
                if (response.status === 'success') {
                    showToast('缓存已清理', 'success');
                } else {
                    showToast('清理失败: ' + (response.message || '未知错误'), 'error');
                }
            }).fail(function() {
                showToast('清理失败，请检查网络连接', 'error');
            });
        }

        // 导出配置
        function exportConfig() {
            // 获取当前配置
            const config = {
                app: {
                    name: $('#appName').val() || '巨神兵AIAPI辅助平台',
                    version: $('#appVersion').val() || '1.0.0',
                    debug: $('#debugMode').is(':checked')
                },
                exportTime: new Date().toISOString(),
                version: '1.0'
            };
            
            // 创建下载
            const dataStr = JSON.stringify(config, null, 2);
            const dataBlob = new Blob([dataStr], { type: 'application/json' });
            const url = URL.createObjectURL(dataBlob);
            
            const link = document.createElement('a');
            link.href = url;
            link.download = `platform-config-${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            URL.revokeObjectURL(url);
            showToast('配置已导出', 'success');
        }

        // 导入配置
        function importConfig() {
            // 创建文件输入
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.json,application/json';
            input.style.display = 'none';
            
            input.onchange = function(e) {
                const file = e.target.files[0];
                if (!file) return;
                
                const reader = new FileReader();
                reader.onload = function(event) {
                    try {
                        const config = JSON.parse(event.target.result);
                        
                        // 验证配置格式
                        if (!config.app || !config.version) {
                            showToast('无效的配置文件格式', 'error');
                            return;
                        }
                        
                        // 应用配置
                        if (config.app.name) {
                            $('#appName').val(config.app.name);
                        }
                        if (config.app.version) {
                            $('#appVersion').val(config.app.version);
                        }
                        if (config.app.debug !== undefined) {
                            $('#debugMode').prop('checked', config.app.debug);
                        }
                        
                        showToast('配置已导入，请点击保存设置按钮保存', 'success');
                    } catch (err) {
                        showToast('配置文件解析失败: ' + err.message, 'error');
                    }
                };
                reader.readAsText(file);
            };
            
            document.body.appendChild(input);
            input.click();
            document.body.removeChild(input);
        }

        // ========== 知识库管理功能 ==========

        // 加载可用模型列表到训练配置（支持本地和在线API模型）
        function loadAvailableModelsForTraining() {
            const select = $('#trainTargetModel');
            select.empty();
            select.append('<option value="">正在加载可用模型...</option>');
            
            // 同时加载本地模型和在线API模型
            Promise.all([
                // 1. 获取本地Ollama模型
                $.get('api/api_handler.php?request=models'),
                // 2. 获取在线API提供商模型
                $.get('api/providers_handler.php?action=get_providers&enabled=1')
            ]).then(([localResponse, providerResponse]) => {
                select.empty();
                select.append('<option value="">选择要训练的模型</option>');
                
                // 添加在线API模型分组
                if (providerResponse.data && providerResponse.data.length > 0) {
                    select.append('<optgroup label="☁️ 在线API模型">');
                    providerResponse.data.forEach(provider => {
                        if (provider.models && provider.models.length > 0) {
                            provider.models.forEach(modelName => {
                                const displayName = modelName + ' (' + provider.name + ')';
                                select.append('<option value="' + modelName + '" data-provider="' + provider.id + '" data-type="api">' + displayName + '</option>');
                            });
                        }
                    });
                    select.append('</optgroup>');
                }
                
                // 添加本地模型分组
                if (localResponse.status === 'success' && localResponse.models) {
                    select.append('<optgroup label="💻 本地模型">');
                    Object.entries(localResponse.models).forEach(([id, model]) => {
                        // 修复：model是对象，需要提取name属性
                        const modelName = typeof model === 'object' ? (model.name || id) : model;
                        const modelSize = typeof model === 'object' && model.parameter_size ? ' [' + model.parameter_size + ']' : '';
                        select.append('<option value="' + id + '" data-type="local">' + modelName + modelSize + ' (本地)</option>');
                    });
                    select.append('</optgroup>');
                }
            }).catch(error => {
                console.error('加载模型列表失败:', error);
                select.empty();
                select.append('<option value="">加载模型失败，请刷新重试</option>');
            });
        }

        // 加载知识库列表
        function loadKnowledgeBase() {
            // 加载模型列表
            loadAvailableModelsForTraining();

            $.get('api/knowledge_simple.php?action=getDocuments', function(response) {
                const container = $('#knowledgeBaseList');
                container.empty();

                // 检查响应是否为有效JSON
                if (typeof response !== 'object') {
                    container.html(
                        '<div style="color: #ef4444; text-align: center; padding: 40px;">' +
                            '<i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>' +
                            '加载知识库失败：服务器返回无效数据<br><small>请检查网络连接或联系管理员</small>' +
                        '</div>'
                    );
                    console.error('Invalid response:', response);
                    return;
                }

                // 支持两种返回格式：response.success 或 response.status
                const isSuccess = response.success === true || response.status === 'success';

                if (!isSuccess) {
                    container.html(
                        '<div style="color: #ef4444; text-align: center; padding: 40px;">' +
                            '<i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>' +
                            '加载知识库失败：' + (response.error || '未知错误') +
                        '</div>'
                    );
                    return;
                }

                if (response.documents && response.documents.length > 0) {
                    response.documents.forEach(doc => {
                        const iconClass = getDocIcon(doc.file_type);
                        container.append(
                            '<div class="model-item">' +
                                '<div class="model-info">' +
                                    '<div class="model-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">' +
                                        '<i class="fas ' + iconClass + '"></i>' +
                                    '</div>' +
                                    '<div class="model-details">' +
                                        '<h4>' + (doc.name || doc.file_name) + '</h4>' +
                                        '<p>' + (doc.description || '无描述') + ' | 标签: ' + (doc.tags || '无') + '</p>' +
                                    '</div>' +
                                    '<span class="model-badge local">' + formatFileSize(doc.size || doc.file_size) + '</span>' +
                                '</div>' +
                                '<div class="model-actions">' +
                                    '<span style="color: #64748b; font-size: 13px;">' + new Date(doc.created_at).toLocaleDateString() + '</span>' +
                                    '<button class="btn btn-danger btn-sm" onclick="deleteKnowledgeDoc(' + doc.id + ')" style="margin-left: 12px;">' +
                                        '<i class="fas fa-trash"></i>' +
                                    '</button>' +
                                '</div>' +
                            '</div>'
                        );
                    });
                } else {
                    container.html(
                        '<div style="color: #64748b; text-align: center; padding: 40px;">' +
                            '<i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>' +
                            '知识库为空，请先上传文档' +
                        '</div>'
                    );
                }
            }).fail(function() {
                $('#knowledgeBaseList').html(
                    '<div style="color: #ef4444; text-align: center; padding: 40px;">' +
                        '<i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>' +
                        '加载知识库失败，请刷新重试' +
                    '</div>'
                );
            });
        }

        // 获取文档图标
        function getDocIcon(fileType) {
            const iconMap = {
                'txt': 'fa-file-alt',
                'doc': 'fa-file-word',
                'docx': 'fa-file-word',
                'pdf': 'fa-file-pdf'
            };
            return iconMap[fileType] || 'fa-file';
        }

        // 格式化文件大小 - 修复：添加对无效输入的处理
        function formatFileSize(bytes) {
            // 处理 undefined, null, 或非数字值
            if (bytes === undefined || bytes === null || isNaN(bytes)) {
                return '未知大小';
            }
            
            // 转换为数字
            bytes = Number(bytes);
            
            if (bytes === 0) return '0 B';
            if (bytes < 0) return '无效大小';
            
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            // 确保索引在有效范围内
            const index = Math.min(i, sizes.length - 1);
            
            return parseFloat((bytes / Math.pow(k, index)).toFixed(2)) + ' ' + sizes[index];
        }

        // 上传训练文档
        function uploadTrainingDoc() {
            const fileInput = $('#trainingFile')[0];
            if (!fileInput.files || fileInput.files.length === 0) {
                showToast('请选择要上传的文档', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'uploadDocument');
            formData.append('file', fileInput.files[0]);
            formData.append('name', $('#docName').val() || fileInput.files[0].name);
            formData.append('tags', $('#docTags').val());
            formData.append('description', $('#docDescription').val());

            $.ajax({
                url: 'api/knowledge_simple.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // 支持两种返回格式
                    const isSuccess = response.success === true || response.status === 'success';
                    const message = response.message || response.error || '未知错误';
                    
                    if (isSuccess) {
                        showToast('文档已上传到知识库', 'success');
                        // 清空表单
                        $('#trainingFile').val('');
                        $('#docName').val('');
                        $('#docTags').val('');
                        $('#docDescription').val('');
                        // 刷新列表
                        loadKnowledgeBase();
                    } else {
                        showToast('上传失败: ' + message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    let errorMsg = error || '网络错误';
                    // 尝试解析服务器返回的错误信息
                    if (xhr.responseText) {
                        try {
                            const errorResponse = JSON.parse(xhr.responseText);
                            if (errorResponse.error) {
                                errorMsg = errorResponse.error;
                            }
                        } catch (e) {
                            // 如果不是JSON，可能是HTML错误页面
                            if (xhr.responseText.indexOf('<') === 0) {
                                errorMsg = '服务器内部错误，请检查日志';
                            } else {
                                errorMsg = xhr.responseText.substring(0, 100);
                            }
                        }
                    }
                    showToast('上传失败: ' + errorMsg, 'error');
                    console.error('Upload error:', xhr.responseText);
                }
            });
        }

        // 删除知识库文档
        function deleteKnowledgeDoc(docId) {
            if (!confirm('确定要删除这个文档吗？')) return;

            $.post('api/knowledge_simple.php', {
                action: 'deleteDocument',
                doc_id: docId
            }, function(response) {
                // 支持两种返回格式
                const isSuccess = response.success === true || response.status === 'success';
                const message = response.message || response.error || '未知错误';
                
                if (isSuccess) {
                    loadKnowledgeBase();
                    showToast('文档已删除', 'success');
                } else {
                    showToast('删除失败: ' + message, 'error');
                }
            }).fail(function() {
                showToast('删除失败，请检查网络连接', 'error');
            });
        }

        // 当前训练任务ID
        let currentTrainingId = null;
        let trainingProgressInterval = null;
        let trainingStartTime = null;

        // 开始训练模型
        function trainModelWithKnowledge() {
            const targetModel = $('#trainTargetModel').val();
            if (!targetModel) {
                showToast('请选择目标模型', 'error');
                return;
            }

            // 检查是否有训练文档
            const docCount = $('#knowledgeBaseList .model-item').length;
            if (docCount === 0 || $('#knowledgeBaseList').text().includes('知识库为空')) {
                showToast('知识库为空，请先上传训练文档', 'error');
                return;
            }

            if (!confirm('确定要开始训练模型吗？这可能需要较长时间。\n\n提示：训练过程中请勿关闭页面。')) return;

            const data = {
                action: 'trainModel',
                target_model: targetModel,
                epochs: $('#trainEpochs').val(),
                learning_rate: $('#trainLearningRate').val(),
                batch_size: $('#trainBatchSize').val(),
                incremental: $('#incrementalTraining').is(':checked')
            };

            // 显示进度面板 - 修复：使用正确的日志容器ID
            $('#trainingProgressCard').show();
            $('#trainingStatus').text('正在启动...');
            $('#trainingStatus').css('color', '#4c51bf');
            $('#trainingProgressBar').css('width', '0%');
            $('#trainingProgressText').text('0%');
            $('#btnStopTraining').show();
            $('#trainingSpinner').addClass('fa-spin');
            // 修复：使用 trainingLogContainer 而不是 trainingLog
            $('#trainingLogContainer').html(
                '<div style="color: #10b981; padding: 2px 0;">[' + new Date().toLocaleTimeString() + '] [信息] 正在初始化训练任务...</div>' +
                '<div style="color: #64748b; padding: 2px 0;">[' + new Date().toLocaleTimeString() + '] [信息] 目标模型: ' + targetModel + '</div>' +
                '<div style="color: #64748b; padding: 2px 0;">[' + new Date().toLocaleTimeString() + '] [信息] 训练轮数: ' + $('#trainEpochs').val() + '</div>'
            );
            trainingStartTime = Date.now();

            $.post('api/knowledge_handler.php', data, function(response) {
                if (response.status === 'success') {
                    currentTrainingId = response.task_id;
                    showToast('模型训练任务已启动', 'success');
                    $('#trainingStatus').text('训练中...');

                    // 开始轮询进度
                    startTrainingProgressPolling();
                } else {
                    showToast('训练启动失败: ' + response.message, 'error');
                    $('#trainingStatus').text('启动失败');
                    $('#trainingStatus').css('color', '#ef4444');
                }
            }).fail(function() {
                showToast('训练启动失败，请检查网络连接', 'error');
                $('#trainingStatus').text('启动失败');
                $('#trainingStatus').css('color', '#ef4444');
            });
        }

        // 开始训练进度轮询
        function startTrainingProgressPolling() {
            if (trainingProgressInterval) {
                clearInterval(trainingProgressInterval);
            }

            trainingProgressInterval = setInterval(function() {
                if (!currentTrainingId) return;

                $.get('api/knowledge_handler.php?action=getTrainingProgress&task_id=' + currentTrainingId, function(response) {
                    if (response.status === 'success') {
                        updateTrainingProgress(response.data);

                        // 训练完成或失败时停止轮询
                        if (response.data.status === 'completed' || response.data.status === 'failed') {
                            clearInterval(trainingProgressInterval);
                            currentTrainingId = null;
                        }
                    }
                });
            }, 2000); // 每2秒轮询一次

            // 同时更新时间显示
            setInterval(updateTrainingTime, 1000);
        }

        // 更新训练进度显示
        function updateTrainingProgress(data) {
            const progress = data.progress || 0;
            $('#trainingProgressBar').css('width', progress + '%');
            $('#trainingProgressText').text(progress + '%');

            // 更新状态文字
            const statusText = {
                'pending': '等待中...',
                'preparing': '准备数据...',
                'training': '训练中...',
                'completed': '训练完成',
                'failed': '训练失败',
                'stopped': '已停止'
            };
            $('#trainingStatus').text(statusText[data.status] || data.status);

            // 更新日志 - 修复：使用正确的容器ID并添加空值检查
            if (data.logs && Array.isArray(data.logs) && data.logs.length > 0) {
                const logHtml = data.logs.map(log => {
                    // 安全地访问日志属性
                    const time = log.time || log.created_at || new Date().toLocaleTimeString();
                    const message = log.message || log.msg || '无消息';
                    const type = log.type || 'info';
                    const color = type === 'error' ? '#ef4444' : type === 'success' ? '#10b981' : '#e2e8f0';
                    return '<div style="color: ' + color + '; padding: 2px 0;">' +
                           '[' + time + '] ' + message +
                           '</div>';
                }).join('');
                $('#trainingLogContainer').html(logHtml);
                $('#trainingLogContainer').scrollTop($('#trainingLogContainer')[0].scrollHeight);
            } else if (data.message) {
                // 如果没有日志数组但有消息，显示消息
                $('#trainingLogContainer').html('<div style="color: #e2e8f0;">[' + new Date().toLocaleTimeString() + '] ' + data.message + '</div>');
            }

            // 训练完成
            if (data.status === 'completed') {
                $('#trainingStatus').css('color', '#10b981');
                $('#trainingSpinner').removeClass('fa-spin');
                showToast('模型训练完成！', 'success');
                loadTrainingHistory();
            }

            // 训练失败
            if (data.status === 'failed') {
                $('#trainingStatus').css('color', '#ef4444');
                $('#trainingSpinner').removeClass('fa-spin');
                showToast('训练失败: ' + (data.message || '未知错误'), 'error');
                
                // 显示错误日志
                if (data.message) {
                    const errorHtml = '<div style="color: #ef4444; padding: 2px 0;">' +
                                     '[' + new Date().toLocaleTimeString() + '] [错误] ' + data.message +
                                     '</div>';
                    $('#trainingLogContainer').append(errorHtml);
                }
            }
        }

        // 更新训练时间显示
        function updateTrainingTime() {
            if (!trainingStartTime || !currentTrainingId) return;

            const elapsed = Math.floor((Date.now() - trainingStartTime) / 1000);
            const hours = Math.floor(elapsed / 3600).toString().padStart(2, '0');
            const minutes = Math.floor((elapsed % 3600) / 60).toString().padStart(2, '0');
            const seconds = (elapsed % 60).toString().padStart(2, '0');
            $('#trainingTimeElapsed').text('已用时: ' + hours + ':' + minutes + ':' + seconds);
        }

        // 停止训练
        function stopTraining() {
            if (!currentTrainingId) {
                showToast('没有正在进行的训练任务', 'error');
                return;
            }

            if (!confirm('确定要停止当前训练任务吗？')) return;

            $.post('api/knowledge_handler.php', {
                action: 'stopTraining',
                task_id: currentTrainingId
            }, function(response) {
                if (response.status === 'success') {
                    showToast('训练任务已停止', 'success');
                    clearInterval(trainingProgressInterval);
                    currentTrainingId = null;
                    $('#trainingStatus').text('已停止');
                    loadTrainingHistory();
                }
            });
        }

        // 查看训练详情
        function viewTrainingDetails() {
            if (!currentTrainingId) {
                showToast('没有正在进行的训练任务', 'error');
                return;
            }
            // 可以扩展为显示详细信息的模态框
            showToast('训练任务ID: ' + currentTrainingId, 'info');
        }

        // 加载训练历史
        function loadTrainingHistory() {
            $.get('api/knowledge_handler.php?action=getTrainingHistory', function(response) {
                const container = $('#trainingHistoryList');
                container.empty();

                if (response.status === 'success' && response.tasks && response.tasks.length > 0) {
                    response.tasks.forEach(task => {
                        const statusClass = {
                            'completed': 'online',
                            'failed': 'offline',
                            'training': 'online',
                            'pending': 'local',
                            'stopped': 'offline'
                        }[task.status] || 'local';

                        const statusText = {
                            'completed': '已完成',
                            'failed': '失败',
                            'training': '训练中',
                            'pending': '等待中',
                            'stopped': '已停止'
                        }[task.status] || task.status;

                        container.append(
                            '<div class="model-item">' +
                                '<div class="model-info">' +
                                    '<div class="model-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">' +
                                        '<i class="fas fa-brain"></i>' +
                                    '</div>' +
                                    '<div class="model-details">' +
                                        '<h4>' + task.target_model + '</h4>' +
                                        '<p>Epochs: ' + task.epochs + ' | 学习率: ' + task.learning_rate + '</p>' +
                                    '</div>' +
                                    '<span class="model-badge ' + statusClass + '">' + statusText + '</span>' +
                                '</div>' +
                                '<div class="model-actions">' +
                                    '<span style="color: #64748b; font-size: 13px;">' + new Date(task.created_at).toLocaleString() + '</span>' +
                                    (task.status === 'completed' ?
                                        '<button class="btn btn-primary btn-sm" style="margin-left: 12px;" onclick="deployModel(' + task.id + ')">' +
                                            '<i class="fas fa-rocket"></i> 部署' +
                                        '</button>' : '') +
                                '</div>' +
                            '</div>'
                        );
                    });
                } else {
                    container.html(
                        '<div style="color: #64748b; text-align: center; padding: 40px;">' +
                            '<i class="fas fa-history" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>' +
                            '暂无训练记录' +
                        '</div>'
                    );
                }
            });
        }

        // 部署训练好的模型
        function deployModel(taskId) {
            $.post('api/knowledge_handler.php', {
                action: 'deployModel',
                task_id: taskId
            }, function(response) {
                if (response.status === 'success') {
                    showToast('模型部署成功！', 'success');
                } else {
                    showToast('部署失败: ' + response.message, 'error');
                }
            });
        }

        // ========== 日志查看功能 ==========

        let currentLogTaskId = null;
        let currentLogs = [];

        // 查看训练日志
        function viewTrainingLogs(taskId) {
            currentLogTaskId = taskId;
            $('#trainingLogModal').addClass('show');
            loadTrainingLogs(taskId);
        }

        // 关闭训练日志模态框
        function closeTrainingLogModal() {
            $('#trainingLogModal').removeClass('show');
            currentLogTaskId = null;
            currentLogs = [];
        }

        // 加载训练日志
        function loadTrainingLogs(taskId) {
            $('#trainingLogContent').html('<div style="color: #64748b; text-align: center;"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>');
            
            $.get('api/knowledge_handler.php?action=getTrainingLogs&task_id=' + taskId, function(response) {
                if (response.status === 'success') {
                    // 更新任务信息
                    const task = response.task;
                    $('#logTaskId').text(task.id);
                    $('#logModel').text(task.target_model);
                    $('#logStatus').html(getStatusBadge(task.status));
                    $('#logEpochs').text(task.epochs);
                    $('#logLR').text(task.learning_rate);
                    $('#logCreated').text(task.created_at);
                    
                    // 合并日志
                    currentLogs = [];
                    
                    // 数据库日志
                    if (response.logs) {
                        response.logs.forEach(log => {
                            currentLogs.push({
                                time: log.created_at,
                                type: log.type,
                                message: log.message
                            });
                        });
                    }
                    
                    // 文件日志
                    if (response.file_logs) {
                        response.file_logs.forEach(log => {
                            // 解析日志格式 [2024-01-01 12:00:00] message
                            const match = log.match(/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (.*)/);
                            if (match) {
                                currentLogs.push({
                                    time: match[1],
                                    type: 'info',
                                    message: match[2],
                                    source: 'file'
                                });
                            }
                        });
                    }
                    
                    // 按时间排序
                    currentLogs.sort((a, b) => new Date(a.time) - new Date(b.time));
                    
                    renderLogs('all');
                } else {
                    $('#trainingLogContent').html('<div style="color: #ef4444; text-align: center;">加载失败: ' + (response.message || '未知错误') + '</div>');
                }
            }).fail(function() {
                $('#trainingLogContent').html('<div style="color: #ef4444; text-align: center;">加载失败，请检查网络连接</div>');
            });
        }

        // 渲染日志
        function renderLogs(filter) {
            const container = $('#trainingLogContent');
            
            let filteredLogs = currentLogs;
            if (filter !== 'all') {
                filteredLogs = currentLogs.filter(log => log.type === filter);
            }
            
            if (filteredLogs.length === 0) {
                container.html('<div style="color: #64748b; text-align: center;">暂无日志</div>');
                return;
            }
            
            let html = '';
            filteredLogs.forEach(log => {
                const colorClass = {
                    'error': '#ef4444',
                    'warning': '#f59e0b',
                    'info': '#3b82f6',
                    'success': '#10b981'
                }[log.type] || '#94a3b8';
                
                const typeLabel = {
                    'error': '[错误]',
                    'warning': '[警告]',
                    'info': '[信息]',
                    'success': '[成功]'
                }[log.type] || '[日志]';
                
                html += `<div style="margin-bottom: 4px; padding: 4px 0; border-bottom: 1px solid #2d3748;">
                    <span style="color: #64748b; font-size: 11px;">${log.time}</span>
                    <span style="color: ${colorClass}; font-weight: 600;">${typeLabel}</span>
                    <span style="color: #e2e8f0;">${escapeHtml(log.message)}</span>
                </div>`;
            });
            
            container.html(html);
            container.scrollTop(container[0].scrollHeight);
        }

        // 筛选日志
        function filterLogs(type) {
            // 更新按钮样式
            $('#filterAll, #filterInfo, #filterError, #filterWarning').removeClass('btn-primary').addClass('btn-secondary');
            $(`#filter${type.charAt(0).toUpperCase() + type.slice(1)}`).removeClass('btn-secondary').addClass('btn-primary');
            
            renderLogs(type);
        }

        // 下载日志
        function downloadLog() {
            if (currentLogs.length === 0) {
                showToast('没有可下载的日志', 'warning');
                return;
            }
            
            let content = `训练任务日志 - 任务ID: ${currentLogTaskId}\n`;
            content += `导出时间: ${new Date().toLocaleString()}\n`;
            content += '='.repeat(80) + '\n\n';
            
            currentLogs.forEach(log => {
                const typeLabel = {
                    'error': '[错误]',
                    'warning': '[警告]',
                    'info': '[信息]',
                    'success': '[成功]'
                }[log.type] || '[日志]';
                content += `[${log.time}] ${typeLabel} ${log.message}\n`;
            });
            
            const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `training-log-${currentLogTaskId}-${new Date().toISOString().split('T')[0]}.txt`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            showToast('日志已下载', 'success');
        }

        // 查看所有错误日志
        function viewAllErrorLogs() {
            $('#systemErrorLogModal').addClass('show');
            loadSystemErrorLogs();
        }

        // 关闭系统错误日志模态框
        function closeSystemErrorLogModal() {
            $('#systemErrorLogModal').removeClass('show');
        }

        // 加载系统错误日志
        function loadSystemErrorLogs() {
            $('#systemErrorLogContent').html('<div style="color: #64748b; text-align: center;"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>');
            $('#failedTasksList').html('<div style="color: #64748b; text-align: center;">加载中...</div>');
            
            $.get('api/knowledge_handler.php?action=getTrainingErrorLog', function(response) {
                if (response.status === 'success') {
                    // 更新统计
                    const errorLogs = response.error_logs || [];
                    const failedTasks = response.failed_tasks || [];
                    
                    $('#errorCountTotal').text(errorLogs.length);
                    $('#errorCountToday').text(errorLogs.filter(l => l.time && l.time.includes(new Date().toISOString().split('T')[0])).length);
                    $('#failedTaskCount').text(failedTasks.length);
                    
                    // 计算成功率（假设最近20个任务）
                    $.get('api/knowledge_handler.php?action=getTrainingHistory', function(historyRes) {
                        if (historyRes.status === 'success') {
                            const tasks = historyRes.tasks || [];
                            const completed = tasks.filter(t => t.status === 'completed').length;
                            const rate = tasks.length > 0 ? Math.round((completed / tasks.length) * 100) : 100;
                            $('#successRate').text(rate + '%');
                        }
                    });
                    
                    // 渲染失败任务
                    if (failedTasks.length > 0) {
                        let tasksHtml = '';
                        failedTasks.forEach(task => {
                            tasksHtml += `<div style="padding: 12px; margin-bottom: 8px; background: #fee2e2; border-radius: 6px; border-left: 4px solid #dc2626;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-weight: 600; color: #7f1d1d;">任务 #${task.id} - ${task.target_model}</div>
                                        <div style="font-size: 12px; color: #991b1b; margin-top: 4px;">${task.message || '训练失败'}</div>
                                        <div style="font-size: 11px; color: #b91c1c; margin-top: 2px;">${task.created_at}</div>
                                    </div>
                                    <button class="btn btn-sm btn-danger" onclick="viewTrainingLogs(${task.id})">
                                        <i class="fas fa-eye"></i> 查看日志
                                    </button>
                                </div>
                            </div>`;
                        });
                        $('#failedTasksList').html(tasksHtml);
                    } else {
                        $('#failedTasksList').html('<div style="color: #64748b; text-align: center; padding: 20px;"><i class="fas fa-check-circle" style="color: #10b981;"></i> 暂无失败任务</div>');
                    }
                    
                    // 渲染错误日志
                    if (errorLogs.length > 0) {
                        let logsHtml = '';
                        errorLogs.slice(-50).forEach(log => { // 只显示最近50条
                            logsHtml += `<div style="margin-bottom: 4px; padding: 4px 0; border-bottom: 1px solid #2d3748; color: #ef4444;">
                                <span style="color: #64748b; font-size: 11px;">${log.time || '-'}</span>
                                ${escapeHtml(log.message)}
                            </div>`;
                        });
                        $('#systemErrorLogContent').html(logsHtml);
                    } else {
                        $('#systemErrorLogContent').html('<div style="color: #64748b; text-align: center;"><i class="fas fa-check-circle" style="color: #10b981;"></i> 暂无错误日志</div>');
                    }
                } else {
                    $('#systemErrorLogContent').html('<div style="color: #ef4444; text-align: center;">加载失败</div>');
                    $('#failedTasksList').html('<div style="color: #ef4444; text-align: center;">加载失败</div>');
                }
            }).fail(function() {
                $('#systemErrorLogContent').html('<div style="color: #ef4444; text-align: center;">加载失败，请检查网络连接</div>');
                $('#failedTasksList').html('<div style="color: #ef4444; text-align: center;">加载失败</div>');
            });
        }

        // 辅助函数：状态标签
        function getStatusBadge(status) {
            const badges = {
                'completed': '<span style="background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 4px; font-size: 12px;">已完成</span>',
                'failed': '<span style="background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 4px; font-size: 12px;">失败</span>',
                'training': '<span style="background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 4px; font-size: 12px;">训练中</span>',
                'pending': '<span style="background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 4px; font-size: 12px;">等待中</span>',
                'stopped': '<span style="background: #e2e8f0; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 12px;">已停止</span>'
            };
            return badges[status] || `<span style="background: #e2e8f0; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 12px;">${status}</span>`;
        }

        // 辅助函数：HTML转义
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // ========== 清除任务功能 ==========

        // 显示清除任务模态框
        function showClearTasksModal() {
            $('#clearTasksModal').addClass('show');
        }

        // 确认清除任务
        function confirmClearTasks() {
            const deleteType = $('#clearTaskType').val();
            let confirmText = '';
            
            switch(deleteType) {
                case 'completed':
                    confirmText = '确定要清除所有已完成的任务吗？';
                    break;
                case 'failed':
                    confirmText = '确定要清除所有失败的任务吗？';
                    break;
                case 'stopped':
                    confirmText = '确定要清除所有已停止的任务吗？';
                    break;
                case 'all':
                    confirmText = '确定要清除所有任务吗？这将包括正在运行的任务！';
                    break;
            }
            
            if (!confirm(confirmText)) return;
            
            $.post('api/knowledge_handler.php', {
                action: 'clearAllTasks',
                delete_type: deleteType
            }, function(response) {
                if (response.status === 'success') {
                    showToast(response.message, 'success');
                    closeModal();
                    loadTrainingHistory();
                    // 如果清除了所有任务，也刷新进度面板
                    if (deleteType === 'all') {
                        $('#trainingProgressCard').hide();
                        currentTrainingId = null;
                        if (trainingProgressInterval) {
                            clearInterval(trainingProgressInterval);
                        }
                    }
                } else {
                    showToast('清除失败: ' + response.message, 'error');
                }
            });
        }

        // ========== 存储管理功能 ==========

        // 加载存储概览
        function loadStorageOverview() {
            $.get('api/storage_handler.php?action=getOverview', function(response) {
                if (response.status === 'success') {
                    const data = response.data;
                    $('#currentStorageType').text(data.current_type || '本地存储');
                    $('#currentStorageStatus').text(data.status || '运行正常');
                    $('#storageUsed').text(data.used_space || '0 MB');
                    $('#storageTotal').text('共 ' + (data.total_space || '0 MB'));
                    $('#storageLocation').text(data.location || '本地');
                }
            });
        }

        // 加载存储列表
        function loadStorageList() {
            $.get('api/storage_handler.php?action=getStorageList', function(response) {
                const container = $('#storageList');
                container.empty();

                if (response.status === 'success' && response.storages && response.storages.length > 0) {
                    response.storages.forEach(storage => {
                        const typeIcons = {
                            'local': 'fa-desktop',
                            's3': 'fab fa-aws',
                            'oss': 'fas fa-cloud',
                            'cos': 'fas fa-cloud',
                            'minio': 'fas fa-database',
                            'custom': 'fas fa-server',
                            'ipsan': 'fa-network-wired'
                        };
                        const typeColors = {
                            'local': '#4c51bf',
                            's3': '#ff9900',
                            'oss': '#00c1de',
                            'cos': '#006eff',
                            'minio': '#c72e49',
                            'custom': '#16a34a',
                            'ipsan': '#7c3aed'
                        };
                        const typeNames = {
                            'local': '本地存储',
                            's3': 'Amazon S3',
                            'oss': '阿里云OSS',
                            'cos': '腾讯云COS',
                            'minio': 'MinIO',
                            'custom': '自定义存储',
                            'ipsan': 'IP-SAN'
                        };

                        const isDefault = storage.is_default ? '<span class="model-badge online" style="margin-left: 8px;">默认</span>' : '';
                        const statusBadge = storage.status === 'active' 
                            ? '<span class="model-badge online">正常</span>' 
                            : '<span class="model-badge offline">异常</span>';

                        container.append(
                            '<div class="model-item">' +
                                '<div class="model-info">' +
                                    '<div class="model-icon" style="background: ' + (typeColors[storage.type] || '#4c51bf') + ';">' +
                                        '<i class="fas ' + (typeIcons[storage.type] || 'fa-hdd') + '"></i>' +
                                    '</div>' +
                                    '<div class="model-details">' +
                                        '<h4>' + storage.name + isDefault + '</h4>' +
                                        '<p>' + (typeNames[storage.type] || storage.type) + ' | ' + (storage.notes || '无备注') + '</p>' +
                                    '</div>' +
                                    statusBadge +
                                '</div>' +
                                '<div class="model-actions">' +
                                    '<button class="btn btn-secondary btn-sm" onclick="editStorage(' + storage.id + ')" title="编辑">' +
                                        '<i class="fas fa-edit"></i>' +
                                    '</button>' +
                                    (!storage.is_default ? '<button class="btn btn-primary btn-sm" onclick="setDefaultStorage(' + storage.id + ')" style="margin-left: 8px;" title="设为默认">' +
                                        '<i class="fas fa-check"></i>' +
                                    '</button>' : '') +
                                    (!storage.is_default ? '<button class="btn btn-danger btn-sm" onclick="deleteStorage(' + storage.id + ')" style="margin-left: 8px;" title="删除">' +
                                        '<i class="fas fa-trash"></i>' +
                                    '</button>' : '') +
                                '</div>' +
                            '</div>'
                        );
                    });
                } else {
                    container.html(
                        '<div style="color: #64748b; text-align: center; padding: 40px;">' +
                            '<i class="fas fa-hdd" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>' +
                            '暂无存储配置，请先添加存储' +
                        '</div>'
                    );
                }
            });
        }

        // 显示添加存储模态框
        function showAddStorageModal() {
            $('#storageModalTitle').html('<i class="fas fa-hdd"></i> 添加存储');
            $('#storageId').val('');
            $('#storageName').val('');
            $('#storageType').val('local');
            $('#storageIsDefault').prop('checked', false);
            $('#localPath').val('');
            $('#cloudEndpoint').val('');
            $('#cloudBucket').val('');
            $('#cloudRegion').val('');
            $('#cloudAccessKey').val('');
            $('#cloudSecretKey').val('');
            $('#customPathStyle').prop('checked', false);
            $('#storageNotes').val('');
            onStorageTypeChange();
            $('#storageModal').addClass('show');
        }

        // 编辑存储
        function editStorage(storageId) {
            $.get('api/storage_handler.php?action=getStorage&id=' + storageId, function(response) {
                if (response.status === 'success') {
                    const storage = response.storage;
                    $('#storageModalTitle').html('<i class="fas fa-edit"></i> 编辑存储');
                    $('#storageId').val(storage.id);
                    $('#storageName').val(storage.name);
                    $('#storageType').val(storage.type);
                    $('#storageIsDefault').prop('checked', storage.is_default);
                    $('#storageNotes').val(storage.notes || '');
                    
                    // 根据类型填充配置
                    const config = JSON.parse(storage.config || '{}');
                    if (storage.type === 'local') {
                        $('#localPath').val(config.path || '');
                    } else if (storage.type === 'ipsan') {
                        $('#ipsanIp').val(config.ip || '');
                        $('#ipsanPort').val(config.port || 3260);
                        $('#ipsanIqn').val(config.iqn || '');
                        $('#ipsanUsername').val(config.username || '');
                        $('#ipsanMountPath').val(config.mount_path || '');
                        $('#ipsanMutualChap').prop('checked', config.mutual_chap || false);
                        $('#ipsanMutualUsername').val(config.mutual_username || '');
                        if (config.mutual_chap) {
                            $('#ipsanMutualChapFields').show();
                        }
                    } else {
                        $('#cloudEndpoint').val(config.endpoint || '');
                        $('#cloudBucket').val(config.bucket || '');
                        $('#cloudRegion').val(config.region || '');
                        $('#cloudAccessKey').val(config.access_key || '');
                        $('#customPathStyle').prop('checked', config.path_style || false);
                    }
                    
                    onStorageTypeChange();
                    $('#storageModal').addClass('show');
                }
            });
        }

        // 存储类型变化
        function onStorageTypeChange() {
            const type = $('#storageType').val();
            // 隐藏所有特定类型字段
            $('#localStorageFields').hide();
            $('#cloudStorageFields').hide();
            $('#ipsanStorageFields').hide();
            
            if (type === 'local') {
                $('#localStorageFields').show();
            } else if (type === 'ipsan') {
                $('#ipsanStorageFields').show();
            } else {
                $('#cloudStorageFields').show();
                // 自定义存储显示额外选项
                if (type === 'custom' || type === 'minio') {
                    $('#customStorageFields').show();
                } else {
                    $('#customStorageFields').hide();
                }
            }
        }
        
        // IP-SAN Mutual CHAP 切换
        $('#ipsanMutualChap').on('change', function() {
            if ($(this).is(':checked')) {
                $('#ipsanMutualChapFields').show();
            } else {
                $('#ipsanMutualChapFields').hide();
            }
        });

        // 保存存储
        function saveStorage() {
            const storageId = $('#storageId').val();
            const type = $('#storageType').val();
            
            // 构建配置对象
            let config = {};
            if (type === 'local') {
                config = { path: $('#localPath').val() };
            } else if (type === 'ipsan') {
                config = {
                    ip: $('#ipsanIp').val(),
                    port: parseInt($('#ipsanPort').val()) || 3260,
                    iqn: $('#ipsanIqn').val(),
                    username: $('#ipsanUsername').val(),
                    secret_key: $('#ipsanPassword').val(),
                    mount_path: $('#ipsanMountPath').val(),
                    mutual_chap: $('#ipsanMutualChap').is(':checked'),
                    mutual_username: $('#ipsanMutualUsername').val(),
                    mutual_secret: $('#ipsanMutualPassword').val()
                };
            } else {
                config = {
                    endpoint: $('#cloudEndpoint').val(),
                    bucket: $('#cloudBucket').val(),
                    region: $('#cloudRegion').val(),
                    access_key: $('#cloudAccessKey').val(),
                    secret_key: $('#cloudSecretKey').val(),
                    path_style: $('#customPathStyle').is(':checked')
                };
            }
            
            const data = {
                action: storageId ? 'updateStorage' : 'addStorage',
                id: storageId,
                name: $('#storageName').val(),
                type: type,
                is_default: $('#storageIsDefault').is(':checked'),
                config: JSON.stringify(config),
                notes: $('#storageNotes').val()
            };
            
            if (!data.name) {
                showToast('请输入存储名称', 'error');
                return;
            }
            
            // IP-SAN 验证
            if (type === 'ipsan') {
                if (!config.ip || !config.iqn) {
                    showToast('请填写 IP-SAN 的 IP 地址和 IQN', 'error');
                    return;
                }
            }
            
            $.post('api/storage_handler.php', data, function(response) {
                if (response.status === 'success') {
                    showToast(storageId ? '存储已更新' : '存储已添加', 'success');
                    closeModal();
                    loadStorageList();
                    loadStorageOverview();
                } else {
                    showToast('保存失败: ' + response.message, 'error');
                }
            });
        }

        // 设为默认存储
        function setDefaultStorage(storageId) {
            $.post('api/storage_handler.php', {
                action: 'setDefaultStorage',
                id: storageId
            }, function(response) {
                if (response.status === 'success') {
                    showToast('已设为默认存储', 'success');
                    loadStorageList();
                    loadStorageOverview();
                } else {
                    showToast('设置失败: ' + response.message, 'error');
                }
            });
        }

        // 删除存储
        function deleteStorage(storageId) {
            if (!confirm('确定要删除这个存储配置吗？\n\n注意：这不会删除已存储的文件。')) return;
            
            $.post('api/storage_handler.php', {
                action: 'deleteStorage',
                id: storageId
            }, function(response) {
                if (response.status === 'success') {
                    showToast('存储已删除', 'success');
                    loadStorageList();
                    loadStorageOverview();
                } else {
                    showToast('删除失败: ' + response.message, 'error');
                }
            });
        }

        // 测试存储连接
        function testStorageConnection() {
            const type = $('#storageType').val();
            let config = {};
            
            if (type === 'local') {
                config = { path: $('#localPath').val() };
            } else if (type === 'ipsan') {
                config = {
                    ip: $('#ipsanIp').val(),
                    port: parseInt($('#ipsanPort').val()) || 3260,
                    iqn: $('#ipsanIqn').val(),
                    username: $('#ipsanUsername').val(),
                    secret_key: $('#ipsanPassword').val(),
                    mount_path: $('#ipsanMountPath').val(),
                    mutual_chap: $('#ipsanMutualChap').is(':checked'),
                    mutual_username: $('#ipsanMutualUsername').val(),
                    mutual_secret: $('#ipsanMutualPassword').val()
                };
            } else {
                config = {
                    endpoint: $('#cloudEndpoint').val(),
                    bucket: $('#cloudBucket').val(),
                    region: $('#cloudRegion').val(),
                    access_key: $('#cloudAccessKey').val(),
                    secret_key: $('#cloudSecretKey').val()
                };
            }
            
            showToast('正在测试连接...', 'info');
            
            $.post('api/storage_handler.php', {
                action: 'testStorage',
                type: type,
                config: JSON.stringify(config)
            }, function(response) {
                if (response.status === 'success') {
                    showToast(response.message, 'success');
                } else {
                    showToast(response.message, 'error');
                }
            });
        }

        // ========== 计费管理功能 ==========

        // ========== AI服务管理功能 (API配置 + 提供商管理合并) ==========

        // 加载提供商列表
        function loadProviders() {
            $.get('api/providers_handler.php?action=get_providers', function(response) {
                const container = $('#providersList');
                
                if (!response.success) {
                    container.html('<div style="color: #ef4444; text-align: center; padding: 40px;"><i class="fas fa-exclamation-circle" style="font-size: 32px; margin-bottom: 16px; display: block;"></i>加载失败: ' + (response.error || '未知错误') + '</div>');
                    return;
                }
                
                const providers = response.data || [];
                
                if (providers.length === 0) {
                    container.html('<div style="color: #64748b; text-align: center; padding: 40px;"><i class="fas fa-server" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>暂无API提供商<br><button class="btn btn-primary" style="margin-top: 16px;" onclick="showAddProviderModal()"><i class="fas fa-plus"></i> 添加第一个提供商</button></div>');
                    return;
                }
                
                let html = '';
                providers.forEach(provider => {
                    const isActive = provider.is_default ? '<span style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; margin-left: 8px;">默认</span>' : '';
                    const statusBadge = provider.enabled 
                        ? '<span style="background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500;"><i class="fas fa-check-circle"></i> 启用</span>'
                        : '<span style="background: #fee2e2; color: #991b1b; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500;"><i class="fas fa-times-circle"></i> 禁用</span>';
                    
                    const typeIcons = {
                        'ollama': 'fa-server', 'openai': 'fa-cloud', 'azure_openai': 'fa-microsoft',
                        'anthropic': 'fa-brain', 'gemini': 'fa-google', 'deepseek': 'fa-water',
                        'hunyuan': 'fa-qq', 'zhipu': 'fa-comment', 'qwen': 'fa-cloud-upload',
                        'moonshot': 'fa-moon', 'llamacpp': 'fa-microchip', 'vllm': 'fa-bolt',
                        'xinference': 'fa-rocket', 'custom_openai': 'fa-cog', 'gpustack': 'fa-server'
                    };
                    const icon = typeIcons[provider.type] || 'fa-robot';
                    
                    html += `
                        <div class="model-item" style="display: flex; align-items: center; justify-content: space-between; padding: 16px; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 12px; background: white;">
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">
                                    <i class="fas ${icon}"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: #1a202c; font-size: 16px; display: flex; align-items: center;">
                                        ${provider.name}${isActive}
                                    </div>
                                    <div style="font-size: 13px; color: #64748b; margin-top: 4px;">
                                        <i class="fas fa-link"></i> ${provider.config.base_url}
                                        <span style="margin: 0 8px;">|</span>
                                        <i class="fas fa-cube"></i> ${provider.config.default_model || '未设置默认模型'}
                                    </div>
                                    <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">
                                        ${provider.models ? provider.models.length : 0} 个可用模型
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                ${statusBadge}
                                <button class="btn btn-secondary btn-sm" onclick="testProvider('${provider.id}')" title="测试连接">
                                    <i class="fas fa-plug"></i>
                                </button>
                                <button class="btn btn-secondary btn-sm" onclick="fetchProviderModels('${provider.id}')" title="刷新模型列表">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                                <button class="btn btn-primary btn-sm" onclick="editProvider('${provider.id}')" title="编辑">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="deleteProvider('${provider.id}', '${provider.name}')" title="删除">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                });
                
                container.html(html);
            });
        }

        // 加载提供商统计
        function loadProviderStats() {
            $.get('api/providers_handler.php?action=get_stats', function(response) {
                if (response.success) {
                    const stats = response.data;
                    $('#totalProviders').text(stats.total);
                    $('#onlineProviders').text(
                        (stats.by_type['openai']?.count || 0) + 
                        (stats.by_type['anthropic']?.count || 0) + 
                        (stats.by_type['gemini']?.count || 0) +
                        (stats.by_type['deepseek']?.count || 0) +
                        (stats.by_type['hunyuan']?.count || 0) +
                        (stats.by_type['zhipu']?.count || 0) +
                        (stats.by_type['qwen']?.count || 0) +
                        (stats.by_type['moonshot']?.count || 0)
                    );
                    $('#localProviders').text(stats.by_type['ollama']?.count || 0 + stats.by_type['llamacpp']?.count || 0 + stats.by_type['vllm']?.count || 0);
                }
            });
            
            $.get('api/providers_handler.php?action=get_active', function(response) {
                if (response.success) {
                    $('#activeProviderName').text(response.data.name);
                } else {
                    $('#activeProviderName').text('未设置');
                }
            });
        }

        // 测试提供商连接
        function testProvider(providerId) {
            showToast('正在测试连接...', 'info');
            $.get('api/providers_handler.php?action=test_provider&provider_id=' + providerId, function(response) {
                if (response.success) {
                    const modelCount = response.model_count || 0;
                    showToast(`连接成功! 发现 ${modelCount} 个模型`, 'success');
                } else {
                    showToast('连接失败: ' + (response.message || response.error), 'error');
                }
            });
        }

        // 刷新提供商模型列表
        function fetchProviderModels(providerId) {
            showToast('正在获取模型列表...', 'info');
            $.get('api/providers_handler.php?action=fetch_models&provider_id=' + providerId, function(response) {
                if (response.success) {
                    const modelCount = response.model_count || 0;
                    showToast(`已获取 ${modelCount} 个模型`, 'success');
                    loadProviders();
                } else {
                    showToast('获取失败: ' + (response.message || response.error), 'error');
                }
            });
        }

        // 删除提供商
        function deleteProvider(providerId, providerName) {
            if (!confirm(`确定要删除提供商 "${providerName}" 吗?`)) return;
            
            $.post('api/providers_handler.php', {
                action: 'delete_provider',
                provider_id: providerId
            }, function(response) {
                if (response.success) {
                    showToast('提供商已删除', 'success');
                    loadProviders();
                    loadProviderStats();
                } else {
                    showToast('删除失败: ' + (response.error || '未知错误'), 'error');
                }
            });
        }

        // 加载计费统计
        function loadBillingStats() {
            $.get('api/billing_handler.php?action=getStats', function(response) {
                if (response.status === 'success') {
                    $('#totalCost').text(response.data.total_cost.toFixed(2));
                    $('#totalTokens').text(response.data.total_tokens.toLocaleString());
                    $('#activeUsers').text(response.data.active_users);
                    $('#avgCost').text(response.data.avg_cost.toFixed(2));
                }
            });

            // 加载账单记录
            loadBillingRecords();
        }

        // 加载账单记录
        function loadBillingRecords() {
            $.get('api/billing_handler.php?action=getRecords', function(response) {
                const container = $('#billingRecords');
                container.empty();

                if (response.status === 'success' && response.records && response.records.length > 0) {
                    response.records.forEach(record => {
                        container.append(
                            '<div class="model-item">' +
                                '<div class="model-info">' +
                                    '<div class="model-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">' +
                                        '<i class="fas fa-user"></i>' +
                                    '</div>' +
                                    '<div class="model-details">' +
                                        '<h4>' + record.username + '</h4>' +
                                        '<p>' + new Date(record.date).toLocaleDateString() + ' | ' + record.tokens.toLocaleString() + ' Token</p>' +
                                    '</div>' +
                                '</div>' +
                                '<div class="model-actions">' +
                                    '<span style="font-weight: 600; color: #4c51bf;">¥' + record.cost.toFixed(2) + '</span>' +
                                '</div>' +
                            '</div>'
                        );
                    });
                } else {
                    container.html(
                        '<div style="color: #64748b; text-align: center; padding: 40px;">' +
                            '<i class="fas fa-receipt" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>' +
                            '暂无账单记录' +
                        '</div>'
                    );
                }
            });
        }

        // 保存计费配置
        function saveBillingConfig() {
            const data = {
                action: 'saveBillingConfig',
                enabled: $('#billingEnabled').is(':checked'),
                free_quota: $('#freeQuota').val(),
                price_per_1k: $('#tokenPrice').val(),
                cycle: $('#billingCycle').val()
            };

            $.post('api/billing_handler.php', data, function(response) {
                if (response.status === 'success') {
                    showToast('计费设置已保存', 'success');
                } else {
                    showToast('保存失败: ' + response.message, 'error');
                }
            });
        }

        // 导出账单报表
        function exportBillingReport() {
            window.open('api/billing_handler.php?action=exportReport', '_blank');
        }

        // 刷新日志
        function refreshLogs() {
            $('#logContainer').html('<div style="color: #64748b;">日志加载中...</div>');
            $.get('api/admin_handler.php?action=getLogs', function(response) {
                if (response.status === 'success' && response.logs) {
                    $('#logContainer').html(response.logs.replace(/\n/g, '<br>'));
                } else {
                    $('#logContainer').html('<div style="color: #64748b;">暂无日志</div>');
                }
            });
        }

        // ========== 系统状态功能 ==========

        // 加载系统状态数据
        function loadSystemStatusData() {
            // 计算磁盘空间
            updateDiskSpaceDisplay();
            
            // 运行一次快速检查
            $.get('api/system_handler.php?action=getStatus', function(response) {
                if (response.status === 'success') {
                    // 更新数据库状态显示
                    const dbStatus = response.data.database.connected ? '正常' : '异常';
                    const dbColor = response.data.database.connected ? '#10b981' : '#ef4444';
                }
            }).fail(function() {
                console.log('状态检查完成');
            });
        }

        // 更新磁盘空间显示
        function updateDiskSpaceDisplay() {
            // 获取磁盘空间信息（这里使用模拟数据，实际应从后端获取）
            $.get('api/system_handler.php?action=getDiskSpace', function(response) {
                if (response.status === 'success') {
                    const free = formatBytes(response.data.free);
                    const total = formatBytes(response.data.total);
                    const usedPercent = ((response.data.total - response.data.free) / response.data.total * 100).toFixed(1);
                    $('#diskSpace').html(free + ' / ' + total + '<br><span style="font-size: 12px; color: #64748b;">已用 ' + usedPercent + '%</span>');
                } else {
                    $('#diskSpace').text('无法获取');
                }
            }).fail(function() {
                $('#diskSpace').text('无法获取');
            });
        }

        // 运行系统巡检
        function runSystemInspect() {
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 巡检中...';
            btn.disabled = true;
            
            const resultDiv = $('#inspectResult');
            resultDiv.hide();
            
            $.get('api/system_handler.php?action=inspect', function(response) {
                btn.innerHTML = originalText;
                btn.disabled = false;
                
                let resultClass = 'success';
                let icon = 'fa-check-circle';
                let title = '巡检完成';
                
                if (response.status !== 'success') {
                    resultClass = 'error';
                    icon = 'fa-times-circle';
                    title = '巡检发现问题';
                } else if (response.warnings && response.warnings.length > 0) {
                    resultClass = 'warning';
                    icon = 'fa-exclamation-triangle';
                    title = '巡检完成，存在警告';
                }
                
                let html = '<h4><i class="fas ' + icon + '"></i> ' + title + '</h4>';
                html += '<div style="margin-top: 12px;">';
                
                if (response.checks) {
                    response.checks.forEach(check => {
                        const checkIcon = check.passed ? 'fa-check' : (check.warning ? 'fa-exclamation' : 'fa-times');
                        const checkColor = check.passed ? '#10b981' : (check.warning ? '#f59e0b' : '#ef4444');
                        html += '<div style="padding: 8px 0; border-bottom: 1px solid rgba(0,0,0,0.05);">';
                        html += '<i class="fas ' + checkIcon + '" style="color: ' + checkColor + '; margin-right: 8px;"></i>';
                        html += '<strong>' + check.name + '</strong>: ' + check.message;
                        html += '</div>';
                    });
                }
                
                html += '</div>';
                
                resultDiv.removeClass('success warning error').addClass(resultClass).html(html).show();
            }).fail(function() {
                btn.innerHTML = originalText;
                btn.disabled = false;
                resultDiv.removeClass('success warning error').addClass('error');
                resultDiv.html('<h4><i class="fas fa-times-circle"></i> 巡检失败</h4><p>无法连接到服务器</p>').show();
            });
        }

        // ========== 日志查看功能 ==========

        let currentLogPage = 1;
        let totalLogPages = 1;

        // 加载系统日志数据
        function loadSystemLogsData() {
            const level = $('#logLevelFilter').val();
            const date = $('#logDateFilter').val();
            const keyword = $('#logSearchFilter').val();
            
            const tbody = $('#logsTableBody');
            tbody.html('<tr><td colspan="4" style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #667eea;"></i><br>加载中...</td></tr>');
            
            $.get('api/logs_handler.php?action=getLogs&page=' + currentLogPage + '&level=' + level + '&date=' + date + '&keyword=' + encodeURIComponent(keyword), function(response) {
                if (response.status === 'success') {
                    renderLogsTable(response.data.logs);
                    totalLogPages = response.data.total_pages || 1;
                    renderLogsPagination();
                } else {
                    tbody.html('<tr><td colspan="4" style="text-align: center; padding: 40px; color: #94a3b8;">暂无日志数据</td></tr>');
                }
            }).fail(function() {
                tbody.html('<tr><td colspan="4" style="text-align: center; padding: 40px; color: #ef4444;">加载失败，请重试</td></tr>');
            });
        }

        // 渲染日志表格
        function renderLogsTable(logs) {
            const tbody = $('#logsTableBody');
            tbody.empty();
            
            if (!logs || logs.length === 0) {
                tbody.html('<tr><td colspan="4" style="text-align: center; padding: 40px; color: #94a3b8;"><i class="fas fa-inbox" style="font-size: 24px; margin-bottom: 12px; display: block;"></i>暂无日志数据</td></tr>');
                return;
            }
            
            logs.forEach(log => {
                const levelColors = {
                    'error': '#fee2e2',
                    'warning': '#fef3c7',
                    'info': '#dbeafe',
                    'debug': '#f3f4f6'
                };
                const levelTextColors = {
                    'error': '#dc2626',
                    'warning': '#d97706',
                    'info': '#2563eb',
                    'debug': '#6b7280'
                };
                const bgColor = levelColors[log.level] || levelColors.info;
                const textColor = levelTextColors[log.level] || levelTextColors.info;
                
                const row = `<tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 12px; font-size: 13px; color: #64748b; white-space: nowrap;">${log.time}</td>
                    <td style="padding: 12px;"><span style="background: ${bgColor}; color: ${textColor}; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase;">${log.level}</span></td>
                    <td style="padding: 12px; font-size: 13px; color: #64748b;">${log.source}</td>
                    <td style="padding: 12px; font-size: 13px; color: #374151; max-width: 400px; overflow: hidden; text-overflow: ellipsis;">${log.message}</td>
                </tr>`;
                tbody.append(row);
            });
        }

        // 渲染日志分页
        function renderLogsPagination() {
            const container = $('#logsPagination');
            if (totalLogPages <= 1) {
                container.empty();
                return;
            }
            
            let html = '';
            // 上一页
            html += `<button class="btn btn-secondary btn-sm" onclick="changeLogPage(${currentLogPage - 1})" ${currentLogPage <= 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i></button>`;
            
            // 页码
            for (let i = 1; i <= totalLogPages; i++) {
                if (i === 1 || i === totalLogPages || (i >= currentLogPage - 2 && i <= currentLogPage + 2)) {
                    const activeClass = i === currentLogPage ? 'btn-primary' : 'btn-secondary';
                    html += `<button class="btn ${activeClass} btn-sm" onclick="changeLogPage(${i})">${i}</button>`;
                } else if (i === currentLogPage - 3 || i === currentLogPage + 3) {
                    html += `<span style="padding: 0 8px; color: #94a3b8;">...</span>`;
                }
            }
            
            // 下一页
            html += `<button class="btn btn-secondary btn-sm" onclick="changeLogPage(${currentLogPage + 1})" ${currentLogPage >= totalLogPages ? 'disabled' : ''}><i class="fas fa-chevron-right"></i></button>`;
            
            container.html(html);
        }

        // 切换日志页码
        function changeLogPage(page) {
            if (page < 1 || page > totalLogPages) return;
            currentLogPage = page;
            loadSystemLogsData();
        }

        // AI分析日志
        function analyzeLogsWithAI() {
            const level = $('#logLevelFilter').val();
            const date = $('#logDateFilter').val();
            
            showToast('正在使用AI分析日志...', 'info');
            
            $.get('api/logs_handler.php?action=analyzeWithAI&level=' + level + '&date=' + date, function(response) {
                if (response.status === 'success') {
                    // 显示分析结果弹窗
                    const modal = $('<div class="modal" style="display: flex;"><div class="modal-content" style="max-width: 700px; max-height: 80vh; overflow-y: auto;"><div class="modal-header"><h3 class="modal-title"><i class="fas fa-brain"></i> AI日志分析</h3><button class="modal-close" onclick="$(this).closest(\'.modal\').remove()">&times;</button></div><div class="modal-body">' + response.analysis.replace(/\n/g, '<br>') + '</div></div></div>');
                    $('body').append(modal);
                } else {
                    showToast('分析失败: ' + (response.message || '未知错误'), 'error');
                }
            }).fail(function() {
                showToast('分析请求失败', 'error');
            });
        }

        // 导出日志
        function exportLogs() {
            const level = $('#logLevelFilter').val();
            const date = $('#logDateFilter').val();
            window.open('api/logs_handler.php?action=export&level=' + level + '&date=' + date, '_blank');
        }

        // 清空日志
        function clearLogs() {
            if (!confirm('确定要清空所有日志吗？此操作不可恢复。')) return;
            
            $.post('api/logs_handler.php?action=clear', function(response) {
                if (response.status === 'success') {
                    showToast('日志已清空', 'success');
                    loadSystemLogsData();
                } else {
                    showToast('清空失败: ' + (response.message || '未知错误'), 'error');
                }
            }).fail(function() {
                showToast('请求失败', 'error');
            });
        }

        // 格式化字节
        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Toast提示
        function showToast(message, type) {
            const toast = $(`<div class="toast ${type}">${message}</div>`);
            $('body').append(toast);
            setTimeout(() => toast.fadeOut(300, function() { $(this).remove(); }), 3000);
        }

        // 初始化
        $(document).ready(function() {
            disableLegacyLocalModelUI();
            loadOnlineModels();
            
            // 绑定创建API密钥按钮事件
            const createApiKeyBtn = document.getElementById('createApiKeyBtn');
            if (createApiKeyBtn) {
                createApiKeyBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Create API Key button clicked');
                    showCreateApiKeyModal();
                });
            }
        });
    </script>
</body>
</html>
