<?php
/**
 * 我的智能体 - 采用场景演示中心风格
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
    <title>我的智能体 - 巨神兵API辅助平台API辅助平台</title>
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
            color: #1a202c;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
        }

        /* 头部 */
        .header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 24px 30px;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 28px;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .header p {
            color: #64748b;
            font-size: 16px;
        }

        /* 控制面板 */
        .control-panel {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
            justify-content: space-between;
        }

        .control-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .control-group label {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
        }

        .stats-row {
            display: flex;
            gap: 30px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #667eea;
        }

        .stat-label {
            font-size: 13px;
            color: #6b7280;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #4b5563;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
        }

        /* 智能体网格 */
        .agents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
            align-items: stretch;
            justify-content: center;
        }

        .agent-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .agent-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.12);
        }

        .agent-icon {
            height: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 56px;
            color: white;
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
        }

        .agent-icon::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0,0,0,0.2) 0%, rgba(0,0,0,0) 100%);
        }

        .agent-icon.purple { background: #4c51bf; }
        .agent-icon.pink { background: #e53e3e; }
        .agent-icon.blue { background: #3182ce; }
        .agent-icon.green { background: #38a169; }
        .agent-icon.orange { background: #d69e2e; }
        .agent-icon.teal { background: #319795; }
        .agent-icon.cyan { background: #805ad5; }
        .agent-icon.rose { background: #dd6b20; }

        .agent-content {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .agent-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 10px;
            width: fit-content;
        }

        .agent-status-badge.active {
            background: #dcfce7;
            color: #166534;
        }

        .agent-status-badge.draft {
            background: #f3f4f6;
            color: #6b7280;
        }

        .agent-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #1a202c;
            line-height: 1.3;
        }

        .agent-desc {
            font-size: 13px;
            color: #64748b;
            line-height: 1.5;
            margin-bottom: 12px;
            flex: 1;
        }

        .agent-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
        }

        .agent-stat {
            font-size: 12px;
            color: #6b7280;
        }

        .agent-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .agent-actions .btn {
            flex: 1;
            justify-content: center;
        }

        /* 空状态 */
        .empty-state {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 80px 20px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .empty-state i {
            font-size: 80px;
            color: #e5e7eb;
            margin-bottom: 24px;
        }

        .empty-state h3 {
            font-size: 20px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 12px;
        }

        .empty-state p {
            color: #6b7280;
            margin-bottom: 24px;
        }

        /* 加载动画 */
        .loading-container {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 12px;
        }

        .loading-spinner {
            width: 48px;
            height: 48px;
            border: 4px solid #f3f4f6;
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* 提示信息 */
        .toast {
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%);
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            z-index: 1000;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateX(-50%) translateY(-20px); }
            to { opacity: 1; transform: translateX(-50%) translateY(0); }
        }

        .toast.success {
            background: #10b981;
            color: white;
        }

        .toast.error {
            background: #ef4444;
            color: white;
        }

        /* 删除确认弹窗 */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background: white;
            border-radius: 16px;
            padding: 32px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            transform: scale(0.9);
            transition: all 0.3s;
        }

        .modal-overlay.active .modal {
            transform: scale(1);
        }

        .modal-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #fee2e2;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 28px;
            color: #ef4444;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 12px;
        }

        .modal-desc {
            color: #6b7280;
            margin-bottom: 24px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
        }

        .modal-actions .btn {
            flex: 1;
        }

        /* 移动端响应式设计 */
        @media screen and (max-width: 768px) {
            body {
                padding: 10px;
            }

            .header {
                flex-direction: column;
                gap: 12px;
                padding: 16px;
                margin-bottom: 20px;
            }

            .header h1 {
                font-size: 20px;
            }

            .agents-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .agent-card {
                padding: 20px;
            }

            .agent-icon {
                width: 56px;
                height: 56px;
                font-size: 24px;
            }

            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .stat-card {
                padding: 16px;
            }

            .stat-value {
                font-size: 24px;
            }

            .control-panel {
                padding: 16px;
            }

            /* 模态框移动端适配 */
            .modal-content {
                width: 95%;
                margin: 20px auto;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .color-options {
                flex-wrap: wrap;
            }
        }

        @media screen and (max-width: 480px) {
            .header h1 {
                font-size: 18px;
            }

            .btn {
                padding: 8px 14px;
                font-size: 13px;
            }

            .stats-cards {
                grid-template-columns: 1fr 1fr;
            }

            .stat-label {
                font-size: 12px;
            }

            .agent-card h3 {
                font-size: 16px;
            }

            .agent-meta {
                flex-direction: column;
                gap: 4px;
            }

            .modal-header {
                padding: 16px 20px;
            }

            .modal-body {
                padding: 20px;
            }

            .modal-title {
                font-size: 16px;
            }

            .form-group input,
            .form-group textarea,
            .form-group select {
                font-size: 16px; /* 防止iOS缩放 */
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 返回首页按钮 -->
        <a href="?route=home" class="back-btn" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; background: rgba(255,255,255,0.95); color: #667eea; text-decoration: none; border-radius: 10px; font-weight: 600; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: all 0.2s;">
            <i class="fas fa-arrow-left"></i> 返回首页
        </a>
        
        <!-- 头部 -->
        <div class="header">
            <h1><i class="fas fa-robot"></i> 我的智能体</h1>
            <p>创建和管理你的AI智能体，让它们帮你完成各种任务</p>
        </div>

        <!-- 控制面板 -->
        <div class="control-panel">
            <div class="stats-row">
                <div class="stat-item">
                    <div class="stat-value" id="totalAgents">0</div>
                    <div class="stat-label">智能体总数</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="activeAgents">0</div>
                    <div class="stat-label">已部署</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="totalUsage">0</div>
                    <div class="stat-label">总使用次数</div>
                </div>
            </div>
            <div style="display: flex; gap: 12px; align-items: center;">
                <!-- 模型选择 -->
                <div style="display: flex; gap: 8px; align-items: center;">
                    <select id="providerSelect" class="model-select" style="padding: 10px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 14px; min-width: 140px;">
                        <option value="">加载提供商...</option>
                    </select>
                    <select id="modelSelect" class="model-select" style="padding: 10px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 14px; min-width: 160px;">
                        <option value="">选择模型...</option>
                    </select>
                    <button onclick="refreshModels()" class="btn btn-secondary btn-sm" title="刷新模型列表">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <a href="?route=agent_editor" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    创建智能体
                </a>
            </div>
        </div>

        <!-- 智能体列表 -->
        <div id="agentsContainer">
            <div class="loading-container">
                <div class="loading-spinner"></div>
                <p style="color: #6b7280;">加载中...</p>
            </div>
        </div>
    </div>

    <!-- 删除确认弹窗 -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <div class="modal-icon">
                <i class="fas fa-trash-alt"></i>
            </div>
            <h3 class="modal-title">确认删除?</h3>
            <p class="modal-desc">删除后将无法恢复，相关的部署链接也将失效。</p>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">取消</button>
                <button class="btn btn-danger" onclick="confirmDelete()">删除</button>
            </div>
        </div>
    </div>

    <!-- 嵌入代码弹窗 -->
    <div class="modal-overlay" id="embedModal">
        <div class="modal" style="max-width: 600px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 24px; border-radius: 16px 16px 0 0;">
                <h3 style="margin: 0; font-size: 18px;"><i class="fas fa-code"></i> 嵌入到网站</h3>
                <button onclick="closeEmbedModal()" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; position: absolute; right: 20px; top: 18px;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 24px;">
                <p style="color: #6b7280; margin-bottom: 20px;">将以下代码复制到你的网站HTML中，即可嵌入智能体聊天窗口。</p>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #374151;">方式一：JavaScript SDK（推荐）</label>
                    <div style="position: relative;">
                        <textarea id="embedSdkCode" readonly style="width: 100%; height: 120px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-family: monospace; font-size: 13px; resize: none; background: #f9fafb;"></textarea>
                        <button onclick="copyEmbedCode('embedSdkCode')" class="btn btn-secondary btn-sm" style="position: absolute; right: 8px; top: 8px;">
                            <i class="fas fa-copy"></i> 复制
                        </button>
                    </div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #374151;">方式二：iframe嵌入</label>
                    <div style="position: relative;">
                        <textarea id="embedIframeCode" readonly style="width: 100%; height: 100px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-family: monospace; font-size: 13px; resize: none; background: #f9fafb;"></textarea>
                        <button onclick="copyEmbedCode('embedIframeCode')" class="btn btn-secondary btn-sm" style="position: absolute; right: 8px; top: 8px;">
                            <i class="fas fa-copy"></i> 复制
                        </button>
                    </div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #374151;">方式三：REST API调用</label>
                    <div style="position: relative;">
                        <textarea id="embedApiCode" readonly style="width: 100%; height: 180px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-family: monospace; font-size: 13px; resize: none; background: #f9fafb;"></textarea>
                        <button onclick="copyEmbedCode('embedApiCode')" class="btn btn-secondary btn-sm" style="position: absolute; right: 8px; top: 8px;">
                            <i class="fas fa-copy"></i> 复制
                        </button>
                    </div>
                </div>
                
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 16px;">
                    <p style="margin: 0; color: #166534; font-size: 14px;">
                        <i class="fas fa-info-circle"></i> 
                        <strong>提示：</strong>SDK方式会自动在页面右下角显示聊天按钮，iframe方式可以自定义位置和大小。
                    </p>
                </div>
            </div>
            <div class="modal-actions" style="padding: 0 24px 24px;">
                <button class="btn btn-secondary" onclick="closeEmbedModal()" style="flex: 1;">关闭</button>
                <a id="previewEmbedLink" href="#" target="_blank" class="btn btn-primary" style="flex: 1; text-align: center;">
                    <i class="fas fa-eye"></i> 预览效果
                </a>
            </div>
        </div>
    </div>

    <script>
        let agents = [];
        let deleteAgentId = null;

        const iconColors = ['purple', 'pink', 'blue', 'green', 'orange', 'teal', 'cyan', 'rose'];

        // 加载智能体列表
        async function loadAgents() {
            try {
                const response = await fetch('api/agent_handler.php?action=getMyAgents');
                const data = await response.json();

                if (data.success) {
                    agents = data.agents || [];
                    renderAgents();
                    updateStats(agents);
                } else {
                    showToast(data.error || '加载失败', 'error');
                }
            } catch (error) {
                showToast('加载失败: ' + error.message, 'error');
            }
        }

        // 渲染智能体列表
        function renderAgents() {
            const container = document.getElementById('agentsContainer');

            if (agents.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-robot"></i>
                        <h3>还没有创建智能体</h3>
                        <p>创建你的第一个AI智能体，让它帮你完成各种任务</p>
                        <a href="?route=agent_editor" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            立即创建
                        </a>
                    </div>
                `;
                return;
            }

            const html = agents.map((agent, index) => {
                const colorClass = iconColors[index % iconColors.length];
                return `
                <div class="agent-card" onclick="location.href='?route=agent_editor&id=${agent.id}'">
                    <div class="agent-icon ${colorClass}">
                        <i class="fas ${agent.icon || 'fa-robot'}"></i>
                    </div>
                    <div class="agent-content">
                        <span class="agent-status-badge ${agent.status}">
                            <i class="fas ${agent.status === 'active' ? 'fa-check-circle' : agent.status === 'draft' ? 'fa-pencil-alt' : 'fa-pause-circle'}"></i>
                            ${agent.status === 'active' ? '已部署' : agent.status === 'draft' ? '草稿' : '已停用'}
                        </span>
                        <h3 class="agent-title">${escapeHtml(agent.name)}</h3>
                        <p class="agent-desc">${escapeHtml(agent.description || '暂无描述')}</p>
                        <div class="agent-stats">
                            <span class="agent-stat"><i class="fas fa-comments"></i> ${agent.usage_count || 0} 次对话</span>
                            <span class="agent-stat"><i class="fas fa-clock"></i> ${formatDate(agent.updated_at)}</span>
                        </div>
                        <div class="agent-actions" onclick="event.stopPropagation()">
                            <a href="?route=agent_editor&id=${agent.id}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-edit"></i> 编辑
                            </a>
                            ${agent.status === 'active' ? `
                                <a href="?route=agent_chat&token=${agent.deploy_token}" target="_blank" class="btn btn-success btn-sm">
                                    <i class="fas fa-external-link-alt"></i> 打开
                                </a>
                                <button class="btn btn-primary btn-sm" onclick="showEmbedModal('${agent.deploy_token}', '${escapeHtml(agent.name)}')">
                                    <i class="fas fa-code"></i> 嵌入
                                </button>
                            ` : `
                                <button class="btn btn-primary btn-sm" onclick="quickDeploy(${agent.id})">
                                    <i class="fas fa-rocket"></i> 部署
                                </button>
                            `}
                            <button class="btn btn-danger btn-sm" onclick="showDeleteModal(${agent.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                `;
            }).join('');

            container.innerHTML = `<div class="agents-grid">${html}</div>`;
        }

        // 更新统计
        function updateStats(agents) {
            document.getElementById('totalAgents').textContent = agents.length;
            document.getElementById('activeAgents').textContent = agents.filter(a => a.status === 'active').length;
            document.getElementById('totalUsage').textContent = agents.reduce((sum, a) => sum + (a.usage_count || 0), 0);
        }

        // 快速部署
        async function quickDeploy(agentId) {
            const formData = new FormData();
            formData.append('action', 'deployAgent');
            formData.append('agent_id', agentId);

            try {
                const response = await fetch('api/agent_handler.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    showToast('部署成功', 'success');
                    loadAgents();
                } else {
                    showToast(result.error || '部署失败', 'error');
                }
            } catch (error) {
                showToast('部署失败: ' + error.message, 'error');
            }
        }

        // 显示删除弹窗
        function showDeleteModal(id) {
            deleteAgentId = id;
            document.getElementById('deleteModal').classList.add('active');
        }

        // 关闭删除弹窗
        function closeDeleteModal() {
            deleteAgentId = null;
            document.getElementById('deleteModal').classList.remove('active');
        }

        // 确认删除
        async function confirmDelete() {
            if (!deleteAgentId) return;

            const formData = new FormData();
            formData.append('action', 'deleteAgent');
            formData.append('agent_id', deleteAgentId);

            try {
                const response = await fetch('api/agent_handler.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    showToast('删除成功', 'success');
                    closeDeleteModal();
                    loadAgents();
                } else {
                    showToast(result.error || '删除失败', 'error');
                }
            } catch (error) {
                showToast('删除失败: ' + error.message, 'error');
            }
        }

        // 显示嵌入代码弹窗
        function showEmbedModal(token, agentName) {
            const baseUrl = window.location.origin + '/gpustack_platform';
            const apiUrl = baseUrl + '/api/agent_api.php';
            const chatUrl = baseUrl + '/?route=agent_chat&token=' + token;
            
            // SDK代码
            const sdkCode = `<!-- 在<head>中添加Font Awesome（如已有可省略） -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- 在<body>结束标签前添加以下代码 -->
<script src="${baseUrl}/api/agent_sdk.js?token=${token}"><\/script>
<script>
  // 可选：自定义配置
  window.AgentSDKConfig = {
    position: 'bottom-right',  // 位置: bottom-right, bottom-left, top-right, top-left
    theme: 'light',            // 主题: light, dark
    title: '${agentName}',     // 窗口标题
    placeholder: '输入消息...'  // 输入框提示
  };
<\/script>`;
            
            // iframe代码
            const iframeCode = `<!-- 自定义iframe嵌入 -->
<iframe 
  src="${chatUrl}" 
  width="100%" 
  height="600" 
  frameborder="0"
  style="border: 1px solid #e5e7eb; border-radius: 12px;"
><\/iframe>`;
            
            // API代码
            const apiCode = `// 使用JavaScript调用智能体API
async function chatWithAgent(message) {
  const response = await fetch('${apiUrl}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: new URLSearchParams({
      token: '${token}',
      action: 'chat',
      message: message,
      session_id: 'your_session_id'  // 可选，用于保持对话上下文
    })
  });
  
  const data = await response.json();
  if (data.success) {
    console.log('AI回复:', data.response);
    return data.response;
  } else {
    console.error('错误:', data.error);
  }
}

// 获取智能体信息
async function getAgentInfo() {
  const response = await fetch('${apiUrl}?token=${token}&action=info');
  const data = await response.json();
  console.log('智能体信息:', data.agent);
}`;
            
            document.getElementById('embedSdkCode').value = sdkCode;
            document.getElementById('embedIframeCode').value = iframeCode;
            document.getElementById('embedApiCode').value = apiCode;
            document.getElementById('previewEmbedLink').href = chatUrl;
            document.getElementById('embedModal').classList.add('active');
        }
        
        // 关闭嵌入代码弹窗
        function closeEmbedModal() {
            document.getElementById('embedModal').classList.remove('active');
        }
        
        // 复制嵌入代码
        function copyEmbedCode(textareaId) {
            const textarea = document.getElementById(textareaId);
            textarea.select();
            document.execCommand('copy');
            showToast('代码已复制到剪贴板', 'success');
        }

        // 格式化日期
        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            const now = new Date();
            const diff = now - date;
            
            if (diff < 60000) return '刚刚';
            if (diff < 3600000) return Math.floor(diff / 60000) + '分钟前';
            if (diff < 86400000) return Math.floor(diff / 3600000) + '小时前';
            if (diff < 604800000) return Math.floor(diff / 86400000) + '天前';
            
            return date.toLocaleDateString('zh-CN');
        }

        // HTML转义
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // 显示提示
        function showToast(message, type) {
            const existing = document.querySelector('.toast');
            if (existing) existing.remove();

            const toast = document.createElement('div');
            toast.className = 'toast ' + type;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => toast.remove(), 3000);
        }

        // 实际配置的提供商和模型（从服务器获取）
        let configuredProviders = [];
        let currentProvider = '';
        let currentModel = '';

        // 加载提供商列表 - 直接使用系统配置的模型
        async function loadProviders() {
            const select = document.getElementById('providerSelect');
            const modelSelect = document.getElementById('modelSelect');
            select.innerHTML = '<option value="">加载提供商...</option>';
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
                        option.dataset.models = JSON.stringify(provider.models);
                        option.dataset.type = provider.type;
                        select.appendChild(option);
                    });
                    
                    // 如果没有配置任何提供商
                    if (configuredProviders.length === 0) {
                        select.innerHTML = '<option value="">未配置AI提供商</option>';
                        showToast('未配置AI提供商，请先前往管理后台配置', 'error');
                    }
                }
            } catch (error) {
                console.error('加载提供商失败:', error);
                select.innerHTML = '<option value="">加载失败</option>';
            }
        }

        // 提供商切换时更新模型列表
        function updateModelSelect() {
            const providerId = document.getElementById('providerSelect').value;
            const modelSelect = document.getElementById('modelSelect');
            currentProvider = providerId;

            if (!providerId) {
                modelSelect.innerHTML = '<option value="">选择模型...</option>';
                return;
            }

            // 找到选中的提供商
            const selectedProvider = configuredProviders.find(p => p.id === providerId);
            modelSelect.innerHTML = '<option value="">选择模型...</option>';

            if (selectedProvider && selectedProvider.models) {
                selectedProvider.models.forEach(modelName => {
                    const option = document.createElement('option');
                    option.value = modelName;
                    option.textContent = modelName;
                    modelSelect.appendChild(option);
                });

                // 自动选择第一个模型
                if (selectedProvider.models.length > 0) {
                    modelSelect.value = selectedProvider.models[0];
                    currentModel = selectedProvider.models[0];
                }
            }
        }

        // 刷新模型列表
        function refreshModels() {
            loadProviders();
            showToast('正在刷新模型列表...', 'success');
        }

        // 监听提供商选择
        document.getElementById('providerSelect').addEventListener('change', updateModelSelect);

        document.getElementById('modelSelect').addEventListener('change', function() {
            currentModel = this.value;
        });

        // 初始化
        loadProviders();
        loadAgents();
    </script>
</body>
</html>
