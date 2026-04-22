<template>
    <view class="detail-page">
        <view class="hero">
            <view class="hero-icon">{{ agent.icon || '🤖' }}</view>
            <text class="hero-name">{{ agent.name || '智能体' }}</text>
            <text class="hero-category">{{ agent.category || '通用' }}</text>
            <view class="hero-status" :class="agent.status || 'draft'">
                <text>{{ statusLabel }}</text>
            </view>
        </view>

        <view class="section-card">
            <text class="section-title">简介</text>
            <text class="section-text">{{ agent.description || '暂无简介' }}</text>
        </view>

        <view class="section-card" v-if="agent.tags && agent.tags.length">
            <text class="section-title">标签</text>
            <view class="tag-list">
                <text v-for="tag in agent.tags" :key="tag" class="tag">{{ tag }}</text>
            </view>
        </view>

        <view class="section-card">
            <text class="section-title">模型配置</text>
            <view class="info-row">
                <text class="info-label">模型</text>
                <text class="info-value strong">{{ agent.model_id || '未配置' }}</text>
            </view>
            <view class="info-row">
                <text class="info-label">提供商</text>
                <text class="info-value">{{ providerName }}</text>
            </view>
            <view class="info-row">
                <text class="info-label">Temperature</text>
                <text class="info-value">{{ agent.temperature || '0.7' }}</text>
            </view>
            <view class="info-row">
                <text class="info-label">Max Tokens</text>
                <text class="info-value">{{ agent.max_tokens || 4096 }}</text>
            </view>
        </view>

        <view class="section-card" v-if="agent.welcome_message">
            <text class="section-title">欢迎语</text>
            <text class="section-text">{{ agent.welcome_message }}</text>
        </view>

        <view class="section-card" v-if="agent.system_prompt">
            <text class="section-title">系统提示词</text>
            <text class="section-text prompt">{{ agent.system_prompt }}</text>
        </view>

        <view class="section-card" v-if="examples.length">
            <text class="section-title">示例问题</text>
            <view
                v-for="example in examples"
                :key="example"
                class="example-item"
                @click="useExample(example)"
            >
                <text class="example-text">{{ example }}</text>
                <text class="example-arrow">></text>
            </view>
        </view>

        <view class="footer-actions">
            <view class="btn secondary" @click="collectAgent">{{ isCollected ? '已收藏' : '收藏' }}</view>
            <view class="btn primary" @click="startChat">{{ agent.status === 'active' ? '开始对话' : '部署并对话' }}</view>
        </view>
    </view>
</template>

<script>
import agentsApi from '@/api/agents.js'

export default {
    data() {
        return {
            agentId: null,
            agent: {},
            providerMap: {},
            isCollected: false,
            examples: []
        }
    },

    computed: {
        statusLabel() {
            const map = {
                active: '运行中',
                draft: '草稿',
                disabled: '已停用'
            }
            return map[this.agent.status] || '草稿'
        },

        providerName() {
            const providerId = this.agent.model_provider || ''
            return this.providerMap[providerId] || providerId || '未配置'
        }
    },

    onLoad(options) {
        this.agentId = options.id
        this.loadPageData()
    },

    methods: {
        async loadPageData() {
            uni.showLoading({ title: '加载中...' })
            try {
                await Promise.all([this.loadProviders(), this.loadAgentDetail()])
            } finally {
                uni.hideLoading()
            }
        },

        async loadProviders() {
            try {
                const res = await agentsApi.getProviders()
                if (res.success && res.data) {
                    const map = {}
                    res.data.forEach(item => {
                        map[item.id] = item.name || item.id
                    })
                    this.providerMap = map
                }
            } catch (error) {
                console.error('loadProviders failed', error)
            }
        },

        async loadAgentDetail() {
            try {
                const res = await agentsApi.getAgentDetail(this.agentId)
                if (res.success && res.agent) {
                    const agent = { ...res.agent }

                    if (typeof agent.tags === 'string') {
                        try {
                            agent.tags = JSON.parse(agent.tags)
                        } catch (e) {
                            agent.tags = agent.tags.split(',').map(item => item.trim()).filter(Boolean)
                        }
                    }

                    if (!Array.isArray(agent.tags)) {
                        agent.tags = []
                    }

                    this.agent = agent
                    this.examples = this.buildExamples(agent)
                } else {
                    uni.showToast({ title: res.error || '加载失败', icon: 'none' })
                }
            } catch (error) {
                console.error('loadAgentDetail failed', error)
                uni.showToast({ title: '加载失败', icon: 'none' })
            }
        },

        buildExamples(agent) {
            const category = agent.category || '通用'
            const map = {
                写作: ['请帮我润色这段文字。', '请给我一份文章大纲。', '请写一段产品介绍。'],
                编程: ['请解释这段代码。', '请帮我定位 bug。', '请写一个示例函数。'],
                分析: ['请总结这份数据的趋势。', '请帮我做一个对比分析。', '请输出关键结论。'],
                商务: ['请帮我写商务邮件。', '请整理会议纪要。', '请分析客户需求。']
            }
            return map[category] || ['你好，请介绍一下你的能力。', '你可以帮我完成什么工作？', '请先给我一个使用建议。']
        },

        useExample(example) {
            uni.switchTab({
                url: '/pages/chat/chat',
                success: () => {
                    getApp().globalData.currentAgent = this.agent
                    uni.$emit('setChatInput', example)
                }
            })
        },

        collectAgent() {
            this.isCollected = !this.isCollected
            uni.showToast({
                title: this.isCollected ? '已收藏' : '已取消收藏',
                icon: 'none'
            })
        },

        async startChat() {
            if (this.agent.status !== 'active') {
                const res = await new Promise(resolve => {
                    uni.showModal({
                        title: '提示',
                        content: '当前智能体尚未部署，是否先部署？',
                        success: resolve
                    })
                })

                if (!res.confirm) {
                    return
                }

                const deployRes = await agentsApi.deployAgent(this.agentId)
                if (!deployRes.success) {
                    uni.showToast({ title: deployRes.error || '部署失败', icon: 'none' })
                    return
                }
                this.agent.status = 'active'
            }

            uni.switchTab({
                url: '/pages/chat/chat',
                success: () => {
                    getApp().globalData.currentAgent = this.agent
                    uni.$emit('setChatInput', `你好，${this.agent.name}！`)
                }
            })
        }
    }
}
</script>

<style scoped>
.detail-page {
    min-height: 100vh;
    background: #f8fafc;
    padding-bottom: 160rpx;
}

.hero {
    padding: 48rpx 32rpx;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
}

.hero-icon {
    width: 160rpx;
    height: 160rpx;
    border-radius: 40rpx;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 72rpx;
}

.hero-name {
    margin-top: 20rpx;
    font-size: 40rpx;
    font-weight: 700;
    color: #ffffff;
}

.hero-category {
    margin-top: 8rpx;
    font-size: 26rpx;
    color: rgba(255, 255, 255, 0.8);
}

.hero-status {
    margin-top: 16rpx;
    padding: 8rpx 22rpx;
    border-radius: 999rpx;
    font-size: 24rpx;
    color: #ffffff;
}

.hero-status.active {
    background: #16a34a;
}

.hero-status.draft {
    background: #d97706;
}

.hero-status.disabled {
    background: #dc2626;
}

.section-card {
    margin: 24rpx;
    padding: 24rpx;
    border-radius: 20rpx;
    background: #ffffff;
}

.section-title {
    display: block;
    margin-bottom: 16rpx;
    font-size: 30rpx;
    font-weight: 700;
    color: #1f2937;
}

.section-text {
    font-size: 27rpx;
    line-height: 1.75;
    color: #4b5563;
}

.prompt {
    white-space: pre-wrap;
}

.tag-list {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
}

.tag {
    padding: 8rpx 16rpx;
    border-radius: 999rpx;
    background: #eef2ff;
    color: #4c51bf;
    font-size: 24rpx;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 24rpx;
    padding: 14rpx 0;
    border-bottom: 1rpx solid #f3f4f6;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-size: 25rpx;
    color: #6b7280;
}

.info-value {
    flex: 1;
    text-align: right;
    font-size: 26rpx;
    color: #1f2937;
    word-break: break-all;
}

.info-value.strong {
    font-weight: 700;
}

.example-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20rpx;
    padding: 20rpx 0;
    border-bottom: 1rpx solid #f3f4f6;
}

.example-item:last-child {
    border-bottom: none;
}

.example-text {
    flex: 1;
    font-size: 26rpx;
    color: #4b5563;
}

.example-arrow {
    color: #4c51bf;
}

.footer-actions {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    gap: 24rpx;
    padding: 24rpx 32rpx calc(24rpx + env(safe-area-inset-bottom));
    background: #ffffff;
    border-top: 1rpx solid #e5e7eb;
}

.btn {
    flex: 1;
    height: 88rpx;
    border-radius: 16rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30rpx;
    font-weight: 600;
}

.btn.primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #ffffff;
}

.btn.secondary {
    background: #f3f4f6;
    color: #4b5563;
}
</style>
