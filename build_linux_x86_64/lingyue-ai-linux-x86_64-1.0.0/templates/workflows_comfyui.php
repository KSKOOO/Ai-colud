<?php
/**
 * ComfyUI 风格工作流编辑器
 * 参考 ComfyUI 的界面设计和交互方式
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']) {
    header('Location: ?route=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>工作流编辑器 - 巨神兵API辅助平台API辅助平台</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --comfy-bg: #1e1e1e;
            --comfy-panel: #2a2a2a;
            --comfy-border: #3a3a3a;
            --comfy-text: #e0e0e0;
            --comfy-text-muted: #888;
            --comfy-accent: #4c51bf;
            --comfy-accent-hover: #5a67d8;
            --node-header-bg: #2d3748;
            --port-input: #ff6b6b;
            --port-output: #4ecdc4;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--comfy-bg);
            color: var(--comfy-text);
            height: 100vh;
            overflow: hidden;
        }

        /* 顶部工具栏 */
        .comfy-menu {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 50px;
            background: var(--comfy-panel);
            border-bottom: 1px solid var(--comfy-border);
            display: flex;
            align-items: center;
            padding: 0 16px;
            gap: 12px;
            z-index: 1000;
        }

        .comfy-menu-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-right: 20px;
        }

        .comfy-menu-brand img {
            height: 28px;
            width: auto;
        }

        .comfy-menu-brand span {
            font-weight: 600;
            font-size: 16px;
        }

        .comfy-btn {
            padding: 8px 16px;
            background: var(--comfy-accent);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .comfy-btn:hover {
            background: var(--comfy-accent-hover);
        }

        .comfy-btn.secondary {
            background: #4a5568;
        }

        .comfy-btn.secondary:hover {
            background: #5a6578;
        }

        .comfy-btn.danger {
            background: #e53e3e;
        }

        .comfy-spacer {
            flex: 1;
        }

        /* 侧边栏 - 节点库 */
        .comfy-sidebar {
            position: fixed;
            left: 0;
            top: 50px;
            bottom: 0;
            width: 280px;
            background: var(--comfy-panel);
            border-right: 1px solid var(--comfy-border);
            display: flex;
            flex-direction: column;
            z-index: 100;
        }

        .sidebar-tabs {
            display: flex;
            border-bottom: 1px solid var(--comfy-border);
        }

        .sidebar-tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            font-size: 13px;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }

        .sidebar-tab:hover {
            background: rgba(255,255,255,0.05);
        }

        .sidebar-tab.active {
            border-bottom-color: var(--comfy-accent);
            color: var(--comfy-accent);
        }

        .sidebar-content {
            flex: 1;
            overflow-y: auto;
            padding: 12px;
        }

        .node-search {
            width: 100%;
            padding: 10px 12px;
            background: var(--comfy-bg);
            border: 1px solid var(--comfy-border);
            border-radius: 6px;
            color: var(--comfy-text);
            font-size: 13px;
            margin-bottom: 12px;
        }

        .node-search:focus {
            outline: none;
            border-color: var(--comfy-accent);
        }

        .node-category {
            margin-bottom: 8px;
        }

        .node-category-title {
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 600;
            color: var(--comfy-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .node-category-title:hover {
            color: var(--comfy-text);
        }

        .node-item {
            padding: 10px 12px 10px 24px;
            font-size: 13px;
            cursor: grab;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
        }

        .node-item:hover {
            background: var(--comfy-accent);
            color: white;
        }

        .node-item i {
            font-size: 14px;
            width: 20px;
            text-align: center;
        }

        /* 画布区域 */
        .comfy-canvas-container {
            position: fixed;
            left: 280px;
            top: 50px;
            right: 0;
            bottom: 0;
            overflow: hidden;
        }

        .comfy-canvas {
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle, #333 1px, transparent 1px);
            background-size: 20px 20px;
            cursor: default;
        }

        .comfy-canvas.dragging {
            cursor: grabbing;
        }

        /* 节点样式 - ComfyUI 风格 */
        .comfy-node {
            position: absolute;
            min-width: 180px;
            background: var(--comfy-panel);
            border: 1px solid var(--comfy-border);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            user-select: none;
            z-index: 10;
        }

        .comfy-node.selected {
            border-color: var(--comfy-accent);
            box-shadow: 0 0 0 2px rgba(76, 81, 191, 0.5), 0 4px 12px rgba(0,0,0,0.3);
        }

        .comfy-node.running {
            border-color: #48bb78;
            box-shadow: 0 0 20px rgba(72, 187, 120, 0.8), 0 0 40px rgba(72, 187, 120, 0.4);
            animation: nodeRunning 1.5s ease-in-out infinite;
        }
        
        .comfy-node.completed {
            border-color: #4299e1;
            box-shadow: 0 0 15px rgba(66, 153, 225, 0.6);
        }
        
        .comfy-node.error {
            border-color: #f56565;
            box-shadow: 0 0 15px rgba(245, 101, 101, 0.6);
        }
        
        .comfy-node.current::before {
            content: '';
            position: absolute;
            top: -4px;
            left: -4px;
            right: -4px;
            bottom: -4px;
            border: 2px solid #ecc94b;
            border-radius: 10px;
            animation: currentNodePulse 1s ease-in-out infinite;
            pointer-events: none;
        }
        
        @keyframes nodeRunning {
            0%, 100% { 
                transform: scale(1);
                box-shadow: 0 0 20px rgba(72, 187, 120, 0.8), 0 0 40px rgba(72, 187, 120, 0.4);
            }
            50% { 
                transform: scale(1.02);
                box-shadow: 0 0 30px rgba(72, 187, 120, 1), 0 0 60px rgba(72, 187, 120, 0.6);
            }
        }
        
        @keyframes currentNodePulse {
            0%, 100% { 
                opacity: 1;
                transform: scale(1);
            }
            50% { 
                opacity: 0.5;
                transform: scale(1.02);
            }
        }

        .comfy-node.error {
            border-color: #f56565;
        }

        .node-header {
            background: var(--node-header-bg);
            padding: 8px 12px;
            border-radius: 7px 7px 0 0;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: move;
        }

        .node-header i {
            font-size: 12px;
        }

        .node-content {
            padding: 12px;
        }

        .node-input-row {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            position: relative;
        }

        .node-input-row:last-child {
            margin-bottom: 0;
        }

        .node-input-label {
            font-size: 12px;
            color: var(--comfy-text-muted);
            flex: 1;
        }

        .node-input-field {
            flex: 1;
            padding: 6px 10px;
            background: var(--comfy-bg);
            border: 1px solid var(--comfy-border);
            border-radius: 4px;
            color: var(--comfy-text);
            font-size: 12px;
            width: 100%;
        }

        .node-input-field:focus {
            outline: none;
            border-color: var(--comfy-accent);
        }

        textarea.node-input-field {
            resize: vertical;
            min-height: 60px;
        }

        select.node-input-field {
            cursor: pointer;
        }

        .node-file-upload {
            padding: 12px;
            border: 2px dashed var(--comfy-border);
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .node-file-upload:hover {
            border-color: var(--comfy-accent);
            background: rgba(76, 81, 191, 0.1);
        }

        /* 端口 */
        .port {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            position: absolute;
            cursor: crosshair;
            transition: all 0.2s;
            z-index: 20;
        }

        .port:hover {
            transform: scale(1.5);
        }

        .port-input {
            background: var(--port-input);
            left: -5px;
        }

        .port-output {
            background: var(--port-output);
            right: -5px;
        }

        .port.connected {
            box-shadow: 0 0 6px currentColor;
        }

        /* 连线 SVG */
        .connections-svg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 5;
        }

        .connection-path {
            fill: none;
            stroke: #666;
            stroke-width: 2;
            pointer-events: stroke;
            transition: stroke 0.2s;
        }

        .connection-path:hover {
            stroke: var(--comfy-accent);
            stroke-width: 3;
        }

        .connection-path.selected {
            stroke: var(--comfy-accent);
        }

        .connection-path.temp {
            stroke-dasharray: 5, 5;
            stroke: var(--comfy-accent);
            opacity: 0.7;
        }

        /* 右侧属性面板 */
        .properties-panel {
            position: fixed;
            right: 0;
            top: 50px;
            bottom: 0;
            width: 300px;
            background: var(--comfy-panel);
            border-left: 1px solid var(--comfy-border);
            transform: translateX(100%);
            transition: transform 0.3s;
            z-index: 90;
            display: flex;
            flex-direction: column;
        }

        .properties-panel.open {
            transform: translateX(0);
        }

        .properties-header {
            padding: 16px;
            border-bottom: 1px solid var(--comfy-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .properties-title {
            font-size: 14px;
            font-weight: 600;
        }

        .properties-close {
            background: none;
            border: none;
            color: var(--comfy-text-muted);
            cursor: pointer;
            font-size: 16px;
        }

        .properties-close:hover {
            color: var(--comfy-text);
        }

        .properties-content {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
        }

        .property-group {
            margin-bottom: 16px;
        }

        .property-label {
            font-size: 12px;
            color: var(--comfy-text-muted);
            margin-bottom: 6px;
            display: block;
        }

        .property-input {
            width: 100%;
            padding: 8px 12px;
            background: var(--comfy-bg);
            border: 1px solid var(--comfy-border);
            border-radius: 4px;
            color: var(--comfy-text);
            font-size: 13px;
        }

        .property-input:focus {
            outline: none;
            border-color: var(--comfy-accent);
        }

        /* 队列面板 */
        .queue-panel {
            position: fixed;
            bottom: 20px;
            left: 300px;
            background: var(--comfy-panel);
            border: 1px solid var(--comfy-border);
            border-radius: 8px;
            padding: 12px 16px;
            min-width: 300px;
            z-index: 80;
        }

        .queue-title {
            font-size: 12px;
            color: var(--comfy-text-muted);
            margin-bottom: 8px;
        }

        .queue-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
            border-bottom: 1px solid var(--comfy-border);
            font-size: 12px;
        }

        .queue-item:last-child {
            border-bottom: none;
        }

        .queue-status {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .queue-status.pending { background: #ecc94b; }
        .queue-status.running { background: #48bb78; animation: pulse 1s infinite; }
        .queue-status.completed { background: #4299e1; }
        .queue-status.error { background: #f56565; }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* 提示框 */
        .comfy-toast {
            position: fixed;
            top: 60px;
            right: 20px;
            background: var(--comfy-panel);
            border: 1px solid var(--comfy-border);
            border-radius: 8px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 9999;
            animation: slideIn 0.3s;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        /* 确认对话框 - 网站主题风格 */
        .confirm-dialog-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: fadeIn 0.2s ease;
        }

        .confirm-dialog-overlay.show {
            display: flex;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px) scale(0.95);
                opacity: 0;
            }
            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }

        .confirm-dialog {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 3px;
            min-width: 400px;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            animation: slideUp 0.3s ease;
        }

        .confirm-dialog-inner {
            background: white;
            border-radius: 13px;
            padding: 28px;
        }

        .confirm-dialog-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 28px;
            color: white;
        }

        .confirm-dialog-title {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            text-align: center;
            margin-bottom: 12px;
        }

        .confirm-dialog-message {
            font-size: 15px;
            color: #6b7280;
            text-align: center;
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .confirm-dialog-buttons {
            display: flex;
            justify-content: center;
            gap: 12px;
        }

        .confirm-dialog-btn {
            padding: 12px 28px;
            border-radius: 10px;
            border: none;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .confirm-dialog-btn.cancel {
            background: #f3f4f6;
            color: #4b5563;
        }

        .confirm-dialog-btn.cancel:hover {
            background: #e5e7eb;
            transform: translateY(-1px);
        }

        .confirm-dialog-btn.confirm {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .confirm-dialog-btn.confirm:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
        }

        /* 导入/导出对话框 */
        .comfy-dialog-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(4px);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .comfy-dialog {
            background: var(--comfy-panel);
            border: 1px solid var(--comfy-border);
            border-radius: 12px;
            min-width: 400px;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            animation: dialogSlideUp 0.3s ease;
        }

        @keyframes dialogSlideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .comfy-dialog-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--comfy-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .comfy-dialog-title {
            font-size: 16px;
            font-weight: 600;
        }

        .comfy-dialog-close {
            background: none;
            border: none;
            color: var(--comfy-text-muted);
            cursor: pointer;
            font-size: 18px;
            padding: 4px;
        }

        .comfy-dialog-close:hover {
            color: var(--comfy-text);
        }

        .comfy-dialog-content {
            padding: 20px;
        }

        .comfy-dialog-buttons {
            padding: 16px 20px;
            border-top: 1px solid var(--comfy-border);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .import-area {
            border: 2px dashed var(--comfy-border);
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .import-area:hover {
            border-color: var(--comfy-accent);
            background: rgba(76, 81, 191, 0.1);
        }

        .import-area i {
            font-size: 48px;
            color: var(--comfy-text-muted);
            margin-bottom: 12px;
        }

        .import-area p {
            color: var(--comfy-text-muted);
            font-size: 14px;
        }

        /* 轻码模式 */
        .comfy-node.simple-mode {
            min-width: 140px;
        }

        .comfy-node.simple-mode .node-header {
            padding: 6px 10px;
            font-size: 11px;
        }

        .comfy-node.simple-mode .node-content {
            display: none !important;
        }

        .comfy-node.simple-mode .ports-row {
            min-height: 20px;
            padding: 4px 0;
        }

        .comfy-node.simple-mode .port {
            width: 8px;
            height: 8px;
        }

        #simpleModeBtn.active {
            background: var(--comfy-accent);
            color: white;
        }

        /* 隐藏 */
        .hidden {
            display: none !important;
        }
    </style>
</head>
<body>
    <!-- 顶部菜单 -->
    <div class="comfy-menu">
        <a href="?route=home" class="comfy-menu-brand" style="text-decoration: none; color: inherit;">
            <img src="assets/images/logo.png" alt="Logo" onerror="this.style.display='none'">
            <span>巨神兵API辅助平台AI</span>
        </a>
        
        <button class="comfy-btn" onclick="queuePrompt()">
            <i class="fas fa-play"></i> 执行队列
        </button>
        
        <button class="comfy-btn secondary" onclick="saveWorkflow()">
            <i class="fas fa-save"></i> 保存
        </button>
        
        <button class="comfy-btn secondary" onclick="importWorkflow()">
            <i class="fas fa-file-import"></i> 导入
        </button>
        
        <button class="comfy-btn secondary" onclick="exportWorkflow()">
            <i class="fas fa-file-export"></i> 导出
        </button>
        
        <button class="comfy-btn secondary" onclick="clearWorkflow()">
            <i class="fas fa-trash"></i> 清空
        </button>
        
        <button class="comfy-btn secondary" id="simpleModeBtn" onclick="toggleSimpleMode()">
            <i class="fas fa-feather-alt"></i> 轻码
        </button>
        
        <button class="comfy-btn secondary" onclick="testImport()" title="测试导入">
            <i class="fas fa-vial"></i> 测试
        </button>
        
        <div class="comfy-spacer"></div>
        
        <!-- 模型选择 -->
        <select id="providerSelect" style="padding: 8px 12px; background: #3a3a3a; border: 1px solid #4a4a4a; border-radius: 6px; color: #e0e0e0; font-size: 13px; min-width: 130px;">
            <option value="">加载提供商...</option>
        </select>
        <select id="modelSelect" style="padding: 8px 12px; background: #3a3a3a; border: 1px solid #4a4a4a; border-radius: 6px; color: #e0e0e0; font-size: 13px; min-width: 150px;">
            <option value="">选择模型...</option>
        </select>
        <button class="comfy-btn secondary" onclick="refreshModels()" title="刷新模型">
            <i class="fas fa-sync-alt"></i>
        </button>
        
        <button class="comfy-btn secondary" onclick="toggleQueuePanel()">
            <i class="fas fa-list"></i> 队列
        </button>
    </div>

    <!-- 侧边栏 -->
    <div class="comfy-sidebar">
        <div class="sidebar-tabs">
            <div class="sidebar-tab active" onclick="switchTab('nodes')">节点</div>
            <div class="sidebar-tab" onclick="switchTab('workflows')">工作流</div>
        </div>
        
        <div class="sidebar-content" id="nodesTab">
            <input type="text" class="node-search" id="nodeSearch" placeholder="搜索节点..." oninput="searchNodes()">
            <div id="nodeList"></div>
        </div>
        
        <div class="sidebar-content hidden" id="workflowsTab">
            <div id="workflowList"></div>
        </div>
    </div>

    <!-- 画布 -->
    <div class="comfy-canvas-container">
        <div class="comfy-canvas" id="canvas">
            <svg class="connections-svg" id="connectionsSvg"></svg>
        </div>
    </div>

    <!-- 属性面板 -->
    <div class="properties-panel" id="propertiesPanel">
        <div class="properties-header">
            <span class="properties-title">属性</span>
            <button class="properties-close" onclick="closeProperties()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="properties-content" id="propertiesContent">
            <p style="color: var(--comfy-text-muted); text-align: center; padding: 40px 0;">
                选择节点查看属性
            </p>
        </div>
    </div>

    <!-- 队列面板 -->
    <div class="queue-panel" id="queuePanel" style="display: none;">
        <div class="queue-title">执行队列</div>
        <div id="queueList"></div>
    </div>

    <!-- Toast 容器 -->
    <div id="toastContainer"></div>

    <!-- 导入/导出对话框 -->
    <div class="comfy-dialog-overlay" id="importExportDialog" style="display: none;">
        <div class="comfy-dialog" style="min-width: 500px;">
            <div class="comfy-dialog-header">
                <span class="comfy-dialog-title" id="ieDialogTitle">导入工作流</span>
                <button class="comfy-dialog-close" onclick="closeImportExportDialog()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="comfy-dialog-content">
                <div id="importSection">
                    <div class="import-area" onclick="document.getElementById('importFile').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>点击或拖拽文件到此处</p>
                        <p style="font-size: 12px; color: var(--comfy-text-muted); margin-top: 8px;">支持 .json 格式</p>
                        <input type="file" id="importFile" accept=".json" style="display: none;" onchange="handleFileImport(event)">
                    </div>
                    <div style="margin-top: 16px;">
                        <label style="font-size: 12px; color: var(--comfy-text-muted); display: block; margin-bottom: 8px;">或粘贴 JSON:</label>
                        <textarea id="importTextarea" style="width: 100%; height: 150px; background: var(--comfy-bg); border: 1px solid var(--comfy-border); border-radius: 6px; padding: 12px; color: var(--comfy-text); font-family: monospace; resize: vertical;"></textarea>
                    </div>
                </div>
                <div id="exportSection" style="display: none;">
                    <textarea id="exportTextarea" style="width: 100%; height: 300px; background: var(--comfy-bg); border: 1px solid var(--comfy-border); border-radius: 6px; padding: 12px; color: var(--comfy-text); font-family: monospace; resize: vertical;" readonly></textarea>
                    <button class="comfy-btn" style="margin-top: 12px; width: 100%;" onclick="copyExportText()">
                        <i class="fas fa-copy"></i> 复制到剪贴板
                    </button>
                </div>
            </div>
            <div class="comfy-dialog-buttons" id="ieDialogButtons">
                <button class="comfy-btn secondary" onclick="closeImportExportDialog()">取消</button>
                <button class="comfy-btn" onclick="confirmImport()">导入</button>
            </div>
        </div>
    </div>

    <!-- 确认对话框 -->
    <div class="confirm-dialog-overlay" id="confirmDialog">
        <div class="confirm-dialog">
            <div class="confirm-dialog-inner">
                <div class="confirm-dialog-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="confirm-dialog-title" id="confirmTitle">确认删除</div>
                <div class="confirm-dialog-message" id="confirmMessage">确定要删除此项目吗？</div>
                <div class="confirm-dialog-buttons">
                    <button class="confirm-dialog-btn cancel" onclick="closeConfirmDialog()">
                        <i class="fas fa-times"></i> 取消
                    </button>
                    <button class="confirm-dialog-btn confirm" onclick="executeConfirmCallback()">
                        <i class="fas fa-check"></i> 确定
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        /**
         * ComfyUI 风格工作流编辑器
         * 参考 ComfyUI 的节点系统和执行机制
         */

        // 节点定义 - 参考 ComfyUI 的节点系统
        const NODE_DEFINITIONS = {
            // ========== 加载器/输入节点 ==========
            'CheckpointLoaderSimple': {
                category: '加载器',
                displayName: 'Checkpoint加载器',
                description: '加载 Stable Diffusion 模型',
                inputs: [],
                outputs: ['MODEL', 'CLIP', 'VAE'],
                widgets: [
                    { name: 'ckpt_name', type: 'combo', options: ['v1-5-pruned-emaonly.safetensors', 'v2-1_768-ema-pruned.safetensors', 'SDXL.safetensors'], default: 'v1-5-pruned-emaonly.safetensors', label: '模型名称' }
                ]
            },
            'VAELoader': {
                category: '加载器',
                displayName: 'VAE加载器',
                description: '加载 VAE 模型',
                inputs: [],
                outputs: ['VAE'],
                widgets: [
                    { name: 'vae_name', type: 'combo', options: ['vae-ft-mse-840000-ema-pruned.safetensors', 'ClearVAE.safetensors'], default: 'vae-ft-mse-840000-ema-pruned.safetensors', label: 'VAE名称' }
                ]
            },
            'CLIPLoader': {
                category: '加载器',
                displayName: 'CLIP加载器',
                description: '加载 CLIP 文本编码器',
                inputs: [],
                outputs: ['CLIP'],
                widgets: [
                    { name: 'clip_name', type: 'text', default: 'clip_l.safetensors', label: 'CLIP名称' },
                    { name: 'type', type: 'combo', options: ['stable_diffusion', 'stable_cascade', 'sd3', 'stable_audio', 'sdxl', 'flux'], default: 'stable_diffusion', label: '类型' }
                ]
            },
            'LoadImage': {
                category: '输入',
                displayName: '加载图像',
                description: '从文件加载图像',
                inputs: [],
                outputs: ['IMAGE', 'MASK'],
                widgets: [
                    { name: 'image', type: 'file', accept: 'image/*', label: '图像文件' }
                ]
            },
            'LoadText': {
                category: '输入',
                displayName: '文本输入',
                description: '输入文本内容',
                inputs: [],
                outputs: ['TEXT'],
                widgets: [
                    { name: 'text', type: 'textarea', default: '', label: '文本内容' }
                ]
            },
            'LoadFile': {
                category: '输入',
                displayName: '文件输入',
                description: '上传文件',
                inputs: [],
                outputs: ['FILE'],
                widgets: [
                    { name: 'file', type: 'file', label: '选择文件' }
                ]
            },
            
            // ========== 条件/提示词节点 ==========
            'CLIPTextEncode': {
                category: '条件',
                displayName: 'CLIP文本编码',
                description: '使用CLIP模型编码文本',
                inputs: ['CLIP'],
                outputs: ['CONDITIONING'],
                widgets: [
                    { name: 'text', type: 'textarea', default: 'masterpiece, best quality', label: '文本提示词' }
                ]
            },
            'CLIPTextEncodeFlux': {
                category: '条件',
                displayName: 'FLUX文本编码',
                description: 'FLUX模型的双CLIP文本编码',
                inputs: ['CLIP'],
                outputs: ['CONDITIONING'],
                widgets: [
                    { name: 'clip_l', type: 'textarea', default: '', label: 'CLIP-L提示词' },
                    { name: 't5xxl', type: 'textarea', default: '', label: 'T5-XXL提示词' },
                    { name: 'guidance', type: 'float', min: 0, max: 100, step: 0.1, default: 3.5, label: '引导强度' }
                ]
            },
            'ConditioningCombine': {
                category: '条件',
                displayName: '条件合并',
                description: '合并两个条件',
                inputs: ['CONDITIONING', 'CONDITIONING_2'],
                outputs: ['CONDITIONING'],
                widgets: []
            },
            'ConditioningAverage': {
                category: '条件',
                displayName: '条件混合',
                description: '混合两个条件',
                inputs: ['CONDITIONING_TO', 'CONDITIONING_FROM'],
                outputs: ['CONDITIONING'],
                widgets: [
                    { name: 'conditioning_to_strength', type: 'float', min: 0, max: 1, step: 0.01, default: 1.0, label: '混合强度' }
                ]
            },
            'ConditioningConcat': {
                category: '条件',
                displayName: '条件拼接',
                description: '拼接两个条件',
                inputs: ['CONDITIONING_TO', 'CONDITIONING_FROM'],
                outputs: ['CONDITIONING'],
                widgets: []
            },
            'ConditioningSetArea': {
                category: '条件',
                displayName: '设置条件区域',
                description: '为条件设置空间区域',
                inputs: ['CONDITIONING'],
                outputs: ['CONDITIONING'],
                widgets: [
                    { name: 'width', type: 'int', min: 64, max: 16384, step: 8, default: 64, label: '宽度' },
                    { name: 'height', type: 'int', min: 64, max: 16384, step: 8, default: 64, label: '高度' },
                    { name: 'x', type: 'int', min: 0, max: 16384, step: 8, default: 0, label: 'X坐标' },
                    { name: 'y', type: 'int', min: 0, max: 16384, step: 8, default: 0, label: 'Y坐标' },
                    { name: 'strength', type: 'float', min: 0, max: 10, step: 0.01, default: 1.0, label: '强度' }
                ]
            },
            
            // ========== 采样器节点 ==========
            'KSampler': {
                category: '采样器',
                displayName: 'K采样器',
                description: 'Latent扩散采样器',
                inputs: ['MODEL', 'POSITIVE', 'NEGATIVE', 'LATENT_IMAGE'],
                outputs: ['LATENT'],
                widgets: [
                    { name: 'seed', type: 'int', min: 0, max: 18446744073709552000, default: 0, label: '种子' },
                    { name: 'control_after_generate', type: 'combo', options: ['fixed', 'increment', 'decrement', 'randomize'], default: 'fixed', label: '生成后控制' },
                    { name: 'steps', type: 'int', min: 1, max: 10000, default: 20, label: '步数' },
                    { name: 'cfg', type: 'float', min: 0, max: 100, step: 0.1, default: 8.0, label: 'CFG缩放' },
                    { name: 'sampler_name', type: 'combo', options: ['euler', 'euler_ancestral', 'heun', 'heunpp2', 'dpm_2', 'dpm_2_ancestral', 'lms', 'dpm_fast', 'dpm_adaptive', 'dpmpp_2s_ancestral', 'dpmpp_sde', 'dpmpp_sde_gpu', 'dpmpp_2m', 'dpmpp_2m_sde', 'dpmpp_2m_sde_gpu', 'dpmpp_3m_sde', 'dpmpp_3m_sde_gpu', 'ddpm', 'lcm', 'ipndm', 'ipndm_v', 'deis', 'uni_pc', 'uni_pc_bh2'], default: 'euler', label: '采样器' },
                    { name: 'scheduler', type: 'combo', options: ['normal', 'karras', 'exponential', 'sgm_uniform', 'simple', 'ddim_uniform'], default: 'normal', label: '调度器' },
                    { name: 'denoise', type: 'float', min: 0, max: 1, step: 0.01, default: 1.0, label: '去噪强度' }
                ]
            },
            'KSamplerAdvanced': {
                category: '采样器',
                displayName: '高级K采样器',
                description: '高级Latent扩散采样器',
                inputs: ['MODEL', 'POSITIVE', 'NEGATIVE', 'LATENT_IMAGE'],
                outputs: ['LATENT'],
                widgets: [
                    { name: 'add_noise', type: 'combo', options: ['enable', 'disable'], default: 'enable', label: '添加噪声' },
                    { name: 'noise_seed', type: 'int', min: 0, max: 18446744073709552000, default: 0, label: '噪声种子' },
                    { name: 'control_after_generate', type: 'combo', options: ['fixed', 'increment', 'decrement', 'randomize'], default: 'fixed', label: '生成后控制' },
                    { name: 'steps', type: 'int', min: 1, max: 10000, default: 20, label: '步数' },
                    { name: 'cfg', type: 'float', min: 0, max: 100, step: 0.1, default: 8.0, label: 'CFG缩放' },
                    { name: 'sampler_name', type: 'combo', options: ['euler', 'euler_ancestral', 'heun', 'dpm_2', 'dpmpp_2m', 'ddpm', 'lcm'], default: 'euler', label: '采样器' },
                    { name: 'scheduler', type: 'combo', options: ['normal', 'karras', 'exponential'], default: 'normal', label: '调度器' },
                    { name: 'start_at_step', type: 'int', min: 0, max: 10000, default: 0, label: '开始步数' },
                    { name: 'end_at_step', type: 'int', min: 0, max: 10000, default: 10000, label: '结束步数' },
                    { name: 'return_with_leftover_noise', type: 'combo', options: ['disable', 'enable'], default: 'disable', label: '返回剩余噪声' }
                ]
            },
            
            // ========== Latent节点 ==========
            'EmptyLatentImage': {
                category: 'Latent',
                displayName: '空Latent图像',
                description: '创建空的Latent图像',
                inputs: [],
                outputs: ['LATENT'],
                widgets: [
                    { name: 'width', type: 'int', min: 16, max: 16384, step: 8, default: 512, label: '宽度' },
                    { name: 'height', type: 'int', min: 16, max: 16384, step: 8, default: 512, label: '高度' },
                    { name: 'batch_size', type: 'int', min: 1, max: 4096, default: 1, label: '批次大小' }
                ]
            },
            'LatentUpscale': {
                category: 'Latent',
                displayName: 'Latent放大',
                description: '使用模型放大Latent',
                inputs: ['SAMPLES'],
                outputs: ['LATENT'],
                widgets: [
                    { name: 'upscale_method', type: 'combo', options: ['nearest-exact', 'bilinear', 'area', 'bislerp'], default: 'nearest-exact', label: '放大方法' },
                    { name: 'width', type: 'int', min: 16, max: 16384, step: 8, default: 1024, label: '宽度' },
                    { name: 'height', type: 'int', min: 16, max: 16384, step: 8, default: 1024, label: '高度' },
                    { name: 'crop', type: 'combo', options: ['disabled', 'center'], default: 'disabled', label: '裁剪' }
                ]
            },
            'LatentComposite': {
                category: 'Latent',
                displayName: 'Latent合成',
                description: '将Latent合成到另一Latent上',
                inputs: ['SAMPLES_TO', 'SAMPLES_FROM'],
                outputs: ['LATENT'],
                widgets: [
                    { name: 'x', type: 'int', min: 0, max: 16384, default: 0, label: 'X坐标' },
                    { name: 'y', type: 'int', min: 0, max: 16384, default: 0, label: 'Y坐标' },
                    { name: 'feather', type: 'int', min: 0, max: 16384, default: 0, label: '羽化' }
                ]
            },
            'SetLatentNoiseMask': {
                category: 'Latent',
                displayName: '设置Latent噪声遮罩',
                description: '为Latent设置噪声遮罩',
                inputs: ['SAMPLES', 'MASK'],
                outputs: ['LATENT'],
                widgets: []
            },
            
            // ========== VAE节点 ==========
            'VAEDecode': {
                category: 'VAE',
                displayName: 'VAE解码',
                description: '将Latent解码为图像',
                inputs: ['SAMPLES', 'VAE'],
                outputs: ['IMAGE'],
                widgets: []
            },
            'VAEEncode': {
                category: 'VAE',
                displayName: 'VAE编码',
                description: '将图像编码为Latent',
                inputs: ['PIXELS', 'VAE'],
                outputs: ['LATENT'],
                widgets: []
            },
            'VAEDecodeTiled': {
                category: 'VAE',
                displayName: 'VAE分块解码',
                description: '分块解码大图像',
                inputs: ['SAMPLES', 'VAE'],
                outputs: ['IMAGE'],
                widgets: [
                    { name: 'tile_size', type: 'int', min: 64, max: 16384, default: 512, label: '块大小' },
                    { name: 'overlap', type: 'int', min: 0, max: 16384, default: 64, label: '重叠' }
                ]
            },
            
            // ========== 图像节点 ==========
            'SaveImage': {
                category: '图像',
                displayName: '保存图像',
                description: '保存图像到文件',
                inputs: ['IMAGES'],
                outputs: [],
                widgets: [
                    { name: 'filename_prefix', type: 'text', default: 'ComfyUI', label: '文件名前缀' }
                ]
            },
            'PreviewImage': {
                category: '图像',
                displayName: '预览图像',
                description: '预览图像结果',
                inputs: ['IMAGES'],
                outputs: [],
                widgets: []
            },
            'LoadImageMask': {
                category: '图像',
                displayName: '加载图像遮罩',
                description: '加载图像作为遮罩',
                inputs: [],
                outputs: ['MASK'],
                widgets: [
                    { name: 'image', type: 'file', accept: 'image/*', label: '图像文件' },
                    { name: 'channel', type: 'combo', options: ['red', 'green', 'blue', 'alpha'], default: 'alpha', label: '通道' }
                ]
            },
            'ImageScale': {
                category: '图像',
                displayName: '图像缩放',
                description: '缩放图像',
                inputs: ['IMAGE'],
                outputs: ['IMAGE'],
                widgets: [
                    { name: 'upscale_method', type: 'combo', options: ['nearest-exact', 'bilinear', 'area', 'bicubic'], default: 'nearest-exact', label: '放大方法' },
                    { name: 'width', type: 'int', min: 1, max: 16384, default: 512, label: '宽度' },
                    { name: 'height', type: 'int', min: 1, max: 16384, default: 512, label: '高度' },
                    { name: 'crop', type: 'combo', options: ['disabled', 'center'], default: 'disabled', label: '裁剪' }
                ]
            },
            'ImageCompositeMasked': {
                category: '图像',
                displayName: '图像遮罩合成',
                description: '使用遮罩合成图像',
                inputs: ['DESTINATION', 'SOURCE', 'MASK'],
                outputs: ['IMAGE'],
                widgets: [
                    { name: 'x', type: 'int', min: 0, max: 16384, default: 0, label: 'X坐标' },
                    { name: 'y', type: 'int', min: 0, max: 16384, default: 0, label: 'Y坐标' },
                    { name: 'resize_source', type: 'combo', options: ['false', 'true'], default: 'false', label: '调整源大小' }
                ]
            },
            
            // ========== 视频节点 ==========
            'LoadVideo': {
                category: '视频',
                displayName: '加载视频',
                description: '加载视频文件',
                inputs: [],
                outputs: ['VIDEO', 'AUDIO'],
                widgets: [
                    { name: 'video', type: 'file', accept: 'video/*', label: '视频文件' }
                ]
            },
            'SaveVideo': {
                category: '视频',
                displayName: '保存视频',
                description: '保存视频到文件',
                inputs: ['VIDEO', 'AUDIO'],
                outputs: [],
                widgets: [
                    { name: 'filename_prefix', type: 'text', default: 'output', label: '文件名前缀' },
                    { name: 'format', type: 'combo', options: ['mp4', 'webm', 'mov', 'avi'], default: 'mp4', label: '格式' },
                    { name: 'fps', type: 'int', min: 1, max: 120, default: 30, label: '帧率' },
                    { name: 'quality', type: 'combo', options: ['low', 'medium', 'high', 'ultra'], default: 'high', label: '质量' }
                ]
            },
            'VideoCombine': {
                category: '视频',
                displayName: '视频合成',
                description: '将图像序列合成为视频',
                inputs: ['IMAGES', 'AUDIO'],
                outputs: ['VIDEO'],
                widgets: [
                    { name: 'fps', type: 'float', min: 0.1, max: 120, step: 0.1, default: 30, label: '帧率' },
                    { name: 'loop_count', type: 'int', min: 0, max: 100, default: 0, label: '循环次数' },
                    { name: 'filename_prefix', type: 'text', default: 'animated', label: '文件名前缀' },
                    { name: 'format', type: 'combo', options: ['image/gif', 'video/webm', 'video/mp4'], default: 'video/mp4', label: '格式' },
                    { name: 'pix_fmt', type: 'combo', options: ['yuv420p', 'yuva420p', 'rgb24'], default: 'yuv420p', label: '像素格式' }
                ]
            },
            'VideoUpscale': {
                category: '视频',
                displayName: '视频放大',
                description: '放大视频分辨率',
                inputs: ['VIDEO'],
                outputs: ['VIDEO'],
                widgets: [
                    { name: 'upscale_method', type: 'combo', options: ['nearest', 'bilinear', 'bicubic', 'lanczos'], default: 'lanczos', label: '放大方法' },
                    { name: 'width', type: 'int', min: 64, max: 8192, default: 1920, label: '宽度' },
                    { name: 'height', type: 'int', min: 64, max: 8192, default: 1080, label: '高度' }
                ]
            },
            'VideoFrameExtract': {
                category: '视频',
                displayName: '提取视频帧',
                description: '从视频中提取帧',
                inputs: ['VIDEO'],
                outputs: ['IMAGES'],
                widgets: [
                    { name: 'frame_rate', type: 'float', min: 0.1, max: 60, step: 0.1, default: 1, label: '提取帧率' },
                    { name: 'start_time', type: 'float', min: 0, max: 86400, step: 0.1, default: 0, label: '开始时间(秒)' },
                    { name: 'end_time', type: 'float', min: 0, max: 86400, step: 0.1, default: 0, label: '结束时间(秒,0为全部)' }
                ]
            },
            
            // ========== AI 文本节点 ==========
            'GPTNode': {
                category: 'AI文本',
                displayName: 'AI 文本生成',
                description: '调用AI模型生成文本（支持本地和在线模型）',
                inputs: ['PROMPT', 'SYSTEM'],
                outputs: ['TEXT'],
                widgets: [
                    { name: 'provider', type: 'combo', options: 'DYNAMIC_PROVIDERS', default: 'ollama', label: '提供商' },
                    { name: 'model', type: 'combo', options: 'DYNAMIC_MODELS', default: 'llama2', label: '模型' },
                    { name: 'temperature', type: 'float', min: 0, max: 2, step: 0.1, default: 0.7, label: '温度' },
                    { name: 'max_tokens', type: 'int', min: 1, max: 4096, default: 1024, label: '最大Token' },
                    { name: 'top_p', type: 'float', min: 0, max: 1, step: 0.01, default: 1.0, label: 'Top P' }
                ]
            },
            'ChatNode': {
                category: 'AI文本',
                displayName: 'AI 对话',
                description: '使用AI模型进行对话（支持本地和在线模型）',
                inputs: ['MESSAGE', 'HISTORY'],
                outputs: ['RESPONSE', 'HISTORY'],
                widgets: [
                    { name: 'provider', type: 'combo', options: 'DYNAMIC_PROVIDERS', default: 'ollama', label: '提供商' },
                    { name: 'model', type: 'combo', options: 'DYNAMIC_MODELS', default: 'llama2', label: '模型' }
                ]
            },
            'TextCombine': {
                category: 'AI文本',
                displayName: '文本合并',
                description: '合并多个文本',
                inputs: ['TEXT1', 'TEXT2'],
                outputs: ['TEXT'],
                widgets: [
                    { name: 'separator', type: 'text', default: ' ', label: '分隔符' }
                ]
            },
            'TextReplace': {
                category: 'AI文本',
                displayName: '文本替换',
                description: '替换文本内容',
                inputs: ['TEXT'],
                outputs: ['TEXT'],
                widgets: [
                    { name: 'search', type: 'text', default: '', label: '查找' },
                    { name: 'replace', type: 'text', default: '', label: '替换为' }
                ]
            },
            
            // ========== 逻辑控制节点 ==========
            'IfCondition': {
                category: '逻辑',
                displayName: '条件判断',
                description: '根据条件选择输出',
                inputs: ['CONDITION'],
                outputs: ['TRUE', 'FALSE'],
                widgets: [
                    { name: 'operator', type: 'combo', options: ['==', '!=', '>', '<', 'contains', 'starts_with', 'ends_with'], default: '==', label: '运算符' },
                    { name: 'value', type: 'text', default: '', label: '比较值' }
                ]
            },
            'LoopNode': {
                category: '逻辑',
                displayName: '循环',
                description: '循环执行',
                inputs: ['INPUT'],
                outputs: ['OUTPUT', 'INDEX'],
                widgets: [
                    { name: 'count', type: 'int', min: 1, max: 1000, default: 5, label: '循环次数' }
                ]
            },
            'DelayNode': {
                category: '逻辑',
                displayName: '延迟',
                description: '延迟执行',
                inputs: ['INPUT'],
                outputs: ['OUTPUT'],
                widgets: [
                    { name: 'seconds', type: 'float', min: 0, max: 3600, step: 0.1, default: 1.0, label: '延迟秒数' }
                ]
            },
            
            // ========== 输出节点 ==========
            'SaveText': {
                category: '输出',
                displayName: '保存文本',
                description: '保存文本到文件',
                inputs: ['TEXT'],
                outputs: [],
                widgets: [
                    { name: 'filename', type: 'text', default: 'output.txt', label: '文件名' }
                ]
            },
            'PreviewAny': {
                category: '输出',
                displayName: '预览任意',
                description: '预览任何类型的数据',
                inputs: ['INPUT'],
                outputs: [],
                widgets: []
            },
            'ShowText': {
                category: '输出',
                displayName: '显示文本',
                description: '显示文本内容',
                inputs: ['TEXT'],
                outputs: ['STRING'],
                widgets: [
                    { name: 'multiline', type: 'combo', options: ['false', 'true'], default: 'true', label: '多行显示' }
                ]
            }
        };

        // 状态
        let nodes = [];
        let links = [];
        let nodeIdCounter = 1;
        let linkIdCounter = 1;
        let selectedNode = null;
        let draggingNode = null;
        let dragOffset = { x: 0, y: 0 };
        let connecting = false;
        let connectingFrom = null;
        let canvasOffset = { x: 0, y: 0 };
        let queue = [];

        // 全局模型和提供商列表
        let availableProviders = {
            'ollama': { name: 'Ollama', models: ['llama2', 'llama3', 'mistral', 'codellama', 'phi3'] },
            'openai': { name: 'OpenAI', models: ['gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo'] },
            'deepseek': { name: 'DeepSeek', models: ['deepseek-chat', 'deepseek-reasoner'] },
            'qwen': { name: '通义千问', models: ['qwen-turbo', 'qwen-plus', 'qwen-max'] },
            'moonshot': { name: 'Moonshot', models: ['moonshot-v1-8k', 'moonshot-v1-32k'] },
            'zhipu': { name: '智谱AI', models: ['glm-4', 'glm-3-turbo'] }
        };
        let currentProviderModels = [];

        // 初始化
        $(document).ready(function() {
            // 先加载所有提供商配置，再初始化节点库
            loadAllProvidersForWorkflow(function() {
                initNodeLibrary();
                initCanvas();
                loadWorkflowList();
                updateQueueDisplay();
            });
        });

        // 加载所有AI提供商配置
        function loadAllProvidersForWorkflow(callback) {
            $.ajax({
                url: 'api/model_handler.php?action=getAllProviders',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.providers) {
                        // 更新提供商列表
                        response.providers.forEach(provider => {
                            if (!availableProviders[provider.type]) {
                                availableProviders[provider.type] = {
                                    name: provider.name,
                                    models: []
                                };
                            }
                        });
                        console.log('✅ 工作流已加载提供商:', Object.keys(availableProviders));
                    }
                    
                    // 加载Ollama模型作为默认
                    loadOllamaModelsForWorkflow(function() {
                        if (callback) callback();
                    });
                },
                error: function() {
                    console.warn('⚠️ 无法加载提供商列表，使用默认配置');
                    loadOllamaModelsForWorkflow(callback);
                }
            });
        }

        // 加载Ollama模型列表
        function loadOllamaModelsForWorkflow(callback) {
            $.ajax({
                url: 'api/api_handler.php?request=models',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.models && Object.keys(response.models).length > 0) {
                        // 获取模型名称列表（不含标签版本）
                        const ollamaModels = Object.keys(response.models).map(fullName => {
                            return fullName.split(':')[0];
                        });
                        // 去重并更新
                        availableProviders.ollama.models = [...new Set(ollamaModels)];
                        console.log('✅ 工作流已加载Ollama模型:', availableProviders.ollama.models);
                    } else {
                        console.warn('⚠️ 无法加载Ollama模型，使用默认列表');
                    }
                    if (callback) callback();
                },
                error: function() {
                    console.error('❌ 无法连接到Ollama服务');
                    if (callback) callback();
                }
            });
        }

        // 获取当前可用的提供商列表
        function getAvailableProvidersForNodes() {
            return Object.keys(availableProviders).map(key => ({
                value: key,
                label: availableProviders[key].name
            }));
        }

        // 获取当前可用的模型列表（用于节点配置）
        function getAvailableModelsForNodes(provider) {
            let models = [];
            if (provider && availableProviders[provider]) {
                models = availableProviders[provider].models;
            } else if (availableProviders.ollama) {
                models = availableProviders.ollama.models;
            }
            // 确保返回数组
            if (!Array.isArray(models)) {
                console.warn('getAvailableModelsForNodes 返回的不是数组:', models);
                return ['llama2', 'qwen', 'mistral'];
            }
            return models.length > 0 ? models : ['llama2', 'qwen', 'mistral'];
        }

        // 初始化节点库
        function initNodeLibrary() {
            const categories = {};
            
            Object.entries(NODE_DEFINITIONS).forEach(([type, def]) => {
                if (!categories[def.category]) {
                    categories[def.category] = [];
                }
                categories[def.category].push({ type, ...def });
            });

            const list = $('#nodeList');
            list.empty();
            
            Object.entries(categories).forEach(([catName, items]) => {
                const catEl = $(`
                    <div class="node-category">
                        <div class="node-category-title" onclick="toggleCategory(this)">
                            <i class="fas fa-chevron-down"></i>
                            ${catName}
                        </div>
                        <div class="node-category-content">
                            ${items.map(item => `
                                <div class="node-item" draggable="true" 
                                     ondragstart="onNodeDragStart(event, '${item.type}')"
                                     onclick="addNode('${item.type}')">
                                    <i class="fas fa-cube"></i>
                                    ${item.displayName}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `);
                list.append(catEl);
            });
        }

        // 切换分类
        function toggleCategory(el) {
            $(el).find('i').toggleClass('fa-chevron-down fa-chevron-right');
            $(el).next('.node-category-content').slideToggle(200);
        }

        // 搜索节点
        function searchNodes() {
            const term = $('#nodeSearch').val().toLowerCase();
            $('.node-item').each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.includes(term));
                if (text.includes(term)) {
                    $(this).closest('.node-category-content').show();
                }
            });
        }

        // 拖拽添加节点
        function onNodeDragStart(e, type) {
            e.dataTransfer.setData('nodeType', type);
        }

        // 初始化画布
        function initCanvas() {
            const canvas = $('#canvas');
            
            canvas.on('dragover', function(e) {
                e.preventDefault();
            });
            
            canvas.on('drop', function(e) {
                e.preventDefault();
                const type = e.originalEvent.dataTransfer.getData('nodeType');
                if (type) {
                    const rect = canvas[0].getBoundingClientRect();
                    const x = e.originalEvent.clientX - rect.left;
                    const y = e.originalEvent.clientY - rect.top;
                    addNodeAt(type, x, y);
                }
            });

            canvas.on('mousedown', function(e) {
                if (e.target === canvas[0] || e.target.className === 'comfy-canvas') {
                    deselectAll();
                }
            });
        }

        // 添加节点
        function addNode(type) {
            const canvas = $('#canvas');
            const x = canvas.width() / 2 + (Math.random() - 0.5) * 100;
            const y = canvas.height() / 2 + (Math.random() - 0.5) * 100;
            addNodeAt(type, x, y);
        }

        // 在指定位置添加节点
        function addNodeAt(type, x, y) {
            const def = NODE_DEFINITIONS[type];
            if (!def) return;

            const node = {
                id: nodeIdCounter++,
                type: type,
                pos: [x, y],
                inputs: {},
                outputs: {},
                widgets: {}
            };

            // 初始化 widgets 值
            if (def.widgets) {
                def.widgets.forEach(w => {
                    let defaultValue = w.default !== undefined ? w.default : '';
                    // 处理动态选项的默认值
                    if (w.options === 'DYNAMIC_PROVIDERS') {
                        const providers = getAvailableProvidersForNodes();
                        defaultValue = providers[0]?.value || 'ollama';
                    } else if (w.options === 'DYNAMIC_MODELS') {
                        const providerWidget = def.widgets.find(dw => dw.name === 'provider');
                        const provider = providerWidget ? (node.widgets['provider'] || 'ollama') : 'ollama';
                        const models = getAvailableModelsForNodes(provider);
                        defaultValue = models[0] || 'llama2';
                    }
                    node.widgets[w.name] = defaultValue;
                    node.inputs[w.name] = defaultValue;
                });
            }

            nodes.push(node);
            renderNode(node);
            selectNode(node.id);
            
            showToast(`已添加 ${def.displayName}`);
        }

        // 渲染节点
        function renderNode(node) {
            const def = NODE_DEFINITIONS[node.type];
            if (!def) {
                console.warn(`无法渲染节点 ${node.id}: 类型 ${node.type} 未定义`);
                return null;
            }

            // 输入端口
            let inputsHtml = '';
            if (def.inputs) {
                def.inputs.forEach((input, i) => {
                    inputsHtml += `
                        <div class="node-input-row">
                            <div class="port port-input" data-node="${node.id}" data-slot="${i}" data-type="input"></div>
                            <span class="node-input-label">${input}</span>
                        </div>
                    `;
                });
            }

            // Widgets
            let widgetsHtml = '';
            if (def.widgets) {
                def.widgets.forEach(w => {
                    const value = node.inputs[w.name] !== undefined ? node.inputs[w.name] : (w.default || '');
                    
                    if (w.type === 'textarea') {
                        widgetsHtml += `
                            <div class="node-input-row">
                                <textarea class="node-input-field" 
                                    data-node="${node.id}" data-widget="${w.name}"
                                    placeholder="${w.label}"
                                    oninput="updateWidget(${node.id}, '${w.name}', this.value)">${value}</textarea>
                            </div>
                        `;
                    } else if (w.type === 'file') {
                        widgetsHtml += `
                            <div class="node-input-row">
                                <div class="node-file-upload" onclick="$('#file_${node.id}_${w.name}').click()">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>${value || '点击上传'}</span>
                                </div>
                                <input type="file" id="file_${node.id}_${w.name}" style="display:none;"
                                    accept="${w.accept || '*'}"
                                    onchange="handleFileUpload(${node.id}, '${w.name}', this)">
                            </div>
                        `;
                    } else if (w.type === 'combo') {
                        // 处理动态模型选项
                        let options = w.options;
                        if (options === 'DYNAMIC_MODELS') {
                            options = getAvailableModelsForNodes();
                        }
                        // 确保options是数组
                        if (!Array.isArray(options)) {
                            console.warn(`节点 ${node.type} 的 widget ${w.name} 的 options 不是数组:`, options);
                            options = [];
                        }
                        // 确保有默认选项
                        if (options.length === 0) {
                            options = ['未配置'];
                        }
                        // 确保当前值在选项中，如果不在则使用第一个
                        const currentValue = value && options.includes(value) ? value : options[0];

                        widgetsHtml += `
                            <div class="node-input-row">
                                <select class="node-input-field"
                                    data-node="${node.id}" data-widget="${w.name}"
                                    onchange="updateWidget(${node.id}, '${w.name}', this.value)">
                                    ${options.map(opt => `<option value="${opt}" ${opt === currentValue ? 'selected' : ''}>${opt}</option>`).join('')}
                                </select>
                            </div>
                        `;
                    } else if (w.type === 'float' || w.type === 'int') {
                        widgetsHtml += `
                            <div class="node-input-row">
                                <input type="number" class="node-input-field" 
                                    data-node="${node.id}" data-widget="${w.name}"
                                    value="${value}" min="${w.min}" max="${w.max}" step="${w.step || 1}"
                                    onchange="updateWidget(${node.id}, '${w.name}', this.value)">
                            </div>
                        `;
                    } else {
                        widgetsHtml += `
                            <div class="node-input-row">
                                <input type="text" class="node-input-field" 
                                    data-node="${node.id}" data-widget="${w.name}"
                                    value="${value}" placeholder="${w.label}"
                                    onchange="updateWidget(${node.id}, '${w.name}', this.value)">
                            </div>
                        `;
                    }
                });
            }

            // 输出端口
            let outputsHtml = '';
            if (def.outputs) {
                def.outputs.forEach((output, i) => {
                    outputsHtml += `
                        <div class="node-input-row">
                            <span class="node-input-label" style="text-align: right;">${output}</span>
                            <div class="port port-output" data-node="${node.id}" data-slot="${i}" data-type="output"></div>
                        </div>
                    `;
                });
            }

            const el = $(`
                <div class="comfy-node ${simpleMode ? 'simple-mode' : ''}" id="node_${node.id}" style="left: ${node.pos[0]}px; top: ${node.pos[1]}px;">
                    <div class="node-header" onmousedown="startDrag(event, ${node.id})">
                        <i class="fas fa-cube"></i>
                        ${def.displayName}
                    </div>
                    <div class="node-content">
                        ${inputsHtml}
                        ${widgetsHtml}
                        ${outputsHtml}
                    </div>
                </div>
            `);

            $('#canvas').append(el);

            // 绑定端口事件
            el.find('.port').on('mousedown', function(e) {
                e.stopPropagation();
                const nodeId = $(this).data('node');
                const slot = $(this).data('slot');
                const type = $(this).data('type');
                startConnection(nodeId, slot, type);
            });

            el.find('.port').on('mouseup', function(e) {
                e.stopPropagation();
                if (connecting) {
                    const nodeId = $(this).data('node');
                    const slot = $(this).data('slot');
                    const type = $(this).data('type');
                    finishConnection(nodeId, slot, type);
                }
            });

            // 点击选择
            el.on('mousedown', function(e) {
                if ($(e.target).hasClass('port') || $(e.target).closest('.port').length) return;
                selectNode(node.id);
            });
            
            return el;
        }

        // 更新 widget 值
        function updateWidget(nodeId, name, value) {
            const node = nodes.find(n => n.id === nodeId);
            if (node) {
                node.widgets[name] = value;
                node.inputs[name] = value;
                
                // 如果改变的是提供商，刷新模型列表
                if (name === 'provider') {
                    // 获取新提供商的模型列表
                    const newModels = getAvailableModelsForNodes(value);
                    // 更新模型widget的值为第一个可用模型
                    if (newModels.length > 0) {
                        node.widgets['model'] = newModels[0];
                        node.inputs['model'] = newModels[0];
                    }
                    // 重新渲染属性面板
                    if (selectedNode === nodeId) {
                        renderProperties(nodeId);
                    }
                }
                
                // 标记工作流已修改
                markWorkflowModified();
            }
        }

        // 文件上传处理
        function handleFileUpload(nodeId, name, input) {
            const file = input.files[0];
            if (file) {
                updateWidget(nodeId, name, file.name);
                $(input).prev('.node-file-upload').find('span').text(file.name);
                showToast(`已选择文件: ${file.name}`);
            }
        }

        // 开始拖拽
        function startDrag(e, nodeId) {
            e.preventDefault();
            e.stopPropagation();
            
            draggingNode = nodeId;
            const node = nodes.find(n => n.id === nodeId);
            const el = $(`#node_${nodeId}`);
            
            dragOffset.x = e.clientX - node.pos[0];
            dragOffset.y = e.clientY - node.pos[1];

            $(document).on('mousemove.nodeDrag', function(e) {
                const x = e.clientX - dragOffset.x;
                const y = e.clientY - dragOffset.y;
                
                node.pos[0] = x;
                node.pos[1] = y;
                
                el.css({ left: x, 'top': y });
                updateConnections();
            });

            $(document).on('mouseup.nodeDrag', function() {
                $(document).off('.nodeDrag');
                draggingNode = null;
            });

            selectNode(nodeId);
        }

        // 开始连接
        function startConnection(nodeId, slot, type) {
            connecting = true;
            connectingFrom = { node: nodeId, slot, type };

            // 创建临时连线
            const svg = $('#connectionsSvg');
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('class', 'connection-path temp');
            path.setAttribute('id', 'tempLink');
            svg.append(path);

            $(document).on('mousemove.connect', function(e) {
                updateTempConnection(e);
            });

            $(document).on('mouseup.connect', function() {
                cancelConnection();
            });
        }

        // 更新临时连线
        function updateTempConnection(e) {
            if (!connectingFrom) return;
            
            const fromEl = $(`#node_${connectingFrom.node}`);
            const fromPort = fromEl.find(connectingFrom.type === 'output' ? '.port-output' : '.port-input').eq(connectingFrom.slot);
            
            const canvasRect = $('#canvas')[0].getBoundingClientRect();
            const fromRect = fromPort[0].getBoundingClientRect();
            
            const x1 = fromRect.left - canvasRect.left + fromRect.width / 2;
            const y1 = fromRect.top - canvasRect.top + fromRect.height / 2;
            const x2 = e.clientX - canvasRect.left;
            const y2 = e.clientY - canvasRect.top;
            
            const path = document.getElementById('tempLink');
            if (path) {
                const cp1x = x1 + 50;
                const cp2x = x2 - 50;
                path.setAttribute('d', `M${x1},${y1} C${cp1x},${y1} ${cp2x},${y2} ${x2},${y2}`);
            }
        }

        // 完成连接
        function finishConnection(nodeId, slot, type) {
            if (!connectingFrom) return;
            
            // 验证连接
            if (connectingFrom.type === type) {
                showToast('不能连接相同类型的端口', 'error');
                cancelConnection();
                return;
            }
            
            if (connectingFrom.node === nodeId) {
                cancelConnection();
                return;
            }
            
            // 创建连接
            let from, to;
            if (connectingFrom.type === 'output') {
                from = { node: connectingFrom.node, slot: connectingFrom.slot };
                to = { node: nodeId, slot };
            } else {
                from = { node: nodeId, slot };
                to = { node: connectingFrom.node, slot: connectingFrom.slot };
            }
            
            // 检查是否已存在
            const exists = links.some(l => 
                l.from.node === from.node && l.from.slot === from.slot &&
                l.to.node === to.node && l.to.slot === to.slot
            );
            
            if (exists) {
                showToast('连接已存在', 'warning');
                cancelConnection();
                return;
            }
            
            const link = {
                id: linkIdCounter++,
                from,
                to
            };
            
            links.push(link);
            renderLink(link);
            
            // 标记端口为已连接
            $(`#node_${from.node}`).find('.port-output').eq(from.slot).addClass('connected');
            $(`#node_${to.node}`).find('.port-input').eq(to.slot).addClass('connected');
            
            cancelConnection();
            showToast('连接已创建');
        }

        // 取消连接
        function cancelConnection() {
            connecting = false;
            connectingFrom = null;
            $('#tempLink').remove();
            $(document).off('.connect');
        }

        // 渲染连线
        function renderLink(link) {
            const svg = $('#connectionsSvg');
            
            const fromEl = $(`#node_${link.from.node}`);
            const toEl = $(`#node_${link.to.node}`);
            
            if (!fromEl.length || !toEl.length) return;
            
            const fromPort = fromEl.find('.port-output').eq(link.from.slot);
            const toPort = toEl.find('.port-input').eq(link.to.slot);
            
            const canvasRect = $('#canvas')[0].getBoundingClientRect();
            const fromRect = fromPort[0].getBoundingClientRect();
            const toRect = toPort[0].getBoundingClientRect();
            
            const x1 = fromRect.left - canvasRect.left + fromRect.width / 2;
            const y1 = fromRect.top - canvasRect.top + fromRect.height / 2;
            const x2 = toRect.left - canvasRect.left + toRect.width / 2;
            const y2 = toRect.top - canvasRect.top + toRect.height / 2;
            
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('class', 'connection-path');
            path.setAttribute('id', `link_${link.id}`);
            path.setAttribute('data-link', link.id);
            
            const cp1x = x1 + 50;
            const cp2x = x2 - 50;
            path.setAttribute('d', `M${x1},${y1} C${cp1x},${y1} ${cp2x},${y2} ${x2},${y2}`);
            
            path.onclick = function() {
                deleteLink(link.id);
            };
            
            svg.append(path);
        }

        // 更新所有连线
        function updateConnections() {
            $('#connectionsSvg').empty();
            links.forEach(link => renderLink(link));
        }

        // 删除连线
        function deleteLink(linkId) {
            if (!confirm('删除此连接?')) return;
            
            const link = links.find(l => l.id === linkId);
            if (link) {
                // 移除端口连接标记
                $(`#node_${link.from.node}`).find('.port-output').eq(link.from.slot).removeClass('connected');
                $(`#node_${link.to.node}`).find('.port-input').eq(link.to.slot).removeClass('connected');
                
                links = links.filter(l => l.id !== linkId);
                updateConnections();
                showToast('连接已删除');
            }
        }

        // 选择节点
        function selectNode(nodeId) {
            $('.comfy-node').removeClass('selected');
            $(`#node_${nodeId}`).addClass('selected');
            selectedNode = nodeId;
            
            showProperties(nodeId);
        }

        // 取消选择
        function deselectAll() {
            $('.comfy-node').removeClass('selected');
            selectedNode = null;
            closeProperties();
        }

        // 显示属性
        function showProperties(nodeId) {
            const node = nodes.find(n => n.id === nodeId);
            const def = NODE_DEFINITIONS[node.type];
            if (!node || !def) return;

            let html = '';
            
            if (def.widgets) {
                def.widgets.forEach(w => {
                    const value = node.inputs[w.name] !== undefined ? node.inputs[w.name] : '';
                    html += `
                        <div class="property-group">
                            <label class="property-label">${w.label}</label>
                    `;
                    
                    if (w.type === 'textarea') {
                        html += `<textarea class="property-input" rows="4" onchange="updateWidget(${nodeId}, '${w.name}', this.value)">${value}</textarea>`;
                    } else if (w.type === 'combo') {
                        // 处理动态选项
                        let options = w.options;
                        let optionsHtml = '';
                        
                        if (options === 'DYNAMIC_MODELS') {
                            // 获取当前选中的提供商
                            const providerValue = node.widgets['provider'] || 'ollama';
                            options = getAvailableModelsForNodes(providerValue);
                            const currentValue = value && options.includes(value) ? value : options[0];
                            optionsHtml = options.map(opt => `<option value="${opt}" ${opt === currentValue ? 'selected' : ''}>${opt}</option>`).join('');
                        } else if (options === 'DYNAMIC_PROVIDERS') {
                            options = getAvailableProvidersForNodes();
                            const currentValue = value && options.find(o => o.value === value) ? value : options[0]?.value;
                            optionsHtml = options.map(opt => `<option value="${opt.value}" ${opt.value === currentValue ? 'selected' : ''}>${opt.label}</option>`).join('');
                        } else {
                            const currentValue = value && options.includes(value) ? value : options[0];
                            optionsHtml = options.map(opt => `<option value="${opt}" ${opt === currentValue ? 'selected' : ''}>${opt}</option>`).join('');
                        }

                        html += `<select class="property-input" onchange="updateWidget(${nodeId}, '${w.name}', this.value)" data-widget="${w.name}">
                            ${optionsHtml}
                        </select>`;
                    } else if (w.type === 'float' || w.type === 'int') {
                        html += `<input type="number" class="property-input" value="${value}" min="${w.min}" max="${w.max}" step="${w.step || 1}" onchange="updateWidget(${nodeId}, '${w.name}', this.value)">`;
                    } else {
                        html += `<input type="text" class="property-input" value="${value}" onchange="updateWidget(${nodeId}, '${w.name}', this.value)">`;
                    }
                    
                    html += '</div>';
                });
            }
            
            html += `
                <div class="property-group">
                    <button class="comfy-btn danger" style="width: 100%;" onclick="deleteNode(${nodeId})">
                        <i class="fas fa-trash"></i> 删除节点
                    </button>
                </div>
            `;
            
            $('#propertiesContent').html(html);
            $('#propertiesPanel').addClass('open');
        }

        // 关闭属性面板
        function closeProperties() {
            $('#propertiesPanel').removeClass('open');
        }

        // 确认对话框回调
        let confirmCallback = null;

        // 显示确认对话框
        function showConfirmDialog(title, message, callback) {
            confirmCallback = callback;
            $('#confirmTitle').text(title);
            $('#confirmMessage').text(message);
            $('#confirmDialog').addClass('show');
        }

        // 关闭确认对话框
        function closeConfirmDialog() {
            $('#confirmDialog').removeClass('show');
            confirmCallback = null;
        }

        // 执行确认回调
        function executeConfirmCallback() {
            if (confirmCallback) {
                confirmCallback();
            }
            closeConfirmDialog();
        }

        // ========== 轻码模式 ==========
        let simpleMode = false;

        function toggleSimpleMode() {
            simpleMode = !simpleMode;
            $('#simpleModeBtn').toggleClass('active', simpleMode);
            $('.comfy-node').toggleClass('simple-mode', simpleMode);
            
            if (simpleMode) {
                closeProperties();
                showToast('已开启轻码模式', 'info');
            } else {
                showToast('已退出轻码模式', 'info');
            }
        }

        // ========== 导入/导出功能 ==========
        let importExportMode = 'import'; // 'import' or 'export'

        function importWorkflow() {
            importExportMode = 'import';
            $('#ieDialogTitle').text('导入工作流');
            $('#importSection').show();
            $('#exportSection').hide();
            $('#importExportDialog').show();
            $('#importTextarea').val('');
            $('#ieDialogButtons').show();
        }

        function exportWorkflow() {
            importExportMode = 'export';
            $('#ieDialogTitle').text('导出工作流');
            $('#importSection').hide();
            $('#exportSection').show();
            
            const workflow = {
                version: '1.0',
                created: new Date().toISOString(),
                nodes: nodes,
                links: links
            };
            
            $('#exportTextarea').val(JSON.stringify(workflow, null, 2));
            $('#importExportDialog').show();
            $('#ieDialogButtons').hide();
        }

        function closeImportExportDialog() {
            $('#importExportDialog').hide();
        }

        function handleFileImport(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    $('#importTextarea').val(e.target.result);
                } catch (err) {
                    showToast('文件读取失败', 'error');
                }
            };
            reader.readAsText(file);
        }

        function confirmImport() {
            const jsonText = $('#importTextarea').val().trim();
            if (!jsonText) {
                showToast('请输入或粘贴JSON数据', 'error');
                return;
            }
            
            try {
                console.log('=== 开始导入流程 ===');
                const data = JSON.parse(jsonText);
                console.log('Import data type:', typeof data);
                console.log('Import data keys:', Object.keys(data).slice(0, 10));
                
                // 检测是否为ComfyUI格式 (prompt格式或workflow格式)
                let workflow = data;
                let isComfyUI = false;
                
                // 检测格式1: ComfyUI prompt格式 (节点ID为字符串键，包含class_type)
                if (!data.nodes && !data.links) {
                    console.log('检测格式1: Prompt API格式');
                    const keys = Object.keys(data);
                    console.log('Top-level keys:', keys.slice(0, 5));
                    
                    const hasComfyNodes = keys.some(key => {
                        const node = data[key];
                        const isValid = node && typeof node === 'object' && (node.class_type || node.type);
                        if (isValid) console.log(`找到有效节点 ${key}:`, node.class_type || node.type);
                        return isValid;
                    });
                    
                    console.log('hasComfyNodes:', hasComfyNodes);
                    
                    if (hasComfyNodes) {
                        isComfyUI = true;
                        showToast('检测到ComfyUI格式，正在转换...', 'info');
                        workflow = convertComfyUIToInternal(data);
                    }
                }
                // 检测格式2: ComfyUI workflow格式 (有nodes数组，但结构不同)
                else if (data.nodes && Array.isArray(data.nodes)) {
                    console.log('检测格式2: Workflow格式');
                    const firstNode = data.nodes[0];
                    console.log('First node:', firstNode);
                    
                    if (firstNode && (firstNode.class_type || firstNode.type || firstNode.widgets_values)) {
                        isComfyUI = true;
                        showToast('检测到ComfyUI Workflow格式，正在转换...', 'info');
                        workflow = convertComfyUIToInternal(data);
                    }
                }
                
                console.log('转换后的 workflow:', workflow);
                console.log('节点数量:', workflow.nodes ? workflow.nodes.length : 0);
                
                // 验证数据结构
                if (!workflow.nodes || !Array.isArray(workflow.nodes)) {
                    showToast('无效的工作流格式: 缺少节点数据', 'error');
                    return;
                }
                
                if (workflow.nodes.length === 0) {
                    showToast('工作流为空，没有可导入的节点', 'error');
                    return;
                }
                
                // 直接清空（不调用clearWorkflow避免确认对话框）
                console.log('清空画布...');
                nodes = [];
                links = [];
                $('.comfy-node').remove();
                $('#connectionsSvg').empty();
                closeProperties();
                console.log('画布已清空，准备导入节点...');
                
                // 恢复节点
                let maxId = 0;
                let renderedCount = 0;
                workflow.nodes.forEach((node, idx) => {
                    // 确保节点有必要的属性
                    if (!node.id) node.id = idx + 1;
                    if (!node.pos) node.pos = [150 + (idx * 50) % 400, 100 + (idx * 80) % 500];
                    if (!node.inputs) node.inputs = {};
                    
                    // 检查节点类型
                    if (!NODE_DEFINITIONS[node.type]) {
                        console.warn(`节点类型 ${node.type} 未定义，尝试映射为预览节点`);
                        node.type = 'PreviewAny';
                    }
                    
                    nodes.push(node);
                    const el = renderNode(node);
                    if (el) renderedCount++;
                    maxId = Math.max(maxId, node.id);
                });
                nodeIdCounter = maxId + 1;
                console.log(`成功渲染 ${renderedCount}/${workflow.nodes.length} 个节点`);
                
                // 恢复连接
                if (workflow.links && Array.isArray(workflow.links) && workflow.links.length > 0) {
                    workflow.links.forEach(link => {
                        links.push(link);
                    });
                    // 延迟更新连接，确保DOM已渲染
                    setTimeout(updateConnections, 100);
                }
                
                closeImportExportDialog();
                
                // 强制重新渲染所有连接
                setTimeout(() => {
                    updateConnections();
                    console.log('导入完成，最终节点数:', nodes.length);
                }, 200);
                
                showToast(`工作流导入成功，共 ${workflow.nodes.length} 个节点${workflow.links ? ', ' + workflow.links.length + ' 条连接' : ''}`, 'success');
                
            } catch (err) {
                console.error('Import error:', err);
                showToast('导入失败: ' + err.message, 'error');
                console.error('Error stack:', err.stack);
            }
        }

        // 测试导入 - 创建一个简单的测试工作流
        function testImport() {
            console.log('创建测试工作流...');
            const testWorkflow = {
                nodes: [
                    { id: 1, type: 'LoadText', pos: [100, 100], inputs: { text: '测试文本' } },
                    { id: 2, type: 'GPTNode', pos: [350, 100], inputs: { model: 'llama2', temperature: 0.7 } },
                    { id: 3, type: 'PreviewAny', pos: [600, 100], inputs: {} }
                ],
                links: [
                    { id: 1, from: { node: 1, slot: 0 }, to: { node: 2, slot: 0 } },
                    { id: 2, from: { node: 2, slot: 0 }, to: { node: 3, slot: 0 } }
                ]
            };
            
            // 清空并导入
            nodes = [];
            links = [];
            $('.comfy-node').remove();
            $('#connectionsSvg').empty();
            
            testWorkflow.nodes.forEach(node => {
                nodes.push(node);
                renderNode(node);
            });
            
            testWorkflow.links.forEach(link => {
                links.push(link);
            });
            updateConnections();
            
            nodeIdCounter = 4;
            showToast('测试工作流已加载', 'success');
        }

        // 转换ComfyUI格式到内部格式
        function convertComfyUIToInternal(comfyData) {
            console.log('开始转换ComfyUI格式...', comfyData);
            const nodes = [];
            const links = [];
            
            // 检测是哪种ComfyUI格式
            // 格式1: workflow API格式 { "1": { class_type: "...", inputs: {...} }, ... }
            // 格式2: workflow格式 { nodes: [...], links: [...] }
            
            let sourceNodes = [];
            let sourceLinks = [];
            
            if (comfyData.nodes && Array.isArray(comfyData.nodes)) {
                // 格式2: 完整的workflow格式
                console.log('检测到格式2: Workflow格式');
                sourceNodes = comfyData.nodes;
                sourceLinks = comfyData.links || [];
            } else {
                // 格式1: API prompt格式
                console.log('检测到格式1: Prompt API格式');
                Object.entries(comfyData).forEach(([id, node]) => {
                    if (node && typeof node === 'object' && (node.class_type || node.type)) {
                        sourceNodes.push({
                            id: parseInt(id) || id,
                            class_type: node.class_type || node.type,
                            inputs: node.inputs || {},
                            pos: node.pos || null
                        });
                    }
                });
            }
            console.log(`提取到 ${sourceNodes.length} 个源节点`);
            
            // 映射原始ID到新ID
            const nodeIdMap = {};
            
            // 第一遍：创建所有节点
            console.log('开始创建节点...');
            sourceNodes.forEach((comfyNode, index) => {
                const originalId = comfyNode.id || index + 1;
                const newId = typeof originalId === 'number' ? originalId : (index + 1);
                nodeIdMap[originalId] = newId;
                nodeIdMap[String(originalId)] = newId;
                
                // 获取节点类型
                const classType = comfyNode.class_type || comfyNode.type || 'PreviewNode';
                let internalType = mapComfyUIType(classType);
                
                // 获取节点位置
                let pos = [150 + (index * 50) % 500, 100 + (index * 80) % 600];
                if (comfyNode.pos && Array.isArray(comfyNode.pos) && comfyNode.pos.length >= 2) {
                    pos = [comfyNode.pos[0], comfyNode.pos[1]];
                }
                
                const node = {
                    id: newId,
                    type: internalType,
                    pos: pos,
                    inputs: {}
                };
                
                // 复制widget值 (从inputs中提取非连接的值)
                const inputData = comfyNode.inputs || comfyNode.widgets_values || {};
                Object.entries(inputData).forEach(([key, value]) => {
                    // 跳过连接引用 [nodeId, slot]
                    if (Array.isArray(value) && value.length === 2 && 
                        (typeof value[0] === 'string' || typeof value[0] === 'number')) {
                        return;
                    }
                    node.inputs[key] = value;
                });
                
                nodes.push(node);
                console.log(`创建节点 ${newId}: 类型=${internalType}, 原类型=${classType}`);
            });
            console.log(`共创建 ${nodes.length} 个节点`);
            
            // 第二遍：创建连接
            if (comfyData.links && Array.isArray(comfyData.links)) {
                // 格式2的links: [id, fromNode, fromSlot, toNode, toSlot, type]
                comfyData.links.forEach((link, idx) => {
                    let fromNodeId, fromSlot, toNodeId, toSlot;
                    
                    if (Array.isArray(link)) {
                        // [id, fromNode, fromSlot, toNode, toSlot, type]
                        fromNodeId = nodeIdMap[link[1]] || link[1];
                        fromSlot = link[2];
                        toNodeId = nodeIdMap[link[3]] || link[3];
                        toSlot = link[4];
                    } else if (typeof link === 'object') {
                        // { id, from_id, to_id, from_slot, to_slot }
                        fromNodeId = nodeIdMap[link.from_id || link.from] || link.from_id || link.from;
                        fromSlot = link.from_slot || link.fromSlot || 0;
                        toNodeId = nodeIdMap[link.to_id || link.to] || link.to_id || link.to;
                        toSlot = link.to_slot || link.toSlot || 0;
                    }
                    
                    if (fromNodeId && toNodeId) {
                        links.push({
                            id: idx + 1,
                            from: { node: fromNodeId, slot: fromSlot },
                            to: { node: toNodeId, slot: toSlot }
                        });
                    }
                });
            } else {
                // 从inputs中解析连接 (格式1)
                sourceNodes.forEach(comfyNode => {
                    const toNodeId = nodeIdMap[comfyNode.id];
                    const inputs = comfyNode.inputs || {};
                    
                    Object.entries(inputs).forEach(([inputName, value]) => {
                        // 检查是否为连接引用 [nodeId, slot]
                        if (Array.isArray(value) && value.length === 2) {
                            const fromOriginalId = value[0];
                            const fromSlot = value[1];
                            
                            const fromNodeId = nodeIdMap[fromOriginalId];
                            
                            if (fromNodeId && toNodeId) {
                                // 查找输入槽位索引
                                const toNode = nodes.find(n => n.id === toNodeId);
                                const toDef = toNode ? NODE_DEFINITIONS[toNode.type] : null;
                                let toSlotIdx = 0;
                                
                                if (toDef && toDef.inputs) {
                                    const inputIdx = toDef.inputs.findIndex(
                                        inp => inp.toUpperCase() === inputName.toUpperCase()
                                    );
                                    if (inputIdx >= 0) toSlotIdx = inputIdx;
                                }
                                
                                links.push({
                                    id: links.length + 1,
                                    from: { node: fromNodeId, slot: fromSlot },
                                    to: { node: toNodeId, slot: toSlotIdx }
                                });
                            }
                        }
                    });
                });
            }
            
            console.log(`转换完成: ${nodes.length} 个节点, ${links.length} 条连接`);
            console.log('节点类型:', nodes.map(n => n.type));
            
            return { nodes, links };
        }

        // ComfyUI类型映射到内部类型
        function mapComfyUIType(comfyType) {
            const typeMap = {
                // 加载器
                'CheckpointLoaderSimple': 'CheckpointLoaderSimple',
                'CheckpointLoader': 'CheckpointLoaderSimple',
                'VAELoader': 'VAELoader',
                'CLIPLoader': 'CLIPLoader',
                'UNETLoader': 'CheckpointLoaderSimple',
                
                // 输入
                'LoadImage': 'LoadImage',
                'LoadImageMask': 'LoadImageMask',
                'LoadText': 'LoadText',
                'LoadFile': 'LoadFile',
                'LoadVideo': 'LoadVideo',
                'LoadAudio': 'LoadVideo',
                
                // 条件/CLIP
                'CLIPTextEncode': 'CLIPTextEncode',
                'CLIPTextEncodeFlux': 'CLIPTextEncodeFlux',
                'CLIPSetLastLayer': 'CLIPTextEncode',
                'ConditioningCombine': 'ConditioningCombine',
                'ConditioningAverage': 'ConditioningAverage',
                'ConditioningConcat': 'ConditioningConcat',
                'ConditioningSetArea': 'ConditioningSetArea',
                'ConditioningSetAreaPercentage': 'ConditioningSetArea',
                'ConditioningSetMask': 'ConditioningSetArea',
                'ConditioningZeroOut': 'ConditioningCombine',
                
                // 采样器
                'KSampler': 'KSampler',
                'KSamplerAdvanced': 'KSamplerAdvanced',
                'SamplerCustom': 'KSampler',
                
                // Latent
                'EmptyLatentImage': 'EmptyLatentImage',
                'EmptySD3LatentImage': 'EmptyLatentImage',
                'LatentUpscale': 'LatentUpscale',
                'LatentUpscaleBy': 'LatentUpscale',
                'LatentComposite': 'LatentComposite',
                'LatentCompositeMasked': 'LatentComposite',
                'LatentBlend': 'LatentComposite',
                'SetLatentNoiseMask': 'SetLatentNoiseMask',
                
                // VAE
                'VAEDecode': 'VAEDecode',
                'VAEEncode': 'VAEEncode',
                'VAEDecodeTiled': 'VAEDecodeTiled',
                'VAEEncodeTiled': 'VAEEncode',
                
                // 图像处理
                'SaveImage': 'SaveImage',
                'PreviewImage': 'PreviewImage',
                'ImageScale': 'ImageScale',
                'ImageScaleBy': 'ImageScale',
                'ImageUpscaleWithModel': 'ImageScale',
                'ImageCompositeMasked': 'ImageCompositeMasked',
                'ImageBlend': 'ImageCompositeMasked',
                'InvertMask': 'LoadImageMask',
                'SolidMask': 'LoadImageMask',
                
                // 视频/音频
                'SaveVideo': 'SaveVideo',
                'VideoCombine': 'VideoCombine',
                'SaveAudio': 'SaveVideo',
                'PreviewAudio': 'PreviewAny',
                'PreviewVideo': 'PreviewAny',
                
                // 实用工具
                'Note': 'LoadText',
                'Reroute': 'PreviewAny',
                'PrimitiveNode': 'LoadText',
                'ShowText|pysssss': 'ShowText',
                'ShowText': 'ShowText',
                
                // AI文本
                'GPTNode': 'GPTNode',
                'ChatNode': 'ChatNode',
                'TextCombine': 'TextCombine',
                'TextReplace': 'TextReplace',
                
                // 逻辑
                'IfCondition': 'IfCondition',
                'LoopNode': 'LoopNode',
                'DelayNode': 'DelayNode',
                
                // 输出
                'SaveText': 'SaveText',
                'PreviewAny': 'PreviewAny'
            };
            
            // 尝试直接匹配
            if (typeMap[comfyType]) {
                return typeMap[comfyType];
            }
            
            // 尝试部分匹配
            for (const [key, value] of Object.entries(typeMap)) {
                if (comfyType.includes(key) || key.includes(comfyType)) {
                    return value;
                }
            }
            
            // 根据关键词猜测类型
            if (comfyType.includes('Image') || comfyType.includes('image')) return 'LoadImage';
            if (comfyType.includes('Text') || comfyType.includes('text')) return 'LoadText';
            if (comfyType.includes('Video') || comfyType.includes('video')) return 'LoadVideo';
            if (comfyType.includes('Audio') || comfyType.includes('audio')) return 'LoadVideo';
            if (comfyType.includes('Save')) return 'SaveText';
            if (comfyType.includes('Preview')) return 'PreviewAny';
            
            console.warn(`未知节点类型: ${comfyType}，使用默认节点`);
            return 'PreviewAny'; // 默认使用通用预览节点
        }

        function copyExportText() {
            const textarea = $('#exportTextarea')[0];
            textarea.select();
            document.execCommand('copy');
            showToast('已复制到剪贴板', 'success');
        }

        // 拖拽导入
        $(document).on('dragover', function(e) {
            e.preventDefault();
        });

        $(document).on('drop', function(e) {
            e.preventDefault();
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0 && files[0].name.endsWith('.json')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#importTextarea').val(e.target.result);
                    importWorkflow();
                };
                reader.readAsText(files[0]);
            }
        });

        // 删除节点
        function deleteNode(nodeId) {
            const node = nodes.find(n => n.id === nodeId);
            const nodeName = node ? (NODE_DEFINITIONS[node.type]?.displayName || '节点') : '节点';
            
            showConfirmDialog(
                '删除节点',
                `确定要删除 "${nodeName}" 吗？此操作将同时删除该节点的所有连接。`,
                function() {
                    // 删除相关连接
                    links = links.filter(l => {
                        if (l.from.node === nodeId || l.to.node === nodeId) {
                            $(`#link_${l.id}`).remove();
                            return false;
                        }
                        return true;
                    });
                    
                    // 删除节点
                    nodes = nodes.filter(n => n.id !== nodeId);
                    $(`#node_${nodeId}`).remove();
                    
                    updateConnections();
                    closeProperties();
                    showToast('节点已删除', 'success');
                }
            );
        }

        // 清空工作流
        function clearWorkflow() {
            showConfirmDialog(
                '清空工作流',
                `确定要清空所有节点和连接吗？此操作不可撤销，共 ${nodes.length} 个节点将被删除。`,
                function() {
                    nodes = [];
                    links = [];
                    $('.comfy-node').remove();
                    $('#connectionsSvg').empty();
                    closeProperties();
                    showToast('工作流已清空', 'success');
                }
            );
        }

        // 生成 ComfyUI 格式的 prompt
        function generatePrompt() {
            const prompt = {};
            
            nodes.forEach(node => {
                const def = NODE_DEFINITIONS[node.type];
                const nodeData = {
                    class_type: node.type,
                    inputs: {}
                };
                
                // 添加 widget 值
                if (def.widgets) {
                    def.widgets.forEach(w => {
                        if (node.inputs[w.name] !== undefined) {
                            nodeData.inputs[w.name] = node.inputs[w.name];
                        }
                    });
                }
                
                // 添加连接
                links.forEach(link => {
                    if (link.to.node === node.id) {
                        const inputName = def.inputs ? def.inputs[link.to.slot] : `input_${link.to.slot}`;
                        nodeData.inputs[inputName] = [String(link.from.node), link.from.slot];
                    }
                });
                
                prompt[String(node.id)] = nodeData;
            });
            
            return prompt;
        }

        // 当前执行ID
        let currentExecutionId = null;
        let progressInterval = null;
        let runningNodes = new Set();

        // 执行队列
        function queuePrompt() {
            if (nodes.length === 0) {
                showToast('工作流为空', 'error');
                return;
            }
            
            const prompt = generatePrompt();
            const workflowData = {
                nodes: nodes.reduce((acc, node) => {
                    acc[node.id] = {
                        id: node.id,
                        type: node.type,
                        config: node.config || {},
                        inputs: node.inputs || [],
                        outputs: node.outputs || []
                    };
                    return acc;
                }, {}),
                edges: links.map(link => ({
                    source: String(link.from.node),
                    target: String(link.to.node),
                    sourceHandle: link.fromPort || `output_${link.from.slot}`,
                    targetHandle: link.toPort || `input_${link.to.slot}`
                }))
            };
            
            // 添加到队列
            const queueItem = {
                id: Date.now(),
                prompt: prompt,
                status: 'pending',
                time: new Date().toLocaleTimeString()
            };
            
            queue.push(queueItem);
            updateQueueDisplay();
            
            // 发送到服务器执行
            $.ajax({
                url: 'api/workflow_api.php?action=runWorkflow',
                type: 'POST',
                data: JSON.stringify({
                    workflow_id: currentWorkflowId || 'temp_' + Date.now(),
                    workflow_data: workflowData,
                    inputs: prompt
                }),
                contentType: 'application/json',
                success: function(res) {
                    if (res.status === 'success') {
                        currentExecutionId = res.execution_id;
                        queueItem.executionId = res.execution_id;
                        queueItem.status = 'running';
                        updateQueueDisplay();
                        
                        // 开始轮询进度
                        startProgressPolling(res.execution_id);
                        showToast('工作流已开始执行');
                    } else {
                        queueItem.status = 'error';
                        updateQueueDisplay();
                        showToast('执行失败: ' + res.message, 'error');
                    }
                },
                error: function() {
                    // 使用本地模拟执行
                    simulateLocalExecution(queueItem);
                }
            });
            
            showToast('已加入执行队列');
        }

        // 本地模拟执行（服务器不可用时的降级方案）
        function simulateLocalExecution(queueItem) {
            setTimeout(() => {
                queueItem.status = 'running';
                updateQueueDisplay();
                
                // 模拟节点执行
                let nodeIndex = 0;
                const executeNextNode = () => {
                    if (nodeIndex >= nodes.length) {
                        queueItem.status = 'completed';
                        updateQueueDisplay();
                        showToast('工作流执行完成');
                        clearRunningNodes();
                        return;
                    }
                    
                    const node = nodes[nodeIndex];
                    highlightNode(node.id, 'running');
                    runningNodes.add(node.id);
                    
                    setTimeout(() => {
                        highlightNode(node.id, 'completed');
                        nodeIndex++;
                        executeNextNode();
                    }, 1000);
                };
                
                executeNextNode();
                
            }, 500);
        }

        // 开始轮询执行进度
        function startProgressPolling(executionId) {
            if (progressInterval) {
                clearInterval(progressInterval);
            }
            
            progressInterval = setInterval(() => {
                $.ajax({
                    url: 'api/workflow_api.php?action=getExecutionStatus&execution_id=' + executionId,
                    type: 'GET',
                    success: function(res) {
                        if (res.status === 'success') {
                            updateExecutionProgress(res.execution);
                            
                            if (res.execution.status === 'completed' || res.execution.status === 'error') {
                                clearInterval(progressInterval);
                                progressInterval = null;
                                
                                if (res.execution.status === 'completed') {
                                    showToast('工作流执行完成');
                                } else {
                                    showToast('执行出错: ' + (res.execution.error || '未知错误'), 'error');
                                }
                                
                                // 更新队列状态
                                const queueItem = queue.find(q => q.executionId === executionId);
                                if (queueItem) {
                                    queueItem.status = res.execution.status;
                                    updateQueueDisplay();
                                }
                            }
                        }
                    },
                    error: function() {
                        // 忽略错误，继续轮询
                    }
                });
            }, 500); // 每500ms查询一次
        }

        // 更新执行进度和节点状态
        function updateExecutionProgress(execution) {
            // 更新进度条
            const progress = execution.progress || 0;
            updateProgressBar(progress);
            
            // 更新节点高亮
            const nodeStatus = execution.node_status || {};
            
            // 清除之前的状态
            clearRunningNodes();
            
            // 应用新状态
            Object.entries(nodeStatus).forEach(([nodeId, status]) => {
                if (status.status === 'running') {
                    highlightNode(nodeId, 'running');
                    runningNodes.add(nodeId);
                } else if (status.status === 'completed') {
                    highlightNode(nodeId, 'completed');
                } else if (status.status === 'error') {
                    highlightNode(nodeId, 'error');
                }
            });
            
            // 显示当前执行节点
            if (execution.current_node) {
                showCurrentNodeIndicator(execution.current_node);
            }
            
            // 显示生成的图像
            if (execution.generated_images && execution.generated_images.length > 0) {
                showGeneratedImages(execution.generated_images);
            }
        }
        
        // 显示生成的图像
        function showGeneratedImages(images) {
            let panel = $('#generatedImagesPanel');
            if (!panel.length) {
                $('body').append(`
                    <div id="generatedImagesPanel" style="
                        position: fixed;
                        right: 20px;
                        top: 70px;
                        width: 300px;
                        max-height: 80vh;
                        background: var(--comfy-panel);
                        border: 1px solid var(--comfy-border);
                        border-radius: 8px;
                        padding: 16px;
                        z-index: 9998;
                        overflow-y: auto;
                        box-shadow: 0 4px 20px rgba(0,0,0,0.5);
                    ">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <h4 style="margin: 0; color: var(--comfy-text);">
                                <i class="fas fa-images"></i> 生成的图像
                            </h4>
                            <button onclick="$('#generatedImagesPanel').fadeOut()" style="background: none; border: none; color: var(--comfy-text-muted); cursor: pointer;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div id="generatedImagesList"></div>
                    </div>
                `);
                panel = $('#generatedImagesPanel');
            }
            
            panel.show();
            const list = $('#generatedImagesList');
            list.empty();
            
            images.forEach((img, index) => {
                list.append(`
                    <div style="margin-bottom: 16px; border: 1px solid var(--comfy-border); border-radius: 6px; overflow: hidden;">
                        <img src="${img.url}" style="width: 100%; display: block;" 
                             onerror="this.src='assets/images/placeholder.png'" 
                             onclick="previewImage('${img.url}')" 
                             style="cursor: pointer;">
                        <div style="padding: 8px; background: rgba(0,0,0,0.3);">
                            <div style="font-size: 11px; color: var(--comfy-text-muted);">
                                ${img.width}x${img.height} | Node: ${img.node_id}
                            </div>
                            <div style="margin-top: 8px; display: flex; gap: 8px;">
                                <a href="${img.url}" download class="comfy-btn" style="flex: 1; font-size: 11px; padding: 4px 8px; text-decoration: none; text-align: center;">
                                    <i class="fas fa-download"></i> 下载
                                </a>
                                <button class="comfy-btn" style="flex: 1; font-size: 11px; padding: 4px 8px;" onclick="previewImage('${img.url}')">
                                    <i class="fas fa-eye"></i> 预览
                                </button>
                            </div>
                        </div>
                    </div>
                `);
            });
        }
        
        // 预览图像
        function previewImage(url) {
            // 创建模态框预览
            const modal = $(`
                <div style="
                    position: fixed;
                    top: 0; left: 0; right: 0; bottom: 0;
                    background: rgba(0,0,0,0.9);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                    cursor: zoom-out;
                " onclick="$(this).remove()">
                    <img src="${url}" style="max-width: 90%; max-height: 90%; object-fit: contain;" 
                         onclick="event.stopPropagation()">
                    <button style="
                        position: absolute;
                        top: 20px; right: 20px;
                        background: rgba(255,255,255,0.2);
                        border: none;
                        color: white;
                        padding: 10px 15px;
                        border-radius: 4px;
                        cursor: pointer;
                    " onclick="$(this).parent().remove()">
                        <i class="fas fa-times"></i> 关闭
                    </button>
                </div>
            `);
            $('body').append(modal);
        }

        // 高亮节点
        function highlightNode(nodeId, state) {
            const nodeEl = $(`.comfy-node[data-id="${nodeId}"]`);
            if (!nodeEl.length) return;
            
            // 移除所有状态类
            nodeEl.removeClass('running completed error');
            
            // 添加新状态类
            if (state) {
                nodeEl.addClass(state);
            }
            
            // 添加动画效果
            if (state === 'running') {
                nodeEl.css('animation', 'pulse 1s infinite');
            } else {
                nodeEl.css('animation', '');
            }
        }

        // 清除所有运行中节点的高亮
        function clearRunningNodes() {
            runningNodes.forEach(nodeId => {
                highlightNode(nodeId, null);
            });
            runningNodes.clear();
        }

        // 显示当前执行节点指示器
        function showCurrentNodeIndicator(nodeId) {
            $('.comfy-node').removeClass('current');
            $(`.comfy-node[data-id="${nodeId}"]`).addClass('current');
        }

        // 更新进度条
        function updateProgressBar(progress) {
            // 创建或更新进度条
            let progressBar = $('#workflowProgressBar');
            if (!progressBar.length) {
                $('body').append(`
                    <div id="workflowProgressContainer" style="
                        position: fixed;
                        top: 60px;
                        left: 50%;
                        transform: translateX(-50%);
                        width: 400px;
                        background: var(--comfy-panel);
                        border-radius: 8px;
                        padding: 12px 16px;
                        box-shadow: 0 4px 20px rgba(0,0,0,0.4);
                        z-index: 9999;
                        display: none;
                    ">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px;">
                            <span id="workflowProgressText">执行中...</span>
                            <span id="workflowProgressPercent">0%</span>
                        </div>
                        <div style="
                            height: 6px;
                            background: var(--comfy-border);
                            border-radius: 3px;
                            overflow: hidden;
                        ">
                            <div id="workflowProgressBar" style="
                                height: 100%;
                                background: linear-gradient(90deg, #4c51bf, #667eea);
                                border-radius: 3px;
                                transition: width 0.3s ease;
                                width: 0%;
                            "></div>
                        </div>
                    </div>
                `);
                progressBar = $('#workflowProgressBar');
            }
            
            const container = $('#workflowProgressContainer');
            if (progress > 0 && progress < 100) {
                container.show();
            } else if (progress >= 100) {
                setTimeout(() => container.fadeOut(), 1000);
            }
            
            progressBar.css('width', progress + '%');
            $('#workflowProgressPercent').text(progress + '%');
            
            if (progress < 30) {
                $('#workflowProgressText').text('初始化中...');
            } else if (progress < 70) {
                $('#workflowProgressText').text('执行节点中...');
            } else if (progress < 100) {
                $('#workflowProgressText').text('完成中...');
            } else {
                $('#workflowProgressText').text('执行完成');
            }
        }

        // 更新队列显示
        function updateQueueDisplay() {
            const list = $('#queueList');
            list.empty();
            
            if (queue.length === 0) {
                list.html('<div class="queue-item" style="color: var(--comfy-text-muted);">队列为空</div>');
                return;
            }
            
            queue.slice(-5).forEach(item => {
                const statusText = {
                    'pending': '等待中',
                    'running': '执行中',
                    'completed': '已完成',
                    'error': '出错'
                }[item.status] || item.status;
                
                list.append(`
                    <div class="queue-item">
                        <div class="queue-status ${item.status}"></div>
                        <span style="flex: 1;">任务 #${item.id.toString().slice(-4)} - ${statusText}</span>
                        <span style="color: var(--comfy-text-muted);">${item.time}</span>
                    </div>
                `);
            });
        }

        // 切换队列面板
        function toggleQueuePanel() {
            $('#queuePanel').toggle();
        }

        // 保存工作流
        function saveWorkflow() {
            const data = {
                nodes: nodes,
                links: links
            };
            
            $.ajax({
                url: 'api/workflow_api.php?action=saveWorkflow',
                type: 'POST',
                data: JSON.stringify({
                    name: '我的工作流',
                    nodes: data.nodes,
                    connections: data.links
                }),
                contentType: 'application/json',
                success: function(res) {
                    if (res.status === 'success') {
                        showToast('工作流已保存');
                    } else {
                        showToast('保存失败: ' + res.message, 'error');
                    }
                },
                error: function() {
                    // 保存到本地存储
                    localStorage.setItem('comfy_workflow', JSON.stringify(data));
                    showToast('工作流已保存到本地');
                }
            });
        }

        // 加载工作流列表
        function loadWorkflowList() {
            $.ajax({
                url: 'api/workflow_api.php?action=getWorkflows',
                type: 'GET',
                success: function(res) {
                    if (res.status === 'success') {
                        renderWorkflowList(res.workflows);
                    }
                },
                error: function() {
                    // 显示本地保存的工作流
                    const saved = localStorage.getItem('comfy_workflow');
                    if (saved) {
                        renderWorkflowList([{
                            id: 'local',
                            name: '本地工作流',
                            description: '存储在浏览器本地',
                            node_count: JSON.parse(saved).nodes.length
                        }]);
                    }
                }
            });
        }

        // 渲染工作流列表
        function renderWorkflowList(workflows) {
            const list = $('#workflowList');
            list.empty();
            
            workflows.forEach(wf => {
                list.append(`
                    <div class="node-item" onclick="loadWorkflowData(${wf.id})">
                        <i class="fas fa-project-diagram"></i>
                        <div style="flex: 1;">
                            <div>${wf.name}</div>
                            <div style="font-size: 11px; color: var(--comfy-text-muted);">
                                ${wf.node_count || 0} 节点
                            </div>
                        </div>
                    </div>
                `);
            });
        }

        // 加载工作流数据
        function loadWorkflowData(id) {
            if (id === 'local') {
                const saved = localStorage.getItem('comfy_workflow');
                if (saved) {
                    const data = JSON.parse(saved);
                    restoreWorkflow(data);
                }
                return;
            }
            
            $.ajax({
                url: `api/workflow_api.php?action=getWorkflow&id=${id}`,
                type: 'GET',
                success: function(res) {
                    if (res.status === 'success') {
                        restoreWorkflow({
                            nodes: res.nodes || [],
                            links: res.connections || []
                        });
                    }
                }
            });
        }

        // 恢复工作流
        function restoreWorkflow(data) {
            clearWorkflow();
            
            // 恢复节点
            data.nodes.forEach(node => {
                nodes.push(node);
                renderNode(node);
                nodeIdCounter = Math.max(nodeIdCounter, node.id + 1);
            });
            
            // 恢复连接
            if (data.links) {
                data.links.forEach(link => {
                    links.push(link);
                    linkIdCounter = Math.max(linkIdCounter, link.id + 1);
                });
                updateConnections();
            }
            
            showToast('工作流已加载');
        }

        // 加载工作流按钮
        function loadWorkflow() {
            switchTab('workflows');
            showToast('请在侧边栏选择工作流');
        }

        // 切换标签页
        function switchTab(tab) {
            $('.sidebar-tab').removeClass('active');
            $(`.sidebar-tab:contains('${tab === 'nodes' ? '节点' : '工作流'}')`).addClass('active');
            
            if (tab === 'nodes') {
                $('#nodesTab').removeClass('hidden');
                $('#workflowsTab').addClass('hidden');
            } else {
                $('#nodesTab').addClass('hidden');
                $('#workflowsTab').removeClass('hidden');
                loadWorkflowList();
            }
        }

        // Toast 提示
        function showToast(message, type = 'info') {
            const colors = {
                info: '#4299e1',
                success: '#48bb78',
                warning: '#ecc94b',
                error: '#f56565'
            };
            
            const toast = $(`
                <div class="comfy-toast" style="border-left: 3px solid ${colors[type]}">
                    <i class="fas fa-${type === 'error' ? 'exclamation-circle' : type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                    <span>${message}</span>
                </div>
            `);
            
            $('#toastContainer').append(toast);
            setTimeout(() => toast.fadeOut(300, function() { $(this).remove(); }), 3000);
        }

        // 键盘快捷键
        $(document).on('keydown', function(e) {
            if (e.key === 'Delete' && selectedNode) {
                deleteNode(selectedNode);
            }
            if (e.key === 'Escape') {
                if (connecting) {
                    cancelConnection();
                } else {
                    deselectAll();
                }
            }
        });

        // ==================== 多模态模型选择功能 ====================
        
        // 多模态模型定义
        const multimodalModelDefs = {
            'ollama': [
                {id: 'llava', name: 'LLaVA', desc: '图像理解', supports: ['image', 'text']},
                {id: 'llava-phi3', name: 'LLaVA-Phi3', desc: '图像理解轻量版', supports: ['image', 'text']},
                {id: 'bakllava', name: 'BakLLaVA', desc: '增强图像理解', supports: ['image', 'text']},
                {id: 'moondream', name: 'Moondream', desc: '轻量图像模型', supports: ['image', 'text']}
            ],
            'openai': [
                {id: 'gpt-4o', name: 'GPT-4o', desc: '全能多模态', supports: ['image', 'text', 'file']},
                {id: 'gpt-4o-mini', name: 'GPT-4o Mini', desc: '轻量多模态', supports: ['image', 'text', 'file']},
                {id: 'gpt-4-vision-preview', name: 'GPT-4 Vision', desc: '视觉增强', supports: ['image', 'text']}
            ],
            'qwen': [
                {id: 'qwen-vl-plus', name: '通义千问VL', desc: '视觉语言模型', supports: ['image', 'text', 'video']},
                {id: 'qwen-vl-max', name: '通义千问VL Max', desc: '增强视觉语言', supports: ['image', 'text', 'video']}
            ],
            'gemini': [
                {id: 'gemini-1.5-pro', name: 'Gemini 1.5 Pro', desc: '多模态Pro', supports: ['image', 'text', 'video', 'file']},
                {id: 'gemini-1.5-flash', name: 'Gemini 1.5 Flash', desc: '快速多模态', supports: ['image', 'text', 'video', 'file']}
            ],
            'hunyuan': [
                {id: 'hunyuan-vision', name: '混元Vision', desc: '腾讯视觉模型', supports: ['image', 'text']}
            ],
            'anthropic': [
                {id: 'claude-3-opus', name: 'Claude 3 Opus', desc: '最强多模态', supports: ['image', 'text', 'file']},
                {id: 'claude-3-sonnet', name: 'Claude 3 Sonnet', desc: '平衡多模态', supports: ['image', 'text', 'file']}
            ]
        };
        
        // 模型能力映射
        const modelCapabilities = {
            'llava': ['image', 'text'],
            'llava-phi3': ['image', 'text'],
            'bakllava': ['image', 'text'],
            'moondream': ['image', 'text'],
            'gpt-4o': ['image', 'text', 'file'],
            'gpt-4o-mini': ['image', 'text', 'file'],
            'gpt-4-vision-preview': ['image', 'text'],
            'qwen-vl-plus': ['image', 'text', 'video'],
            'qwen-vl-max': ['image', 'text', 'video'],
            'qwen-omni-turbo': ['image', 'text', 'video', 'audio'],
            'gemini-1.5-pro': ['image', 'text', 'video', 'file'],
            'gemini-1.5-flash': ['image', 'text', 'video', 'file'],
            'hunyuan-vision': ['image', 'text'],
            'claude-3-opus': ['image', 'text', 'file'],
            'claude-3-sonnet': ['image', 'text', 'file'],
            'claude-3-haiku': ['image', 'text', 'file'],
            'glm-4v': ['image', 'text']
        };
        
        // 获取模型能力
        function getModelCapabilities(modelName) {
            if (!modelName) return ['text'];
            const lowerName = modelName.toLowerCase();
            
            if (modelCapabilities[lowerName]) {
                return modelCapabilities[lowerName];
            }
            
            for (const [key, caps] of Object.entries(modelCapabilities)) {
                if (lowerName.includes(key) || key.includes(lowerName)) {
                    return caps;
                }
            }
            
            if (lowerName.includes('vision') || lowerName.includes('vl') || lowerName.includes('v')) {
                return ['image', 'text'];
            }
            if (lowerName.includes('omni')) {
                return ['image', 'text', 'video', 'audio'];
            }
            
            return ['text'];
        }
        
        // 存储配置信息
        let configuredProviders = [];
        let currentProviderId = '';
        let currentProviderType = '';
        let currentModel = '';

        // 加载提供商列表
        async function loadProviders() {
            const select = document.getElementById('providerSelect');
            const modelSelect = document.getElementById('modelSelect');
            select.innerHTML = '<option value="">加载...</option>';
            modelSelect.innerHTML = '<option value="">选择模型...</option>';

            try {
                const response = await fetch('api/providers_handler.php?action=get_providers&enabled=1');
                const data = await response.json();

                if (data.success && data.data) {
                    select.innerHTML = '<option value="">选择提供商...</option>';
                    configuredProviders = data.data.filter(p => p.enabled && p.models && p.models.length > 0);
                    
                    // 填充提供商下拉菜单
                    configuredProviders.forEach(provider => {
                        const option = document.createElement('option');
                        option.value = provider.id;
                        option.textContent = provider.name;
                        option.dataset.type = provider.type;
                        option.dataset.models = JSON.stringify(provider.models);
                        select.appendChild(option);
                    });
                    
                    if (configuredProviders.length === 0) {
                        showToast('未配置AI提供商，请先配置API密钥');
                    }
                }
            } catch (error) {
                console.error('加载提供商失败:', error);
                select.innerHTML = '<option value="">加载失败</option>';
            }
        }

        // 提供商切换时更新模型列表
        function updateModelSelect() {
            const providerSelect = document.getElementById('providerSelect');
            const modelSelect = document.getElementById('modelSelect');
            const providerId = providerSelect.value;
            const selectedOption = providerSelect.options[providerSelect.selectedIndex];
            
            currentProviderId = providerId;
            currentProviderType = selectedOption.dataset.type || '';
            currentModel = '';

            if (!providerId || !selectedOption.dataset.models) {
                modelSelect.innerHTML = '<option value="">选择模型...</option>';
                return;
            }

            const models = JSON.parse(selectedOption.dataset.models);
            modelSelect.innerHTML = '<option value="">选择模型...</option>';

            // 只显示多模态模型，使用真实模型名称
            models.forEach(modelName => {
                const caps = getModelCapabilities(modelName);
                const isMultimodal = caps.includes('image') || caps.includes('video') || caps.includes('file');
                
                if (isMultimodal) {
                    const option = document.createElement('option');
                    option.value = modelName;
                    option.textContent = modelName; // 使用真实模型名称
                    option.title = `支持: ${caps.join(', ')}`;
                    option.dataset.supports = JSON.stringify(caps);
                    modelSelect.appendChild(option);
                }
            });

            // 自动选择第一个模型
            if (modelSelect.options.length > 1) {
                modelSelect.selectedIndex = 1;
                currentModel = modelSelect.value;
            }
        }

        // 刷新模型列表
        function refreshModels() {
            loadProviders();
            showToast('正在刷新模型列表...');
        }

        // 监听提供商选择
        document.getElementById('providerSelect').addEventListener('change', updateModelSelect);

        document.getElementById('modelSelect').addEventListener('change', function() {
            currentModel = this.value;
        });

        // 初始化时加载提供商
        loadProviders();
    </script>
</body>
</html>
