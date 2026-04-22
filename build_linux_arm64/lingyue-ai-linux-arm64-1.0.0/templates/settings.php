<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']) {
    header('Location: ?route=login');
    exit;
}

if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ?route=home&error=permission_denied');
    exit;
}


$config = require __DIR__ . '/../config/config.php';


$moduleHealth = checkModuleHealth();


function checkModuleHealth() {
    $health = [
        'database' => ['status' => 'unknown', 'message' => ''],
        'ollama' => ['status' => 'unknown', 'message' => ''],
        'gpustack' => ['status' => 'unknown', 'message' => ''],
        'storage' => ['status' => 'unknown', 'message' => ''],
        'logs' => ['status' => 'unknown', 'message' => ''],
        'uploads' => ['status' => 'unknown', 'message' => ''],
        'workflows' => ['status' => 'unknown', 'message' => ''],
        'ffmpeg' => ['status' => 'unknown', 'message' => '']
    ];
    

    try {
        require_once __DIR__ . '/../config/database.php';
        $db = Database::getInstance();
        $db->query("SELECT 1");
        $health['database'] = ['status' => 'healthy', 'message' => '连接正常'];
    } catch (Exception $e) {
        $health['database'] = ['status' => 'error', 'message' => $e->getMessage()];
    }
    

    $ollamaConfig = require __DIR__ . '/../config/config.php';
    $ollamaUrl = $ollamaConfig['ollama_api']['base_url'] ?? 'http://localhost:11434';
    $ch = curl_init($ollamaUrl . '/api/tags');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    
    if ($httpCode === 200) {
        $health['ollama'] = ['status' => 'healthy', 'message' => '服务正常运行'];
    } else {
        $health['ollama'] = ['status' => 'warning', 'message' => '服务未响应 (HTTP ' . $httpCode . ')'];
    }
    

    if (!empty($ollamaConfig['gpustack_api']['enabled'])) {
        $gpustackUrl = $ollamaConfig['gpustack_api']['base_url'];
        if (!empty($gpustackUrl)) {
            $ch = curl_init($gpustackUrl . '/v1/models');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $apiKey = $ollamaConfig['gpustack_api']['api_key'] ?? '';
            if ($apiKey) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $apiKey]);
            }
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            
            if ($httpCode === 200) {
                $health['gpustack'] = ['status' => 'healthy', 'message' => 'API连接正常'];
            } else {
                $health['gpustack'] = ['status' => 'warning', 'message' => 'API未响应 (HTTP ' . $httpCode . ')'];
            }
        } else {
            $health['gpustack'] = ['status' => 'disabled', 'message' => '未配置'];
        }
    } else {
        $health['gpustack'] = ['status' => 'disabled', 'message' => '已禁用'];
    }
    

    $dirs = [
        'storage' => __DIR__ . '/../storage/',
        'logs' => __DIR__ . '/../logs/',
        'uploads' => __DIR__ . '/../uploads/'
    ];
    
    foreach ($dirs as $key => $dir) {
        if (!is_dir($dir)) {
            $health[$key] = ['status' => 'error', 'message' => '目录不存在'];
        } elseif (!is_writable($dir)) {
            $health[$key] = ['status' => 'error', 'message' => '目录不可写'];
        } else {
            $freeSpace = disk_free_space($dir);
            $totalSpace = disk_total_space($dir);
            $usedPercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;
            
            if ($usedPercent > 90) {
                $health[$key] = ['status' => 'warning', 'message' => '存储空间不足 (' . round($usedPercent, 1) . '% 已用)'];
            } else {
                $health[$key] = ['status' => 'healthy', 'message' => '正常 (' . round($usedPercent, 1) . '% 已用)'];
            }
        }
    }
    

    $workflowFiles = [
        __DIR__ . '/../lib/WorkflowEngine.php',
        __DIR__ . '/../lib/ComfyUIWorkflowEngine.php'
    ];
    $allWorkflowFilesExist = true;
    foreach ($workflowFiles as $file) {
        if (!file_exists($file)) {
            $allWorkflowFilesExist = false;
            break;
        }
    }
    $health['workflows'] = $allWorkflowFilesExist 
        ? ['status' => 'healthy', 'message' => '引擎文件完整'] 
        : ['status' => 'error', 'message' => '引擎文件缺失'];
    

    $ffmpegCmd = shell_exec('where ffmpeg 2>nul') ?: shell_exec('which ffmpeg 2>/dev/null');
    $ffmpegPath = $ffmpegCmd !== null ? trim($ffmpegCmd) : '';
    if (!empty($ffmpegPath)) {
        $version = shell_exec('ffmpeg -version 2>&1 | head -1');
        preg_match('/version\s+([\d\.]+)/i', $version, $matches);
        $versionNum = $matches[1] ?? 'unknown';
        $health['ffmpeg'] = ['status' => 'healthy', 'message' => 'v' . $versionNum];
    } else {
        $health['ffmpeg'] = ['status' => 'warning', 'message' => '未安装'];
    }
    
    return $health;
}


function getSystemInfo() {
    return [
        'php_version' => PHP_VERSION,
        'os' => PHP_OS,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'disk_free' => disk_free_space(__DIR__),
        'disk_total' => disk_total_space(__DIR__)
    ];
}

$systemInfo = getSystemInfo();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统设置 - 巨神兵API辅助平台API辅助平台</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
        }
        
        
        .header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .logo { display: flex; align-items: center; gap: 12px; font-size: 20px; font-weight: 700; color: #1e293b; }
        .nav { display: flex; gap: 8px; }
        .nav-item {
            padding: 10px 20px;
            border-radius: 10px;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .nav-item:hover { background: #f3f4f6; }
        .nav-item.active { background: #4c51bf; color: white; }
        
        
        .main-container { display: flex; min-height: calc(100vh - 73px); }
        
        
        .sidebar {
            width: 280px;
            background: white;
            border-right: 1px solid rgba(0,0,0,0.1);
            padding: 24px 0;
            overflow-y: auto;
        }
        .sidebar-section { margin-bottom: 24px; }
        .sidebar-title {
            padding: 0 24px 12px;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            font-size: 14px;
        }
        .sidebar-item:hover { background: #f3f4f6; }
        .sidebar-item.active { background: rgba(76, 81, 191, 0.1); }
        .sidebar-item i { width: 20px; text-align: center; }
        
        
        .content {
            flex: 1;
            padding: 24px;
            overflow-y: auto;
        }
        .content-section { display: none; }
        .content-section.active { display: block; }
        
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .page-title { font-size: 24px; font-weight: 700; color: #1e293b; }
        .page-actions { display: flex; gap: 12px; }
        
        
        .card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
            overflow: hidden;
        }
        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-title { font-size: 16px; font-weight: 600; color: #1e293b; }
        .card-body { padding: 24px; }
        
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-healthy { background: #10b981; }
        .status-warning { background: #f59e0b; }
        .status-error { background: #ef4444; }
        .status-disabled { background: #9ca3af; }
        .status-unknown { background: #6b7280; }
        
        
        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }
        .module-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.2s;
        }
        .module-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .module-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .module-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .module-icon.blue { background: #3b82f6; }
        .module-icon.green { background: #10b981; }
        .module-icon.orange { background: #f59e0b; }
        .module-icon.purple { background: #8b5cf6; }
        .module-icon.red { background: #ef4444; }
        .module-name { font-weight: 600; color: #64748b; }
        .module-desc { font-size: 13px; color: #94a3b8; }
        
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
        }
        .stat-label { font-size: 13px; color: #64748b; }
        .stat-value { font-size: 28px; font-weight: 700; color: #1e293b; }
        .stat-change {
            font-size: 12px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .stat-change.positive { color: #10b981; }
        .stat-change.negative { color: #ef4444; }
        
        
        .log-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            gap: 12px;
            flex-wrap: wrap;
        }
        .log-selector {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .log-selector label {
            font-size: 14px;
            font-weight: 500;
            color: #64748b;
        }
        .log-selector select {
            padding: 10px 36px 10px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            min-width: 240px;
            background: white;
            color: #64748b;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .log-selector select:hover {
            border-color: #cbd5e1;
            box-shadow: 0 2px 4px rgba(76, 81, 191, 0.1);
        }
        .log-selector select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(76, 81, 191, 0.15);
        }
        .log-selector select option {
            padding: 10px 14px;
            font-size: 14px;
        }
        .log-actions { display: flex; gap: 8px; }
        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            border: none;
        }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .btn-primary:hover { background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%); }
        .btn-secondary { background: #6b7280; }
        .btn-secondary:hover { background: #4b5563; }
        .btn-danger { background: #ef4444; }
        .btn-danger:hover { background: #dc2626; }
        .btn-sm { padding: 6px 12px; font-size: 13px; }
        
        .log-content {
            background: #f8fafc;
            color: #64748b;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 13px;
            line-height: 1.6;
            padding: 20px;
            max-height: 500px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .log-line { padding: 2px 0; }
        .log-line.error { color: #ef4444; }
        .log-line.warning { color: #f59e0b; }
        .log-line.info { color: #3b82f6; }
        .log-line.success { color: #10b981; }
        .log-timestamp { color: #94a3b8; }
        
        
        .log-file-list {
            max-height: 400px;
            overflow-y: auto;
        }
        .log-file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.2s;
        }
        .log-file-item:hover { background: #f3f4f6; }
        .log-file-info { flex: 1; }
        .log-file-name { font-weight: 500; color: #475569; }
        .log-file-actions { display: flex; gap: 8px; }
        
        
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-label { color: #64748b; }
        .info-value { color: #1e293b; }
        
        
        .refresh-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #64748b;
        }
        .refresh-indicator.spinning i { animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        .toast {
            background: white;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 300px;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .toast.success { border-left: 4px solid #667eea;
        .toast.error { border-left: 4px solid #667eea;
        .toast.warning { border-left: 4px solid #667eea;
        
        
        .auto-scroll-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #64748b;
        }
        .toggle-switch {
            position: relative;
            width: 40px;
            height: 22px;
            background: #f8fafc;
            border-radius: 11px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .toggle-switch.active { background: #10b981; }
        .toggle-switch::after {
            content: '';
            position: absolute;
            width: 18px;
            height: 18px;
            background: white;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            transition: transform 0.3s;
        }
        .toggle-switch.active::after { transform: translateX(18px); }
        
        
        .filter-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .filter-tag {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid #e2e8f0;
            background: white;
            color: #64748b;
        }
        .filter-tag:hover { background: #f1f5f9; }
        .filter-tag.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }
    }
}
}
    </style>
</head>
<body>
    
    <header class="header">
        <div class="logo">
            <i class="fas fa-cog"></i>
            系统设置
        </div>
        <nav class="nav">
            <a href="?route=home" class="nav-item">
                <i class="fas fa-home"></i> 首页
            </a>
            <a href="?route=admin" class="nav-item">
                <i class="fas fa-shield-alt"></i> 后台管理
            </a>
            <a href="?route=settings" class="nav-item active">
                <i class="fas fa-cog"></i> 系统设置
            </a>
        </nav>
    </header>
    
    
    <div class="main-container">
        
        <aside class="sidebar">
            <div class="sidebar-section">
                <div class="sidebar-title">系统监控</div>
                <div class="sidebar-item active" data-section="overview">
                    <i class="fas fa-chart-line"></i> 总览仪表盘
                </div>
                <div class="sidebar-item" data-section="modules">
                    <i class="fas fa-cubes"></i> 模块健康检查
                </div>
                <div class="sidebar-item" data-section="logs">
                    <i class="fas fa-file-alt"></i> 系统日志
                </div>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-title">系统信息</div>
                <div class="sidebar-item" data-section="system">
                    <i class="fas fa-server"></i> 服务器信息
                </div>
                <div class="sidebar-item" data-section="config">
                    <i class="fas fa-sliders-h"></i> 配置管理
                </div>
            </div>
        </aside>
        
        
        <main class="content">
            
            <section id="overview" class="content-section active">
                <div class="page-header">
                    <h1 class="page-title">系统总览</h1>
                    <div class="page-actions">
                        <span class="refresh-indicator" id="refresh-indicator">
                            <i class="fas fa-sync-alt"></i> 上次更新: <span id="last-update">刚刚</span>
                        </span>
                        <button class="btn btn-secondary" onclick="refreshAllData()">
                            <i class="fas fa-sync-alt"></i> 刷新数据
                        </button>
                    </div>
                </div>
                
                
                <div class="stats-grid" id="stats-grid">
                    
                </div>
                
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">错误趋势 (最近7天)</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="errorTrendChart"></canvas>
                        </div>
                    </div>
                </div>
                
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">模块状态概览</h3>
                        <button class="btn btn-secondary btn-sm" onclick="showSection('modules')">
                            查看详情 <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="module-grid" id="module-summary">
                            
                        </div>
                    </div>
                </div>
            </section>
            
            
            <section id="modules" class="content-section">
                <div class="page-header">
                    <h1 class="page-title">模块健康检查</h1>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="runHealthCheck()">
                            <i class="fas fa-stethoscope"></i> 重新检测
                        </button>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="module-grid" id="module-detail-grid">
                            <?php foreach ($moduleHealth as $module => $status): ?>
                            <div class="module-card">
                                <div class="module-header">
                                    <div class="module-icon <?php echo $status['status'] === 'healthy' ? 'green' : ($status['status'] === 'error' ? 'red' : ($status['status'] === 'warning' ? 'orange' : 'blue')); ?>">
                                        <i class="fas <?php 
                                            echo $module === 'database' ? 'fa-database' : 
                                                ($module === 'ollama' ? 'fa-brain' : 
                                                ($module === 'gpustack' ? 'fa-server' : 
                                                ($module === 'storage' ? 'fa-hdd' : 
                                                ($module === 'logs' ? 'fa-file-alt' : 
                                                ($module === 'uploads' ? 'fa-cloud-upload-alt' : 
                                                ($module === 'workflows' ? 'fa-project-diagram' : 'fa-film')))))); 
                                        ?>"></i>
                                    </div>
                                    <span class="status-badge status-<?php echo $status['status']; ?>">
                                        <i class="fas <?php 
                                            echo $status['status'] === 'healthy' ? 'fa-check-circle' : 
                                                ($status['status'] === 'error' ? 'fa-times-circle' : 
                                                ($status['status'] === 'warning' ? 'fa-exclamation-triangle' : 'fa-minus-circle')); 
                                        ?>"></i>
                                        <?php 
                                            echo $status['status'] === 'healthy' ? '正常' : 
                                                ($status['status'] === 'error' ? '错误' : 
                                                ($status['status'] === 'warning' ? '警告' : '禁用')); 
                                        ?>
                                    </span>
                                </div>
                                <div class="module-name">
                                    <?php 
                                        echo $module === 'database' ? '数据库' : 
                                            ($module === 'ollama' ? 'Ollama AI' : 
                                            ($module === 'gpustack' ? 'GPUStack API' : 
                                            ($module === 'storage' ? '存储系统' : 
                                            ($module === 'logs' ? '日志系统' : 
                                            ($module === 'uploads' ? '上传目录' : 
                                            ($module === 'workflows' ? '工作流引擎' : 'FFmpeg')))))); 
                                    ?>
                                </div>
                                <div class="module-desc"><?php echo htmlspecialchars($status['message']); ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>
            
            
            <section id="logs" class="content-section">
                <div class="page-header">
                    <h1 class="page-title">系统日志</h1>
                    <div class="page-actions">
                        <div class="auto-scroll-toggle">
                            <span>自动刷新</span>
                            <div class="toggle-switch active" id="auto-refresh-toggle" onclick="toggleAutoRefresh()"></div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="log-toolbar">
                        <div class="log-selector">
                            <label>选择日志:</label>
                            <select id="log-file-select" onchange="loadLogContent()">
                                <option value="">-- 选择日志文件 --</option>
                            </select>
                        </div>
                        <div class="filter-tags">
                            <span class="filter-tag active" data-filter="all">全部</span>
                            <span class="filter-tag" data-filter="error">错误</span>
                            <span class="filter-tag" data-filter="warning">警告</span>
                            <span class="filter-tag" data-filter="info">信息</span>
                        </div>
                        <div class="log-actions">
                            <button class="btn btn-secondary btn-sm" onclick="loadLogContent()">
                                <i class="fas fa-sync-alt"></i> 刷新
                            </button>
                            <button class="btn btn-secondary btn-sm" onclick="downloadCurrentLog()">
                                <i class="fas fa-download"></i> 下载
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="clearCurrentLog()">
                                <i class="fas fa-trash"></i> 清空
                            </button>
                        </div>
                    </div>
                    <div class="log-content" id="log-content">
                        <div style="color: #64748b; text-align: center; padding: 40px;">
                            <i class="fas fa-file-alt" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                            请选择左侧日志文件查看内容
                        </div>
                    </div>
                </div>
                
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">日志文件列表</h3>
                        <button class="btn btn-secondary btn-sm" onclick="loadLogList()">
                            <i class="fas fa-sync-alt"></i> 刷新列表
                        </button>
                    </div>
                    <div class="log-file-list" id="log-file-list">
                        
                    </div>
                </div>
            </section>
            
            
            <section id="system" class="content-section">
                <div class="page-header">
                    <h1 class="page-title">服务器信息</h1>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">PHP 环境</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">PHP 版本</span>
                                <span class="info-value"><?php echo $systemInfo['php_version']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">操作系统</span>
                                <span class="info-value"><?php echo $systemInfo['os']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Web 服务器</span>
                                <span class="info-value"><?php echo $systemInfo['server_software']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">内存限制</span>
                                <span class="info-value"><?php echo $systemInfo['memory_limit']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">最大执行时间</span>
                                <span class="info-value"><?php echo $systemInfo['max_execution_time']; ?> 秒</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">上传大小限制</span>
                                <span class="info-value"><?php echo $systemInfo['upload_max_filesize']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">磁盘空间</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">总空间</span>
                                <span class="info-value"><?php echo formatBytes($systemInfo['disk_total']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">可用空间</span>
                                <span class="info-value"><?php echo formatBytes($systemInfo['disk_free']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">已用空间</span>
                                <span class="info-value"><?php echo formatBytes($systemInfo['disk_total'] - $systemInfo['disk_free']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">使用率</span>
                                <span class="info-value"><?php echo round((($systemInfo['disk_total'] - $systemInfo['disk_free']) / $systemInfo['disk_total']) * 100, 1); ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            
            <section id="config" class="content-section">
                <div class="page-header">
                    <h1 class="page-title">配置管理</h1>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">应用配置</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">应用名称</span>
                                <span class="info-value"><?php echo $config['app']['name'] ?? 'N/A'; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">版本</span>
                                <span class="info-value"><?php echo $config['app']['version'] ?? 'N/A'; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">调试模式</span>
                                <span class="info-value"><?php echo ($config['app']['debug'] ?? false) ? '开启' : '关闭'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Ollama 配置</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">API 地址</span>
                                <span class="info-value"><?php echo $config['ollama_api']['base_url'] ?? 'N/A'; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">默认模型</span>
                                <span class="info-value"><?php echo $config['ollama_api']['default_model'] ?? 'N/A'; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">温度参数</span>
                                <span class="info-value"><?php echo $config['ollama_api']['temperature'] ?? 'N/A'; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">最大Token</span>
                                <span class="info-value"><?php echo $config['ollama_api']['max_tokens'] ?? 'N/A'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
    
    
    <div class="toast-container" id="toast-container"></div>
    
    <script>

        let currentLogFile = '';
        let autoRefreshInterval = null;
        let errorTrendChart = null;
        let logFilter = 'all';
        

        document.addEventListener('DOMContentLoaded', function() {
            initSidebar();
            loadLogList();
            loadAnalysisData();
            startAutoRefresh();
            initFilterTags();
        });
        

        function initSidebar() {
            document.querySelectorAll('.sidebar-item').forEach(item => {
                item.addEventListener('click', function() {
                    const section = this.dataset.section;
                    if (section) {
                        showSection(section);
                    }
                });
            });
        }
        
        function showSection(sectionId) {

            document.querySelectorAll('.sidebar-item').forEach(item => {
                item.classList.remove('active');
                if (item.dataset.section === sectionId) {
                    item.classList.add('active');
                }
            });
            

            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            document.getElementById(sectionId).classList.add('active');
        }
        

        async function loadLogList() {
            try {
                const response = await fetch('api/log_handler.php?action=get_logs');
                const data = await response.json();
                
                if (data.success) {
                    renderLogList(data.logs);
                    updateLogSelector(data.logs);
                    renderModuleSummary(data.logs);
                } else {
                    showToast('加载日志列表失败: ' + data.error, 'error');
                }
            } catch (error) {
                showToast('加载日志列表失败: ' + error.message, 'error');
            }
        }
        
        function renderLogList(logs) {
            const container = document.getElementById('log-file-list');
            if (logs.length === 0) {
                container.innerHTML = '<div style="padding: 24px; text-align: center; color: #64748b;">暂无日志文件</div>';
                return;
            }
            
            container.innerHTML = logs.map(log => `
                <div class="log-file-item ${log.filename === currentLogFile ? 'active' : ''}" onclick="selectLogFile('${log.filename}')">
                    <div class="log-file-info">
                        <div class="log-file-name">
                            <i class="fas fa-file-alt" style="color: ${log.status_color}; margin-right: 8px;"></i>
                            ${log.filename}
                        </div>
                        <div class="log-file-meta">
                            ${log.type} · ${log.size} · ${log.lines} 行 · ${log.modified}
                        </div>
                    </div>
                    <div class="log-file-actions">
                        <button class="btn btn-secondary btn-sm" onclick="event.stopPropagation(); downloadLog('${log.filename}')">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        }
        
        function updateLogSelector(logs) {
            const select = document.getElementById('log-file-select');
            const currentValue = select.value;
            
            select.innerHTML = '<option value="">-- 选择日志文件 --</option>' + 
                logs.map(log => `<option value="${log.filename}">${log.filename} (${log.size})</option>`).join('');
            
            if (currentValue) {
                select.value = currentValue;
            }
        }
        
        function renderModuleSummary(logs) {
            const summary = {
                error: logs.filter(l => l.status === 'error').length,
                warning: logs.filter(l => l.status === 'warning').length,
                normal: logs.filter(l => l.status === 'normal').length,
                total: logs.length
            };
            
            const container = document.getElementById('module-summary');
            const items = [
                { name: '日志文件', icon: 'fa-file-alt', color: 'blue', count: summary.total, status: '总计' },
                { name: '正常日志', icon: 'fa-check-circle', color: 'green', count: summary.normal, status: '正常' },
                { name: '警告日志', icon: 'fa-exclamation-triangle', color: 'orange', count: summary.warning, status: '警告' },
                { name: '错误日志', icon: 'fa-times-circle', color: 'red', count: summary.error, status: '错误' }
            ];
            
            container.innerHTML = items.map(item => `
                <div class="module-card">
                    <div class="module-header">
                        <div class="module-icon ${item.color}">
                            <i class="fas ${item.icon}"></i>
                        </div>
                    </div>
                    <div class="module-name">${item.name}</div>
                    <div style="font-size: 24px; font-weight: 700; color: #1e293b; margin-top: 8px;">${item.count}</div>
                </div>
            `).join('');
        }
        

        function selectLogFile(filename) {
            currentLogFile = filename;
            document.getElementById('log-file-select').value = filename;
            loadLogContent();
            loadLogList();
        }
        
        async function loadLogContent() {
            const filename = document.getElementById('log-file-select').value;
            if (!filename) {
                document.getElementById('log-content').innerHTML = `
                    <div style="color: #64748b; text-align: center; padding: 40px;">
                        <i class="fas fa-file-alt" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                        请选择左侧日志文件查看内容
                    </div>
                `;
                return;
            }
            
            currentLogFile = filename;
            
            try {
                const response = await fetch(`api/log_handler.php?action=get_log_content&file=${encodeURIComponent(filename)}&lines=200`);
                const data = await response.json();
                
                if (data.success) {
                    renderLogContent(data.content);
                } else {
                    document.getElementById('log-content').innerHTML = `<div style="color: #fca5a5;">Error: ${data.error}</div>`;
                }
            } catch (error) {
                document.getElementById('log-content').innerHTML = `<div style="color: #fca5a5;">Error: ${error.message}</div>`;
            }
        }
        
        function renderLogContent(content) {
            const container = document.getElementById('log-content');
            
            if (!content.trim()) {
                container.innerHTML = '<div style="color: #64748b; text-align: center; padding: 40px;">日志文件为空</div>';
                return;
            }
            
            const lines = content.split('\n').filter(line => line.trim());
            const filteredLines = logFilter === 'all' ? lines : lines.filter(line => {
                const upper = line.toUpperCase();
                if (logFilter === 'error') return upper.includes('ERROR') || upper.includes('FATAL');
                if (logFilter === 'warning') return upper.includes('WARNING') || upper.includes('WARN');
                if (logFilter === 'info') return upper.includes('INFO') || upper.includes('DEBUG');
                return true;
            });
            
            container.innerHTML = filteredLines.map(line => {
                let cssClass = '';
                const upper = line.toUpperCase();
                if (upper.includes('ERROR') || upper.includes('FATAL')) cssClass = 'error';
                else if (upper.includes('WARNING') || upper.includes('WARN')) cssClass = 'warning';
                else if (upper.includes('SUCCESS')) cssClass = 'success';
                else if (upper.includes('INFO') || upper.includes('DEBUG')) cssClass = 'info';
                

                line = line.replace(/\[(\d{4}-\d{2}-\d{2}[\sT]\d{2}:\d{2}:\d{2})\]/g, '<span class="log-timestamp">[$1]</span>');
                
                return `<div class="log-line ${cssClass}">${line}</div>`;
            }).join('');
            

            if (document.getElementById('auto-refresh-toggle').classList.contains('active')) {
                container.scrollTop = container.scrollHeight;
            }
        }
        

        function initFilterTags() {
            document.querySelectorAll('.filter-tag').forEach(tag => {
                tag.addEventListener('click', function() {
                    document.querySelectorAll('.filter-tag').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    logFilter = this.dataset.filter;
                    loadLogContent();
                });
            });
        }
        

        async function loadAnalysisData() {
            try {
                const response = await fetch('api/log_handler.php?action=analyze_logs');
                const data = await response.json();
                
                if (data.success) {
                    renderStats(data.analysis);
                    renderErrorTrendChart(data.analysis.error_trends);
                }
            } catch (error) {
                console.error('Failed to load analysis:', error);
            }
        }
        
        function renderStats(analysis) {
            const container = document.getElementById('stats-grid');
            const stats = [
                { label: '日志文件总数', value: analysis.total_logs, icon: 'fa-file-alt', color: '#4c51bf' },
                { label: '错误总数', value: analysis.error_count, icon: 'fa-times-circle', color: '#ef4444' },
                { label: '警告总数', value: analysis.warning_count, icon: 'fa-exclamation-triangle', color: '#f59e0b' },
                { label: 'API 调用次数', value: analysis.api_calls, icon: 'fa-api', color: '#22c55e' }
            ];
            
            container.innerHTML = stats.map(stat => `
                <div class="stat-card">
                    <div class="stat-label">${stat.label}</div>
                    <div class="stat-value" style="color: ${stat.color}">${stat.value.toLocaleString()}</div>
                </div>
            `).join('');
        }
        
        function renderErrorTrendChart(trends) {
            const ctx = document.getElementById('errorTrendChart').getContext('2d');
            
            if (errorTrendChart) {
                errorTrendChart.destroy();
            }
            
            const dates = Object.keys(trends);
            const errors = dates.map(d => trends[d].errors);
            const warnings = dates.map(d => trends[d].warnings);
            
            errorTrendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dates.map(d => d.substring(5)),
                    datasets: [
                        {
                            label: '错误',
                            data: errors,
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: '警告',
                            data: warnings,
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245, 158, 11, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }
        

        function toggleAutoRefresh() {
            const toggle = document.getElementById('auto-refresh-toggle');
            toggle.classList.toggle('active');
            
            if (toggle.classList.contains('active')) {
                startAutoRefresh();
            } else {
                stopAutoRefresh();
            }
        }
        
        function startAutoRefresh() {
            if (autoRefreshInterval) return;
            
            autoRefreshInterval = setInterval(() => {
                loadLogContent();
                loadLogList();
                loadAnalysisData();
                updateLastRefreshTime();
            }, 5000);
        }
        
        function stopAutoRefresh() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
            }
        }
        
        function updateLastRefreshTime() {
            document.getElementById('last-update').textContent = new Date().toLocaleTimeString();
        }
        

        async function refreshAllData() {
            const indicator = document.getElementById('refresh-indicator');
            indicator.classList.add('spinning');
            
            await Promise.all([
                loadLogList(),
                loadLogContent(),
                loadAnalysisData()
            ]);
            
            updateLastRefreshTime();
            indicator.classList.remove('spinning');
            showToast('数据已刷新', 'success');
        }
        
        function runHealthCheck() {
            showToast('正在重新检测模块状态...', 'info');
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
        
        async function downloadCurrentLog() {
            if (!currentLogFile) {
                showToast('请先选择日志文件', 'warning');
                return;
            }
            downloadLog(currentLogFile);
        }
        
        function downloadLog(filename) {
            window.open(`api/log_handler.php?action=download_log&file=${encodeURIComponent(filename)}`, '_blank');
        }
        
        async function clearCurrentLog() {
            if (!currentLogFile) {
                showToast('请先选择日志文件', 'warning');
                return;
            }
            
            if (!confirm(`确定要清空日志文件 "${currentLogFile}" 吗？\n系统会自动创建备份。`)) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'clear_log');
                formData.append('file', currentLogFile);
                
                const response = await fetch('api/log_handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('日志已清空，备份: ' + data.backup, 'success');
                    loadLogContent();
                    loadLogList();
                } else {
                    showToast('清空失败: ' + data.error, 'error');
                }
            } catch (error) {
                showToast('清空失败: ' + error.message, 'error');
            }
        }
        

        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-times-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };
            
            toast.innerHTML = `
                <i class="fas ${icons[type]}"></i>
                <span>${message}</span>
            `;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
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
