<template>
    <view class="mine-container">
        <!-- Logo区域 -->
        <view class="logo-section">
            <image class="page-logo" src="http://tijo45376797.vicp.fun/assets/images/logo.png" mode="aspectFit"></image>
            <text class="logo-text">凌岳AI助手</text>
        </view>
        
        <!-- 用户信息卡片 -->
        <view class="user-card">
            <view class="user-header">
                <view class="avatar">
                    <text class="avatar-text">{{ (userInfo.username || 'U')[0].toUpperCase() }}</text>
                </view>
                <view class="user-info">
                    <text class="username">{{ userInfo.username || '未登录' }}</text>
                    <text class="user-id">ID: {{ userInfo.id || '--' }}</text>
                </view>
                <view class="edit-btn" @click="editProfile">
                    <text>编辑</text>
                </view>
            </view>
            
            <!-- 用户统计 -->
            <view class="user-stats">
                <view class="stat-item">
                    <text class="stat-value">{{ stats.chatCount }}</text>
                    <text class="stat-label">对话次数</text>
                </view>
                <view class="stat-divider"></view>
                <view class="stat-item">
                    <text class="stat-value">{{ stats.tokenUsed }}</text>
                    <text class="stat-label">消耗Token</text>
                </view>
                <view class="stat-divider"></view>
                <view class="stat-item">
                    <text class="stat-value">{{ stats.days }}</text>
                    <text class="stat-label">使用天数</text>
                </view>
            </view>
        </view>
        
        <!-- 功能菜单 -->
        <view class="menu-section">
            <view class="menu-item" @click="navigateTo('/pages/chat/chat')">
                <text class="menu-icon">💬</text>
                <text class="menu-title">我的对话</text>
                <text class="menu-arrow">›</text>
            </view>
            <view class="menu-item" @click="showCollections">
                <text class="menu-icon">❤️</text>
                <text class="menu-title">我的收藏</text>
                <text class="menu-arrow">›</text>
            </view>
            <view class="menu-item" @click="showHistory">
                <text class="menu-icon">📋</text>
                <text class="menu-title">历史记录</text>
                <text class="menu-arrow">›</text>
            </view>
        </view>
        
        <view class="menu-section">
            <view class="menu-item" @click="showSettings">
                <text class="menu-icon">⚙️</text>
                <text class="menu-title">设置</text>
                <text class="menu-arrow">›</text>
            </view>
            <view class="menu-item" @click="showHelp">
                <text class="menu-icon">❓</text>
                <text class="menu-title">帮助中心</text>
                <text class="menu-arrow">›</text>
            </view>
            <view class="menu-item" @click="showAbout">
                <text class="menu-icon">ℹ️</text>
                <text class="menu-title">关于我们</text>
                <text class="menu-arrow">›</text>
            </view>
        </view>
        
        <!-- 退出登录 -->
        <view class="logout-section">
            <view class="logout-btn" @click="handleLogout">
                <text>退出登录</text>
            </view>
        </view>
        
        <!-- 版本信息 -->
        <view class="version-info">
            <text>版本 1.0.0</text>
        </view>
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
    
    onLoad() {
        this.loadUserInfo()
    },
    
    onShow() {
        this.loadUserInfo()
    },
    
    methods: {
        // 加载用户信息
        async loadUserInfo() {
            this.userInfo = uni.getStorageSync('userInfo') || {}
            
            // 从服务器获取真实统计数据
            try {
                const res = await userApi.getUserStats()
                if (res.success && res.data) {
                    this.stats = {
                        chatCount: res.data.total_calls || 0,
                        tokenUsed: res.data.total_tokens || 0,
                        days: this.calculateDays()
                    }
                } else {
                    // 使用本地存储的数据作为备选
                    this.loadLocalStats()
                }
            } catch (error) {
                console.error('获取统计数据失败:', error)
                this.loadLocalStats()
            }
        },
        
        // 加载本地统计数据
        loadLocalStats() {
            const localStats = uni.getStorageSync('userStats') || {}
            this.stats = {
                chatCount: localStats.chatCount || 0,
                tokenUsed: localStats.tokenUsed || 0,
                days: this.calculateDays()
            }
        },
        
        // 计算使用天数
        calculateDays() {
            const registerTime = uni.getStorageSync('registerTime')
            if (registerTime) {
                const days = Math.floor((Date.now() - new Date(registerTime).getTime()) / (1000 * 60 * 60 * 24))
                return Math.max(1, days)
            }
            return 1
        },
        
        // 编辑资料
        editProfile() {
            uni.showToast({ title: '功能开发中', icon: 'none' })
        },
        
        // 页面跳转
        navigateTo(url) {
            if (url.includes('chat')) {
                uni.switchTab({ url })
            } else {
                uni.navigateTo({ url })
            }
        },
        
        // 显示收藏
        showCollections() {
            uni.navigateTo({ url: '/pages/collections/collections' })
        },
        
        // 显示历史
        showHistory() {
            uni.navigateTo({ url: '/pages/history/history' })
        },
        
        // 显示设置
        showSettings() {
            uni.navigateTo({ url: '/pages/settings/settings' })
        },
        
        // 显示帮助
        showHelp() {
            uni.navigateTo({ url: '/pages/help/help' })
        },
        
        // 显示关于
        showAbout() {
            uni.showModal({
                title: '关于凌岳AI助手',
                content: '凌岳AI助手是一款强大的智能对话应用，集成了多种AI模型，为您提供专业的智能服务。\n\n版本：1.0.0\n开发者：巨神兵API辅助平台',
                showCancel: false
            })
        },
        
        // 退出登录
        handleLogout() {
            uni.showModal({
                title: '确认退出',
                content: '确定要退出登录吗？',
                success: async (res) => {
                    if (res.confirm) {
                        try {
                            await userApi.logout()
                        } catch (error) {
                            console.log('退出登录失败')
                        }
                        
                        // 清除本地存储
                        uni.removeStorageSync('token')
                        uni.removeStorageSync('userInfo')
                        
                        // 跳转登录页
                        uni.redirectTo({
                            url: '/pages/login/login'
                        })
                    }
                }
            })
        }
    }
}
</script>

<style scoped>
.mine-container {
    min-height: 100vh;
    background: #f8fafc;
    padding-bottom: 120rpx;
}

/* Logo区域 */
.logo-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 48rpx 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.page-logo {
    width: 120rpx;
    height: 120rpx;
    margin-bottom: 16rpx;
}

.logo-text {
    font-size: 32rpx;
    font-weight: 600;
    color: #ffffff;
}

/* 用户卡片 */
.user-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    margin: 24rpx;
    border-radius: 24rpx;
    padding: 32rpx;
    box-shadow: 0 8rpx 24rpx rgba(102, 126, 234, 0.3);
}

.user-header {
    display: flex;
    align-items: center;
    margin-bottom: 32rpx;
}

.avatar {
    width: 120rpx;
    height: 120rpx;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 24rpx;
}

.avatar-text {
    font-size: 48rpx;
    font-weight: 700;
    color: #ffffff;
}

.user-info {
    flex: 1;
}

.username {
    display: block;
    font-size: 36rpx;
    font-weight: 600;
    color: #ffffff;
    margin-bottom: 8rpx;
}

.user-id {
    display: block;
    font-size: 24rpx;
    color: rgba(255, 255, 255, 0.7);
}

.edit-btn {
    padding: 12rpx 24rpx;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 24rpx;
    font-size: 24rpx;
    color: #ffffff;
}

/* 用户统计 */
.user-stats {
    display: flex;
    align-items: center;
    justify-content: space-around;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 16rpx;
    padding: 24rpx;
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

.stat-divider {
    width: 1rpx;
    height: 48rpx;
    background: rgba(255, 255, 255, 0.2);
}

/* 菜单区块 */
.menu-section {
    background: #ffffff;
    margin: 24rpx;
    border-radius: 20rpx;
    overflow: hidden;
}

.menu-item {
    display: flex;
    align-items: center;
    padding: 28rpx 24rpx;
    border-bottom: 1rpx solid #f3f4f6;
}

.menu-item:last-child {
    border-bottom: none;
}

.menu-icon {
    font-size: 36rpx;
    margin-right: 20rpx;
}

.menu-title {
    flex: 1;
    font-size: 28rpx;
    color: #1f2937;
}

.menu-arrow {
    font-size: 32rpx;
    color: #9ca3af;
}

/* 退出登录 */
.logout-section {
    padding: 32rpx 24rpx;
}

.logout-btn {
    background: #ffffff;
    padding: 28rpx;
    border-radius: 20rpx;
    text-align: center;
    font-size: 28rpx;
    color: #ef4444;
}

/* 版本信息 */
.version-info {
    text-align: center;
    padding: 32rpx;
    color: #9ca3af;
    font-size: 24rpx;
}
</style>
