<script>
export default {
    globalData: {
        // 当前选中的智能体
        currentAgent: null,
        // 用户信息
        userInfo: null,
        // 系统配置
        config: null
    },
    
    onLaunch: function() {
        console.log('App Launch')
        // 检查登录状态
        const token = uni.getStorageSync('token')
        if (!token) {
            uni.redirectTo({
                url: '/pages/login/login'
            })
        }
    },
    onShow: function() {
        console.log('App Show')
    },
    onHide: function() {
        console.log('App Hide')
    }
}
</script>

<style>
/* 全局样式 */
page {
    background-color: #f8fafc;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

/* 主题色 */
:root {
    --primary-color: #4c51bf;
    --primary-light: #667eea;
    --primary-dark: #434190;
    --success-color: #22c55e;
    --warning-color: #f59e0b;
    --danger-color: #ef4444;
    --text-primary: #1f2937;
    --text-secondary: #6b7280;
    --text-muted: #9ca3af;
    --border-color: #e5e7eb;
    --bg-color: #f8fafc;
}

/* 通用工具类 */
.container {
    padding: 24rpx;
}

.card {
    background: #ffffff;
    border-radius: 16rpx;
    padding: 24rpx;
    margin-bottom: 24rpx;
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.06);
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #ffffff;
    border: none;
    border-radius: 12rpx;
    padding: 24rpx 48rpx;
    font-size: 28rpx;
    font-weight: 500;
}

.btn-primary:active {
    opacity: 0.9;
}

.text-primary {
    color: var(--primary-color);
}

.text-secondary {
    color: var(--text-secondary);
}

.text-muted {
    color: var(--text-muted);
}

/* 加载动画 */
.loading-spinner {
    width: 48rpx;
    height: 48rpx;
    border: 4rpx solid #e5e7eb;
    border-top-color: var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* 淡入动画 */
.fade-in {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20rpx);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
