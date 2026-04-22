<template>
    <view class="collections-container">
        <!-- 分类标签 -->
        <view class="category-tabs">
            <view 
                class="tab-item" 
                :class="{ active: currentTab === 'agents' }"
                @click="changeTab('agents')"
            >
                智能体
            </view>
            <view 
                class="tab-item" 
                :class="{ active: currentTab === 'chats' }"
                @click="changeTab('chats')"
            >
                对话
            </view>
            <view 
                class="tab-item" 
                :class="{ active: currentTab === 'models' }"
                @click="changeTab('models')"
            >
                模型
            </view>
        </view>
        
        <!-- 收藏列表 -->
        <scroll-view scroll-y class="collections-list">
            <!-- 智能体收藏 -->
            <view v-if="currentTab === 'agents'">
                <view 
                    class="collection-item" 
                    v-for="item in agentCollections" 
                    :key="item.id"
                    @click="navigateTo('/pages/agent-detail/agent-detail?id=' + item.id)"
                >
                    <view class="item-icon">{{ item.icon }}</view>
                    <view class="item-info">
                        <text class="item-name">{{ item.name }}</text>
                        <text class="item-desc">{{ item.description }}</text>
                    </view>
                    <view class="item-action" @click.stop="removeCollection('agent', item.id)">
                        <text class="remove-btn">取消收藏</text>
                    </view>
                </view>
                <view class="empty-state" v-if="agentCollections.length === 0">
                    <text class="empty-icon">🤖</text>
                    <text class="empty-text">暂无收藏的智能体</text>
                    <text class="empty-desc">去智能体页面发现更多</text>
                </view>
            </view>
            
            <!-- 对话收藏 -->
            <view v-if="currentTab === 'chats'">
                <view 
                    class="collection-item" 
                    v-for="(item, index) in chatCollections" 
                    :key="index"
                    @click="continueChat(item)"
                >
                    <view class="item-icon">💬</view>
                    <view class="item-info">
                        <text class="item-name">{{ item.title }}</text>
                        <text class="item-desc">{{ item.time }}</text>
                    </view>
                    <view class="item-action" @click.stop="removeCollection('chat', index)">
                        <text class="remove-btn">取消收藏</text>
                    </view>
                </view>
                <view class="empty-state" v-if="chatCollections.length === 0">
                    <text class="empty-icon">💬</text>
                    <text class="empty-text">暂无收藏的对话</text>
                    <text class="empty-desc">在聊天时长按收藏</text>
                </view>
            </view>
            
            <!-- 模型收藏 -->
            <view v-if="currentTab === 'models'">
                <view 
                    class="collection-item" 
                    v-for="(item, index) in modelCollections" 
                    :key="index"
                    @click="selectModel(item)"
                >
                    <view class="item-icon">🧠</view>
                    <view class="item-info">
                        <text class="item-name">{{ item.name }}</text>
                        <text class="item-desc">{{ item.remark }}</text>
                    </view>
                    <view class="item-action" @click.stop="removeCollection('model', index)">
                        <text class="remove-btn">取消收藏</text>
                    </view>
                </view>
                <view class="empty-state" v-if="modelCollections.length === 0">
                    <text class="empty-icon">🧠</text>
                    <text class="empty-text">暂无收藏的模型</text>
                    <text class="empty-desc">在模型选择时收藏</text>
                </view>
            </view>
        </scroll-view>
    </view>
</template>

<script>
export default {
    data() {
        return {
            currentTab: 'agents',
            agentCollections: [],
            chatCollections: [],
            modelCollections: []
        }
    },
    onShow() {
        this.loadCollections()
    },
    methods: {
        changeTab(tab) {
            this.currentTab = tab
        },
        loadCollections() {
            // 从本地存储加载收藏
            this.agentCollections = uni.getStorageSync('collectedAgents') || []
            this.chatCollections = uni.getStorageSync('collectedChats') || []
            this.modelCollections = uni.getStorageSync('collectedModels') || []
        },
        removeCollection(type, id) {
            uni.showModal({
                title: '确认取消收藏',
                content: '确定要取消收藏吗？',
                success: (res) => {
                    if (res.confirm) {
                        if (type === 'agent') {
                            this.agentCollections = this.agentCollections.filter(item => item.id !== id)
                            uni.setStorageSync('collectedAgents', this.agentCollections)
                        } else if (type === 'chat') {
                            this.chatCollections.splice(id, 1)
                            uni.setStorageSync('collectedChats', this.chatCollections)
                        } else if (type === 'model') {
                            this.modelCollections.splice(id, 1)
                            uni.setStorageSync('collectedModels', this.modelCollections)
                        }
                        uni.showToast({ title: '已取消收藏', icon: 'success' })
                    }
                }
            })
        },
        navigateTo(url) {
            uni.navigateTo({ url })
        },
        continueChat(chat) {
            uni.switchTab({
                url: '/pages/chat/chat',
                success: () => {
                    uni.$emit('loadChatHistory', chat)
                }
            })
        },
        selectModel(model) {
            uni.switchTab({
                url: '/pages/chat/chat',
                success: () => {
                    uni.$emit('selectModel', model.name)
                }
            })
        }
    }
}
</script>

<style scoped>
.collections-container {
    min-height: 100vh;
    background: #f8fafc;
}

.category-tabs {
    display: flex;
    padding: 16rpx 24rpx;
    background: #ffffff;
    border-bottom: 1rpx solid #e5e7eb;
}

.tab-item {
    flex: 1;
    text-align: center;
    padding: 20rpx 0;
    font-size: 28rpx;
    color: #6b7280;
    border-bottom: 3rpx solid transparent;
}

.tab-item.active {
    color: #4c51bf;
    border-bottom-color: #4c51bf;
    font-weight: 600;
}

.collections-list {
    padding: 24rpx;
}

.collection-item {
    display: flex;
    align-items: center;
    background: #ffffff;
    border-radius: 16rpx;
    padding: 24rpx;
    margin-bottom: 16rpx;
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.04);
}

.item-icon {
    width: 72rpx;
    height: 72rpx;
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    border-radius: 16rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32rpx;
    margin-right: 20rpx;
}

.item-info {
    flex: 1;
}

.item-name {
    display: block;
    font-size: 28rpx;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4rpx;
}

.item-desc {
    display: block;
    font-size: 24rpx;
    color: #6b7280;
}

.remove-btn {
    padding: 8rpx 20rpx;
    background: #fee2e2;
    color: #dc2626;
    border-radius: 20rpx;
    font-size: 22rpx;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 120rpx 0;
}

.empty-icon {
    font-size: 80rpx;
    margin-bottom: 24rpx;
}

.empty-text {
    font-size: 30rpx;
    color: #1f2937;
    margin-bottom: 8rpx;
}

.empty-desc {
    font-size: 26rpx;
    color: #6b7280;
}
</style>
