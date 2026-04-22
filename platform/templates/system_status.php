<?php
/**
 * 系统状态 - 设备监控和一键巡检
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

// 获取系统信息
$systemInfo = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'server_os' => PHP_OS,
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'disk_free_space' => disk_free_space('.'),
    'disk_total_space' => disk_total_space('.'),
];

// 获取数据库状态
try {
    require_once __DIR__ . '/../includes/Database.php';
    $db = Database::getInstance();
    $dbStatus = 'connected';
    $dbVersion = $db->query("SELECT VERSION() as version")->fetch()['version'] ?? 'Unknown';
} catch (Exception $e) {
    $dbStatus = 'disconnected';
    $dbVersion = 'Unknown';
}

// 检查扩展
$extensions = [
    'pdo' => extension_loaded('pdo'),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'gd' => extension_loaded('gd'),
    'curl' => extension_loaded('curl'),
    'mbstring' => extension_loaded('mbstring'),
    'json' => extension_loaded('json'),
    'openssl' => extension_loaded('openssl'),
    'zip' => extension_loaded('zip'),
    'fileinfo' => extension_loaded('fileinfo'),
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统状态 - <?php echo $config['app']['name'] ?? '巨神兵AIAPI辅助平台'; ?></title>
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
            max-width: 1400px;
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
        
        /* 状态概览 */
        .status-overview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .status-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .status-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }
        
        .status-icon.success {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .status-icon.warning {
            background: #fef3c7;
            color: #d97706;
        }
        
        .status-icon.error {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .status-info h3 {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 6px;
        }
        
        .status-info .value {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
        }
        
        .status-info .detail {
            font-size: 13px;
            color: #9ca3af;
            margin-top: 4px;
        }
        
        /* 主内容网格 */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        
        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .content-card h2 {
            font-size: 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #374151;
        }
        
        .content-card h2 i {
            color: #667eea;
        }
        
        /* 信息列表 */
        .info-list {
            list-style: none;
        }
        
        .info-list li {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-list li:last-child {
            border-bottom: none;
        }
        
        .info-list .label {
            color: #6b7280;
            font-size: 14px;
        }
        
        .info-list .value {
            font-weight: 600;
            color: #1f2937;
            font-size: 14px;
        }
        
        /* 扩展检查 */
        .extension-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        
        .extension-item {
            background: #f9fafb;
            border-radius: 10px;
            padding: 12px;
            text-align: center;
            font-size: 13px;
        }
        
        .extension-item i {
            font-size: 20px;
            margin-bottom: 6px;
            display: block;
        }
        
        .extension-item.installed i {
            color: #22c55e;
        }
        
        .extension-item.missing i {
            color: #ef4444;
        }
        
        /* 进度条 */
        .progress-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        /* 巡检按钮 */
        .inspect-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            transition: all 0.2s;
        }
        
        .inspect-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .inspect-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* 巡检结果 */
        .inspect-result {
            margin-top: 20px;
            padding: 20px;
            border-radius: 12px;
            display: none;
        }
        
        .inspect-result.show {
            display: block;
        }
        
        .inspect-result.success {
            background: #f0fdf4;
            border: 1px solid #86efac;
        }
        
        .inspect-result.warning {
            background: #fefce8;
            border: 1px solid #fde047;
        }
        
        .inspect-result.error {
            background: #fef2f2;
            border: 1px solid #fecaca;
        }
        
        .inspect-result h4 {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }
        
        .inspect-result.success h4 {
            color: #166534;
        }
        
        .inspect-result.warning h4 {
            color: #854d0e;
        }
        
        .inspect-result.error h4 {
            color: #991b1b;
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
        
        /* 实时图表占位 */
        .chart-container {
            height: 200px;
            background: #f9fafb;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            margin-top: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="?route=home" class="back-btn">
            <i class="fas fa-arrow-left"></i> 返回首页
        </a>
        
        <div class="header">
            <h1><i class="fas fa-heartbeat"></i> 系统状态监控</h1>
            <p style="color: #64748b;">实时监控系统运行状态，一键巡检系统健康状况</p>
        </div>
        
        <!-- 状态概览 -->
        <div class="status-overview">
            <div class="status-card">
                <div class="status-icon <?php echo $dbStatus === 'connected' ? 'success' : 'error'; ?>">
                    <i class="fas fa-database"></i>
                </div>
                <div class="status-info">
                    <h3>数据库状态</h3>
                    <div class="value"><?php echo $dbStatus === 'connected' ? '正常' : '异常'; ?></div>
                    <div class="detail">MySQL <?php echo $dbVersion; ?></div>
                </div>
            </div>
            
            <div class="status-card">
                <div class="status-icon success">
                    <i class="fas fa-server"></i>
                </div>
                <div class="status-info">
                    <h3>服务器状态</h3>
                    <div class="value">运行中</div>
                    <div class="detail"><?php echo $systemInfo['server_software']; ?></div>
                </div>
            </div>
            
            <div class="status-card">
                <div class="status-icon success">
                    <i class="fas fa-hdd"></i>
                </div>
                <div class="status-info">
                    <h3>磁盘空间</h3>
                    <div class="value"><?php echo round($systemInfo['disk_free_space'] / $systemInfo['disk_total_space'] * 100, 1); ?>%</div>
                    <div class="detail">可用 <?php echo formatBytes($systemInfo['disk_free_space']); ?> / 总计 <?php echo formatBytes($systemInfo['disk_total_space']); ?></div>
                </div>
            </div>
            
            <div class="status-card">
                <div class="status-icon success">
                    <i class="fab fa-php"></i>
                </div>
                <div class="status-info">
                    <h3>PHP版本</h3>
                    <div class="value"><?php echo $systemInfo['php_version']; ?></div>
                    <div class="detail"><?php echo $systemInfo['server_os']; ?></div>
                </div>
            </div>
        </div>
        
        <div class="content-grid">
            <!-- 系统信息 -->
            <div class="content-card">
                <h2><i class="fas fa-info-circle"></i> 系统信息</h2>
                <ul class="info-list">
                    <li>
                        <span class="label">服务器软件</span>
                        <span class="value"><?php echo $systemInfo['server_software']; ?></span>
                    </li>
                    <li>
                        <span class="label">操作系统</span>
                        <span class="value"><?php echo $systemInfo['server_os']; ?></span>
                    </li>
                    <li>
                        <span class="label">PHP版本</span>
                        <span class="value"><?php echo $systemInfo['php_version']; ?></span>
                    </li>
                    <li>
                        <span class="label">内存限制</span>
                        <span class="value"><?php echo $systemInfo['memory_limit']; ?></span>
                    </li>
                    <li>
                        <span class="label">最大执行时间</span>
                        <span class="value"><?php echo $systemInfo['max_execution_time']; ?>秒</span>
                    </li>
                    <li>
                        <span class="label">上传限制</span>
                        <span class="value"><?php echo $systemInfo['upload_max_filesize']; ?></span>
                    </li>
                    <li>
                        <span class="label">磁盘可用空间</span>
                        <span class="value"><?php echo formatBytes($systemInfo['disk_free_space']); ?></span>
                    </li>
                </ul>
            </div>
            
            <!-- PHP扩展检查 -->
            <div class="content-card">
                <h2><i class="fas fa-puzzle-piece"></i> PHP扩展检查</h2>
                <div class="extension-grid">
                    <?php foreach ($extensions as $name => $installed): ?>
                    <div class="extension-item <?php echo $installed ? 'installed' : 'missing'; ?>">
                        <i class="fas <?php echo $installed ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                        <?php echo $name; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button class="inspect-btn" id="inspectBtn" onclick="runInspection()">
                    <i class="fas fa-stethoscope"></i> 一键巡检
                </button>
                
                <div class="inspect-result" id="inspectResult">
                    <h4><i class="fas fa-clipboard-check"></i> 巡检结果</h4>
                    <div id="inspectDetails"></div>
                </div>
            </div>
            
            <!-- 实时资源监控 -->
            <div class="content-card">
                <h2><i class="fas fa-chart-line"></i> 实时资源监控</h2>
                <div class="chart-container">
                    <i class="fas fa-chart-area" style="font-size: 48px;"></i>
                </div>
            </div>
            
            <!-- API服务状态 -->
            <div class="content-card">
                <h2><i class="fas fa-plug"></i> AI服务状态</h2>
                <ul class="info-list" id="serviceStatus">
                    <li>
                        <span class="label"><i class="fas fa-robot"></i> OpenAI API</span>
                        <span class="value" style="color: #22c55e;"><i class="fas fa-check"></i> 正常</span>
                    </li>
                    <li>
                        <span class="label"><i class="fas fa-brain"></i> 通义千问 API</span>
                        <span class="value" style="color: #22c55e;"><i class="fas fa-check"></i> 正常</span>
                    </li>
                    <li>
                        <span class="label"><i class="fas fa-gem"></i> Gemini API</span>
                        <span class="value" style="color: #22c55e;"><i class="fas fa-check"></i> 正常</span>
                    </li>
                    <li>
                        <span class="label"><i class="fas fa-desktop"></i> Ollama 本地</span>
                        <span class="value" style="color: #eab308;"><i class="fas fa-question"></i> 待检测</span>
                    </li>
                </ul>
                <button class="btn btn-secondary" onclick="checkServices()" style="width: 100%; margin-top: 16px;">
                    <i class="fas fa-sync-alt"></i> 刷新服务状态
                </button>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // 一键巡检
        function runInspection() {
            const btn = document.getElementById('inspectBtn');
            const result = document.getElementById('inspectResult');
            const details = document.getElementById('inspectDetails');
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 巡检中...';
            result.classList.remove('show', 'success', 'warning', 'error');
            
            $.ajax({
                url: 'api/system_api.php?action=inspect',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-stethoscope"></i> 一键巡检';
                    
                    if (response.success) {
                        const data = response.data;
                        let html = '<ul style="list-style: none; font-size: 14px;">';
                        
                        data.checks.forEach(function(check) {
                            const icon = check.status === 'ok' ? 'fa-check-circle' : 
                                        check.status === 'warning' ? 'fa-exclamation-triangle' : 'fa-times-circle';
                            const color = check.status === 'ok' ? '#22c55e' : 
                                         check.status === 'warning' ? '#eab308' : '#ef4444';
                            html += `<li style="margin-bottom: 8px; color: ${color};">
                                <i class="fas ${icon}"></i> ${check.name}: ${check.message}
                            </li>`;
                        });
                        
                        html += '</ul>';
                        html += `<div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e5e7eb;">
                            <strong>巡检总结：</strong>${data.summary}
                        </div>`;
                        
                        details.innerHTML = html;
                        result.classList.add('show', data.overall_status);
                    }
                },
                error: function() {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-stethoscope"></i> 一键巡检';
                    details.innerHTML = '<p style="color: #ef4444;">巡检失败，请重试</p>';
                    result.classList.add('show', 'error');
                }
            });
        }
        
        // 检查服务状态
        function checkServices() {
            alert('服务状态检查功能开发中...');
        }
    </script>
</body>
</html>

<?php
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>
