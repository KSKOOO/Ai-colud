<template>
    <view class="settings-container">
        <!-- 账号设置 -->
        <view class="section">
            <view class="section-title">账号设置</view>
            <view class="menu-list">
                <view class="menu-item" @click="editProfile">
                    <text class="menu-icon">👤</text>
                    <text class="menu-title">编辑资料</text>
                    <text class="menu-value">{{ userInfo.username || '未设置' }}</text>
                    <text class="menu-arrow">›</text>
                </view>
                <view class="menu-item" @click="changePassword">
                    <text class="menu-icon">🔐</text>
                    <text class="menu-title">修改密码</text>
                    <text class="menu-arrow">›</text>
                </view>
                <view class="menu-item" @click="bindEmail">
                    <text class="menu-icon">📧</text>
                    <text class="menu-title">绑定邮箱</text>
                    <text class="menu-value">{{ userInfo.email || '未绑定' }}</text>
                    <text class="menu-arrow">›</text>
                </view>
            </view>
        </view>
        
        <!-- 通用设置 -->
        <view class="section">
            <view class="section-title">通用设置</view>
            <view class="menu-list">
                <view class="menu-item">
                    <text class="menu-icon">🔔</text>
                    <text class="menu-title">消息通知</text>
                    <switch :checked="settings.notification" @change="toggleSetting('notification')" color="#4c51bf"/>
                </view>
                <view class="menu-item">
                    <text class="menu-icon">🌙</text>
                    <text class="menu-title">深色模式</text>
                    <switch :checked="settings.darkMode" @change="toggleSetting('darkMode')" color="#4c51bf"/>
                </view>
                <view class="menu-item">
                    <text class="menu-icon">🔊</text>
                    <text class="menu-title">声音提示</text>
                    <switch :checked="settings.sound" @change="toggleSetting('sound')" color="#4c51bf"/>
                </view>
                <view class="menu-item" @click="clearCache">
                    <text class="menu-icon">🗑️</text>
                    <text class="menu-title">清理缓存</text>
                    <text class="menu-value">{{ cacheSize }}</text>
                    <text class="menu-arrow">›</text>
                </view>
            </view>
        </view>
        
        <!-- 模型设置 -->
        <view class="section">
            <view class="section-title">模型设置</view>
            <view class="menu-list">
                <view class="menu-item" @click="setDefaultModel">
                    <text class="menu-icon">🧠</text>
                    <text class="menu-title">默认模型</text>
                    <text class="menu-value">{{ settings.defaultModel || '自动选择' }}</text>
                    <text class="menu-arrow">›</text>
                </view>
                <view class="menu-item">
                    <text class="menu-icon">⚡</text>
                    <text class="menu-title">快速回复</text>
                    <switch :checked="settings.quickReply" @change="toggleSetting('quickReply')" color="#4c51bf"/>
                </view>
            </view>
        </view>
        
        <!-- 关于 -->
        <view class="section">
            <view class="section-title">关于</view>
            <view class="menu-list">
                <view class="menu-item" @click="checkUpdate">
                    <text class="menu-icon">📦</text>
                    <text class="menu-title">检查更新</text>
                    <text class="menu-value">当前版本 1.0.0</text>
                    <text class="menu-arrow">›</text>
                </view>
                <view class="menu-item" @click="showPrivacy">
                    <text class="menu-icon">📋</text>
                    <text class="menu-title">隐私政策</text>
                    <text class="menu-arrow">›</text>
                </view>
                <view class="menu-item" @click="showTerms">
                    <text class="menu-icon">📄</text>
                    <text class="menu-title">用户协议</text>
                    <text class="menu-arrow">›</text>
                </view>
            </view>
        </view>
    </view>
</template>

<script>
import agentsApi from '@/api/agents.js'

export default {
    data() {
        return {
            userInfo: {},
            settings: {
                notification: true,
                darkMode: false,
                sound: true,
                quickReply: false,
                defaultModel: ''
            },
            cacheSize: '0 MB',
            availableModels: []
        }
    },
    onShow() {
        this.loadUserInfo()
        this.loadSettings()
        this.calcCacheSize()
        this.loadAvailableModels()
    },
    methods: {
        loadUserInfo() {
            this.userInfo = uni.getStorageSync('userInfo') || {}
        },
        loadSettings() {
            const saved = uni.getStorageSync('appSettings')
            if (saved) {
                this.settings = { ...this.settings, ...saved }
            }
        },
        saveSettings() {
            uni.setStorageSync('appSettings', this.settings)
        },
        toggleSetting(key) {
            this.settings[key] = !this.settings[key]
            this.saveSettings()
            uni.showToast({ title: '设置已保存', icon: 'success' })
        },
        calcCacheSize() {
            // 计算缓存大小
            const keys = uni.getStorageInfoSync().keys
            let size = 0
            keys.forEach(key => {
                const item = uni.getStorageSync(key)
                size += JSON.stringify(item).length
            })
            this.cacheSize = (size / 1024 / 1024).toFixed(2) + ' MB'
        },
        editProfile() {
            uni.navigateTo({ url: '/pages/settings/profile' })
        },
        changePassword() {
            uni.navigateTo({ url: '/pages/settings/password' })
        },
        bindEmail() {
            uni.navigateTo({ url: '/pages/settings/email' })
        },
        clearCache() {
            uni.showModal({
                title: '清理缓存',
                content: '确定要清理所有缓存数据吗？',
                success: (res) => {
                    if (res.confirm) {
                        // 保留用户信息和设置
                        const userInfo = uni.getStorageSync('userInfo')
                        const settings = uni.getStorageSync('appSettings')
                        uni.clearStorageSync()
                        uni.setStorageSync('userInfo', userInfo)
                        uni.setStorageSync('appSettings', settings)
                        this.calcCacheSize()
                        uni.showToast({ title: '清理完成', icon: 'success' })
                    }
                }
            })
        },
        // 加载可用模型列表
        async loadAvailableModels() {
            try {
                const res = await agentsApi.getAvailableModels()
                const providersRes = await agentsApi.getProviders()
                
                let models = []
                
                // 添加本地模型
                if (res.status === 'success' && res.models) {
                    Object.entries(res.models).forEach(([id, model]) => {
                        const name = typeof model === 'object' ? (model.name || id) : model
                        models.push({ id: id, name: name, type: 'local' })
                    })
                }
                
                // 添加在线API模型
                if (providersRes.success && providersRes.data) {
                    providersRes.data.forEach(provider => {
                        if (provider.models) {
                            provider.models.forEach(modelName => {
                                models.push({
                                    id: modelName,
                                    name: `${modelName} (${provider.name})`,
                                    type: 'online'
                                })
                            })
                        }
                    })
                }
                
                this.availableModels = models
            } catch (error) {
                console.error('加载模型列表失败:', error)
            }
        },
        
        // 设置默认模型
        setDefaultModel() {
            if (this.availableModels.length === 0) {
                uni.showToast({ title: '暂无可用模型', icon: 'none' })
                return
            }
            
            const modelNames = ['自动选择', ...this.availableModels.map(m => m.name)]
            
            uni.showActionSheet({
                itemList: modelNames,
                success: (res) => {
                    if (res.tapIndex === 0) {
                        this.settings.defaultModel = ''
                    } else {
                        this.settings.defaultModel = this.availableModels[res.tapIndex - 1].name
                    }
                    this.saveSettings()
                }
            })
        },
        checkUpdate() {
            uni.showLoading({ title: '检查中...' })
            setTimeout(() => {
                uni.hideLoading()
                uni.showModal({
                    title: '检查更新',
                    content: '当前已是最新版本',
                    showCancel: false
                })
            }, 1000)
        },
        showPrivacy() {
            uni.navigateTo({ url: '/pages/settings/privacy' })
        },
        showTerms() {
            uni.navigateTo({ url: '/pages/settings/terms' })
        }
    }
}
</script>

<style scoped>
.settings-container {
    min-height: 100vh;
    background: #f8fafc;
    padding-bottom: 32rpx;
}

.section {
    margin-bottom: 24rpx;
}

.section-title {
    padding: 24rpx 32rpx 16rpx;
    font-size: 26rpx;
    color: #6b7280;
    font-weight: 500;
}

.menu-list {
    background: #ffffff;
}

.menu-item {
    display: flex;
    align-items: center;
    padding: 28rpx 32rpx;
    border-bottom: 1rpx solid #f3f4f6;
}

.menu-item:last-child {
    border-bottom: none;
}

.menu-icon {
    font-size: 32rpx;
    margin-right: 20rpx;
    width: 40rpx;
    text-align: center;
}

.menu-title {
    flex: 1;
    font-size: 28rpx;
    color: #1f2937;
}

.menu-value {
    font-size: 26rpx;
    color: #6b7280;
    margin-right: 12rpx;
}

.menu-arrow {
    font-size: 28rpx;
    color: #9ca3af;
}

switch {
    transform: scale(0.8);
}
</style>
