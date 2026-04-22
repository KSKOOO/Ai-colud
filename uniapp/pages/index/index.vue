<template>
    <view class="index-container">
        <!-- 顶部欢迎区 -->
        <view class="welcome-section">
            <view class="welcome-content">
                <view class="header-logo">
                    <text class="logo-text">凌</text>
                </view>
                <text class="welcome-title">欢迎回来</text>
                <text class="welcome-name">{{ userInfo.username || '用户' }}</text>
            </view>
            <view class="welcome-avatar">
                <text class="avatar-text">{{ (userInfo.username || 'U')[0].toUpperCase() }}</text>
            </view>
        </view>
        
        <!-- 快捷功能 -->
        <view class="quick-actions">
            <view class="action-item" @click="navigateTo('/pages/chat/chat')">
                <view class="action-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <text>💬</text>
                </view>
                <text class="action-name">AI聊天</text>
            </view>
            <view class="action-item" @click="navigateTo('/pages/agents/agents')">
                <view class="action-icon" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
                    <text>🤖</text>
                </view>
                <text class="action-name">智能体</text>
            </view>
            <view class="action-item" @click="navigateTo('/pages/chat/chat?mode=vision')">
                <view class="action-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <text>🖼️</text>
                </view>
                <text class="action-name">图像分析</text>
            </view>
            <view class="action-item" @click="navigateTo('/pages/chat/chat?mode=video')">
                <view class="action-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                    <text>🎬</text>
                </view>
                <text class="action-name">视频生成</text>
            </view>
        </view>
        
        <!-- 功能演示卡片 -->
        <view class="section">
            <view class="section-header">
                <text class="section-title">功能演示</text>
                <text class="section-more" @click="showAllScenarios">查看全部</text>
            </view>
            
            <scroll-view scroll-x class="demo-cards">
                <view 
                    class="demo-card" 
                    v-for="(demo, index) in demoList" 
                    :key="index"
                    @click="startDemo(demo)"
                >
                    <view class="demo-icon">{{ demo.icon }}</view>
                    <text class="demo-title">{{ demo.title }}</text>
                    <text class="demo-desc">{{ demo.desc }}</text>
                </view>
            </scroll-view>
        </view>
        
        <!-- 智能体推荐 -->
        <view class="section">
            <view class="section-header">
                <text class="section-title">热门智能体</text>
            </view>
            
            <view class="agent-list">
                <view 
                    class="agent-item" 
                    v-for="agent in agentList" 
                    :key="agent.id"
                    @click="navigateTo('/pages/agent-detail/agent-detail?id=' + agent.id)"
                >
                    <view class="agent-avatar">{{ agent.icon }}</view>
                    <view class="agent-info">
                        <text class="agent-name">{{ agent.name }}</text>
                        <text class="agent-desc">{{ agent.description }}</text>
                    </view>
                    <text class="agent-arrow">→</text>
                </view>
            </view>
        </view>
        
        <!-- 最近对话 -->
        <view class="section" v-if="recentChats.length > 0">
            <view class="section-header">
                <text class="section-title">最近对话</text>
                <text class="section-more" @click="clearHistory">清空</text>
            </view>
            
            <view class="chat-history">
                <view 
                    class="history-item" 
                    v-for="(chat, index) in recentChats" 
                    :key="index"
                    @click="continueChat(chat)"
                >
                    <view class="history-icon">💬</view>
                    <view class="history-content">
                        <text class="history-title">{{ chat.title }}</text>
                        <text class="history-time">{{ chat.time }}</text>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script>
import chatApi from '@/api/chat.js'
import agentsApi from '@/api/agents.js'

export default {
    data() {
        return {
            userInfo: {},
            demoList: [],
            agentList: [
                { id: 1, icon: '🧠', name: '深度思考助手', description: '帮你深入分析复杂问题' },
                { id: 2, icon: '📖', name: '阅读理解专家', description: '快速理解文章核心内容' },
                { id: 3, icon: '🎵', name: '音乐创作助手', description: '歌词创作与音乐建议' }
            ],
            recentChats: [],
            scenariosLoaded: false
        }
    },
    
    onLoad() {
        this.loadUserInfo()
        this.loadAgents()
        this.loadRecentChats()
        this.loadScenarios()
    },
    
    onShow() {
        this.loadRecentChats()
    },
    
    onPullDownRefresh() {
        this.loadAgents()
        this.loadRecentChats()
        setTimeout(() => {
            uni.stopPullDownRefresh()
        }, 500)
    },
    
    methods: {
        // 加载用户信息
        loadUserInfo() {
            this.userInfo = uni.getStorageSync('userInfo') || {}
        },
        
        // 加载智能体列表
        async loadAgents() {
            try {
                const res = await agentsApi.getAgents({ limit: 3 })
                if (res.data && res.data.length > 0) {
                    this.agentList = res.data
                }
            } catch (error) {
                console.log('加载智能体列表失败，使用默认数据')
            }
        },
        
        // 加载最近对话
        loadRecentChats() {
            this.recentChats = uni.getStorageSync('recentChats') || []
        },
        
        // 加载场景演示（从后端API获取）
        async loadScenarios() {
            try {
                const res = await chatApi.getScenarios()
                if (res.data && res.data.length > 0) {
                    this.demoList = res.data.map(item => ({
                        icon: item.icon || '🎯',
                        title: item.name,
                        desc: item.description,
                        prompt: item.prompt || item.default_prompt
                    }))
                    this.scenariosLoaded = true
                } else {
                    this.loadDefaultScenarios()
                }
            } catch (error) {
                console.log('加载场景演示失败，使用默认数据')
                this.loadDefaultScenarios()
            }
        },
        
        // 加载默认场景
        loadDefaultScenarios() {
            this.demoList = [
                { icon: '📝', title: '智能写作', desc: '帮你写文章、文案', prompt: '请帮我写一篇关于人工智能发展的文章' },
                { icon: '💻', title: '代码助手', desc: '编程问题解答', prompt: '请解释一下什么是递归，并给出一个Python示例' },
                { icon: '📊', title: '数据分析', desc: '数据解读分析', prompt: '请帮我分析这组销售数据的趋势' },
                { icon: '🎨', title: '创意设计', desc: '设计灵感建议', prompt: '请给我一些现代简约风格UI设计的建议' },
                { icon: '📚', title: '学习辅导', desc: '知识答疑解惑', prompt: '请解释量子计算的基本原理' },
                { icon: '🌍', title: '翻译助手', desc: '多语言翻译', prompt: '请将这段话翻译成英文：人工智能正在改变我们的生活' }
            ]
        },
        
        // 页面跳转
        navigateTo(url) {
            if (url.startsWith('/pages/chat/chat')) {
                const [path, query] = url.split('?')
                uni.switchTab({
                    url: path,
                    success: () => {
                        if (query) {
                            const mode = query.split('&').find(item => item.startsWith('mode='))?.split('=')[1] || ''
                            if (mode) {
                                uni.$emit('setChatMode', mode)
                            }
                        }
                    }
                })
            } else if (url.startsWith('/pages/index/index') || url.startsWith('/pages/agents/agents') || url.startsWith('/pages/mine/mine')) {
                uni.switchTab({ url })
            } else {
                uni.navigateTo({ url })
            }
        },
        
        // 开始演示
        startDemo(demo) {
            uni.switchTab({
                url: '/pages/chat/chat',
                success: () => {
                    // 通过事件传递prompt
                    uni.$emit('setChatInput', demo.prompt)
                }
            })
        },
        
        // 显示所有场景
        showAllScenarios() {
            uni.navigateTo({ 
                url: '/pages/scenarios/scenarios',
                fail: () => {
                    uni.showToast({ title: '场景列表页开发中', icon: 'none' })
                }
            })
        },
        
        // 清空历史
        clearHistory() {
            uni.showModal({
                title: '确认清空',
                content: '确定要清空所有历史记录吗？',
                success: (res) => {
                    if (res.confirm) {
                        uni.removeStorageSync('recentChats')
                        this.recentChats = []
                        uni.showToast({ title: '已清空', icon: 'success' })
                    }
                }
            })
        },
        
        // 继续对话
        continueChat(chat) {
            uni.switchTab({
                url: '/pages/chat/chat',
                success: () => {
                    uni.$emit('loadChatHistory', chat)
                }
            })
        }
    }
}
</script>

<style scoped>
.index-container {
    min-height: 100vh;
    background: #f8fafc;
    padding-bottom: 32rpx;
}

/* 欢迎区域 */
.welcome-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 48rpx 32rpx;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.welcome-content {
    display: flex;
    flex-direction: column;
}

.header-logo {
    width: 80rpx;
    height: 80rpx;
    margin-bottom: 16rpx;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 16rpx;
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo-text {
    font-size: 40rpx;
    font-weight: 700;
    color: #ffffff;
}

.welcome-title {
    font-size: 28rpx;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 8rpx;
}

.welcome-name {
    font-size: 40rpx;
    font-weight: 700;
    color: #ffffff;
}

.welcome-avatar {
    width: 96rpx;
    height: 96rpx;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-text {
    font-size: 40rpx;
    font-weight: 600;
    color: #ffffff;
}

/* 快捷功能 */
.quick-actions {
    display: flex;
    justify-content: space-around;
    padding: 32rpx 16rpx;
    background: #ffffff;
    margin: -24rpx 24rpx 24rpx;
    border-radius: 24rpx;
    box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.08);
}

.action-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.action-icon {
    width: 88rpx;
    height: 88rpx;
    border-radius: 24rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12rpx;
    font-size: 36rpx;
}

.action-name {
    font-size: 24rpx;
    color: #374151;
}

/* 区块 */
.section {
    padding: 0 24rpx;
    margin-bottom: 32rpx;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24rpx;
}

.section-title {
    font-size: 32rpx;
    font-weight: 600;
    color: #1f2937;
}

.section-more {
    font-size: 26rpx;
    color: #4c51bf;
}

/* 演示卡片 */
.demo-cards {
    white-space: nowrap;
}

.demo-card {
    display: inline-block;
    width: 240rpx;
    background: #ffffff;
    border-radius: 20rpx;
    padding: 24rpx;
    margin-right: 20rpx;
    box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.06);
    vertical-align: top;
}

.demo-icon {
    font-size: 48rpx;
    margin-bottom: 16rpx;
}

.demo-title {
    display: block;
    font-size: 28rpx;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8rpx;
    white-space: normal;
}

.demo-desc {
    display: block;
    font-size: 24rpx;
    color: #6b7280;
    white-space: normal;
}

/* 智能体列表 */
.agent-list {
    background: #ffffff;
    border-radius: 20rpx;
    overflow: hidden;
}

.agent-item {
    display: flex;
    align-items: center;
    padding: 24rpx;
    border-bottom: 1rpx solid #f3f4f6;
}

.agent-item:last-child {
    border-bottom: none;
}

.agent-avatar {
    width: 80rpx;
    height: 80rpx;
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    border-radius: 20rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36rpx;
    margin-right: 20rpx;
}

.agent-info {
    flex: 1;
}

.agent-name {
    display: block;
    font-size: 28rpx;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4rpx;
}

.agent-desc {
    display: block;
    font-size: 24rpx;
    color: #6b7280;
}

.agent-arrow {
    font-size: 28rpx;
    color: #9ca3af;
}

/* 历史记录 */
.chat-history {
    background: #ffffff;
    border-radius: 20rpx;
    overflow: hidden;
}

.history-item {
    display: flex;
    align-items: center;
    padding: 24rpx;
    border-bottom: 1rpx solid #f3f4f6;
}

.history-item:last-child {
    border-bottom: none;
}

.history-icon {
    font-size: 36rpx;
    margin-right: 16rpx;
}

.history-content {
    flex: 1;
}

.history-title {
    display: block;
    font-size: 28rpx;
    color: #1f2937;
    margin-bottom: 4rpx;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.history-time {
    display: block;
    font-size: 24rpx;
    color: #9ca3af;
}
</style>
