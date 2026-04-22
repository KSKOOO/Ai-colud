$(document).ready(function() {
    // 初始化聊天消息
    const chatMessages = $('#chatMessages');
    const userInput = $('#userInput');
    const sendBtn = $('#sendBtn');
    const modelSelect = $('#modelSelect');
    const deepThinkBtn = $('#deepThinkBtn');
    const webSearchBtn = $('#webSearchBtn');
    const uploadImageBtn = $('#uploadImageBtn');
    const toolsMenuBtn = $('#toolsMenuBtn');
    const clearBtn = $('#clearBtn');
    
    let currentMode = 'normal'; // normal, deepThink, webSearch
    let uploadedImages = [];
    let toolsMenu = null;
    let availableModels = {};
    let defaultModel = 'gpt-3.5-turbo';

    // 初始化工具菜单
    function initToolsMenu() {
        if (!toolsMenu) {
            toolsMenu = $('<div>').addClass('tools-menu');
            const tools = [
                { name: '写作', icon: 'fas fa-pen', action: 'writing' },
                { name: '编程', icon: 'fas fa-code', action: 'coding' },
                { name: '解题', icon: 'fas fa-calculator', action: 'math' },
                { name: '录音笔', icon: 'fas fa-microphone', action: 'recording' }
            ];
            
            tools.forEach(tool => {
                const item = $('<div>').addClass('tools-menu-item').html(`
                    <i class="${tool.icon}"></i>
                    <span>${tool.name}</span>
                `);
                item.on('click', function() {
                    handleToolAction(tool.action);
                    toolsMenu.removeClass('show');
                });
                toolsMenu.append(item);
            });
            
            $('.chat-input-area').append(toolsMenu);
        }
    }

    // 处理工具操作
    function handleToolAction(action) {
        addMessage(`已选择工具: ${action}`, 'system', 'gpt-3.5-turbo');
        // 这里可以添加具体的工具逻辑
    }

    // 动态加载模型列表
    function loadAvailableModels() {
        $.ajax({
            url: '?route=api',
            type: 'POST',
            data: {
                request: 'models'
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    availableModels = response.models;
                    defaultModel = response.default_model;
                    
                    // 清空模型选择器
                    modelSelect.empty();
                    
                    // 动态添加模型选项
                    for (const modelId in availableModels) {
                        const option = $('<option>').attr('value', modelId).text(availableModels[modelId]);
                        if (modelId === defaultModel) {
                            option.attr('selected', 'selected');
                        }
                        modelSelect.append(option);
                    }
                    
                    console.log('模型列表加载成功:', availableModels);
                } else {
                    console.error('加载模型列表失败:', response.message);
                    // 加载失败时使用默认模型
                    loadDefaultModels();
                }
            },
            error: function() {
                console.error('无法连接到API服务器');
                // 网络错误时使用默认模型
                loadDefaultModels();
            }
        });
    }
    
    // 加载默认模型（备用方案）
    function loadDefaultModels() {
        availableModels = {
            'gpt-3.5-turbo': 'GPT-3.5 Turbo',
            'gpt-4': 'GPT-4',
            'llama-2': 'Llama 2',
            'mistral': 'Mistral',
            'gpt-4-turbo': 'GPT-4 Turbo',
            'claude-3-opus': 'Claude 3 Opus',
            'claude-3-sonnet': 'Claude 3 Sonnet',
            'claude-3-haiku': 'Claude 3 Haiku'
        };
        defaultModel = 'gpt-3.5-turbo';
        
        modelSelect.empty();
        for (const modelId in availableModels) {
            const option = $('<option>').attr('value', modelId).text(availableModels[modelId]);
            if (modelId === defaultModel) {
                option.attr('selected', 'selected');
            }
            modelSelect.append(option);
        }
    }

    // 加载初始消息
    addMessage('你好！巨神兵AI助手。有什么可以帮助你的吗？', 'system', defaultModel);
    
    // 页面加载时加载模型列表
    loadAvailableModels();

    // 发送消息
    function sendMessage() {
        const message = userInput.val().trim();
        const model = modelSelect.val();
        
        if (message === '') return;
        
        // 添加用户消息
        addMessage(message, 'user', model);
        userInput.val('');
        
        // 禁用输入框
        userInput.prop('disabled', true);
        sendBtn.prop('disabled', true);
        
        // 调用API
        callApi(message, model);
    }

    // 添加消息到聊天区域
    function addMessage(content, type, model) {
        const messageDiv = $('<div>').addClass('message').addClass(type);
        
        // 创建消息内容容器
        const messageContent = $('<div>').addClass('message-content');
        
        // 检测并处理多媒体内容
        if (typeof content === 'string') {
            // 检测图片URL
            const imageRegex = /(https?:\/\/[^\s]+\.(?:jpg|jpeg|png|gif|webp|bmp)(?:\?[^\s]*)?)/gi;
            const videoRegex = /(https?:\/\/[^\s]+\.(?:mp4|webm|ogg|mov)(?:\?[^\s]*)?)/gi;
            
            let processedContent = content;
            
            // 替换图片URL为img标签
            processedContent = processedContent.replace(imageRegex, function(url) {
                return `<div class="media-container"><img src="${url}" alt="AI生成图片" class="ai-image" onclick="window.open('${url}', '_blank')" /></div>`;
            });
            
            // 替换视频URL为video标签
            processedContent = processedContent.replace(videoRegex, function(url) {
                return `<div class="media-container"><video src="${url}" controls class="ai-video"><a href="${url}" target="_blank">下载视频</a></video></div>`;
            });
            
            // 检测base64图片数据
            const base64Regex = /data:image\/[^;]+;base64,[^"]+/g;
            processedContent = processedContent.replace(base64Regex, function(base64Data) {
                return `<div class="media-container"><img src="${base64Data}" alt="AI生成图片" class="ai-image" /></div>`;
            });
            
            // 将换行符转换为HTML换行
            processedContent = processedContent.replace(/\n/g, '<br>');
            
            messageContent.html(processedContent);
        } else {
            // 如果是对象，直接显示
            messageContent.html('<pre>' + JSON.stringify(content, null, 2) + '</pre>');
        }
        
        if (model) {
            const modelBadge = $('<div>').addClass('model-badge').text('模型: ' + model);
            messageDiv.append(modelBadge);
        }
        
        messageDiv.append(messageContent);
        chatMessages.append(messageDiv);
        
        // 滚动到底部
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }

    // 调用API
    function callApi(message, model) {
        let requestType = 'chat';
        
        // 根据当前模式调整请求
        if (currentMode === 'webSearch') {
            requestType = 'web_search';
        } else if (currentMode === 'deepThink') {
            requestType = 'deep_think';
        }
        
        // 如果有上传的图片，添加到请求
        if (uploadedImages.length > 0) {
            // 这里应该处理图片数据，实际应用中需要base64编码或其他方式
            message += ' [图片已上传: ' + uploadedImages.length + '张]';
        }
        
        $.ajax({
            url: window.location.pathname + '?route=api',
            type: 'POST',
            data: {
                request: requestType,
                input: message,
                model: model,
                mode: currentMode
            },
            dataType: 'json',
            success: function(response) {
                console.log('API响应:', response);
                
                if (response && response.status === 'success') {
                    // 添加系统回复
                    addMessage(response.message, 'system', response.model);
                } else {
                    // 显示详细的错误信息
                    addMessage('抱歉，处理请求时出错: ' + (response ? response.message : '未知错误'), 'system', model);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX错误详情:');
                console.error('状态:', status);
                console.error('错误:', error);
                console.error('响应文本:', xhr.responseText);
                
                addMessage('抱歉，无法连接到服务器。请稍后再试。', 'system', model);
            },
            complete: function() {
                // 重新启用输入框
                userInput.prop('disabled', false);
                sendBtn.prop('disabled', false);
                userInput.focus();
            }
        });
    }

    // 深度思考模式
    function toggleDeepThink() {
        currentMode = currentMode === 'deepThink' ? 'normal' : 'deepThink';
        deepThinkBtn.toggleClass('active');
        
        if (currentMode === 'deepThink') {
            addMessage('已启用深度思考模式，AI将进行更深入的思考和分析。', 'system', 'gpt-3.5-turbo');
        } else {
            addMessage('已关闭深度思考模式。', 'system', 'gpt-3.5-turbo');
        }
    }

    // 联网搜索模式
    function toggleWebSearch() {
        currentMode = currentMode === 'webSearch' ? 'normal' : 'webSearch';
        webSearchBtn.toggleClass('active');
        
        if (currentMode === 'webSearch') {
            addMessage('已启用联网搜索模式，AI将搜索网络信息。', 'system', 'gpt-3.5-turbo');
        } else {
            addMessage('已关闭联网搜索模式。', 'system', 'gpt-3.5-turbo');
        }
    }

    // 处理图片上传
    function handleImageUpload() {
        // 创建文件输入元素
        const fileInput = $('<input>').attr({
            type: 'file',
            accept: 'image/*',
            multiple: true
        });
        
        fileInput.on('change', function(e) {
            const files = e.target.files;
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const imageUrl = e.target.result;
                    uploadedImages.push(imageUrl);
                    
                    // 显示图片预览
                    const preview = $('<div>').css({
                        position: 'relative',
                        width: '80px',
                        height: '80px',
                        overflow: 'hidden',
                        borderRadius: '4px',
                        border: '1px solid #ddd',
                        marginBottom: '10px'
                    });
                    
                    const img = $('<img>').attr('src', imageUrl).css({
                        width: '100%',
                        height: '100%',
                        objectFit: 'cover'
                    });
                    
                    const removeBtn = $('<button>').addClass('remove-image').html('&times;');
                    removeBtn.on('click', function() {
                        const index = uploadedImages.indexOf(imageUrl);
                        if (index > -1) {
                            uploadedImages.splice(index, 1);
                            preview.remove();
                        }
                    });
                    
                    preview.append(img);
                    preview.append(removeBtn);
                    
                    // 在输入框上方显示上传的图片
                    if ($('#imagePreview').length === 0) {
                        const previewContainer = $('<div>').attr('id', 'imagePreview').addClass('image-preview');
                        userInput.before(previewContainer);
                    }
                    
                    $('#imagePreview').append(preview);
                };
                
                reader.readAsDataURL(file);
            }
        });
        
        fileInput.click();
    }

    // 绑定事件
    sendBtn.on('click', sendMessage);
    userInput.on('keypress', function(e) {
        if (e.which === 13) {
            sendMessage();
        }
    });

    // 工具按钮事件
    deepThinkBtn.on('click', toggleDeepThink);
    webSearchBtn.on('click', toggleWebSearch);
    uploadImageBtn.on('click', handleImageUpload);
    toolsMenuBtn.on('click', function() {
        initToolsMenu();
        toolsMenu.toggleClass('show');
    });

    // 清空聊天记录
    clearBtn.on('click', function() {
        if (confirm('确定要清空所有聊天记录吗？')) {
            chatMessages.empty();
            uploadedImages = [];
            $('#imagePreview').remove();
            addMessage('聊天记录已清空。有什么可以帮助你的吗？', 'system', 'gpt-3.5-turbo');
        }
    });

    // 点击其他地方关闭工具菜单
    $(document).on('click', function(e) {
        if (toolsMenu && !$(e.target).closest('.tool-btn').length && !$(e.target).closest('.tools-menu').length) {
            toolsMenu.removeClass('show');
        }
    });

    // 初始化焦点
    userInput.focus();
});