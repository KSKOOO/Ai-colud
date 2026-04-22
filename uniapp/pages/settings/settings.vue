<template>
    <view class="page">
        <view class="card">
            <view class="row" @click="editProfile">
                <text class="label">个人资料</text>
                <text class="value">{{ userInfo.username || '未设置' }}</text>
            </view>
            <view class="row" @click="changePassword">
                <text class="label">修改密码</text>
                <text class="value">></text>
            </view>
            <view class="row" @click="bindEmail">
                <text class="label">绑定邮箱</text>
                <text class="value">{{ userInfo.email || '未绑定' }}</text>
            </view>
        </view>

        <view class="card">
            <view class="row" @click="setDefaultModel">
                <text class="label">默认模型</text>
                <text class="value ellipsis">{{ settings.defaultModel || '自动选择' }}</text>
            </view>
            <view class="row" @click="clearCache">
                <text class="label">清理缓存</text>
                <text class="value">{{ cacheSize }}</text>
            </view>
            <view class="row" @click="showPrivacy">
                <text class="label">隐私政策</text>
                <text class="value">></text>
            </view>
            <view class="row" @click="showTerms">
                <text class="label">用户协议</text>
                <text class="value">></text>
            </view>
        </view>
    </view>
</template>

<script>
import agentsApi from '@/api/agents.js'
import userApi from '@/api/user.js'

export default {
    data() {
        return {
            userInfo: {},
            settings: {
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
        async loadUserInfo() {
            this.userInfo = uni.getStorageSync('userInfo') || {}
            try {
                const res = await userApi.getUserInfo()
                if ((res.success || res.status === 'success') && res.data) {
                    this.userInfo = {
                        ...this.userInfo,
                        ...res.data
                    }
                    uni.setStorageSync('userInfo', this.userInfo)
                }
            } catch (error) {
                console.error('settings loadUserInfo failed', error)
            }
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

        calcCacheSize() {
            const keys = uni.getStorageInfoSync().keys || []
            let size = 0
            keys.forEach(key => {
                const item = uni.getStorageSync(key)
                size += JSON.stringify(item || '').length
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
                content: '确定要清理缓存吗？',
                success: (res) => {
                    if (!res.confirm) return
                    const userInfo = uni.getStorageSync('userInfo')
                    const appSettings = uni.getStorageSync('appSettings')
                    const token = uni.getStorageSync('token')
                    const registerTime = uni.getStorageSync('registerTime')
                    uni.clearStorageSync()
                    if (token) uni.setStorageSync('token', token)
                    if (userInfo) uni.setStorageSync('userInfo', userInfo)
                    if (appSettings) uni.setStorageSync('appSettings', appSettings)
                    if (registerTime) uni.setStorageSync('registerTime', registerTime)
                    this.calcCacheSize()
                    uni.showToast({ title: '清理完成', icon: 'success' })
                }
            })
        },

        async loadAvailableModels() {
            try {
                const providerRes = await agentsApi.getProviders()
                const models = []
                if (providerRes.success && providerRes.data) {
                    providerRes.data.forEach(provider => {
                        ;(provider.models || []).forEach(modelName => {
                            models.push({
                                id: `${provider.id}:${modelName}`,
                                name: `${modelName} (${provider.name})`
                            })
                        })
                    })
                }
                this.availableModels = models
            } catch (error) {
                console.error('loadAvailableModels failed', error)
            }
        },

        setDefaultModel() {
            if (!this.availableModels.length) {
                uni.showToast({ title: '暂无可用模型', icon: 'none' })
                return
            }

            const itemList = ['自动选择', ...this.availableModels.map(item => item.name)]
            uni.showActionSheet({
                itemList,
                success: (res) => {
                    this.settings.defaultModel = res.tapIndex === 0 ? '' : this.availableModels[res.tapIndex - 1].name
                    this.saveSettings()
                }
            })
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
.page {
    min-height: 100vh;
    background: #f8fafc;
    padding: 24rpx;
}

.card {
    background: #ffffff;
    border-radius: 20rpx;
    margin-bottom: 24rpx;
    overflow: hidden;
}

.row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20rpx;
    padding: 28rpx 24rpx;
    border-bottom: 1rpx solid #f3f4f6;
}

.row:last-child {
    border-bottom: none;
}

.label {
    font-size: 28rpx;
    color: #1f2937;
}

.value {
    font-size: 25rpx;
    color: #6b7280;
}

.ellipsis {
    max-width: 360rpx;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
