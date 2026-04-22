<?php
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
    <title>🦞 养龙虾 - AI智能体训练系统</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo-icon {
            font-size: 32px;
        }
        
        .logo-text h1 {
            font-size: 20px;
            color: #1a202c;
        }
        
        .logo-text p {
            font-size: 12px;
            color: #64748b;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: white;
            color: #4c51bf;
            border: 1px solid #e2e8f0;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        /* 统计卡片 */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #1a202c;
        }
        
        .stat-label {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }
        
        /* 主布局 */
        .main-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 24px;
        }
        
        @media (max-width: 1024px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* 智能体列表 */
        .agent-list {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .agent-list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .agent-list-header h3 {
            font-size: 16px;
            color: #1a202c;
        }
        
        .agent-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
        }
        
        .agent-card:hover {
            border-color: #667eea;
            transform: translateX(4px);
        }
        
        .agent-card.active {
            border-color: #667eea;
            background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
        }
        
        .agent-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .agent-avatar {
            font-size: 32px;
        }
        
        .agent-name {
            font-weight: 600;
            color: #1a202c;
        }
        
        .agent-level {
            font-size: 11px;
            color: #64748b;
        }
        
        .agent-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            font-size: 11px;
            color: #64748b;
        }
        
        .agent-stat {
            display: flex;
            justify-content: space-between;
        }
        
        .agent-stat span {
            color: #4c51bf;
            font-weight: 500;
        }
        
        /* 内容区 */
        .content-area {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            min-height: 600px;
        }
        
        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
        }
        
        .tab {
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #64748b;
            transition: all 0.2s;
        }
        
        .tab:hover {
            color: #4c51bf;
        }
        
        .tab.active {
            background: #4c51bf;
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* 智能体详情 */
        .agent-detail-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 24px;
            padding: 20px;
            background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
            border-radius: 12px;
        }
        
        .agent-detail-avatar {
            font-size: 64px;
        }
        
        .agent-detail-info h2 {
            font-size: 24px;
            color: #1a202c;
            margin-bottom: 4px;
        }
        
        .agent-detail-info p {
            color: #64748b;
            font-size: 14px;
        }
        
        .level-badge {
            display: inline-block;
            background: #4c51bf;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .experience-bar {
            margin-top: 12px;
            background: #e2e8f0;
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
        }
        
        .experience-fill {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s;
        }
        
        /* 属性面板 */
        .attribute-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .attribute-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
        }
        
        .attribute-icon {
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        .attribute-value {
            font-size: 20px;
            font-weight: 700;
            color: #4c51bf;
        }
        
        .attribute-label {
            font-size: 11px;
            color: #64748b;
            margin-top: 4px;
        }
        
        /* 技能卡片 */
        .skills-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
        }
        
        .skill-card {
            background: #f8fafc;
            border-radius: 8px;
            padding: 12px;
            border-left: 3px solid #667eea;
        }
        
        .skill-name {
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 8px;
        }
        
        .skill-proficiency {
            font-size: 12px;
            color: #64748b;
        }
        
        .skill-progress {
            background: #e2e8f0;
            height: 4px;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .skill-progress-fill {
            background: #667eea;
            height: 100%;
            border-radius: 2px;
        }
        
        /* 喂养区 */
        .feed-area {
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .feed-area h4 {
            color: #166534;
            margin-bottom: 12px;
        }
        
        .feed-form {
            display: flex;
            gap: 12px;
        }
        
        .feed-input {
            flex: 1;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .feed-input:focus {
            outline: none;
            border-color: #4c51bf;
        }
        
        /* 喂养记录 */
        .feeding-history {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .feeding-item {
            display: flex;
            justify-content: space-between;
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .feeding-item:hover {
            background: #f8fafc;
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
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #64748b;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
        }
        
        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-input:focus, .form-textarea:focus, .form-select:focus {
            outline: none;
            border-color: #4c51bf;
        }
        
        .hidden { display: none !important; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <div class="logo-icon">🦞</div>
                <div class="logo-text">
                    <h1>养龙虾</h1>
                    <p>AI智能体训练系统 - 部署·训练·优化</p>
                </div>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="../index.php?route=home" class="btn btn-secondary">
                    <i class="fas fa-home"></i> 返回首页
                </a>
                <a href="../index.php?route=admin" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> 返回后台
                </a>
            </div>
        </div>
        
        <!-- 统计 -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🦞</div>
                <div class="stat-value" id="statAgents">0</div>
                <div class="stat-label">我的龙虾</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🍖</div>
                <div class="stat-value" id="statFeeds">0</div>
                <div class="stat-label">喂养次数</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-value" id="statTasks">0</div>
                <div class="stat-label">完成任务</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💎</div>
                <div class="stat-value" id="statTokens">0</div>
                <div class="stat-label">消耗Tokens</div>
            </div>
        </div>
        
        <div class="main-grid">
            <!-- 智能体列表 -->
            <div class="agent-list">
                <div class="agent-list-header">
                    <h3>我的龙虾</h3>
                    <button class="btn btn-primary btn-sm" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i> 孵化
                    </button>
                </div>
                <div id="agentList"></div>
            </div>
            
            <!-- 内容区 -->
            <div class="content-area">
                <div id="noAgentSelected" style="text-align: center; padding: 100px 20px; color: #64748b;">
                    <div style="font-size: 64px; margin-bottom: 16px;">🦞</div>
                    <p>选择一只龙虾开始培养，或孵化新的智能体</p>
                </div>
                
                <div id="agentContent" class="hidden">
                    <!-- 标签页 -->
                    <div class="tabs">
                        <div class="tab active" data-tab="overview">概览</div>
                        <div class="tab" data-tab="feed">喂养</div>
                        <div class="tab" data-tab="skills">技能</div>
                        <div class="tab" data-tab="memory">记忆</div>
                        <div class="tab" data-tab="tasks">任务</div>
                        <div class="tab" data-tab="settings">设置</div>
                    </div>
                    
                    <!-- 概览 -->
                    <div id="tab-overview" class="tab-content active">
                        <div class="agent-detail-header">
                            <div class="agent-detail-avatar" id="detailAvatar">🦞</div>
                            <div class="agent-detail-info">
                                <h2><span id="detailName">小虾米</span> <span class="level-badge" id="detailLevel">Lv.1</span></h2>
                                <p id="detailPersonality">友好、乐于助人、聪明</p>
                                <div class="experience-bar">
                                    <div class="experience-fill" id="detailExpBar" style="width: 0%"></div>
                                </div>
                                <p style="font-size: 12px; color: #64748b; margin-top: 4px;">
                                    经验值: <span id="detailExp">0</span>/100
                                </p>
                            </div>
                        </div>
                        
                        <div class="attribute-grid">
                            <div class="attribute-card">
                                <div class="attribute-icon">🧠</div>
                                <div class="attribute-value" id="attrIntelligence">50</div>
                                <div class="attribute-label">智力</div>
                            </div>
                            <div class="attribute-card">
                                <div class="attribute-icon">🎯</div>
                                <div class="attribute-value" id="attrAutonomy">50</div>
                                <div class="attribute-label">自主性</div>
                            </div>
                            <div class="attribute-card">
                                <div class="attribute-icon">📊</div>
                                <div class="attribute-value" id="attrSuccessRate">0%</div>
                                <div class="attribute-label">成功率</div>
                            </div>
                            <div class="attribute-card">
                                <div class="attribute-icon">⚡</div>
                                <div class="attribute-value" id="attrTotalTasks">0</div>
                                <div class="attribute-label">总任务</div>
                            </div>
                        </div>
                        
                        <h4 style="margin-bottom: 12px;">已学会的技能</h4>
                        <div class="skills-grid" id="agentSkills"></div>
                    </div>
                    
                    <!-- 喂养 -->
                    <div id="tab-feed" class="tab-content">
                        <div class="feed-area">
                            <h4><i class="fas fa-drumstick-bite"></i> 喂养龙虾 (投喂数据和指令)</h4>
                            <p style="font-size: 13px; color: #166534; margin-bottom: 12px;">
                                通过对话和任务训练你的龙虾，它会不断进化变得更聪明
                            </p>
                            <div class="feed-form">
                                <select class="form-select" id="feedType" style="width: 150px;">
                                    <option value="conversation">对话训练</option>
                                    <option value="task">任务训练</option>
                                    <option value="data">数据投喂</option>
                                    <option value="feedback">反馈调优</option>
                                </select>
                                <input type="text" class="feed-input" id="feedInput" placeholder="输入训练内容或指令...">
                                <button class="btn btn-primary" onclick="feedAgent()">
                                    <i class="fas fa-paper-plane"></i> 喂养
                                </button>
                            </div>
                        </div>
                        
                        <h4 style="margin-bottom: 12px;">喂养记录</h4>
                        <div class="feeding-history" id="feedingHistory"></div>
                    </div>
                    
                    <!-- 技能 -->
                    <div id="tab-skills" class="tab-content">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 16px;">
                            <h4>技能管理</h4>
                            <button class="btn btn-primary btn-sm" onclick="openSkillModal()">
                                <i class="fas fa-plus"></i> 创建技能
                            </button>
                        </div>
                        <div class="skills-grid" id="allSkills"></div>
                    </div>
                    
                    <!-- 记忆 -->
                    <div id="tab-memory" class="tab-content">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 16px;">
                            <h4>记忆库</h4>
                            <button class="btn btn-secondary btn-sm" onclick="clearMemory()">
                                <i class="fas fa-trash"></i> 清除记忆
                            </button>
                        </div>
                        <div id="memoryList"></div>
                    </div>
                    
                    <!-- 任务 -->
                    <div id="tab-tasks" class="tab-content">
                        <h4 style="margin-bottom: 16px;">任务历史</h4>
                        <div id="taskList"></div>
                    </div>
                    
                    <!-- 设置 -->
                    <div id="tab-settings" class="tab-content">
                        <h4 style="margin-bottom: 16px;">智能体设置</h4>
                        <form id="agentSettingsForm">
                            <div class="form-group">
                                <label class="form-label">名称</label>
                                <input type="text" class="form-input" id="settingsName">
                            </div>
                            <div class="form-group">
                                <label class="form-label">性格特点</label>
                                <input type="text" class="form-input" id="settingsPersonality">
                            </div>
                            <div class="form-group">
                                <label class="form-label">系统提示词</label>
                                <textarea class="form-textarea" id="settingsPrompt"></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">模型</label>
                                <select class="form-select" id="settingsModel">
                                    <option value="">正在加载可用模型...</option>
                                </select>
                            </div>
                            <div class="form-row" style="display: flex; gap: 16px;">
                                <div class="form-group" style="flex: 1;">
                                    <label class="form-label">Temperature</label>
                                    <input type="number" class="form-input" id="settingsTemp" step="0.1" min="0" max="2">
                                </div>
                                <div class="form-group" style="flex: 1;">
                                    <label class="form-label">Max Tokens</label>
                                    <input type="number" class="form-input" id="settingsTokens">
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="saveSettings()">
                                <i class="fas fa-save"></i> 保存设置
                            </button>
                            <button type="button" class="btn btn-secondary" style="margin-left: 8px;" onclick="toggleDeploy()">
                                <i class="fas fa-rocket"></i> <span id="deployBtnText">部署</span>
                            </button>
                            <button type="button" class="btn btn-secondary" style="margin-left: 8px;" onclick="deleteAgent()">
                                <i class="fas fa-trash"></i> 释放龙虾
                            </button>
                        </form>
                        
                        <!-- 部署信息 -->
                        <div id="deploymentInfo" class="hidden" style="margin-top: 20px; padding: 16px; background: #f0fdf4; border: 1px solid #86efac; border-radius: 12px;">
                            <h4 style="margin-bottom: 12px; color: #166534;"><i class="fas fa-check-circle"></i> 龙虾已部署</h4>
                            <div class="form-group">
                                <label class="form-label">访问 Token</label>
                                <div style="display: flex; gap: 8px;">
                                    <input type="text" class="form-input" id="deployToken" readonly style="flex: 1;">
                                    <button type="button" class="btn btn-secondary" onclick="copyToken()">
                                        <i class="fas fa-copy"></i> 复制
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">API 地址</label>
                                <input type="text" class="form-input" id="deployApiUrl" readonly>
                            </div>
                            <div class="form-group">
                                <label class="form-label">嵌入代码（iframe）</label>
                                <textarea class="form-textarea" id="deployEmbedCode" readonly rows="3"></textarea>
                            </div>
                            <p style="font-size: 12px; color: #64748b; margin-top: 8px;">
                                <i class="fas fa-info-circle"></i> 部署后，其他用户可以通过 Token 调用你的龙虾进行对话
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 创建智能体模态框 -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🦞 孵化新的龙虾</h3>
                <button class="close-btn" onclick="closeModal('createModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">龙虾名字</label>
                    <input type="text" class="form-input" id="createName" placeholder="例如：小助手">
                </div>
                <div class="form-group">
                    <label class="form-label">性格特点</label>
                    <input type="text" class="form-input" id="createPersonality" placeholder="例如：友好、乐于助人、聪明">
                </div>
                <div class="form-group">
                    <label class="form-label">基础模型</label>
                    <select class="form-select" id="createModel">
                        <option value="">正在加载可用模型...</option>
                    </select>
                    <span class="form-hint" id="modelLoadingHint" style="font-size: 12px; color: #64748b;">加载中...</span>
                </div>
                <div class="form-group">
                    <label class="form-label">系统提示词 (初始化指令)</label>
                    <textarea class="form-textarea" id="createPrompt" placeholder="你是一个AI助手，帮助用户完成各种任务。"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('createModal')">取消</button>
                <button class="btn btn-primary" onclick="createAgent()">
                    <i class="fas fa-egg"></i> 开始孵化
                </button>
            </div>
        </div>
    </div>
    
    <!-- 创建技能模态框 -->
    <div id="skillModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🎯 创建新技能</h3>
                <button class="close-btn" onclick="closeModal('skillModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">技能名称</label>
                    <input type="text" class="form-input" id="skillName" placeholder="例如：数据分析、文案写作">
                </div>
                <div class="form-group">
                    <label class="form-label">技能类型</label>
                    <select class="form-select" id="skillType">
                        <option value="conversation">对话</option>
                        <option value="analysis">分析</option>
                        <option value="generation">生成</option>
                        <option value="integration">集成</option>
                        <option value="custom">自定义</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">技能描述</label>
                    <textarea class="form-textarea" id="skillDescription" placeholder="描述这个技能的功能和用途..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">配置参数 (JSON格式，可选)</label>
                    <textarea class="form-textarea" id="skillConfig" placeholder='{"temperature": 0.7, "max_tokens": 2000}'></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('skillModal')">取消</button>
                <button class="btn btn-primary" onclick="createSkill()">
                    <i class="fas fa-plus"></i> 创建技能
                </button>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let agents = [];
        let currentAgent = null;
        
        $(document).ready(function() {
            loadStats();
            loadAgents();
            loadAvailableModels();
            loadSettingsModels();
            
            // 标签页切换
            $('.tab').click(function() {
                const tab = $(this).data('tab');
                $('.tab').removeClass('active');
                $(this).addClass('active');
                $('.tab-content').removeClass('active');
                $(`#tab-${tab}`).addClass('active');
            });
        });
        
        // 加载可用模型列表
        function loadAvailableModels() {
            const $select = $('#createModel');
            $select.html('<option value="">正在加载模型...</option>');
            
            Promise.all([
                // 加载本地模型
                $.get('../api/api_handler.php?request=models'),
                // 加载在线API提供商
                $.get('../api/providers_handler.php?action=get_providers&enabled=1')
            ]).then(([localRes, providerRes]) => {
                let options = '<option value="">选择模型</option>';
                
                // 添加在线API模型
                if (providerRes.success && providerRes.data && providerRes.data.length > 0) {
                    options += '<optgroup label="☁️ 在线API模型">';
                    providerRes.data.forEach(provider => {
                        if (provider.models && provider.models.length > 0) {
                            provider.models.forEach(modelName => {
                                options += `<option value="${modelName}" data-provider="${provider.id}">${modelName} (${provider.name})</option>`;
                            });
                        }
                    });
                    options += '</optgroup>';
                }
                
                // 添加本地模型
                if (localRes.status === 'success' && localRes.models) {
                    options += '<optgroup label="💻 本地模型">';
                    Object.entries(localRes.models).forEach(([id, model]) => {
                        const modelName = typeof model === 'object' ? (model.name || id) : model;
                        const modelSize = typeof model === 'object' && model.parameter_size ? ` [${model.parameter_size}]` : '';
                        options += `<option value="${id}">${modelName}${modelSize}</option>`;
                    });
                    options += '</optgroup>';
                }
                
                $select.html(options);
                $('#modelLoadingHint').text('');
            }).catch(error => {
                console.error('加载模型失败:', error);
                $select.html('<option value="gpt-3.5-turbo">GPT-3.5 Turbo</option><option value="gpt-4">GPT-4</option>');
                $('#modelLoadingHint').text('加载失败，使用默认模型');
            });
        }
        
        // 加载设置页面的模型列表
        function loadSettingsModels() {
            const $select = $('#settingsModel');
            $select.html('<option value="">正在加载模型...</option>');
            
            Promise.all([
                // 加载本地模型
                $.get('../api/api_handler.php?request=models'),
                // 加载在线API提供商
                $.get('../api/providers_handler.php?action=get_providers&enabled=1')
            ]).then(([localRes, providerRes]) => {
                let options = '<option value="">选择模型</option>';
                
                // 添加在线API模型
                if (providerRes.success && providerRes.data && providerRes.data.length > 0) {
                    options += '<optgroup label="☁️ 在线API模型">';
                    providerRes.data.forEach(provider => {
                        if (provider.models && provider.models.length > 0) {
                            provider.models.forEach(modelName => {
                                options += `<option value="${modelName}" data-provider="${provider.id}">${modelName} (${provider.name})</option>`;
                            });
                        }
                    });
                    options += '</optgroup>';
                }
                
                // 添加本地模型
                if (localRes.status === 'success' && localRes.models) {
                    options += '<optgroup label="💻 本地模型">';
                    Object.entries(localRes.models).forEach(([id, model]) => {
                        const modelName = typeof model === 'object' ? (model.name || id) : model;
                        const modelSize = typeof model === 'object' && model.parameter_size ? ` [${model.parameter_size}]` : '';
                        options += `<option value="${id}">${modelName}${modelSize}</option>`;
                    });
                    options += '</optgroup>';
                }
                
                $select.html(options);
            }).catch(error => {
                console.error('加载设置模型失败:', error);
                $select.html('<option value="gpt-3.5-turbo">GPT-3.5 Turbo</option><option value="gpt-4">GPT-4</option><option value="deepseek-chat">DeepSeek Chat</option>');
            });
        }
        
        function loadStats() {
            $.get('../api/openclaw_handler.php?action=getAgentStats', function(res) {
                if (res.success) {
                    $('#statAgents').text(res.statistics.total_agents);
                    $('#statFeeds').text(res.statistics.total_feeds);
                    $('#statTasks').text(res.statistics.total_tasks);
                    $('#statTokens').text(res.statistics.total_tokens.toLocaleString());
                }
            });
        }
        
        function loadAgents() {
            $.get('../api/openclaw_handler.php?action=getAgents', function(res) {
                if (res.success) {
                    agents = res.agents;
                    renderAgents();
                }
            });
        }
        
        function renderAgents() {
            if (agents.length === 0) {
                $('#agentList').html('<p style="text-align: center; color: #64748b; padding: 20px;">还没有龙虾，点击"孵化"创建第一只</p>');
                return;
            }
            
            let html = '';
            agents.forEach(agent => {
                const activeClass = currentAgent && currentAgent.id === agent.id ? 'active' : '';
                html += `
                    <div class="agent-card ${activeClass}" onclick="selectAgent(${agent.id})">
                        <div class="agent-header">
                            <div class="agent-avatar">${agent.avatar}</div>
                            <div>
                                <div class="agent-name">${agent.name}</div>
                                <div class="agent-level">Lv.${agent.level} · ${agent.status}</div>
                            </div>
                        </div>
                        <div class="agent-stats">
                            <div class="agent-stat">智力: <span>${agent.intelligence}</span></div>
                            <div class="agent-stat">技能: <span>${agent.skill_count}</span></div>
                            <div class="agent-stat">任务: <span>${agent.total_tasks}</span></div>
                            <div class="agent-stat">喂养: <span>${agent.feed_count}</span></div>
                        </div>
                    </div>
                `;
            });
            $('#agentList').html(html);
        }
        
        function selectAgent(id) {
            currentAgent = agents.find(a => a.id === id);
            renderAgents();
            
            $('#noAgentSelected').addClass('hidden');
            $('#agentContent').removeClass('hidden');
            
            loadAgentDetails(id);
            loadAgentSkills(id);
            loadFeedingHistory(id);
            loadAgentMemory(id);
            loadAgentTasks(id);
        }
        
        function loadAgentDetails(id) {
            $.get('../api/openclaw_handler.php?action=getAgentDetails&id=' + id, function(res) {
                if (res.success) {
                    const agent = res.agent;
                    currentAgent = agent;
                    
                    $('#detailAvatar').text(agent.avatar);
                    $('#detailName').text(agent.name);
                    $('#detailLevel').text('Lv.' + agent.level);
                    $('#detailPersonality').text(agent.personality || '暂无性格描述');
                    $('#detailExp').text(agent.experience % 100);
                    $('#detailExpBar').css('width', (agent.experience % 100) + '%');
                    
                    $('#attrIntelligence').text(agent.intelligence);
                    $('#attrAutonomy').text(agent.autonomy);
                    $('#attrSuccessRate').text(agent.success_rate + '%');
                    $('#attrTotalTasks').text(agent.total_tasks);
                    
                    // 设置页面
                    $('#settingsName').val(agent.name);
                    $('#settingsPersonality').val(agent.personality);
                    $('#settingsPrompt').val(agent.system_prompt);
                    $('#settingsModel').val(agent.model);
                    $('#settingsTemp').val(agent.temperature);
                    $('#settingsTokens').val(agent.max_tokens);
                }
            });
        }
        
        function loadAgentSkills(id) {
            $.get('../api/openclaw_handler.php?action=getSkills', function(res) {
                if (res.success) {
                    let html = '';
                    res.skills.forEach(skill => {
                        const agentSkill = currentAgent && currentAgent.skills ? currentAgent.skills.find(s => s.id === skill.id) : null;
                        const proficiency = agentSkill ? agentSkill.proficiency : 0;
                        html += `
                            <div class="skill-card">
                                <div class="skill-name">${skill.name}</div>
                                <div class="skill-proficiency">熟练度: ${proficiency}%</div>
                                <div class="skill-progress">
                                    <div class="skill-progress-fill" style="width: ${proficiency}%"></div>
                                </div>
                            </div>
                        `;
                    });
                    $('#allSkills').html(html);
                }
            });
        }
        
        function loadFeedingHistory(id) {
            $.get('../api/openclaw_handler.php?action=getFeedingHistory&agent_id=' + id, function(res) {
                if (res.success && res.records.length > 0) {
                    let html = '';
                    res.records.forEach(r => {
                        html += `
                            <div class="feeding-item">
                                <div>
                                    <strong>${r.feed_type}</strong>
                                    <p style="font-size: 13px; color: #64748b; margin: 4px 0;">${r.notes || r.feed_data}</p>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 12px; color: #64748b;">${new Date(r.fed_at).toLocaleString()}</div>
                                    <div style="color: #16a34a;">+${r.experience_gained} 经验</div>
                                </div>
                            </div>
                        `;
                    });
                    $('#feedingHistory').html(html);
                } else {
                    $('#feedingHistory').html('<p style="text-align: center; color: #64748b; padding: 20px;">还没有喂养记录</p>');
                }
            });
        }
        
        function loadAgentMemory(id) {
            $.get('../api/openclaw_handler.php?action=getAgentMemory&agent_id=' + id, function(res) {
                if (res.success && res.memories.length > 0) {
                    let html = '<table class="data-table" style="width: 100%;"><thead><tr><th>类型</th><th>内容</th><th>重要性</th><th>时间</th></tr></thead><tbody>';
                    res.memories.forEach(m => {
                        html += `<tr>
                            <td>${m.memory_type}</td>
                            <td>${m.content.substring(0, 50)}...</td>
                            <td>${m.importance}</td>
                            <td>${new Date(m.created_at).toLocaleDateString()}</td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    $('#memoryList').html(html);
                } else {
                    $('#memoryList').html('<p style="text-align: center; color: #64748b; padding: 20px;">记忆库为空</p>');
                }
            });
        }
        
        function loadAgentTasks(id) {
            $.get('../api/openclaw_handler.php?action=getAgentTasks&agent_id=' + id, function(res) {
                if (res.success && res.tasks.length > 0) {
                    let html = '<table class="data-table" style="width: 100%;"><thead><tr><th>类型</th><th>输入</th><th>状态</th><th>时间</th></tr></thead><tbody>';
                    res.tasks.forEach(t => {
                        const statusColor = t.success ? '#16a34a' : (t.status === 'failed' ? '#dc2626' : '#64748b');
                        html += `<tr>
                            <td>${t.task_type}</td>
                            <td>${t.task_input.substring(0, 30)}...</td>
                            <td style="color: ${statusColor}">${t.status}</td>
                            <td>${new Date(t.started_at).toLocaleString()}</td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    $('#taskList').html(html);
                } else {
                    $('#taskList').html('<p style="text-align: center; color: #64748b; padding: 20px;">还没有任务记录</p>');
                }
            });
        }
        
        function openCreateModal() {
            $('#createModal').addClass('active');
        }
        
        function openSkillModal() {
            $('#skillModal').addClass('active');
        }
        
        function closeModal(id) {
            $(`#${id}`).removeClass('active');
        }
        
        function createSkill() {
            const name = $('#skillName').val().trim();
            const type = $('#skillType').val();
            const description = $('#skillDescription').val().trim();
            const configStr = $('#skillConfig').val().trim();
            
            if (!name) {
                alert('请输入技能名称');
                return;
            }
            
            let config = {};
            if (configStr) {
                try {
                    config = JSON.parse(configStr);
                } catch (e) {
                    alert('配置参数JSON格式错误，请检查');
                    return;
                }
            }
            
            $.post('../api/openclaw_handler.php', {
                action: 'createSkill',
                name: name,
                skill_type: type,
                description: description,
                config: JSON.stringify(config)
            }, function(res) {
                if (res.success) {
                    closeModal('skillModal');
                    // 清空表单
                    $('#skillName').val('');
                    $('#skillDescription').val('');
                    $('#skillConfig').val('');
                    // 刷新技能列表
                    if (currentAgent) {
                        loadAgentSkills(currentAgent.id);
                    }
                    alert('🎯 技能创建成功！');
                } else {
                    alert(res.error || '创建技能失败');
                }
            }).fail(function(xhr) {
                alert('网络错误: ' + xhr.statusText);
            });
        }
        
        function createAgent() {
            const data = {
                action: 'createAgent',
                name: $('#createName').val() || '小虾米',
                personality: $('#createPersonality').val(),
                model: $('#createModel').val(),
                system_prompt: $('#createPrompt').val()
            };
            
            $.post('../api/openclaw_handler.php', data, function(res) {
                if (res.success) {
                    closeModal('createModal');
                    loadAgents();
                    loadStats();
                    alert('🦞 龙虾孵化成功！开始培养你的AI智能体吧！');
                } else {
                    alert(res.error || '创建失败');
                }
            });
        }
        
        function feedAgent() {
            if (!currentAgent) {
                alert('请先选择一只龙虾');
                return;
            }
            
            const feedInput = $('#feedInput').val();
            if (!feedInput) {
                alert('请输入喂养内容');
                return;
            }
            
            const tokens = Math.floor(feedInput.length * 1.5); // 估算tokens
            
            $.post('../api/openclaw_handler.php', {
                action: 'feedAgent',
                agent_id: currentAgent.id,
                feed_type: $('#feedType').val(),
                feed_data: feedInput,
                tokens_used: tokens,
                notes: feedInput
            }, function(res) {
                if (res.success) {
                    $('#feedInput').val('');
                    loadAgentDetails(currentAgent.id);
                    loadFeedingHistory(currentAgent.id);
                    loadStats();
                    alert(`喂养成功！获得 ${res.experience_gained} 经验值`);
                    if (res.new_level > currentAgent.level) {
                        alert(`🎉 恭喜！你的龙虾升级到 Lv.${res.new_level}！`);
                    }
                } else {
                    alert(res.error || '喂养失败');
                }
            });
        }
        
        function saveSettings() {
            if (!currentAgent) return;
            
            $.post('../api/openclaw_handler.php', {
                action: 'updateAgent',
                id: currentAgent.id,
                name: $('#settingsName').val(),
                personality: $('#settingsPersonality').val(),
                system_prompt: $('#settingsPrompt').val(),
                model: $('#settingsModel').val(),
                temperature: $('#settingsTemp').val(),
                max_tokens: $('#settingsTokens').val()
            }, function(res) {
                if (res.success) {
                    loadAgents();
                    loadAgentDetails(currentAgent.id);
                    alert('设置保存成功！');
                } else {
                    alert(res.error || '保存失败');
                }
            });
        }
        
        function deleteAgent() {
            if (!currentAgent) return;
            if (!confirm(`确定要释放 ${currentAgent.name} 吗？此操作不可恢复！`)) return;
            
            $.post('../api/openclaw_handler.php', {
                action: 'deleteAgent',
                id: currentAgent.id
            }, function(res) {
                if (res.success) {
                    currentAgent = null;
                    $('#noAgentSelected').removeClass('hidden');
                    $('#agentContent').addClass('hidden');
                    loadAgents();
                    loadStats();
                } else {
                    alert(res.error || '删除失败');
                }
            });
        }
        
        function clearMemory() {
            if (!currentAgent) return;
            if (!confirm('确定要清除所有记忆吗？')) return;
            
            $.post('../api/openclaw_handler.php', {
                action: 'clearMemory',
                agent_id: currentAgent.id
            }, function(res) {
                if (res.success) {
                    loadAgentMemory(currentAgent.id);
                    alert('记忆已清除');
                }
            });
        }
        
        // 部署/取消部署
        function toggleDeploy() {
            if (!currentAgent) return;
            
            // 检查当前部署状态
            $.get('../api/openclaw_handler.php?action=getDeploymentStatus&agent_id=' + currentAgent.id, function(res) {
                if (res.success && res.deployed) {
                    // 已部署，取消部署
                    if (!confirm('确定要取消部署吗？取消后外部将无法访问你的龙虾。')) return;
                    
                    $.post('../api/openclaw_handler.php', {
                        action: 'undeployAgent',
                        agent_id: currentAgent.id
                    }, function(res) {
                        if (res.success) {
                            $('#deploymentInfo').addClass('hidden');
                            $('#deployBtnText').text('部署');
                            loadAgentDetails(currentAgent.id);
                            alert('部署已取消');
                        }
                    });
                } else {
                    // 未部署，进行部署
                    $.post('../api/openclaw_handler.php', {
                        action: 'deployAgent',
                        agent_id: currentAgent.id
                    }, function(res) {
                        if (res.success) {
                            $('#deployToken').val(res.token);
                            $('#deployApiUrl').val(res.urls.chat);
                            $('#deployEmbedCode').val(res.embed_code);
                            $('#deploymentInfo').removeClass('hidden');
                            $('#deployBtnText').text('取消部署');
                            loadAgentDetails(currentAgent.id);
                            alert('🚀 龙虾部署成功！\n\n其他用户现在可以通过Token调用你的龙虾了。');
                        } else {
                            alert(res.error || '部署失败');
                        }
                    });
                }
            });
        }
        
        // 复制Token
        function copyToken() {
            const tokenInput = document.getElementById('deployToken');
            tokenInput.select();
            document.execCommand('copy');
            alert('Token 已复制到剪贴板');
        }
        
        // 加载部署状态
        function loadDeploymentStatus(agentId) {
            $.get('../api/openclaw_handler.php?action=getDeploymentStatus&agent_id=' + agentId, function(res) {
                if (res.success && res.deployed) {
                    $('#deployToken').val(res.token);
                    $('#deployApiUrl').val(res.urls.chat);
                    $('#deploymentInfo').removeClass('hidden');
                    $('#deployBtnText').text('取消部署');
                } else {
                    $('#deploymentInfo').addClass('hidden');
                    $('#deployBtnText').text('部署');
                }
            });
        }
        
        // 在加载智能体详情时同时加载部署状态
        const originalLoadAgentDetails = loadAgentDetails;
        loadAgentDetails = function(id) {
            originalLoadAgentDetails(id);
            loadDeploymentStatus(id);
        };
    </script>
</body>
</html>
