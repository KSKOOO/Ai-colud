<template>
    <view class="agents-container">
        <!-- 搜索栏 -->
        <view class="search-bar">
            <view class="search-input">
                <text class="search-icon">🔍</text>
                <input 
                    type="text" 
                    v-model="searchKeyword" 
                    placeholder="搜索智能体..."
                    placeholder-class="placeholder"
                    @input="handleSearch"
                />
            </view>
        </view>
        
        <!-- 分类标签 -->
        <scroll-view scroll-x class="category-tabs">
            <view 
                class="tab-item" 
                :class="{ active: currentCategory === 'all' }"
                @click="changeCategory('all')"
            >
                全部
            </view>
            <view 
                class="tab-item" 
                :class="{ active: currentCategory === cat.id }"
                v-for="cat in categories" 
                :key="cat.id"
                @click="changeCategory(cat.id)"
            >
                {{ cat.name }}
            </view>
        </scroll-view>
        
        <!-- 智能体列表 -->
        <scroll-view scroll-y class="agent-list" @scrolltolower="loadMore">
            <view 
                class="agent-card" 
                v-for="agent in agentList" 
                :key="agent.id"
                @click="navigateToDetail(agent)"
            >
                <view class="agent-header">
                    <view class="agent-icon">{{ agent.icon || '🤖' }}</view>
                    <view class="agent-info">
                        <text class="agent-name">{{ agent.name }}</text>
                        <text class="agent-category">{{ agent.category_name || '通用' }}</text>
                    </view>
                    <view class="agent-status" :class="agent.status">
                        <text>{{ agent.status === 'active' ? '在线' : (agent.status === 'draft' ? '草稿' : '离线') }}</text>
                    </view>
                </view>
                
                <text class="agent-desc">{{ agent.description }}</text>
                
                <view class="agent-tags">
                    <text class="tag" v-for="(tag, index) in (agent.tags || [])" :key="index">
                        {{ tag }}
                    </text>
                </view>
                
                <view class="agent-footer">
                    <view class="stats">
                        <text class="stat-item">💬 {{ agent.chat_count || 0 }} 次对话</text>
                        <text class="stat-item">👍 {{ agent.likes || 0 }}</text>
                    </view>
                    <view class="action-btn" @click.stop="quickChat(agent)">
                        开始对话
                    </view>
                </view>
            </view>
            
            <!-- 加载更多 -->
            <view class="load-more" v-if="loading">
                <view class="loading-spinner"></view>
                <text>加载中...</text>
            </view>
            
            <!-- 空状态 -->
            <view class="empty-state" v-if="!loading && agentList.length === 0">
                <text class="empty-icon">🤖</text>
                <text class="empty-text">暂无智能体</text>
                <text class="empty-desc">点击右下角 + 创建你的第一个智能体</text>
            </view>
        </scroll-view>
        
        <!-- 创建按钮 -->
        <view class="create-btn" @click="createAgent">
            <text>+</text>
        </view>
    </view>
</template>

<script>
import agentsApi from '@/api/agents.js'

export default {
    data() {
        return {
            searchKeyword: '',
            currentCategory: 'all',
            categories: [
                { id: 'writing', name: '写作' },
                { id: 'coding', name: '编程' },
                { id: 'analysis', name: '分析' },
                { id: 'creative', name: '创意' },
                { id: 'education', name: '教育' },
                { id: 'business', name: '商务' }
            ],
            agentList: [],
            loading: false,
            page: 1,
            hasMore: true
        }
    },
    
    onLoad() {
        this.loadAgents()
    },
    
    onShow() {
        this.loadAgents()
    },
    
    onPullDownRefresh() {
        this.page = 1
        this.hasMore = true
        this.loadAgents().then(() => {
            uni.stopPullDownRefresh()
        })
    },
    
    methods: {
        // 加载智能体列表
        async loadAgents() {
            if (this.loading) return
            this.loading = true
            
            try {
                const res = await agentsApi.getAgents({
                    page: this.page,
                    category: this.currentCategory === 'all' ? '' : this.currentCategory,
                    keyword: this.searchKeyword
                })
                
                if (res.success && res.agents) {
                    const agents = res.agents.map(agent => {
                        // 解析tags字段（可能是JSON字符串或数组）
                        let tags = []
                        if (agent.tags) {
                            try {
                                tags = JSON.parse(agent.tags)
                            } catch (e) {
                                tags = agent.tags.split(',').map(t => t.trim()).filter(t => t)
                            }
                        }
                        
                        return {
                            ...agent,
                            // 兼容字段映射
                            icon: agent.icon || '🤖',
                            category_name: agent.category || '通用',
                            tags: tags,
                            chat_count: agent.total_tasks || agent.usage_count || 0,
                            likes: Math.floor(Math.random() * 1000) // 模拟点赞数
                        }
                    })
                    
                    if (this.page === 1) {
                        this.agentList = agents
                    } else {
                        this.agentList = [...this.agentList, ...agents]
                    }
                    this.hasMore = agents.length >= 10
                } else {
                    if (this.page === 1) {
                        this.agentList = []
                    }
                    this.hasMore = false
                }
            } catch (error) {
                console.error('加载智能体失败:', error)
                uni.showToast({ title: '加载失败', icon: 'none' })
                if (this.page === 1) {
                    this.agentList = []
                }
            } finally {
                this.loading = false
            }
        },
        
        // 加载更多
        loadMore() {
            if (this.hasMore && !this.loading) {
                this.page++
                this.loadAgents()
            }
        },
        
        // 搜索
        handleSearch() {
            this.page = 1
            this.loadAgents()
        },
        
        // 切换分类
        changeCategory(categoryId) {
            this.currentCategory = categoryId
            this.page = 1
            this.loadAgents()
        },
        
        // 跳转详情
        navigateToDetail(agent) {
            uni.navigateTo({
                url: `/pages/agent-detail/agent-detail?id=${agent.id}`
            })
        },
        
        // 快速对话
        quickChat(agent) {
            uni.switchTab({
                url: '/pages/chat/chat',
                success: () => {
                    // 将智能体信息存储到全局
                    getApp().globalData.currentAgent = agent
                    uni.$emit('setChatInput', `你好，${agent.name}！`)
                }
            })
        },
        
        // 创建智能体
        createAgent() {
            uni.navigateTo({
                url: '/pages/agent-create/agent-create'
            })
        }
    }
}
</script>

<style scoped>
.agents-container {
    min-height: 100vh;
    background: #f8fafc;
    padding-bottom: 120rpx;
}

/* 搜索栏 */
.search-bar {
    padding: 24rpx;
    background: #ffffff;
}

.search-input {
    display: flex;
    align-items: center;
    background: #f3f4f6;
    border-radius: 40rpx;
    padding: 16rpx 24rpx;
}

.search-icon {
    font-size: 28rpx;
    margin-right: 12rpx;
}

.search-input input {
    flex: 1;
    font-size: 28rpx;
}

.placeholder {
    color: #9ca3af;
}

/* 分类标签 */
.category-tabs {
    white-space: nowrap;
    padding: 16rpx 24rpx;
    background: #ffffff;
    border-bottom: 1rpx solid #e5e7eb;
}

.tab-item {
    display: inline-block;
    padding: 12rpx 32rpx;
    margin-right: 16rpx;
    font-size: 26rpx;
    color: #6b7280;
    background: #f3f4f6;
    border-radius: 24rpx;
}

.tab-item.active {
    background: #4c51bf;
    color: #ffffff;
}

/* 智能体列表 */
.agent-list {
    padding: 24rpx;
}

.agent-card {
    background: #ffffff;
    border-radius: 24rpx;
    padding: 24rpx;
    margin-bottom: 24rpx;
    box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.06);
}

.agent-header {
    display: flex;
    align-items: center;
    margin-bottom: 16rpx;
}

.agent-icon {
    width: 80rpx;
    height: 80rpx;
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    border-radius: 20rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36rpx;
    margin-right: 16rpx;
}

.agent-info {
    flex: 1;
}

.agent-name {
    display: block;
    font-size: 30rpx;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4rpx;
}

.agent-category {
    display: block;
    font-size: 24rpx;
    color: #6b7280;
}

.agent-status {
    padding: 6rpx 16rpx;
    border-radius: 16rpx;
    font-size: 22rpx;
}

.agent-status.active {
    background: #dcfce7;
    color: #16a34a;
}

.agent-status.draft {
    background: #fef3c7;
    color: #d97706;
}

.agent-status.inactive {
    background: #fee2e2;
    color: #dc2626;
}

.agent-desc {
    display: block;
    font-size: 26rpx;
    color: #4b5563;
    line-height: 1.6;
    margin-bottom: 16rpx;
}

.agent-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
    margin-bottom: 16rpx;
}

.tag {
    padding: 6rpx 16rpx;
    background: #f3f4f6;
    border-radius: 8rpx;
    font-size: 22rpx;
    color: #6b7280;
}

.agent-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 16rpx;
    border-top: 1rpx solid #f3f4f6;
}

.stats {
    display: flex;
    gap: 24rpx;
}

.stat-item {
    font-size: 24rpx;
    color: #6b7280;
}

.action-btn {
    padding: 12rpx 32rpx;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 24rpx;
    color: #ffffff;
    font-size: 26rpx;
    font-weight: 500;
}

/* 加载更多 */
.load-more {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32rpx;
    color: #6b7280;
    font-size: 26rpx;
}

.loading-spinner {
    width: 32rpx;
    height: 32rpx;
    border: 3rpx solid #e5e7eb;
    border-top-color: #4c51bf;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 12rpx;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* 空状态 */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 120rpx 0;
}

.empty-icon {
    font-size: 120rpx;
    margin-bottom: 32rpx;
}

.empty-text {
    font-size: 32rpx;
    color: #1f2937;
    margin-bottom: 12rpx;
}

.empty-desc {
    font-size: 26rpx;
    color: #6b7280;
}

/* 创建按钮 */
.create-btn {
    position: fixed;
    right: 32rpx;
    bottom: calc(160rpx + env(safe-area-inset-bottom));
    width: 100rpx;
    height: 100rpx;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48rpx;
    color: #ffffff;
    box-shadow: 0 8rpx 24rpx rgba(102, 126, 234, 0.4);
}
</style>