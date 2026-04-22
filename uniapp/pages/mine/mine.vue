<template>
    <view class="page">
        <view class="hero">
            <view class="avatar">
                <text class="avatar-text">{{ (userInfo.username || 'U').slice(0, 1).toUpperCase() }}</text>
            </view>
            <view class="user-block">
                <text class="username">{{ userInfo.username || '未登录' }}</text>
                <text class="subtext">ID: {{ userInfo.id || '--' }}</text>
                <text class="subtext">{{ userInfo.email || '未绑定邮箱' }}</text>
            </view>
            <view class="edit-btn" @click="editProfile">编辑</view>
        </view>

        <view class="stats-card">
            <view class="stat">
                <text class="stat-value">{{ stats.chatCount }}</text>
                <text class="stat-label">对话次数</text>
            </view>
            <view class="stat">
                <text class="stat-value">{{ stats.tokenUsed }}</text>
                <text class="stat-label">Token</text>
            </view>
            <view class="stat">
                <text class="stat-value">{{ stats.days }}</text>
                <text class="stat-label">使用天数</text>
            </view>
        </view>

        <view class="menu-card">
            <view class="menu-item" @click="openChat">
                <text class="menu-title">我的对话</text>
                <text class="menu-arrow">></text>
            </view>
            <view class="menu-item" @click="showCollections">
                <text class="menu-title">我的收藏</text>
                <text class="menu-arrow">></text>
            </view>
            <view class="menu-item" @click="showHistory">
                <text class="menu-title">历史记录</text>
                <text class="menu-arrow">></text>
            </view>
            <view class="menu-item" @click="showSettings">
                <text class="menu-title">设置</text>
                <text class="menu-arrow">></text>
            </view>
            <view class="menu-item" @click="showHelp">
                <text class="menu-title">帮助中心</text>
                <text class="menu-arrow">></text>
            </view>
        </view>

        <view class="logout-btn" @click="handleLogout">退出登录</view>
    </view>
</template>

<script>
import userApi from '@/api/user.js'

export default {
    data() {
        return {
            userInfo: {},
            stats: {
                chatCount: 0,
                tokenUsed: 0,
                days: 0
            }
        }
    },

    onShow() {
        this.loadUserInfo()
    },

    methods: {
        async loadUserInfo() {
            this.userInfo = uni.getStorageSync('userInfo') || {}

            try {
                const profileRes = await userApi.getUserInfo()
                if ((profileRes.success || profileRes.status === 'success') && profileRes.data) {
                    this.userInfo = {
                        ...this.userInfo,
                        ...profileRes.data
                    }
                    uni.setStorageSync('userInfo', this.userInfo)
                }
            } catch (error) {
                console.error('loadUserInfo profile failed', error)
            }

            try {
                const statsRes = await userApi.getUserStats()
                if ((statsRes.success || statsRes.status === 'success') && statsRes.data) {
                    this.stats = {
                        chatCount: statsRes.data.total_calls || 0,
                        tokenUsed: statsRes.data.total_tokens || 0,
                        days: this.calculateDays()
                    }
                }
            } catch (error) {
                console.error('loadUserInfo stats failed', error)
            }
        },

        calculateDays() {
            const createdAt = this.userInfo.created_at || uni.getStorageSync('registerTime')
            if (!createdAt) return 1
            const days = Math.floor((Date.now() - new Date(createdAt).getTime()) / (1000 * 60 * 60 * 24))
            return Math.max(1, days)
        },

        editProfile() {
            uni.navigateTo({ url: '/pages/settings/profile' })
        },

        openChat() {
            uni.switchTab({ url: '/pages/chat/chat' })
        },

        showCollections() {
            uni.navigateTo({ url: '/pages/collections/collections' })
        },

        showHistory() {
            uni.navigateTo({ url: '/pages/history/history' })
        },

        showSettings() {
            uni.navigateTo({ url: '/pages/settings/settings' })
        },

        showHelp() {
            uni.navigateTo({ url: '/pages/help/help' })
        },

        async handleLogout() {
            uni.showModal({
                title: '确认退出',
                content: '确定要退出登录吗？',
                success: async (res) => {
                    if (!res.confirm) return
                    await userApi.logout()
                    uni.redirectTo({ url: '/pages/login/login' })
                }
            })
        }
    }
}
</script>

<style scoped>
.page {
    min-height: 100vh;
    background: #f8fafc;
    padding: 24rpx;
}

.hero {
    display: flex;
    align-items: center;
    gap: 20rpx;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 24rpx;
    padding: 28rpx;
    color: #ffffff;
}

.avatar {
    width: 108rpx;
    height: 108rpx;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.22);
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-text {
    font-size: 44rpx;
    font-weight: 700;
}

.user-block {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.username {
    font-size: 34rpx;
    font-weight: 700;
}

.subtext {
    margin-top: 6rpx;
    font-size: 24rpx;
    color: rgba(255, 255, 255, 0.82);
}

.edit-btn {
    padding: 12rpx 22rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.18);
    font-size: 24rpx;
}

.stats-card,
.menu-card {
    margin-top: 24rpx;
    background: #ffffff;
    border-radius: 20rpx;
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);
}

.stats-card {
    display: flex;
    justify-content: space-between;
    padding: 24rpx;
}

.stat {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.stat-value {
    font-size: 34rpx;
    font-weight: 700;
    color: #1f2937;
}

.stat-label {
    margin-top: 8rpx;
    font-size: 24rpx;
    color: #6b7280;
}

.menu-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 28rpx 24rpx;
    border-bottom: 1rpx solid #f3f4f6;
}

.menu-item:last-child {
    border-bottom: none;
}

.menu-title {
    font-size: 28rpx;
    color: #1f2937;
}

.menu-arrow {
    color: #9ca3af;
}

.logout-btn {
    margin-top: 32rpx;
    background: #ffffff;
    border-radius: 18rpx;
    padding: 26rpx;
    text-align: center;
    color: #ef4444;
    font-size: 28rpx;
}
</style>
