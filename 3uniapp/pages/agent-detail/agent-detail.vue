<template>
    <view class="detail-container">
        <!-- 头部信息 -->
        <view class="header-section">
            <view class="agent-avatar">{{ agent.icon || '🤖' }}</view>
            <text class="agent-name">{{ agent.name }}</text>
            <text class="agent-category">{{ agent.category_name || agent.category || '通用智能体' }}</text>
            
            <view class="status-badge" :class="agent.status">
                <text>{{ agent.status === 'active' ? '运行中' : (agent.status === 'draft' ? '草稿' : '未部署') }}</text>
            </view>
            
            <view class="stats-row">
                <view class="stat-item">
                    <text class="stat-value">{{ agent.total_tasks || 0 }}</text>
                    <text class="stat-label">任务数</text>
                </view>
                <view class="stat-item">
                    <text class="stat-value">{{ agent.likes || 0 }}</text>
                    <text class="stat-label">点赞</text>
                </view>
                <view class="stat-item">
                    <text class="stat-value">{{ agent.temperature || '0.7' }}</text>
                    <text class="stat-label">Temperature</text>
                </view>
            </view>
        </view>
        
        <!-- 详细介绍 -->
        <view class="section">
            <text class="section-title">详细介绍</text>
            <text class="section-content">{{ agent.description || '暂无描述' }}</text>
        </view>
        
        <!-- 功能标签 -->
        <view class="section" v-if="agent.tags && agent.tags.length > 0">
            <text class="section-title">功能标签</text>
            <view class="tag-list">
                <text class="tag" v-for="(tag, index) in agent.tags" :key="index">{{ tag }}</text>
            </view>
        </view>
        
        <!-- 模型信息 -->
        <view class="section">
            <text class="section-title">AI模型</text>
            <view class="model-info-card">
                <text class="model-label">模型ID</text>
                <text class="model-value">{{ agent.model_id || '未配置' }}</text>
                <text class="model-label">提供商</text>
                <text class="model-value">{{ agent.model_provider || '未配置' }}</text>
                <text class="model-label">Max Tokens</text>
                <text class="model-value">{{ agent.max_tokens || 4096 }}</text>
            </view>
        </view>
        
        <!-- 使用示例 -->
        <view class="section" v-if="examples.length > 0">
            <text class="section-title">使用示例</text>
            <view class="example-list">
                <view 
                    class="example-item" 
                    v-for="(example, index) in examples" 
                    :key="index"
                    @click="useExample(example)"
                >
                    <text class="example-text">{{ example }}</text>
                    <text class="example-arrow">→</text>
                </view>
            </view>
        </view>
        
        <!-- 系统提示词（仅创建者可见） -->
        <view class="section" v-if="agent.system_prompt">
            <text class="section-title">系统提示词</text>
            <view class="prompt-box">
                <text class="prompt-content">{{ agent.system_prompt }}</text>
            </view>
        </view>
        
        <!-- 底部操作 -->
        <view class="footer-actions">
            <view class="action-btn secondary" @click="collectAgent">
                <text>{{ isCollected ? '❤️ 已收藏' : '🤍 收藏' }}</text>
            </view>
            <view class="action-btn primary" @click="startChat">
                <text>开始对话</text>
            </view>
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
            isCollected: false,
            examples: []
        }
    },
    
    onLoad(options) {
        this.agentId = options.id
        this.loadAgentDetail()
    },
    
    methods: {
        // 加载智能体详情
        async loadAgentDetail() {
            uni.showLoading({ title: '加载中...' })
            
            try {
                const res = await agentsApi.getAgentDetail(this.agentId)
                
                if (res.success && res.agent) {
                    this.agent = res.agent
                    
                    // 处理标签
                    if (this.agent.tags && typeof this.agent.tags === 'string') {
                        this.agent.tags = this.agent.tags.split(',').map(t => t.trim()).filter(t => t)
                    }
                    
                    // 生成示例问题
                    this.generateExamples()
                } else {
                    uni.showToast({ title: res.error || '加载失败', icon: 'none' })
                }
            } catch (error) {
                console.error('加载详情失败:', error)
                uni.showToast({ title: '加载失败', icon: 'none' })
            } finally {
                uni.hideLoading()
            }
        },
        
        // 生成示例问题
        generateExamples() {
            const category = this.agent.category || this.agent.category_name || '通用'
            const name = this.agent.name
            
            // 根据分类生成示例
            const exampleMap = {
                '写作': [
                    `请帮我写一篇关于人工智能的文章`,
                    `给我一些写作灵感`,
                    `帮我润色这段文字`
                ],
                '编程': [
                    `用Python写一个排序算法`,
                    `帮我调试这段代码`,
                    `解释什么是递归`
                ],
                '分析': [
                    `分析这个数据的趋势`,
                    `帮我做SWOT分析`,
                    `解释这个统计结果`
                ],
                '创意': [
                    `帮我设计一个logo`,
                    `给我一些创意灵感`,
                    `帮我写一首诗歌`
                ],
                '教育': [
                    `解释牛顿第二定律`,
                    `帮我制定学习计划`,
                    `出一道数学题`
                ],
                '商务': [
                    `帮我写一封商务邮件`,
                    `制作一份PPT大纲`,
                    `分析市场趋势`
                ]
            }
            
            this.examples = exampleMap[category] || [
                `你好，${name}！请介绍一下你自己。`,
                `你能帮我做什么？`,
                `开始使用`
            ]
        },
        
        // 使用示例
        useExample(example) {
            uni.switchTab({
                url: '/pages/chat/chat',
                success: () => {
                    // 存储当前智能体信息到全局
                    getApp().globalData.currentAgent = this.agent
                    uni.$emit('setChatInput', example)
                }
            })
        },
        
        // 收藏智能体
        async collectAgent() {
            this.isCollected = !this.isCollected
            uni.showToast({
                title: this.isCollected ? '已收藏' : '已取消收藏',
                icon: 'success'
            })
        },
        
        // 开始对话
        startChat() {
            if (this.agent.status !== 'active') {
                uni.showModal({
                    title: '提示',
                    content: '该智能体尚未部署，是否立即部署？',
                    success: (res) => {
                        if (res.confirm) {
                            this.deployAgent()
                        }
                    }
                })
                return
            }
            
            uni.switchTab({
                url: '/pages/chat/chat',
                success: () => {
                    getApp().globalData.currentAgent = this.agent
                    uni.$emit('setChatInput', `你好，${this.agent.name}！`)
                }
            })
        },
        
        // 部署智能体
        async deployAgent() {
            uni.showLoading({ title: '部署中...' })
            
            try {
                const res = await agentsApi.deployAgent(this.agentId)
                
                if (res.success) {
                    uni.showToast({ title: '部署成功', icon: 'success' })
                    this.loadAgentDetail()
                } else {
                    uni.showToast({ title: res.error || '部署失败', icon: 'none' })
                }
            } catch (error) {
                console.error('部署失败:', error)
                uni.showToast({ title: '部署失败', icon: 'none' })
            } finally {
                uni.hideLoading()
            }
        }
    }
}
</script>

<style scoped>
.detail-container {
    min-height: 100vh;
    background: #f8fafc;
    padding-bottom: 160rpx;
}

/* 头部区域 */
.header-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 48rpx 32rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.agent-avatar {
    width: 160rpx;
    height: 160rpx;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 40rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 72rpx;
    margin-bottom: 24rpx;
}

.agent-name {
    font-size: 40rpx;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 8rpx;
}

.agent-category {
    font-size: 26rpx;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 16rpx;
}

.status-badge {
    padding: 8rpx 24rpx;
    border-radius: 24rpx;
    font-size: 24rpx;
    margin-bottom: 24rpx;
}

.status-badge.active {
    background: #16a34a;
    color: #ffffff;
}

.status-badge.draft {
    background: #d97706;
    color: #ffffff;
}

.status-badge.inactive {
    background: #dc2626;
    color: #ffffff;
}

.stats-row {
    display: flex;
    gap: 48rpx;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.stat-value {
    font-size: 36rpx;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 4rpx;
}

.stat-label {
    font-size: 24rpx;
    color: rgba(255, 255, 255, 0.7);
}

/* 区块 */
.section {
    background: #ffffff;
    margin: 24rpx;
    padding: 24rpx;
    border-radius: 20rpx;
}

.section-title {
    display: block;
    font-size: 30rpx;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 16rpx;
}

.section-content {
    font-size: 28rpx;
    color: #4b5563;
    line-height: 1.8;
}

/* 标签列表 */
.tag-list {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
}

.tag {
    padding: 12rpx 24rpx;
    background: #f3f4f6;
    border-radius: 16rpx;
    font-size: 26rpx;
    color: #4c51bf;
}

/* 模型信息卡片 */
.model-info-card {
    background: #f8fafc;
    border-radius: 12rpx;
    padding: 20rpx;
}

.model-label {
    display: block;
    font-size: 24rpx;
    color: #6b7280;
    margin-top: 12rpx;
}

.model-label:first-child {
    margin-top: 0;
}

.model-value {
    display: block;
    font-size: 28rpx;
    color: #1f2937;
    font-weight: 500;
    margin-top: 4rpx;
}

/* 示例列表 */
.example-list {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.example-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20rpx 24rpx;
    background: #f8fafc;
    border-radius: 12rpx;
    border: 1rpx solid #e5e7eb;
}

.example-text {
    flex: 1;
    font-size: 26rpx;
    color: #4b5563;
}

.example-arrow {
    color: #4c51bf;
    font-size: 26rpx;
}

/* 提示词盒子 */
.prompt-box {
    background: #f8fafc;
    border-radius: 12rpx;
    padding: 20rpx;
    border-left: 4rpx solid #4c51bf;
}

.prompt-content {
    font-size: 26rpx;
    color: #4b5563;
    line-height: 1.8;
}

/* 底部操作 */
.footer-actions {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    gap: 24rpx;
    padding: 24rpx 32rpx;
    background: #ffffff;
    border-top: 1rpx solid #e5e7eb;
    padding-bottom: calc(24rpx + env(safe-area-inset-bottom));
}

.action-btn {
    flex: 1;
    height: 88rpx;
    border-radius: 16rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30rpx;
    font-weight: 500;
}

.action-btn.secondary {
    background: #f3f4f6;
    color: #4b5563;
}

.action-btn.primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #ffffff;
}
</style>