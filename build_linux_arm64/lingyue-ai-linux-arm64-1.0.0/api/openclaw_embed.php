<?php
/**
 * OpenClaw 龙虾智能体嵌入页面
 * 用于iframe嵌入到其他网站
 */

$token = $_GET['token'] ?? '';

if (empty($token)) {
    die('缺少Token参数');
}

require_once __DIR__ . '/../includes/Database.php';

try {
    $db = Database::getInstance();
    
    // 验证token
    $agent = $db->fetch("
        SELECT a.*, u.username as owner_name 
        FROM openclaw_agents a 
        JOIN openclaw_deployments d ON a.id = d.agent_id
        JOIN users u ON a.user_id = u.id
        WHERE d.token = ? AND d.status = 'active'
    ", [$token]);
    
    if (!$agent) {
        die('无效的Token或龙虾未部署');
    }
    
} catch (Exception $e) {
    die('系统错误: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($agent['name']); ?> - AI助手</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .avatar {
            font-size: 32px;
        }
        
        .info h3 {
            font-size: 16px;
            font-weight: 600;
        }
        
        .info p {
            font-size: 12px;
            opacity: 0.8;
        }
        
        .chat-container {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
        }
        
        .message {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }
        
        .message.user {
            flex-direction: row-reverse;
        }
        
        .message-bubble {
            max-width: 80%;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .message.lobster .message-bubble {
            background: white;
            border: 1px solid #e2e8f0;
            color: #1a202c;
        }
        
        .message.user .message-bubble {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .input-area {
            padding: 12px 16px;
            background: white;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 8px;
        }
        
        .input-area input {
            flex: 1;
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 20px;
            font-size: 14px;
            outline: none;
        }
        
        .input-area input:focus {
            border-color: #667eea;
        }
        
        .input-area button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .input-area button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #e2e8f0;
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .welcome {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
        }
        
        .welcome .avatar {
            font-size: 64px;
            margin-bottom: 16px;
        }
        
        .welcome h4 {
            color: #1a202c;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="avatar"><?php echo htmlspecialchars($agent['avatar']); ?></div>
        <div class="info">
            <h3><?php echo htmlspecialchars($agent['name']); ?></h3>
            <p>Lv.<?php echo $agent['level']; ?> · 由 <?php echo htmlspecialchars($agent['owner_name']); ?> 训练</p>
        </div>
    </div>
    
    <div class="chat-container" id="chatContainer">
        <div class="welcome">
            <div class="avatar"><?php echo htmlspecialchars($agent['avatar']); ?></div>
            <h4>你好！我是 <?php echo htmlspecialchars($agent['name']); ?></h4>
            <p><?php echo htmlspecialchars($agent['personality'] ?? '友好、乐于助人'); ?></p>
        </div>
    </div>
    
    <div class="input-area">
        <input type="text" id="messageInput" placeholder="输入消息..." onkeypress="if(event.key==='Enter') sendMessage()">
        <button id="sendBtn" onclick="sendMessage()">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const token = '<?php echo $token; ?>';
        let isLoading = false;
        
        function sendMessage() {
            if (isLoading) return;
            
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            // 添加用户消息
            addMessage(message, 'user');
            input.value = '';
            
            // 显示加载状态
            isLoading = true;
            document.getElementById('sendBtn').disabled = true;
            document.getElementById('sendBtn').innerHTML = '<div class="loading"></div>';
            
            // 调用API
            $.ajax({
                url: 'openclaw_api.php?action=chat&token=' + token,
                method: 'POST',
                data: { message: message },
                success: function(res) {
                    if (res.success) {
                        addMessage(res.response, 'lobster');
                    } else {
                        addMessage('抱歉，出现了错误：' + (res.error || '未知错误'), 'lobster');
                    }
                },
                error: function() {
                    addMessage('抱歉，网络连接失败，请稍后重试。', 'lobster');
                },
                complete: function() {
                    isLoading = false;
                    document.getElementById('sendBtn').disabled = false;
                    document.getElementById('sendBtn').innerHTML = '<i class="fas fa-paper-plane"></i>';
                }
            });
        }
        
        function addMessage(text, type) {
            const container = document.getElementById('chatContainer');
            const div = document.createElement('div');
            div.className = 'message ' + type;
            div.innerHTML = '<div class="message-bubble">' + escapeHtml(text) + '</div>';
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
