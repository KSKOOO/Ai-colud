<?php
/**
 * 日志查看器 - 系统日志分析
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']) {
    header('Location: ?route=login');
    exit;
}

// 只有管理员可以查看日志
$isAdmin = ($_SESSION['user']['role'] ?? '') === 'admin';

$config = require __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>日志查看 - <?php echo $config['app']['name'] ?? '巨神兵API辅助平台API辅助平台'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #1a202c;
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #64748b;
            font-size: 16px;
        }
        
        /* 统计卡片 */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .stat-card h3 {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
        }
        
        .stat-change {
            font-size: 13px;
            margin-top: 8px;
        }
        
        .stat-change.positive {
            color: #22c55e;
        }
        
        .stat-change.negative {
            color: #ef4444;
        }
        
        /* 主内容区 */
        .main-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        /* 工具栏 */
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .toolbar select, .toolbar input {
            padding: 10px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
        }
        
        .toolbar select:focus, .toolbar input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        /* 日志表格 */
        .logs-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .logs-table th {
            background: #f9fafb;
            padding: 14px 16px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .logs-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        
        .logs-table tr:hover {
            background: #f9fafb;
        }
        
        .log-level {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .log-level.error {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .log-level.warning {
            background: #fef3c7;
            color: #d97706;
        }
        
        .log-level.info {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .log-level.debug {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .log-message {
            max-width: 400px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .log-message:hover {
            white-space: normal;
            overflow: visible;
        }
        
        /* 分页 */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }
        
        .page-btn {
            padding: 8px 14px;
            border: 1px solid #e5e7eb;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .page-btn:hover, .page-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }
        
        /* 日志分析图表区 */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .chart-card h3 {
            font-size: 16px;
            margin-bottom: 20px;
            color: #374151;
        }
        
        .chart-placeholder {
            height: 200px;
            background: #f9fafb;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
        }
        
        /* 返回按钮 */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.2s;
            margin-bottom: 20px;
        }
        
        .back-btn:hover {
            transform: translateX(-4px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        /* 权限提示 */
        .permission-alert {
            background: #fee2e2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 20px;
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
    <div class="container">
        <a href="?route=home" class="back-btn">
            <i class="fas fa-arrow-left"></i> 返回首页
        </a>
        
        <div class="header">
            <h1><i class="fas fa-file-alt"></i> 日志查看与分析</h1>
            <p>查看系统运行日志，监控系统状态，分析错误信息</p>
        </div>
        
        <?php if (!$isAdmin): ?>
        <div class="permission-alert">
            <i class="fas fa-lock"></i>
            <h3>权限不足</h3>
            <p>只有管理员可以查看系统日志</p>
        </div>
        <?php else: ?>
        
        <!-- 统计卡片 -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><i class="fas fa-list-ol"></i> 今日日志总数</h3>
                <div class="stat-value" id="todayLogs">0</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> <span id="logsChange">0%</span> 较昨日
                </div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-exclamation-triangle"></i> 错误日志</h3>
                <div class="stat-value" id="errorLogs">0</div>
                <div class="stat-change negative">
                    <i class="fas fa-exclamation-circle"></i> 需要关注
                </div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-bolt"></i> API调用次数</h3>
                <div class="stat-value" id="apiCalls">0</div>
                <div class="stat-change positive">
                    <i class="fas fa-check-circle"></i> 运行正常
                </div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-users"></i> 活跃用户数</h3>
                <div class="stat-value" id="activeUsers">0</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> <span id="usersChange">0%</span> 较昨日
                </div>
            </div>
        </div>
        
        <!-- 日志分析图表 -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3><i class="fas fa-chart-line"></i> 日志趋势分析</h3>
                <div class="chart-placeholder" id="trendChart">
                    <i class="fas fa-chart-line" style="font-size: 48px;"></i>
                </div>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-chart-pie"></i> 日志级别分布</h3>
                <div class="chart-placeholder" id="levelChart">
                    <i class="fas fa-chart-pie" style="font-size: 48px;"></i>
                </div>
            </div>
        </div>
        
        <!-- 日志列表 -->
        <div class="main-content">
            <div class="toolbar">
                <select id="logLevel">
                    <option value="">所有级别</option>
                    <option value="error">错误</option>
                    <option value="warning">警告</option>
                    <option value="info">信息</option>
                    <option value="debug">调试</option>
                </select>
                <select id="logSource">
                    <option value="">所有来源</option>
                    <option value="api">API接口</option>
                    <option value="auth">认证系统</option>
                    <option value="database">数据库</option>
                    <option value="system">系统</option>
                </select>
                <input type="date" id="startDate" placeholder="开始日期">
                <input type="date" id="endDate" placeholder="结束日期">
                <input type="text" id="searchKeyword" placeholder="搜索关键词...">
                <button class="btn btn-primary" onclick="loadLogs()">
                    <i class="fas fa-search"></i> 查询
                </button>
                <button class="btn btn-secondary" onclick="exportLogs()">
                    <i class="fas fa-download"></i> 导出
                </button>
                <button class="btn btn-secondary" onclick="analyzeLogs()">
                    <i class="fas fa-brain"></i> AI分析
                </button>
            </div>
            
            <table class="logs-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">级别</th>
                        <th style="width: 140px;">时间</th>
                        <th style="width: 100px;">来源</th>
                        <th>消息</th>
                        <th style="width: 100px;">用户</th>
                        <th style="width: 80px;">IP</th>
                    </tr>
                </thead>
                <tbody id="logsTableBody">
                    <tr>
                        <td colspan="6" style="text-align: center; color: #9ca3af; padding: 40px;">
                            <i class="fas fa-spinner fa-spin"></i> 加载中...
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div class="pagination" id="pagination">
                <button class="page-btn" onclick="changePage(-1)">上一页</button>
                <button class="page-btn active">1</button>
                <button class="page-btn">2</button>
                <button class="page-btn">3</button>
                <button class="page-btn" onclick="changePage(1)">下一页</button>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentPage = 1;
        let totalPages = 1;
        
        $(document).ready(function() {
            <?php if ($isAdmin): ?>
            loadStats();
            loadLogs();
            <?php endif; ?>
        });
        
        // 加载统计数据
        function loadStats() {
            $.ajax({
                url: 'api/logs_api.php?action=get_stats',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#todayLogs').text(response.data.today_logs || 0);
                        $('#errorLogs').text(response.data.error_logs || 0);
                        $('#apiCalls').text(response.data.api_calls || 0);
                        $('#activeUsers').text(response.data.active_users || 0);
                        $('#logsChange').text((response.data.logs_change || 0) + '%');
                        $('#usersChange').text((response.data.users_change || 0) + '%');
                    }
                }
            });
        }
        
        // 加载日志列表
        function loadLogs() {
            const filters = {
                level: $('#logLevel').val(),
                source: $('#logSource').val(),
                start_date: $('#startDate').val(),
                end_date: $('#endDate').val(),
                keyword: $('#searchKeyword').val(),
                page: currentPage
            };
            
            $.ajax({
                url: 'api/logs_api.php?action=get_logs',
                type: 'GET',
                data: filters,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        renderLogs(response.data || []);
                        totalPages = response.total_pages || 1;
                        renderPagination();
                    } else {
                        $('#logsTableBody').html('<tr><td colspan="6" style="text-align: center; color: #ef4444;">加载失败</td></tr>');
                    }
                },
                error: function() {
                    $('#logsTableBody').html('<tr><td colspan="6" style="text-align: center; color: #ef4444;">请求失败</td></tr>');
                }
            });
        }
        
        // 渲染日志列表
        function renderLogs(logs) {
            if (logs.length === 0) {
                $('#logsTableBody').html('<tr><td colspan="6" style="text-align: center; color: #6b7280; padding: 40px;">暂无日志记录</td></tr>');
                return;
            }
            
            let html = '';
            logs.forEach(function(log) {
                const levelClass = log.level || 'info';
                const levelText = {
                    'error': '错误',
                    'warning': '警告',
                    'info': '信息',
                    'debug': '调试'
                }[levelClass] || '信息';
                
                html += `
                    <tr>
                        <td><span class="log-level ${levelClass}">${levelText}</span></td>
                        <td>${log.created_at || '--'}</td>
                        <td>${log.source || '--'}</td>
                        <td class="log-message" title="${log.message || ''}">${log.message || '--'}</td>
                        <td>${log.user_name || '--'}</td>
                        <td>${log.ip || '--'}</td>
                    </tr>
                `;
            });
            $('#logsTableBody').html(html);
        }
        
        // 渲染分页
        function renderPagination() {
            let html = '';
            html += `<button class="page-btn ${currentPage <= 1 ? 'disabled' : ''}" onclick="changePage(-1)">上一页</button>`;
            
            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                    html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
                } else if (i === currentPage - 2 || i === currentPage + 2) {
                    html += `<span>...</span>`;
                }
            }
            
            html += `<button class="page-btn ${currentPage >= totalPages ? 'disabled' : ''}" onclick="changePage(1)">下一页</button>`;
            $('#pagination').html(html);
        }
        
        // 切换页面
        function changePage(delta) {
            const newPage = currentPage + delta;
            if (newPage >= 1 && newPage <= totalPages) {
                currentPage = newPage;
                loadLogs();
            }
        }
        
        // 跳转到指定页
        function goToPage(page) {
            currentPage = page;
            loadLogs();
        }
        
        // 导出日志
        function exportLogs() {
            alert('导出功能开发中...');
        }
        
        // AI分析日志
        function analyzeLogs() {
            alert('AI日志分析功能开发中...');
        }
    </script>
</body>
</html>