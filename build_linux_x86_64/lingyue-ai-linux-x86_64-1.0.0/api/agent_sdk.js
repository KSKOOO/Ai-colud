/**
 * 智能体嵌入SDK
 * 使用方式:
 * <script src="https://your-domain.com/api/agent_sdk.js?token=YOUR_AGENT_TOKEN"></script>
 * <script>
 *   AgentSDK.init({
 *     position: 'bottom-right',
 *     theme: 'light'
 *   });
 * </script>
 */

(function() {
    'use strict';
    
    // 获取当前脚本URL和token
    const currentScript = document.currentScript || document.querySelector('script[src*="agent_sdk.js"]');
    const scriptUrl = new URL(currentScript.src);
    const API_BASE = scriptUrl.origin + '/gpustack_platform';
    const TOKEN = scriptUrl.searchParams.get('token') || '';
    
    if (!TOKEN) {
        console.error('[AgentSDK] 错误: 缺少Token参数');
        return;
    }
    
    // SDK配置
    const config = {
        position: 'bottom-right',
        theme: 'light',
        width: 380,
        height: 600,
        title: 'AI助手',
        placeholder: '输入消息...',
        ...window.AgentSDKConfig
    };
    
    // 会话ID
    let sessionId = localStorage.getItem('agent_session_' + TOKEN) || generateSessionId();
    localStorage.setItem('agent_session_' + TOKEN, sessionId);
    
    // 智能体信息
    let agentInfo = null;
    
    // 生成会话ID
    function generateSessionId() {
        return 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    // 创建样式
    function injectStyles() {
        const styles = `
            .agent-widget {
                position: fixed;
                z-index: 999999;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            
            .agent-widget.bottom-right { bottom: 20px; right: 20px; }
            .agent-widget.bottom-left { bottom: 20px; left: 20px; }
            .agent-widget.top-right { top: 20px; right: 20px; }
            .agent-widget.top-left { top: 20px; left: 20px; }
            
            .agent-launcher {
                width: 60px;
                height: 60px;
                border-radius: 50%;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                cursor: pointer;
                box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
                transition: all 0.3s ease;
            }
            
            .agent-launcher:hover {
                transform: scale(1.1);
                box-shadow: 0 6px 30px rgba(102, 126, 234, 0.5);
            }
            
            .agent-chat {
                position: absolute;
                bottom: 80px;
                right: 0;
                width: ${config.width}px;
                height: ${config.height}px;
                background: white;
                border-radius: 16px;
                box-shadow: 0 10px 50px rgba(0,0,0,0.2);
                display: none;
                flex-direction: column;
                overflow: hidden;
                animation: agentSlideIn 0.3s ease;
            }
            
            .agent-chat.active {
                display: flex;
            }
            
            @keyframes agentSlideIn {
                from {
                    opacity: 0;
                    transform: translateY(20px) scale(0.95);
                }
                to {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
            }
            
            .agent-header {
                padding: 16px 20px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                display: flex;
                align-items: center;
                gap: 12px;
            }
            
            .agent-avatar {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background: rgba(255,255,255,0.2);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
            }
            
            .agent-info {
                flex: 1;
            }
            
            .agent-name {
                font-weight: 600;
                font-size: 16px;
            }
            
            .agent-status {
                font-size: 12px;
                opacity: 0.8;
            }
            
            .agent-close {
                background: none;
                border: none;
                color: white;
                font-size: 20px;
                cursor: pointer;
                opacity: 0.8;
                transition: opacity 0.2s;
            }
            
            .agent-close:hover {
                opacity: 1;
            }
            
            .agent-messages {
                flex: 1;
                overflow-y: auto;
                padding: 20px;
                background: #f8fafc;
            }
            
            .agent-message {
                display: flex;
                gap: 10px;
                margin-bottom: 16px;
                animation: agentMessageIn 0.3s ease;
            }
            
            @keyframes agentMessageIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .agent-message.user {
                flex-direction: row-reverse;
            }
            
            .agent-message-avatar {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                background: #e2e8f0;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 14px;
                flex-shrink: 0;
            }
            
            .agent-message.user .agent-message-avatar {
                background: #667eea;
                color: white;
            }
            
            .agent-message-content {
                max-width: 70%;
                padding: 12px 16px;
                border-radius: 16px;
                font-size: 14px;
                line-height: 1.5;
                word-break: break-word;
            }
            
            .agent-message.agent .agent-message-content {
                background: white;
                color: #1a202c;
                border: 1px solid #e2e8f0;
            }
            
            .agent-message.user .agent-message-content {
                background: #667eea;
                color: white;
            }
            
            .agent-typing {
                display: none;
                align-items: center;
                gap: 4px;
                padding: 12px 16px;
                background: white;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                width: fit-content;
            }
            
            .agent-typing.active {
                display: flex;
            }
            
            .agent-typing-dot {
                width: 8px;
                height: 8px;
                background: #cbd5e1;
                border-radius: 50%;
                animation: agentTyping 1.4s infinite;
            }
            
            .agent-typing-dot:nth-child(2) { animation-delay: 0.2s; }
            .agent-typing-dot:nth-child(3) { animation-delay: 0.4s; }
            
            @keyframes agentTyping {
                0%, 60%, 100% { transform: translateY(0); }
                30% { transform: translateY(-10px); }
            }
            
            .agent-input-area {
                padding: 16px 20px;
                background: white;
                border-top: 1px solid #e2e8f0;
                display: flex;
                gap: 10px;
            }
            
            .agent-input {
                flex: 1;
                padding: 12px 16px;
                border: 1px solid #e2e8f0;
                border-radius: 24px;
                font-size: 14px;
                outline: none;
                resize: none;
                min-height: 44px;
                max-height: 120px;
                font-family: inherit;
            }
            
            .agent-input:focus {
                border-color: #667eea;
            }
            
            .agent-send {
                width: 44px;
                height: 44px;
                border-radius: 50%;
                background: #667eea;
                color: white;
                border: none;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s;
            }
            
            .agent-send:hover {
                background: #5a67d8;
            }
            
            .agent-send:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
            
            .agent-powered {
                text-align: center;
                padding: 8px;
                font-size: 11px;
                color: #94a3b8;
                background: #f8fafc;
            }
            
            .agent-powered a {
                color: #667eea;
                text-decoration: none;
            }
            
            /* 移动端适配 */
            @media (max-width: 480px) {
                .agent-chat {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    width: 100%;
                    height: 100%;
                    border-radius: 0;
                }
                
                .agent-launcher {
                    width: 50px;
                    height: 50px;
                    font-size: 20px;
                }
            }
        `;
        
        const styleEl = document.createElement('style');
        styleEl.textContent = styles;
        document.head.appendChild(styleEl);
    }
    
    // 创建聊天界面
    function createChatWidget() {
        const widget = document.createElement('div');
        widget.className = `agent-widget ${config.position}`;
        widget.innerHTML = `
            <div class="agent-chat" id="agentChat">
                <div class="agent-header">
                    <div class="agent-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="agent-info">
                        <div class="agent-name" id="agentName">${config.title}</div>
                        <div class="agent-status">在线</div>
                    </div>
                    <button class="agent-close" onclick="AgentSDK.toggle()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="agent-messages" id="agentMessages"></div>
                <div class="agent-typing" id="agentTyping">
                    <div class="agent-typing-dot"></div>
                    <div class="agent-typing-dot"></div>
                    <div class="agent-typing-dot"></div>
                </div>
                <div class="agent-input-area">
                    <textarea class="agent-input" id="agentInput" placeholder="${config.placeholder}" rows="1"></textarea>
                    <button class="agent-send" id="agentSend" onclick="AgentSDK.send()">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                <div class="agent-powered">
                    Powered by <a href="${API_BASE}" target="_blank">巨神兵API辅助平台AI</a>
                </div>
            </div>
            <button class="agent-launcher" id="agentLauncher" onclick="AgentSDK.toggle()">
                <i class="fas fa-comments"></i>
            </button>
        `;
        
        document.body.appendChild(widget);
        
        // 绑定回车发送
        const input = document.getElementById('agentInput');
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                AgentSDK.send();
            }
        });
        
        // 自动调整输入框高度
        input.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });
    }
    
    // 添加消息到界面
    function addMessage(content, role) {
        const messagesContainer = document.getElementById('agentMessages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `agent-message ${role}`;
        
        const avatar = role === 'user' ? '<i class="fas fa-user"></i>' : '<i class="fas fa-robot"></i>';
        const agentName = agentInfo ? agentInfo.name : 'AI助手';
        
        messageDiv.innerHTML = `
            <div class="agent-message-avatar">${avatar}</div>
            <div class="agent-message-content">${escapeHtml(content)}</div>
        `;
        
        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    // HTML转义
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML.replace(/\n/g, '<br>');
    }
    
    // 显示/隐藏输入中状态
    function showTyping(show) {
        const typing = document.getElementById('agentTyping');
        if (show) {
            typing.classList.add('active');
        } else {
            typing.classList.remove('active');
        }
    }
    
    // 获取智能体信息
    async function loadAgentInfo() {
        try {
            const response = await fetch(`${API_BASE}/api/agent_api.php?token=${TOKEN}&action=info`);
            const data = await response.json();
            
            if (data.success) {
                agentInfo = data.agent;
                document.getElementById('agentName').textContent = agentInfo.name;
                
                // 显示欢迎消息
                if (agentInfo.welcome_message) {
                    addMessage(agentInfo.welcome_message, 'agent');
                }
            }
        } catch (error) {
            console.error('[AgentSDK] 加载智能体信息失败:', error);
        }
    }
    
    // 发送消息
    async function sendMessage() {
        const input = document.getElementById('agentInput');
        const sendBtn = document.getElementById('agentSend');
        const message = input.value.trim();
        
        if (!message) return;
        
        // 显示用户消息
        addMessage(message, 'user');
        input.value = '';
        input.style.height = 'auto';
        
        // 禁用发送按钮
        sendBtn.disabled = true;
        showTyping(true);
        
        try {
            const response = await fetch(`${API_BASE}/api/agent_api.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `token=${TOKEN}&action=chat&message=${encodeURIComponent(message)}&session_id=${sessionId}`
            });
            
            const data = await response.json();
            showTyping(false);
            
            if (data.success) {
                addMessage(data.response, 'agent');
            } else {
                addMessage('抱歉，发生了错误: ' + (data.error || '未知错误'), 'agent');
            }
        } catch (error) {
            showTyping(false);
            addMessage('抱歉，网络连接失败，请稍后重试。', 'agent');
            console.error('[AgentSDK] 发送消息失败:', error);
        }
        
        sendBtn.disabled = false;
    }
    
    // SDK公共接口
    window.AgentSDK = {
        init: function(userConfig) {
            Object.assign(config, userConfig);
            
            // 加载Font Awesome
            if (!document.querySelector('link[href*="font-awesome"]')) {
                const fa = document.createElement('link');
                fa.rel = 'stylesheet';
                fa.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
                document.head.appendChild(fa);
            }
            
            injectStyles();
            createChatWidget();
            loadAgentInfo();
            
            console.log('[AgentSDK] 初始化完成');
        },
        
        toggle: function() {
            const chat = document.getElementById('agentChat');
            chat.classList.toggle('active');
        },
        
        send: function() {
            sendMessage();
        },
        
        // 程序化发送消息
        chat: function(message) {
            if (message) {
                document.getElementById('agentInput').value = message;
                sendMessage();
            }
        },
        
        // 销毁组件
        destroy: function() {
            const widget = document.querySelector('.agent-widget');
            if (widget) {
                widget.remove();
            }
        }
    };
    
    // 自动初始化（如果配置了自动启动）
    if (window.AgentSDKAutoInit !== false) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                window.AgentSDK.init();
            });
        } else {
            window.AgentSDK.init();
        }
    }
})();
