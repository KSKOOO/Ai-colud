<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/AgentManager.php';

$db = Database::getInstance();
$agentManager = new AgentManager($db);

$token = $_GET['token'] ?? '';
$agent = null;

if ($token) {
    $agent = $agentManager->getAgentByToken($token);
}

if (!$agent) {
    header('HTTP/1.1 404 Not Found');
    echo '<h1>智能体不存在或未部署</h1>';
    exit;
}

// 生成或获取会话ID
$sessionId = $_SESSION['agent_session_' . $agent['id']] ?? uniqid('sess_');
$_SESSION['agent_session_' . $agent['id']] = $sessionId;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($agent['name']); ?></title>
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
            height: 100vh;
            overflow: hidden;
        }

        .chat-app {
            height: 100vh;
            display: flex;
            flex-direction: column;
            max-width: 900px;
            margin: 0 auto;
            background: white;
        }

        /* 头部 */
        .chat-header {
            background: <?php echo $agent['color']; ?>;
            color: white;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .header-info {
            flex: 1;
            min-width: 0;
        }

        .header-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .header-desc {
            font-size: 13px;
            opacity: 0.9;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .header-status {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            background: rgba(255,255,255,0.2);
            padding: 6px 12px;
            border-radius: 20px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: #4ade80;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* 消息区 */
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8fafc;
        }

        .welcome-section {
            text-align: center;
            padding: 40px 20px;
        }

        .welcome-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: <?php echo $agent['color']; ?>;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            margin: 0 auto 24px;
            box-shadow: 0 8px 30px <?php echo $agent['color']; ?>40;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .welcome-title {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 12px;
        }

        .welcome-text {
            font-size: 15px;
            color: #6b7280;
            line-height: 1.8;
            max-width: 500px;
            margin: 0 auto;
        }

        .capabilities {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
        }

        .capability-tag {
            background: white;
            border: 1px solid #e5e7eb;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            color: #4b5563;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .capability-tag i {
            color: <?php echo $agent['color']; ?>;
        }

        /* 消息气泡 */
        .message {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            animation: messageIn 0.3s ease;
        }

        @keyframes messageIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.user {
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .message.assistant .message-avatar {
            background: <?php echo $agent['color']; ?>20;
            color: <?php echo $agent['color']; ?>;
        }

        .message.user .message-avatar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .message-content {
            max-width: 70%;
            padding: 14px 18px;
            border-radius: 18px;
            font-size: 15px;
            line-height: 1.6;
            word-wrap: break-word;
        }

        .message.assistant .message-content {
            background: white;
            color: #1f2937;
            border-bottom-left-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .message.user .message-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 4px;
        }

        /* 输入区 */
        .chat-input-area {
            background: white;
            border-top: 1px solid #e5e7eb;
            padding: 16px 20px;
        }

        .input-container {
            display: flex;
            gap: 12px;
            align-items: flex-end;
            background: #f3f4f6;
            border-radius: 24px;
            padding: 8px 8px 8px 20px;
        }

        .chat-input {
            flex: 1;
            border: none;
            background: transparent;
            font-size: 15px;
            resize: none;
            max-height: 120px;
            min-height: 24px;
            padding: 8px 0;
            font-family: inherit;
        }

        .chat-input:focus {
            outline: none;
        }

        .chat-input::placeholder {
            color: #9ca3af;
        }

        .send-button {
            width: 44px;
            height: 44px;
            border: none;
            border-radius: 50%;
            background: <?php echo $agent['color']; ?>;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .send-button:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px <?php echo $agent['color']; ?>60;
        }

        .send-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* 加载动画 */
        .typing-indicator {
            display: flex;
            gap: 4px;
            padding: 14px 18px;
        }

        .typing-indicator span {
            width: 8px;
            height: 8px;
            background: #cbd5e1;
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }

        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-10px); }
        }

        /* 底部信息 */
        .chat-footer {
            text-align: center;
            padding: 8px;
            font-size: 12px;
            color: #9ca3af;
            background: white;
            border-top: 1px solid #f3f4f6;
        }

        .chat-footer a {
            color: <?php echo $agent['color']; ?>;
            text-decoration: none;
        }

        /* 快速回复按钮 */
        .quick-replies {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 0 20px 16px;
            background: #f8fafc;
        }

        .quick-reply {
            background: white;
            border: 1px solid #e5e7eb;
            padding: 8px 16px;
            border-radius: 18px;
            font-size: 13px;
            color: #4b5563;
            cursor: pointer;
            transition: all 0.2s;
        }

        .quick-reply:hover {
            border-color: <?php echo $agent['color']; ?>;
            color: <?php echo $agent['color']; ?>;
            background: <?php echo $agent['color']; ?>08;
        }

        /* 响应式 */
        @media (max-width: 640px) {
            .chat-app {
                max-width: 100%;
            }
            
            .message-content {
                max-width: 80%;
                font-size: 14px;
            }
            
            .header-desc {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="chat-app">
        <!-- 头部 -->
        <header class="chat-header">
            <div class="header-icon">
                <i class="fas <?php echo $agent['icon']; ?>"></i>
            </div>
            <div class="header-info">
                <div class="header-name"><?php echo htmlspecialchars($agent['name']); ?></div>
                <div class="header-desc"><?php echo htmlspecialchars($agent['description'] ?: $agent['role_name'] ?: 'AI 智能助手'); ?></div>
            </div>
            <div class="header-status">
                <span class="status-dot"></span>
                在线
            </div>
        </header>

        <!-- 消息区 -->
        <div class="chat-messages" id="chatMessages">
            <div class="welcome-section">
                <div class="welcome-avatar">
                    <i class="fas <?php echo $agent['icon']; ?>"></i>
                </div>
                <h1 class="welcome-title"><?php echo htmlspecialchars($agent['name']); ?></h1>
                <p class="welcome-text"><?php echo nl2br(htmlspecialchars($agent['welcome_message'])); ?></p>
                
                <?php 
                $capabilities = $agent['capabilities'] ?? [];
                if (!empty($capabilities)): 
                ?>
                <div class="capabilities">
                    <?php foreach ($capabilities as $cap): 
                        $capInfo = [
                            'chat' => ['icon' => 'fa-comments', 'name' => '对话'],
                            'file_analysis' => ['icon' => 'fa-file-alt', 'name' => '文件分析'],
                            'web_search' => ['icon' => 'fa-search', 'name' => '网络搜索'],
                            'code_execution' => ['icon' => 'fa-code', 'name' => '代码执行']
                        ][$cap] ?? ['icon' => 'fa-check', 'name' => $cap];
                    ?>
                    <div class="capability-tag">
                        <i class="fas <?php echo $capInfo['icon']; ?>"></i>
                        <?php echo $capInfo['name']; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 快速回复 -->
        <div class="quick-replies" id="quickReplies">
            <button class="quick-reply" onclick="sendQuickReply('你好，请介绍一下你自己')">👋 自我介绍</button>
            <button class="quick-reply" onclick="sendQuickReply('你能帮我做什么？')">💡 能做什么</button>
            <button class="quick-reply" onclick="sendQuickReply('开始吧')">🚀 开始</button>
        </div>

        <!-- 输入区 -->
        <div class="chat-input-area">
            <div class="input-container">
                <textarea class="chat-input" id="chatInput" rows="1" 
                          placeholder="输入消息..." 
                          onkeydown="handleKeyDown(event)"></textarea>
                <button class="send-button" id="sendBtn" onclick="sendMessage()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>

        <!-- 底部 -->
        <div class="chat-footer">
            由 <a href="?route=home" target="_blank">巨神兵API辅助平台API辅助平台</a> 提供技术支持
        </div>
    </div>

    <script>
        const agentId = <?php echo $agent['id']; ?>;
        const sessionId = '<?php echo $sessionId; ?>';
        const token = '<?php echo $token; ?>';
        let isLoading = false;
        let lastMessageHash = '';
        let requestController = null;

        // 自动调整输入框高度
        const chatInput = document.getElementById('chatInput');
        chatInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });

        // 处理键盘事件
        function handleKeyDown(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        }

        // 发送快捷回复
        function sendQuickReply(text) {
            document.getElementById('chatInput').value = text;
            sendMessage();
        }

        // 生成消息哈希用于去重
        function getMessageHash(message) {
            return message + '_' + Date.now().toString().slice(0, -3);
        }

        // 发送消息
        async function sendMessage() {
            if (isLoading) {
                console.log('正在处理中，请稍候...');
                return;
            }

            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            if (!message) return;

            // 检查是否重复发送相同消息（2秒内）
            const currentHash = getMessageHash(message);
            if (currentHash === lastMessageHash) {
                console.log('检测到重复消息，忽略');
                return;
            }
            lastMessageHash = currentHash;

            // 取消之前的请求
            if (requestController) {
                requestController.abort();
            }
            requestController = new AbortController();

            // 隐藏欢迎界面和快捷回复
            const welcomeSection = document.querySelector('.welcome-section');
            if (welcomeSection) {
                welcomeSection.style.display = 'none';
                document.getElementById('quickReplies').style.display = 'none';
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
                formData.append('action', 'chat');
                formData.append('token', token);
                formData.append('message', message);
                formData.append('session_id', sessionId);

                const response = await fetch('api/agent_handler.php', {
                    method: 'POST',
                    body: formData,
                    signal: requestController.signal
                });
                
                if (!response.ok) {
                    throw new Error('网络请求失败: ' + response.status);
                }
                
                const result = await response.json();

                removeLoading(loadingId);

                if (result.success) {
                    addMessage(result.message, 'assistant');
                } else {
                    addMessage('抱歉，发生了错误: ' + (result.error || '未知错误'), 'assistant');
                }
            } catch (error) {
                if (error.name === 'AbortError') {
                    console.log('请求被取消');
                    return;
                }
                removeLoading(loadingId);
                addMessage('抱歉，请求失败: ' + error.message, 'assistant');
            } finally {
                isLoading = false;
                document.getElementById('sendBtn').disabled = false;
                requestController = null;
            }
        }

        // 添加消息
        function addMessage(text, role) {
            const container = document.getElementById('chatMessages');
            
            const div = document.createElement('div');
            div.className = 'message ' + role;
            
            if (role === 'assistant') {
                div.innerHTML = `
                    <div class="message-avatar">
                        <i class="fas <?php echo $agent['icon']; ?>"></i>
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

            const div = document.createElement('div');
            div.className = 'message assistant';
            div.id = id;
            div.innerHTML = `
                <div class="message-avatar">
                    <i class="fas <?php echo $agent['icon']; ?>"></i>
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

        // HTML转义
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // 页面加载完成后聚焦输入框
        window.addEventListener('load', () => {
            document.getElementById('chatInput').focus();
        });
    </script>
</body>
</html>
