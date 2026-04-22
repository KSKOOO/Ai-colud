<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']) {
    header('Location: ?route=login');
    exit;
}

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../lib/AIProviderManager.php';

$db = Database::getInstance();
$manager = new AIProviderManager($db);
$providers = $manager->getProviders(true);

// 获取所有知识库（带错误处理）
$knowledgeBases = [];
try {
    $currentUserId = $_SESSION['user']['id'];
    $knowledgeBases = $db->fetchAll(
        "SELECT id, name, description FROM knowledge_bases WHERE user_id = :user_id AND status = 'active' ORDER BY created_at DESC",
        ['user_id' => $currentUserId]
    );
} catch (Exception $e) {
    // 表不存在时返回空数组
    $knowledgeBases = [];
}

$agentId = $_GET['id'] ?? 0;
$agent = null;
if ($agentId) {
    require_once __DIR__ . '/../includes/AgentManager.php';
    $agentManager = new AgentManager($db);
    $agent = $agentManager->getAgent($agentId);
    if (!$agent || $agent['user_id'] != $_SESSION['user']['id']) {
        header('Location: ?route=agents');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $agent ? '编辑智能体' : '创建智能体'; ?> - 巨神兵AIAPI辅助平台</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }

        .app-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* 顶部导航 */
        .top-nav {
            background: white;
            padding: 0 24px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 100;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .back-btn {
            width: 36px;
            height: 36px;
            border: none;
            background: #f3f4f6;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4b5563;
            text-decoration: none;
            transition: all 0.2s;
        }

        .back-btn:hover {
            background: #e5e7eb;
        }

        .page-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
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

        /* 主内容区 */
        .main-content {
            flex: 1;
            display: flex;
            overflow: hidden;
        }

        /* 左侧面板 */
        .left-panel {
            width: 380px;
            background: white;
            border-right: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .panel-tabs {
            display: flex;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .panel-tab {
            flex: 1;
            padding: 14px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            color: #6b7280;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .panel-tab:hover {
            color: #4c51bf;
            background: #f3f4f6;
        }

        .panel-tab.active {
            color: #4c51bf;
            background: white;
            border-bottom: 2px solid #4c51bf;
        }

        .panel-content {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* 表单样式 */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }

        .form-label .required {
            color: #ef4444;
            margin-left: 2px;
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            font-family: inherit;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #4c51bf;
            box-shadow: 0 0 0 3px rgba(76, 81, 191, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-hint {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }

        /* 图标选择器 */
        .icon-selector {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 8px;
            margin-top: 8px;
        }

        .icon-option {
            width: 40px;
            height: 40px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 18px;
            color: #6b7280;
        }

        .icon-option:hover {
            border-color: #4c51bf;
            color: #4c51bf;
        }

        .icon-option.selected {
            border-color: #4c51bf;
            background: rgba(76, 81, 191, 0.1);
            color: #4c51bf;
        }

        /* 颜色选择器 */
        .color-selector {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .color-option {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.2s;
        }

        .color-option:hover {
            transform: scale(1.1);
        }

        .color-option.selected {
            border-color: #1f2937;
            transform: scale(1.1);
        }

        /* 复选框组 */
        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .checkbox-item:hover {
            border-color: #4c51bf;
            background: rgba(76, 81, 191, 0.02);
        }

        .checkbox-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-right: 12px;
            accent-color: #4c51bf;
        }

        .checkbox-content {
            flex: 1;
        }

        .checkbox-title {
            font-weight: 500;
            color: #1f2937;
            font-size: 14px;
        }

        .checkbox-desc {
            font-size: 12px;
            color: #6b7280;
            margin-top: 2px;
        }

        /* 知识库选择 */
        .kb-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .kb-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .kb-item:hover {
            border-color: #4c51bf;
        }

        .kb-item.selected {
            border-color: #4c51bf;
            background: rgba(76, 81, 191, 0.05);
        }

        .kb-item input[type="checkbox"] {
            margin-right: 12px;
            accent-color: #4c51bf;
        }

        .kb-info {
            flex: 1;
        }

        .kb-name {
            font-weight: 500;
            color: #1f2937;
            font-size: 14px;
        }

        .kb-desc {
            font-size: 12px;
            color: #6b7280;
        }

        /* 右侧预览区 */
        .right-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #f5f7fa;
        }

        .preview-header {
            padding: 16px 24px;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .preview-title {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
        }

        .preview-actions {
            display: flex;
            gap: 8px;
        }

        /* 聊天预览 */
        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 600px;
            margin: 0 auto;
            width: 100%;
            padding: 20px;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 16px;
        }

        .welcome-message {
            text-align: center;
            padding: 40px 20px;
        }

        .agent-avatar-preview {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .agent-name-preview {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .agent-desc-preview {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.6;
        }

        .chat-message {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .chat-message.user {
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .chat-message.user .message-avatar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .chat-message.assistant .message-avatar {
            background: #f3f4f6;
            color: #4c51bf;
        }

        .message-content {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 16px;
            font-size: 14px;
            line-height: 1.5;
        }

        .chat-message.user .message-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .chat-message.assistant .message-content {
            background: #f3f4f6;
            color: #1f2937;
            border-bottom-left-radius: 4px;
        }

        /* 输入框 */
        .chat-input-container {
            background: white;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            gap: 12px;
            align-items: flex-end;
        }

        .chat-input {
            flex: 1;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 14px;
            resize: none;
            min-height: 48px;
            max-height: 120px;
            font-family: inherit;
        }

        .chat-input:focus {
            outline: none;
            border-color: #4c51bf;
        }

        .send-btn {
            width: 48px;
            height: 48px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .send-btn:hover {
            opacity: 0.9;
            transform: scale(1.05);
        }

        .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* 部署信息 */
        .deploy-info {
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .deploy-info-title {
            font-size: 14px;
            font-weight: 600;
            color: #166534;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .deploy-url {
            background: white;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 13px;
            color: #1f2937;
            font-family: monospace;
            word-break: break-all;
            margin-bottom: 8px;
            border: 1px solid #bbf7d0;
        }

        .deploy-actions {
            display: flex;
            gap: 8px;
        }

        /* 滑块 */
        .slider-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .slider {
            flex: 1;
            -webkit-appearance: none;
            height: 6px;
            border-radius: 3px;
            background: #e5e7eb;
            outline: none;
        }

        .slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #4c51bf;
            cursor: pointer;
        }

        .slider-value {
            min-width: 40px;
            text-align: right;
            font-size: 14px;
            color: #374151;
            font-weight: 500;
        }

        /* 空状态 */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #9ca3af;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
        }

        /* 加载动画 */
        .typing-indicator {
            display: flex;
            gap: 4px;
            padding: 12px 16px;
        }

        .typing-indicator span {
            width: 8px;
            height: 8px;
            background: #9ca3af;
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }

        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-10px); }
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

        /* 响应式 */
        @media (max-width: 1024px) {
            .left-panel {
                width: 320px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
            }
            .left-panel {
                width: 100%;
                height: 50%;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- 顶部导航 -->
        <header class="top-nav">
            <div class="nav-left">
                <a href="?route=agents" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="page-title"><?php echo $agent ? '编辑智能体' : '创建智能体'; ?></h1>
            </div>
            <div class="nav-right">
                <?php if ($agent && $agent['status'] === 'active'): ?>
                <button class="btn btn-danger" onclick="undeployAgent()">
                    <i class="fas fa-stop-circle"></i>
                    停用
                </button>
                <?php elseif ($agent): ?>
                <button class="btn btn-success" onclick="deployAgent()">
                    <i class="fas fa-rocket"></i>
                    部署
                </button>
                <?php endif; ?>
                <button class="btn btn-secondary" onclick="saveAgent(false)">
                    <i class="fas fa-save"></i>
                    保存
                </button>
                <button class="btn btn-primary" onclick="saveAgent(true)">
                    <i class="fas fa-play"></i>
                    保存并调试
                </button>
            </div>
        </header>

        <!-- 主内容区 -->
        <div class="main-content">
            <!-- 左侧面板 -->
            <div class="left-panel">
                <div class="panel-tabs">
                    <button class="panel-tab active" data-tab="basic">
                        <i class="fas fa-info-circle"></i>
                        基础设置
                    </button>
                    <button class="panel-tab" data-tab="role">
                        <i class="fas fa-user-circle"></i>
                        角色设定
                    </button>
                    <button class="panel-tab" data-tab="model">
                        <i class="fas fa-brain"></i>
                        模型
                    </button>
                </div>

                <div class="panel-content">
                    <!-- 基础设置 -->
                    <div class="tab-content active" id="tab-basic">
                        <div class="form-group">
                            <label class="form-label">
                                智能体名称 <span class="required">*</span>
                            </label>
                            <input type="text" class="form-input" id="agentName" 
                                   value="<?php echo htmlspecialchars($agent['name'] ?? ''); ?>"
                                   placeholder="给你的智能体起个名字">
                        </div>

                        <div class="form-group">
                            <label class="form-label">描述</label>
                            <textarea class="form-textarea" id="agentDesc" rows="3"
                                      placeholder="描述这个智能体的用途"><?php echo htmlspecialchars($agent['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">图标</label>
                            <div class="icon-selector">
                                <?php 
                                $icons = ['fa-robot', 'fa-brain', 'fa-comments', 'fa-magic', 'fa-code', 'fa-paint-brush', 
                                          'fa-music', 'fa-book', 'fa-graduation-cap', 'fa-briefcase', 'fa-gamepad', 'fa-heart'];
                                $selectedIcon = $agent['icon'] ?? 'fa-robot';
                                foreach ($icons as $icon): 
                                ?>
                                <div class="icon-option <?php echo $icon === $selectedIcon ? 'selected' : ''; ?>" data-icon="<?php echo $icon; ?>">
                                    <i class="fas <?php echo $icon; ?>"></i>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="agentIcon" value="<?php echo $selectedIcon; ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">主题颜色</label>
                            <div class="color-selector">
                                <?php 
                                $colors = ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#00f2fe',
                                           '#43e97b', '#38f9d7', '#fa709a', '#fee140', '#30cfd0', '#330867'];
                                $selectedColor = $agent['color'] ?? '#667eea';
                                foreach ($colors as $color): 
                                ?>
                                <div class="color-option <?php echo $color === $selectedColor ? 'selected' : ''; ?>" 
                                     style="background: <?php echo $color; ?>" data-color="<?php echo $color; ?>"></div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="agentColor" value="<?php echo $selectedColor; ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">开场白</label>
                            <textarea class="form-textarea" id="welcomeMessage" rows="3"
                                      placeholder="用户首次进入时显示的欢迎语"><?php echo htmlspecialchars($agent['welcome_message'] ?? '你好！我是你的AI助手，有什么可以帮助你的吗？'); ?></textarea>
                            <p class="form-hint">这是用户首次与智能体对话时显示的消息</p>
                        </div>
                    </div>

                    <!-- 角色设定 -->
                    <div class="tab-content" id="tab-role">
                        <div class="form-group">
                            <label class="form-label">角色名称</label>
                            <input type="text" class="form-input" id="roleName"
                                   value="<?php echo htmlspecialchars($agent['role_name'] ?? ''); ?>"
                                   placeholder="例如：编程助手、产品经理">
                        </div>

                        <div class="form-group">
                            <label class="form-label">角色描述</label>
                            <textarea class="form-textarea" id="roleDescription" rows="4"
                                      placeholder="详细描述这个角色的职责和能力"><?php echo htmlspecialchars($agent['role_description'] ?? ''); ?></textarea>
                            <p class="form-hint">描述角色的专业领域、工作目标等</p>
                        </div>

                        <div class="form-group">
                            <label class="form-label">性格特点</label>
                            <textarea class="form-textarea" id="personality" rows="3"
                                      placeholder="例如：友好、专业、幽默"><?php echo htmlspecialchars($agent['personality'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">能力配置</label>
                            <div class="checkbox-group">
                                <?php
                                $capabilities = $agent['capabilities'] ?? ['chat'];
                                $capabilityList = [
                                    'chat' => ['name' => '对话聊天', 'desc' => '基础对话能力', 'icon' => 'fa-comments'],
                                    'file_analysis' => ['name' => '文件分析', 'desc' => '分析上传的文档内容', 'icon' => 'fa-file-alt'],
                                    'web_search' => ['name' => '网络搜索', 'desc' => '搜索互联网获取最新信息', 'icon' => 'fa-search'],
                                    'code_execution' => ['name' => '代码执行', 'desc' => '执行代码并返回结果', 'icon' => 'fa-code'],
                                    'knowledge_query' => ['name' => '知识库查询', 'desc' => '查询关联知识库获取专业信息', 'icon' => 'fa-database'],
                                    'image_generation' => ['name' => '图像生成', 'desc' => '生成AI图像', 'icon' => 'fa-image'],
                                    'task_planning' => ['name' => '任务规划', 'desc' => '将复杂任务分解为步骤', 'icon' => 'fa-tasks'],
                                    'data_analysis' => ['name' => '数据分析', 'desc' => '分析和可视化数据', 'icon' => 'fa-chart-bar'],
                                    'translation' => ['name' => '翻译', 'desc' => '多语言翻译', 'icon' => 'fa-language'],
                                    'summarization' => ['name' => '摘要生成', 'desc' => '生成长文本摘要', 'icon' => 'fa-file-text']
                                ];
                                foreach ($capabilityList as $key => $cap):
                                ?>
                                <label class="checkbox-item">
                                    <input type="checkbox" value="<?php echo $key; ?>"
                                           <?php echo in_array($key, $capabilities) ? 'checked' : ''; ?>>
                                    <div class="checkbox-content">
                                        <div class="checkbox-title">
                                            <i class="fas <?php echo $cap['icon']; ?>" style="margin-right: 6px; color: #4c51bf;"></i>
                                            <?php echo $cap['name']; ?>
                                        </div>
                                        <div class="checkbox-desc"><?php echo $cap['desc']; ?></div>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">关联知识库</label>
                            <?php if (empty($knowledgeBases)): ?>
                            <div class="empty-state">
                                <i class="fas fa-database"></i>
                                <p>暂无可用知识库</p>
                                <a href="?route=knowledge" target="_blank" style="color: #4c51bf; font-size: 12px;">前往创建</a>
                            </div>
                            <?php else: 
                                $selectedKbs = $agent['knowledge_base_ids'] ?? [];
                            ?>
                            <div class="kb-list">
                                <?php foreach ($knowledgeBases as $kb): ?>
                                <label class="kb-item <?php echo in_array($kb['id'], $selectedKbs) ? 'selected' : ''; ?>">
                                    <input type="checkbox" value="<?php echo $kb['id']; ?>"
                                           <?php echo in_array($kb['id'], $selectedKbs) ? 'checked' : ''; ?>>
                                    <div class="kb-info">
                                        <div class="kb-name"><?php echo htmlspecialchars($kb['name']); ?></div>
                                        <div class="kb-desc"><?php echo htmlspecialchars($kb['description'] ?? ''); ?></div>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 模型设置 -->
                    <div class="tab-content" id="tab-model">
                        <div class="form-group">
                            <label class="form-label">
                                AI提供商 <span class="required">*</span>
                            </label>
                            <select class="form-select" id="modelProvider" onchange="loadModels()">
                                <option value="">选择提供商</option>
                                <?php foreach ($providers as $provider):
                                    $selected = ($agent['model_provider'] ?? '') === $provider['type'] ? 'selected' : '';
                                ?>
                                <option value="<?php echo $provider['type']; ?>" <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($provider['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                模型 <span class="required">*</span>
                            </label>
                            <select class="form-select" id="modelId">
                                <option value="">先选择提供商</option>
                            </select>
                            <p class="form-hint" style="margin-top: 8px; color: #6b7280; font-size: 13px;">
                                <i class="fas fa-info-circle"></i>
                                如需文件分析功能，建议选择支持长上下文的模型（如 GPT-4、Claude、DeepSeek-V3 等）
                            </p>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Temperature</label>
                            <div class="slider-container">
                                <input type="range" class="slider" id="temperature" 
                                       min="0" max="2" step="0.1" 
                                       value="<?php echo $agent['temperature'] ?? 0.7; ?>"
                                       oninput="document.getElementById('tempValue').textContent = this.value">
                                <span class="slider-value" id="tempValue"><?php echo $agent['temperature'] ?? 0.7; ?></span>
                            </div>
                            <p class="form-hint">值越低回答越确定，值越高越有创意</p>
                        </div>

                        <div class="form-group">
                            <label class="form-label">最大Token数</label>
                            <select class="form-select" id="maxTokens">
                                <?php $maxTokens = $agent['max_tokens'] ?? 2048; ?>
                                <option value="512" <?php echo $maxTokens == 512 ? 'selected' : ''; ?>>512</option>
                                <option value="1024" <?php echo $maxTokens == 1024 ? 'selected' : ''; ?>>1024</option>
                                <option value="2048" <?php echo $maxTokens == 2048 ? 'selected' : ''; ?>>2048</option>
                                <option value="4096" <?php echo $maxTokens == 4096 ? 'selected' : ''; ?>>4096</option>
                                <option value="8192" <?php echo $maxTokens == 8192 ? 'selected' : ''; ?>>8192</option>
                            </select>
                        </div>

                        <?php if ($agent && $agent['status'] === 'active'): ?>
                        <div class="deploy-info">
                            <div class="deploy-info-title">
                                <i class="fas fa-check-circle"></i>
                                已部署
                            </div>
                            <div class="deploy-url">
                                <?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/?route=agent_chat&token=<?php echo $agent['deploy_token']; ?>
                            </div>
                            <div class="deploy-actions">
                                <button class="btn btn-secondary" onclick="copyDeployUrl()">
                                    <i class="fas fa-copy"></i>
                                    复制链接
                                </button>
                                <a href="?route=agent_chat&token=<?php echo $agent['deploy_token']; ?>" target="_blank" class="btn btn-primary">
                                    <i class="fas fa-external-link-alt"></i>
                                    打开
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 右侧预览区 -->
            <div class="right-panel">
                <div class="preview-header">
                    <span class="preview-title">
                        <i class="fas fa-play-circle" style="margin-right: 8px; color: #4c51bf;"></i>
                        调试预览
                    </span>
                    <div class="preview-actions">
                        <button class="btn btn-secondary" onclick="clearChat()">
                            <i class="fas fa-trash-alt"></i>
                            清空
                        </button>
                    </div>
                </div>

                <div class="chat-container">
                    <div class="chat-messages" id="chatMessages">
                        <div class="welcome-message">
                            <div class="agent-avatar-preview" id="previewAvatar" style="background: <?php echo $selectedColor; ?>">
                                <i class="fas <?php echo $selectedIcon; ?>"></i>
                            </div>
                            <div class="agent-name-preview" id="previewName">
                                <?php echo $agent['name'] ?? '未命名智能体'; ?>
                            </div>
                            <div class="agent-desc-preview" id="previewDesc">
                                <?php echo $agent['welcome_message'] ?? '你好！我是你的AI助手，有什么可以帮助你的吗？'; ?>
                            </div>
                        </div>
                    </div>

                    <div class="chat-input-container">
                        <textarea class="chat-input" id="chatInput" rows="1" 
                                  placeholder="输入消息测试智能体..."
                                  onkeydown="handleKeyDown(event)"></textarea>
                        <button class="send-btn" onclick="sendMessage()" id="sendBtn">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const agentId = <?php echo $agentId; ?>;
        let currentSessionId = 'debug_' + Date.now();
        let isLoading = false;

        // 标签切换
        document.querySelectorAll('.panel-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.panel-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
            });
        });

        // 图标选择
        document.querySelectorAll('.icon-option').forEach(icon => {
            icon.addEventListener('click', () => {
                document.querySelectorAll('.icon-option').forEach(i => i.classList.remove('selected'));
                icon.classList.add('selected');
                document.getElementById('agentIcon').value = icon.dataset.icon;
                updatePreview();
            });
        });

        // 颜色选择
        document.querySelectorAll('.color-option').forEach(color => {
            color.addEventListener('click', () => {
                document.querySelectorAll('.color-option').forEach(c => c.classList.remove('selected'));
                color.classList.add('selected');
                document.getElementById('agentColor').value = color.dataset.color;
                updatePreview();
            });
        });

        // 更新预览
        function updatePreview() {
            const name = document.getElementById('agentName').value || '未命名智能体';
            const welcome = document.getElementById('welcomeMessage').value;
            const icon = document.getElementById('agentIcon').value;
            const color = document.getElementById('agentColor').value;

            document.getElementById('previewName').textContent = name;
            document.getElementById('previewAvatar').style.background = color;
            document.getElementById('previewAvatar').innerHTML = '<i class="fas ' + icon + '"></i>';
            if (welcome) {
                document.getElementById('previewDesc').textContent = welcome;
            }
        }

        // 实时更新
        document.getElementById('agentName').addEventListener('input', updatePreview);
        document.getElementById('welcomeMessage').addEventListener('input', updatePreview);

        // 加载模型列表
        async function loadModels() {
            const provider = document.getElementById('modelProvider').value;
            const modelSelect = document.getElementById('modelId');
            
            if (!provider) {
                modelSelect.innerHTML = '<option value="">先选择提供商</option>';
                return;
            }

            modelSelect.innerHTML = '<option value="">加载中...</option>';

            try {
                const response = await fetch('api/model_handler.php?action=getModels&provider=' + encodeURIComponent(provider));
                const data = await response.json();

                if (data.success && data.models) {
                    const currentModel = '<?php echo $agent['model_id'] ?? ''; ?>';
                    let html = '<option value="">选择模型</option>';
                    data.models.forEach(model => {
                        const selected = model.id === currentModel ? 'selected' : '';
                        html += `<option value="${model.id}" ${selected}>${model.name}</option>`;
                    });
                    modelSelect.innerHTML = html;
                } else {
                    modelSelect.innerHTML = '<option value="">暂无可用模型</option>';
                }
            } catch (error) {
                modelSelect.innerHTML = '<option value="">加载失败</option>';
            }
        }

        // 如果有已选提供商，加载模型
        <?php if ($agent && $agent['model_provider']): ?>
        loadModels();
        <?php endif; ?>

        // 获取表单数据
        function getAgentData() {
            const capabilities = [];
            document.querySelectorAll('#tab-role .checkbox-item input:checked').forEach(cb => {
                capabilities.push(cb.value);
            });

            const knowledgeBaseIds = [];
            document.querySelectorAll('.kb-item input:checked').forEach(cb => {
                knowledgeBaseIds.push(parseInt(cb.value));
            });

            return {
                name: document.getElementById('agentName').value,
                description: document.getElementById('agentDesc').value,
                icon: document.getElementById('agentIcon').value,
                color: document.getElementById('agentColor').value,
                role_name: document.getElementById('roleName').value,
                role_description: document.getElementById('roleDescription').value,
                personality: document.getElementById('personality').value,
                capabilities: capabilities,
                model_provider: document.getElementById('modelProvider').value,
                model_id: document.getElementById('modelId').value,
                temperature: parseFloat(document.getElementById('temperature').value),
                max_tokens: parseInt(document.getElementById('maxTokens').value),
                knowledge_base_ids: knowledgeBaseIds,
                welcome_message: document.getElementById('welcomeMessage').value
            };
        }

        // 保存智能体
        async function saveAgent(andDebug = false) {
            const data = getAgentData();

            if (!data.name.trim()) {
                showToast('请输入智能体名称', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', agentId ? 'updateAgent' : 'createAgent');
            if (agentId) formData.append('agent_id', agentId);
            formData.append('data', JSON.stringify(data));

            try {
                const response = await fetch('api/agent_handler.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    showToast('保存成功', 'success');
                    if (!agentId && result.agent_id) {
                        window.location.href = '?route=agent_editor&id=' + result.agent_id;
                    }
                } else {
                    showToast(result.error || '保存失败', 'error');
                }
            } catch (error) {
                showToast('保存失败: ' + error.message, 'error');
            }
        }

        // 部署智能体
        async function deployAgent() {
            if (!agentId) {
                showToast('请先保存智能体', 'error');
                return;
            }

            const data = getAgentData();
            if (!data.model_provider || !data.model_id) {
                showToast('请先配置AI模型', 'error');
                return;
            }

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
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(result.error || '部署失败', 'error');
                }
            } catch (error) {
                showToast('部署失败: ' + error.message, 'error');
            }
        }

        // 停用智能体
        async function undeployAgent() {
            if (!confirm('确定要停用此智能体吗？停用后外部用户将无法访问。')) return;

            const formData = new FormData();
            formData.append('action', 'undeployAgent');
            formData.append('agent_id', agentId);

            try {
                const response = await fetch('api/agent_handler.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    showToast('已停用', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(result.error || '操作失败', 'error');
                }
            } catch (error) {
                showToast('操作失败: ' + error.message, 'error');
            }
        }

        // 复制部署链接
        function copyDeployUrl() {
            const url = document.querySelector('.deploy-url').textContent.trim();
            navigator.clipboard.writeText(url).then(() => {
                showToast('链接已复制', 'success');
            });
        }

        // 发送消息
        async function sendMessage() {
            if (isLoading) return;

            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            if (!message) return;

            // 检查是否已配置模型
            const provider = document.getElementById('modelProvider').value;
            const model = document.getElementById('modelId').value;
            if (!provider || !model) {
                showToast('请先配置AI模型', 'error');
                return;
            }

            // 先保存智能体
            const data = getAgentData();
            let currentAgentId = agentId;

            if (!currentAgentId) {
                showToast('请先保存智能体', 'error');
                return;
            }

            // 添加用户消息
            addMessage(message, 'user');
            input.value = '';
            input.style.height = 'auto';

            // 显示加载状态
            isLoading = true;
            document.getElementById('sendBtn').disabled = true;
            const loadingId = addLoading();

            try {
                const formData = new FormData();
                formData.append('action', 'debug');
                formData.append('agent_id', currentAgentId);
                formData.append('message', message);

                const response = await fetch('api/agent_handler.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                removeLoading(loadingId);

                if (result.success) {
                    addMessage(result.message, 'assistant');
                } else {
                    addMessage('抱歉，发生了错误: ' + (result.error || '未知错误'), 'assistant');
                }
            } catch (error) {
                removeLoading(loadingId);
                addMessage('抱歉，请求失败: ' + error.message, 'assistant');
            } finally {
                isLoading = false;
                document.getElementById('sendBtn').disabled = false;
            }
        }

        // 添加消息到聊天区
        function addMessage(text, role) {
            const container = document.getElementById('chatMessages');
            
            // 如果是第一条消息，清空欢迎语
            if (container.querySelector('.welcome-message')) {
                container.innerHTML = '';
            }

            const icon = document.getElementById('agentIcon').value;
            const color = document.getElementById('agentColor').value;

            const div = document.createElement('div');
            div.className = 'chat-message ' + role;
            
            if (role === 'assistant') {
                div.innerHTML = `
                    <div class="message-avatar" style="background: ${color}20; color: ${color};">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div class="message-content">${escapeHtml(text)}</div>
                `;
            } else {
                div.innerHTML = `
                    <div class="message-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="message-content">${escapeHtml(text)}</div>
                `;
            }

            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }

        // 添加加载动画
        function addLoading() {
            const container = document.getElementById('chatMessages');
            const id = 'loading-' + Date.now();
            const icon = document.getElementById('agentIcon').value;
            const color = document.getElementById('agentColor').value;

            const div = document.createElement('div');
            div.className = 'chat-message assistant';
            div.id = id;
            div.innerHTML = `
                <div class="message-avatar" style="background: ${color}20; color: ${color};">
                    <i class="fas ${icon}"></i>
                </div>
                <div class="message-content">
                    <div class="typing-indicator">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            `;

            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
            return id;
        }

        function removeLoading(id) {
            const el = document.getElementById(id);
            if (el) el.remove();
        }

        // 清空聊天
        function clearChat() {
            const container = document.getElementById('chatMessages');
            const icon = document.getElementById('agentIcon').value;
            const color = document.getElementById('agentColor').value;
            const name = document.getElementById('agentName').value || '未命名智能体';
            const welcome = document.getElementById('welcomeMessage').value;

            container.innerHTML = `
                <div class="welcome-message">
                    <div class="agent-avatar-preview" style="background: ${color}">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div class="agent-name-preview">${name}</div>
                    <div class="agent-desc-preview">${welcome}</div>
                </div>
            `;
            currentSessionId = 'debug_' + Date.now();
        }

        // 处理键盘事件
        function handleKeyDown(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        }

        // 自动调整输入框高度
        document.getElementById('chatInput').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });

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

        // 知识库选择样式
        document.querySelectorAll('.kb-item input').forEach(cb => {
            cb.addEventListener('change', function() {
                this.closest('.kb-item').classList.toggle('selected', this.checked);
            });
        });

        // 复选框样式
        document.querySelectorAll('.checkbox-item input').forEach(cb => {
            cb.addEventListener('change', function() {
                this.closest('.checkbox-item').style.borderColor = this.checked ? '#4c51bf' : '#e5e7eb';
            });
        });
    </script>
</body>
</html>
