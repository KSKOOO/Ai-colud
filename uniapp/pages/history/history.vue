<template>
    <view class="history-container">
        <!-- 筛选栏 -->
        <view class="filter-bar">
            <view class="search-box">
                <text class="search-icon">🔍</text>
                <input 
                    type="text" 
                    v-model="searchKeyword" 
                    placeholder="搜索历史记录..."
                    placeholder-class="placeholder"
                    @input="filterHistory"
                />
            </view>
            <view class="filter-btn" @click="showFilter">
                <text>筛选</text>
            </view>
        </view>
        
        <!-- 历史列表 -->
        <scroll-view scroll-y class="history-list" @scrolltolower="loadMore">
            <view 
                class="history-item" 
                v-for="(item, index) in filteredHistory" 
                :key="index"
                @click="continueChat(item)"
            >
                <view class="item-header">
                    <view class="item-type" :class="item.type">
                        <text>{{ getTypeLabel(item.type) }}</text>
                    </view>
                    <text class="item-time">{{ item.time }}</text>
                </view>
                <view class="item-content">
                    <text class="item-title">{{ item.title }}</text>
                    <text class="item-preview">{{ item.preview }}</text>
                </view>
                <view class="item-footer">
                    <text class="item-model">模型: {{ item.model || '默认' }}</text>
                    <view class="item-actions">
                        <text class="action-btn" @click.stop="deleteItem(index)">删除</text>
                        <text class="action-btn primary" @click.stop="collectItem(item)">收藏</text>
                    </view>
                </view>
            </view>
            
            <!-- 空状态 -->
            <view class="empty-state" v-if="filteredHistory.length === 0">
                <text class="empty-icon">📋</text>
                <text class="empty-text">暂无历史记录</text>
                <text class="empty-desc">开始对话后会自动保存</text>
            </view>
        </scroll-view>
        
        <!-- 清空按钮 -->
        <view class="clear-section" v-if="historyList.length > 0">
            <view class="clear-btn" @click="clearAll">
                <text>清空所有历史</text>
            </view>
        </view>
    </view>
</template>

<script>
export default {
    data() {
        return {
            searchKeyword: '',
            historyList: [],
            filteredHistory: []
        }
    },
    onShow() {
        this.loadHistory()
    },
    methods: {
        loadHistory() {
            this.historyList = uni.getStorageSync('chatHistory') || []
            this.filteredHistory = this.historyList
        },
        filterHistory() {
            if (!this.searchKeyword) {
                this.filteredHistory = this.historyList
                return
            }
            const keyword = this.searchKeyword.toLowerCase()
            this.filteredHistory = this.historyList.filter(item => 
                item.title.toLowerCase().includes(keyword) ||
                item.preview.toLowerCase().includes(keyword)
            )
        },
        getTypeLabel(type) {
            const labels = {
                'chat': '对话',
                'agent': '智能体',
                'writing': '写作',
                'code': '代码',
                'analysis': '分析'
            }
            return labels[type] || '对话'
        },
        continueChat(chat) {
            uni.switchTab({
                url: '/pages/chat/chat',
                success: () => {
                    uni.$emit('loadChatHistory', chat)
                }
            })
        },
        deleteItem(index) {
            uni.showModal({
                title: '确认删除',
                content: '确定要删除这条记录吗？',
                success: (res) => {
                    if (res.confirm) {
                        this.historyList.splice(index, 1)
                        uni.setStorageSync('chatHistory', this.historyList)
                        this.filterHistory()
                        uni.showToast({ title: '已删除', icon: 'success' })
                    }
                }
            })
        },
        collectItem(item) {
            const collections = uni.getStorageSync('collectedChats') || []
            const exists = collections.find(c => c.title === item.title)
            if (exists) {
                uni.showToast({ title: '已收藏', icon: 'none' })
                return
            }
            collections.unshift(item)
            uni.setStorageSync('collectedChats', collections)
            uni.showToast({ title: '收藏成功', icon: 'success' })
        },
        clearAll() {
            uni.showModal({
                title: '确认清空',
                content: '确定要清空所有历史记录吗？此操作不可恢复',
                success: (res) => {
                    if (res.confirm) {
                        this.historyList = []
                        uni.setStorageSync('chatHistory', [])
                        this.filteredHistory = []
                        uni.showToast({ title: '已清空', icon: 'success' })
                    }
                }
            })
        },
        showFilter() {
            uni.showActionSheet({
                itemList: ['全部', '对话', '智能体', '写作', '代码'],
                success: (res) => {
                    const types = ['all', 'chat', 'agent', 'writing', 'code']
                    const type = types[res.tapIndex]
                    if (type === 'all') {
                        this.filteredHistory = this.historyList
                    } else {
                        this.filteredHistory = this.historyList.filter(item => item.type === type)
                    }
                }
            })
        },
        loadMore() {
            // 分页加载逻辑
        }
    }
}
</script>

<style scoped>
.history-container {
    min-height: 100vh;
    background: #f8fafc;
}

.filter-bar {
    display: flex;
    padding: 24rpx;
    background: #ffffff;
    gap: 16rpx;
}

.search-box {
    flex: 1;
    display: flex;
    align-items: center;
    background: #f3f4f6;
    border-radius: 32rpx;
    padding: 16rpx 24rpx;
}

.search-icon {
    font-size: 28rpx;
    margin-right: 12rpx;
}

.search-box input {
    flex: 1;
    font-size: 28rpx;
}

.placeholder {
    color: #9ca3af;
}

.filter-btn {
    padding: 16rpx 32rpx;
    background: #4c51bf;
    border-radius: 32rpx;
    color: #ffffff;
    font-size: 26rpx;
}

.history-list {
    padding: 24rpx;
}

.history-item {
    background: #ffffff;
    border-radius: 16rpx;
    padding: 24rpx;
    margin-bottom: 16rpx;
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.04);
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16rpx;
}

.item-type {
    padding: 6rpx 16rpx;
    border-radius: 8rpx;
    font-size: 22rpx;
}

.item-type.chat {
    background: #dbeafe;
    color: #1d4ed8;
}

.item-type.agent {
    background: #dcfce7;
    color: #16a34a;
}

.item-type.writing {
    background: #fef3c7;
    color: #d97706;
}

.item-type.code {
    background: #fce7f3;
    color: #db2777;
}

.item-time {
    font-size: 24rpx;
    color: #9ca3af;
}

.item-content {
    margin-bottom: 16rpx;
}

.item-title {
    display: block;
    font-size: 30rpx;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8rpx;
}

.item-preview {
    display: block;
    font-size: 26rpx;
    color: #6b7280;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.item-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 16rpx;
    border-top: 1rpx solid #f3f4f6;
}

.item-model {
    font-size: 24rpx;
    color: #9ca3af;
}

.item-actions {
    display: flex;
    gap: 16rpx;
}

.action-btn {
    padding: 8rpx 20rpx;
    background: #f3f4f6;
    border-radius: 20rpx;
    font-size: 24rpx;
    color: #6b7280;
}

.action-btn.primary {
    background: #eef2ff;
    color: #4c51bf;
}

.clear-section {
    padding: 24rpx;
}

.clear-btn {
    padding: 24rpx;
    background: #fee2e2;
    border-radius: 16rpx;
    text-align: center;
    color: #dc2626;
    font-size: 28rpx;
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
