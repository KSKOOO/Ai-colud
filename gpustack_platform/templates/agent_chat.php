<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/AgentManager.php';

$db = Database::getInstance();
$agentManager = new AgentManager($db);

$token = $_GET['token'] ?? '';
$agentId = $_GET['id'] ?? 0;
$agent = null;

if ($token) {
    $agent = $agentManager->getAgentByToken($token);
} elseif ($agentId && isset($_SESSION['user']['id'])) {
    // 如果用户已登录，可以通过ID访问自己的智能体
    $agent = $agentManager->getAgent($agentId);
    if ($agent && $agent['user_id'] != $_SESSION['user']['id']) {
        $agent = null; // 无权访问
    }
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
                            'code_execution' => ['icon' => 'fa-code', 'name' => '代码执行'],
                            'knowledge_query' => ['icon' => 'fa-database', 'name' => '知识库'],
                            'image_generation' => ['icon' => 'fa-image', 'name' => '图像生成'],
                            'task_planning' => ['icon' => 'fa-tasks', 'name' => '任务规划'],
                            'data_analysis' => ['icon' => 'fa-chart-bar', 'name' => '数据分析'],
                            'translation' => ['icon' => 'fa-language', 'name' => '翻译'],
                            'summarization' => ['icon' => 'fa-file-text', 'name' => '摘要']
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
            <div style="display: flex; gap: 8px; margin-bottom: 8px; flex-wrap: wrap;">
                <button onclick="executeTask()" class="task-btn" style="padding: 6px 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 16px; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-play-circle"></i> 执行任务
                </button>
                <button onclick="viewTasks()" class="task-btn" style="padding: 6px 12px; background: #f3f4f6; color: #4b5563; border: none; border-radius: 16px; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-tasks"></i> 任务列表
                </button>
                <button onclick="showPerformance()" class="task-btn" style="padding: 6px 12px; background: #f3f4f6; color: #4b5563; border: none; border-radius: 16px; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-chart-line"></i> 绩效评估
                </button>
            </div>

            <!-- 已选文件显示 -->
            <div id="selectedFiles" style="display: none; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;"></div>

            <div class="input-container">
                <button onclick="selectFile()" class="file-btn" style="padding: 8px; background: transparent; border: none; cursor: pointer; color: #6b7280; font-size: 18px;" title="上传文件">
                    <i class="fas fa-paperclip"></i>
                </button>
                <input type="file" id="fileInput" style="display: none;" multiple accept=".txt,.doc,.docx,.pdf,.jpg,.jpeg,.png,.gif" onchange="handleFileSelect(event)">
                <textarea class="chat-input" id="chatInput" rows="1"
                          placeholder="输入消息或任务描述..."
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

            // 如果没有消息且没有文件，直接返回
            if (!message && selectedFiles.length === 0) return;

            // 检查是否重复发送相同消息（2秒内）
            const currentHash = getMessageHash(message + selectedFiles.length);
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

            // 添加用户消息（包含文件信息）
            let displayMessage = message;
            if (selectedFiles.length > 0) {
                const fileNames = selectedFiles.map(f => `[${f.name}]`).join(' ');
                displayMessage = message ? `${message}\n${fileNames}` : fileNames;
            }
            addMessage(displayMessage, 'user');
            input.value = '';
            input.style.height = 'auto';

            // 显示加载状态
            isLoading = true;
            document.getElementById('sendBtn').disabled = true;
            const loadingId = addLoading();

            try {
                const formData = new FormData();
                formData.append('action', 'chat');
                formData.append('agent_id', agentId);
                formData.append('token', token);
                formData.append('message', message);
                formData.append('session_id', sessionId);

                // 添加文件
                selectedFiles.forEach((file, index) => {
                    formData.append(`file_${index}`, file);
                });
                formData.append('file_count', selectedFiles.length);

                // 清空已选文件
                selectedFiles = [];
                updateSelectedFilesDisplay();

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

        // 选中的文件列表
        let selectedFiles = [];

        // 选择文件
        function selectFile() {
            document.getElementById('fileInput').click();
        }

        // 处理文件选择
        function handleFileSelect(event) {
            const files = Array.from(event.target.files);
            files.forEach(file => {
                selectedFiles.push(file);
            });
            updateSelectedFilesDisplay();
        }

        // 更新已选文件显示
        function updateSelectedFilesDisplay() {
            const container = document.getElementById('selectedFiles');
            if (selectedFiles.length === 0) {
                container.style.display = 'none';
                container.innerHTML = '';
                return;
            }

            container.style.display = 'flex';
            container.innerHTML = selectedFiles.map((file, index) => `
                <div style="display: flex; align-items: center; gap: 6px; padding: 4px 10px; background: #e0e7ff; border-radius: 16px; font-size: 12px; color: #4c51bf;">
                    <i class="fas ${getFileIcon(file.name)}"></i>
                    <span style="max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${file.name}</span>
                    <span style="color: #6b7280;">(${formatFileSize(file.size)})</span>
                    <button onclick="removeSelectedFile(${index})" style="background: none; border: none; cursor: pointer; color: #ef4444; padding: 0; margin-left: 4px;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `).join('');
        }

        // 移除已选文件
        function removeSelectedFile(index) {
            selectedFiles.splice(index, 1);
            updateSelectedFilesDisplay();
        }

        // 获取文件图标
        function getFileIcon(filename) {
            const ext = filename.split('.').pop().toLowerCase();
            const iconMap = {
                'pdf': 'fa-file-pdf',
                'doc': 'fa-file-word',
                'docx': 'fa-file-word',
                'txt': 'fa-file-alt',
                'jpg': 'fa-file-image',
                'jpeg': 'fa-file-image',
                'png': 'fa-file-image',
                'gif': 'fa-file-image'
            };
            return iconMap[ext] || 'fa-file';
        }

        // 格式化文件大小
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        }

        // 页面加载完成后聚焦输入框
        window.addEventListener('load', () => {
            document.getElementById('chatInput').focus();
        });

        // ==================== AI员工任务功能 ====================
        
        // 执行任务
        async function executeTask() {
            const input = document.getElementById('chatInput');
            const taskDescription = input.value.trim();
            
            if (!taskDescription) {
                alert('请先输入任务描述');
                return;
            }
            
            if (!confirm('确定要让AI员工执行这个任务吗？\n\n任务描述: ' + taskDescription.substring(0, 100) + (taskDescription.length > 100 ? '...' : ''))) {
                return;
            }
            
            // 隐藏欢迎界面
            const welcomeSection = document.querySelector('.welcome-section');
            if (welcomeSection) {
                welcomeSection.style.display = 'none';
                document.getElementById('quickReplies').style.display = 'none';
            }
            
            // 添加任务开始消息
            addMessage('【任务执行】' + taskDescription, 'user');
            input.value = '';
            input.style.height = 'auto';
            
            // 显示任务执行中状态
            const loadingId = addTaskLoading();
            
            try {
                const response = await fetch('api/agent_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=executeTask&agent_id=${agentId}&task_description=${encodeURIComponent(taskDescription)}&task_type=general`
                });
                
                const result = await response.json();
                removeLoading(loadingId);
                
                if (result.success) {
                    // 显示执行计划
                    addMessage('【执行计划】\n' + result.execution_plan, 'assistant');
                    
                    // 显示执行结果
                    setTimeout(() => {
                        addMessage('【执行结果】\n' + result.execution_result, 'assistant');
                        
                        // 显示总结
                        if (result.summary) {
                            setTimeout(() => {
                                addMessage('【任务总结】\n' + result.summary + '\n\n任务ID: ' + result.task_id, 'assistant');
                            }, 500);
                        }
                    }, 500);
                } else {
                    addMessage('任务执行失败: ' + (result.error || '未知错误'), 'assistant');
                }
            } catch (error) {
                removeLoading(loadingId);
                addMessage('任务执行出错: ' + error.message, 'assistant');
            }
        }
        
        // 添加任务加载动画
        function addTaskLoading() {
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
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="typing-indicator">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <span style="color: #6b7280; font-size: 13px;">AI员工正在执行任务...</span>
                    </div>
                </div>
            `;
            
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
            return id;
        }
        
        // 查看任务列表
        async function viewTasks() {
            try {
                const response = await fetch(`api/agent_handler.php?action=getTasks&agent_id=${agentId}`);
                const result = await response.json();
                
                if (result.success) {
                    let taskList = '【任务列表】\n\n';
                    if (result.tasks && result.tasks.length > 0) {
                        result.tasks.forEach((task, index) => {
                            const statusIcon = {
                                'pending': '⏳',
                                'in_progress': '🔄',
                                'completed': '✅',
                                'failed': '❌',
                                'cancelled': '🚫'
                            }[task.status] || '❓';
                            
                            taskList += `${index + 1}. ${statusIcon} ${task.title}\n`;
                            taskList += `   状态: ${task.status} | 进度: ${task.progress}%\n`;
                            taskList += `   创建时间: ${task.created_at}\n\n`;
                        });
                    } else {
                        taskList += '暂无任务记录\n';
                    }
                    
                    // 隐藏欢迎界面
                    const welcomeSection = document.querySelector('.welcome-section');
                    if (welcomeSection) {
                        welcomeSection.style.display = 'none';
                        document.getElementById('quickReplies').style.display = 'none';
                    }
                    
                    addMessage(taskList, 'assistant');
                } else {
                    alert('获取任务列表失败: ' + (result.error || '未知错误'));
                }
            } catch (error) {
                alert('获取任务列表出错: ' + error.message);
            }
        }
        
        // 显示绩效评估
        async function showPerformance() {
            try {
                const response = await fetch(`api/agent_handler.php?action=getPerformanceStats&agent_id=${agentId}`);
                const result = await response.json();
                
                if (result.success) {
                    const stats = result.stats;
                    let performance = '【AI员工绩效报告】\n\n';
                    
                    if (stats.total_reviews > 0) {
                        performance += `⭐ 平均评分: ${parseFloat(stats.avg_rating).toFixed(1)}/5.0\n`;
                        performance += `📊 任务质量: ${parseFloat(stats.avg_quality).toFixed(1)}/5.0\n`;
                        performance += `⚡ 响应速度: ${parseFloat(stats.avg_speed).toFixed(1)}/5.0\n`;
                        performance += `💡 有用性: ${parseFloat(stats.avg_helpfulness).toFixed(1)}/5.0\n`;
                        performance += `📝 评价次数: ${stats.total_reviews}次\n\n`;
                        
                        if (result.recent_reviews && result.recent_reviews.length > 0) {
                            performance += '【最新评价】\n';
                            result.recent_reviews.slice(0, 3).forEach((review, index) => {
                                performance += `${index + 1}. ⭐${review.rating} - ${review.feedback || '无文字评价'}\n`;
                            });
                        }
                    } else {
                        performance += '暂无绩效评价数据\n\n';
                        performance += '使用AI员工完成任务后，可以对其进行评价。';
                    }
                    
                    // 隐藏欢迎界面
                    const welcomeSection = document.querySelector('.welcome-section');
                    if (welcomeSection) {
                        welcomeSection.style.display = 'none';
                        document.getElementById('quickReplies').style.display = 'none';
                    }
                    
                    addMessage(performance, 'assistant');
                } else {
                    alert('获取绩效数据失败: ' + (result.error || '未知错误'));
                }
            } catch (error) {
                alert('获取绩效数据出错: ' + error.message);
            }
        }
    </script>
</body>
</html>
