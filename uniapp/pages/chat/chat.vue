<template>
    <view class="chat-container">
        <scroll-view class="message-list" scroll-y :scroll-top="scrollTop">
            <view v-if="messages.length === 0" class="message-item assistant">
                <view class="message-avatar">AI</view>
                <view class="message-bubble assistant">
                    <text>你好，我可以帮助你进行聊天、深度思考和联网搜索。</text>
                </view>
            </view>

            <view
                v-for="(msg, index) in messages"
                :key="index"
                class="message-item"
                :class="msg.role === 'user' ? 'user' : 'assistant'"
            >
                <view class="message-avatar">{{ msg.role === 'user' ? '我' : 'AI' }}</view>
                <view class="message-bubble" :class="msg.role === 'user' ? 'user' : 'assistant'">
                    <rich-text v-if="msg.role !== 'user'" :nodes="parseMarkdown(msg.content || '')"></rich-text>
                    <text v-else>{{ msg.content }}</text>
                    <view v-if="msg.modeLabel" class="message-tag">{{ msg.modeLabel }}</view>
                </view>
            </view>

            <view v-if="isLoading" class="message-item assistant">
                <view class="message-avatar">AI</view>
                <view class="message-bubble assistant">
                    <text>正在生成回复...</text>
                </view>
            </view>
        </scroll-view>

        <view class="toolbar">
            <view class="mode-chip" :class="{ active: currentMode === 'deep_think' }" @click="toggleMode('deep_think')">
                <text>深度思考</text>
            </view>
            <view class="mode-chip" :class="{ active: currentMode === 'web_search' }" @click="toggleMode('web_search')">
                <text>联网搜索</text>
            </view>
            <view class="model-chip" @click="showModelPopup = true">
                <text>{{ currentModel || '选择模型' }}</text>
            </view>
        </view>

        <view class="input-area">
            <textarea
                v-model="inputText"
                class="chat-input"
                placeholder="输入消息..."
                auto-height
                :maxlength="4000"
                :show-confirm-bar="false"
            />
            <view class="action-row">
                <view class="clear-btn" @click="clearChat">清空</view>
                <view class="send-btn" :class="{ disabled: !canSend }" @click="sendMessage">发送</view>
            </view>
        </view>

        <view v-if="showModelPopup" class="popup-mask" @click="showModelPopup = false">
            <view class="popup-panel" @click.stop>
                <view class="popup-header">
                    <text class="popup-title">选择模型</text>
                    <text class="popup-close" @click="showModelPopup = false">关闭</text>
                </view>
                <scroll-view class="popup-list" scroll-y>
                    <view
                        v-for="model in modelList"
                        :key="model.provider + ':' + model.name"
                        class="popup-item"
                        :class="{ selected: currentProvider === model.provider && currentModel === model.name }"
                        @click="selectModel(model)"
                    >
                        <view class="popup-item-main">
                            <text class="popup-item-name">{{ model.name }}</text>
                            <text class="popup-item-remark">{{ model.remark }}</text>
                        </view>
                        <view class="popup-item-side">
                            <text class="popup-provider">{{ model.providerName }}</text>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </view>
    </view>
</template>

<script>
import chatApi from '@/api/chat.js'
import { parseMarkdown } from '@/utils/index.js'

export default {
    data() {
        return {
            messages: [],
            inputText: '',
            isLoading: false,
            scrollTop: 0,
            currentMode: 'normal',
            currentModel: '',
            currentProvider: '',
            showModelPopup: false,
            modelList: [],
            contextMessages: []
        }
    },

    computed: {
        canSend() {
            return !!this.inputText.trim() && !this.isLoading
        }
    },

    onLoad(options) {
        if (options.mode) {
            this.currentMode = options.mode
        }

        this.loadModels()

        uni.$on('setChatInput', this.handleSetChatInput)
        uni.$on('loadChatHistory', this.handleLoadChatHistory)
        uni.$on('selectModel', this.handleSelectModel)
        uni.$on('setChatMode', this.handleSetChatMode)
    },

    onShow() {
        const currentAgent = getApp().globalData.currentAgent
        if (currentAgent && currentAgent.model_id && currentAgent.model_provider) {
            this.currentModel = currentAgent.model_id
            this.currentProvider = currentAgent.model_provider
        }
    },

    onUnload() {
        uni.$off('setChatInput', this.handleSetChatInput)
        uni.$off('loadChatHistory', this.handleLoadChatHistory)
        uni.$off('selectModel', this.handleSelectModel)
        uni.$off('setChatMode', this.handleSetChatMode)
    },

    methods: {
        parseMarkdown,

        async loadModels() {
            try {
                const res = await chatApi.getProviders()
                const providers = res.data || []
                const models = []

                providers.forEach(provider => {
                    const providerModels = Array.isArray(provider.models) ? provider.models : []
                    providerModels.forEach(modelName => {
                        models.push({
                            name: modelName,
                            provider: provider.id,
                            providerName: provider.name || provider.id,
                            remark: this.getModelRemark(modelName, provider.name || provider.id)
                        })
                    })
                })

                this.modelList = models

                const defaultProvider = providers.find(item => item.is_default) || providers[0]
                if (defaultProvider) {
                    this.currentProvider = defaultProvider.id
                    this.currentModel = defaultProvider.default_model || (defaultProvider.models && defaultProvider.models[0]) || ''
                }
            } catch (error) {
                console.error('loadModels failed', error)
                this.modelList = []
                this.currentProvider = ''
                this.currentModel = ''
            }
        },

        getModelRemark(name, providerName) {
            const lower = String(name || '').toLowerCase()
            let type = '通用模型'

            if (lower.includes('vl') || lower.includes('vision')) type = '图文理解'
            else if (lower.includes('ocr')) type = 'OCR'
            else if (lower.includes('r1')) type = '推理模型'
            else if (lower.includes('code')) type = '代码生成'
            else if (lower.includes('chat')) type = '对话'
            else if (lower.includes('instruct')) type = '指令模型'
            else if (lower.includes('image')) type = '图像'
            else if (lower.includes('video')) type = '视频'

            return `${type} · ${providerName || 'AI'}`
        },

        toggleMode(mode) {
            this.currentMode = this.currentMode === mode ? 'normal' : mode
        },

        getModeLabel(mode) {
            const map = {
                deep_think: '深度思考',
                web_search: '联网搜索',
                vision_analysis: '图像分析'
            }
            return map[mode] || ''
        },

        selectModel(model) {
            if (!model) return
            this.currentModel = model.name
            this.currentProvider = model.provider
            this.showModelPopup = false
            uni.showToast({
                title: `已切换到 ${model.providerName}`,
                icon: 'none'
            })
        },

        handleSetChatInput(text) {
            this.inputText = text || ''
        },

        handleLoadChatHistory(chat) {
            this.messages = Array.isArray(chat.messages) ? chat.messages : []
            this.contextMessages = this.messages.map(item => ({
                role: item.role,
                content: item.content
            }))
        },

        handleSelectModel(model) {
            if (model && typeof model === 'object') {
                this.selectModel({
                    name: model.name,
                    provider: model.provider || model.provider_id,
                    providerName: model.providerName || model.provider_name || model.provider || model.provider_id,
                    remark: model.remark || this.getModelRemark(model.name, model.providerName || model.provider_name)
                })
            } else if (typeof model === 'string') {
                const target = this.modelList.find(item => item.name === model)
                if (target) this.selectModel(target)
            }
        },

        handleSetChatMode(mode) {
            this.currentMode = mode || 'normal'
        },

        async sendMessage() {
            if (!this.canSend) return

            if (!this.currentProvider || !this.currentModel) {
                uni.showToast({
                    title: '请先选择模型',
                    icon: 'none'
                })
                return
            }

            const content = this.inputText.trim()
            this.inputText = ''

            const userMessage = {
                role: 'user',
                content,
                modeLabel: this.currentMode !== 'normal' ? this.getModeLabel(this.currentMode) : ''
            }
            this.messages.push(userMessage)
            this.contextMessages.push({ role: 'user', content })
            this.scrollToBottom()

            this.isLoading = true
            try {
                let res
                const payload = {
                    input: content,
                    model: this.currentModel,
                    provider_id: this.currentProvider,
                    context: JSON.stringify(this.contextMessages.slice(-10))
                }

                if (this.currentMode === 'deep_think') {
                    res = await chatApi.deepThink(payload)
                } else if (this.currentMode === 'web_search') {
                    res = await chatApi.webSearch(payload)
                } else {
                    res = await chatApi.sendMessage({
                        ...payload,
                        mode: this.currentMode
                    })
                }

                const assistantMessage = {
                    role: 'assistant',
                    content: res.message || '未收到有效回复',
                    modeLabel: ''
                }
                this.messages.push(assistantMessage)
                this.contextMessages.push({ role: 'assistant', content: assistantMessage.content })
                this.saveHistory(content)
            } catch (error) {
                console.error('sendMessage failed', error)
                this.messages.push({
                    role: 'assistant',
                    content: '请求失败，请稍后重试。',
                    modeLabel: ''
                })
            } finally {
                this.isLoading = false
                this.scrollToBottom()
            }
        },

        clearChat() {
            this.messages = []
            this.contextMessages = []
        },

        scrollToBottom() {
            this.$nextTick(() => {
                this.scrollTop = this.messages.length * 999
            })
        },

        saveHistory(title) {
            const historyItem = {
                title: title.substring(0, 30),
                preview: title.substring(0, 60),
                time: new Date().toLocaleString(),
                type: 'chat',
                model: this.currentModel,
                messages: this.messages.slice(-20)
            }

            const recentChats = uni.getStorageSync('recentChats') || []
            recentChats.unshift(historyItem)
            uni.setStorageSync('recentChats', recentChats.slice(0, 10))

            const chatHistory = uni.getStorageSync('chatHistory') || []
            chatHistory.unshift(historyItem)
            uni.setStorageSync('chatHistory', chatHistory.slice(0, 50))
        }
    }
}
</script>

<style scoped>
.chat-container {
    min-height: 100vh;
    background: #f8fafc;
    display: flex;
    flex-direction: column;
}

.message-list {
    flex: 1;
    padding: 24rpx;
    padding-bottom: 220rpx;
}

.message-item {
    display: flex;
    gap: 16rpx;
    margin-bottom: 24rpx;
}

.message-item.user {
    flex-direction: row-reverse;
}

.message-avatar {
    width: 72rpx;
    height: 72rpx;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26rpx;
    flex-shrink: 0;
}

.message-bubble {
    max-width: 72%;
    padding: 20rpx 24rpx;
    border-radius: 24rpx;
    font-size: 28rpx;
    line-height: 1.6;
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.06);
}

.message-bubble.assistant {
    background: #ffffff;
    color: #1f2937;
}

.message-bubble.user {
    background: #4c51bf;
    color: #ffffff;
}

.message-tag {
    display: inline-block;
    margin-top: 12rpx;
    padding: 4rpx 12rpx;
    border-radius: 10rpx;
    background: rgba(76, 81, 191, 0.12);
    color: #4c51bf;
    font-size: 22rpx;
}

.toolbar {
    display: flex;
    gap: 16rpx;
    padding: 16rpx 24rpx;
    background: #ffffff;
    border-top: 1rpx solid #e5e7eb;
}

.mode-chip,
.model-chip {
    padding: 14rpx 20rpx;
    border-radius: 999rpx;
    background: #f3f4f6;
    color: #4b5563;
    font-size: 24rpx;
}

.mode-chip.active {
    background: #eef2ff;
    color: #4c51bf;
}

.model-chip {
    margin-left: auto;
    max-width: 280rpx;
}

.input-area {
    background: #ffffff;
    padding: 16rpx 24rpx calc(16rpx + env(safe-area-inset-bottom));
    border-top: 1rpx solid #e5e7eb;
}

.chat-input {
    width: 100%;
    min-height: 88rpx;
    max-height: 240rpx;
    background: #f3f4f6;
    border-radius: 24rpx;
    padding: 20rpx 24rpx;
    box-sizing: border-box;
    font-size: 28rpx;
}

.action-row {
    display: flex;
    justify-content: flex-end;
    gap: 16rpx;
    margin-top: 16rpx;
}

.clear-btn,
.send-btn {
    padding: 16rpx 32rpx;
    border-radius: 999rpx;
    font-size: 26rpx;
}

.clear-btn {
    background: #f3f4f6;
    color: #4b5563;
}

.send-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #ffffff;
}

.send-btn.disabled {
    opacity: 0.5;
}

.popup-mask {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    display: flex;
    align-items: flex-end;
    z-index: 1000;
}

.popup-panel {
    width: 100%;
    max-height: 70vh;
    background: #ffffff;
    border-radius: 32rpx 32rpx 0 0;
    overflow: hidden;
}

.popup-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 28rpx 32rpx;
    border-bottom: 1rpx solid #e5e7eb;
}

.popup-title {
    font-size: 32rpx;
    font-weight: 600;
    color: #1f2937;
}

.popup-close {
    font-size: 24rpx;
    color: #6b7280;
}

.popup-list {
    max-height: 60vh;
}

.popup-item {
    display: flex;
    justify-content: space-between;
    gap: 16rpx;
    padding: 24rpx 32rpx;
    border-bottom: 1rpx solid #f3f4f6;
}

.popup-item.selected {
    background: #eef2ff;
}

.popup-item-main {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.popup-item-name {
    font-size: 28rpx;
    color: #1f2937;
    font-weight: 600;
}

.popup-item-remark {
    margin-top: 6rpx;
    font-size: 24rpx;
    color: #6b7280;
}

.popup-provider {
    font-size: 22rpx;
    color: #4c51bf;
    background: #eef2ff;
    padding: 8rpx 12rpx;
    border-radius: 10rpx;
    align-self: center;
}
</style>
