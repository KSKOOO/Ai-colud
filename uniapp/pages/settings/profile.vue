<template>
    <view class="page">
        <view class="card">
            <text class="label">用户名</text>
            <input v-model="form.username" class="input" placeholder="请输入用户名" />
            <text class="hint">用户名会同步到服务器。</text>
            <view class="btn" @click="save">保存</view>
        </view>
    </view>
</template>

<script>
import userApi from '@/api/user.js'

export default {
    data() {
        return {
            form: {
                username: '',
                email: ''
            }
        }
    },
    async onLoad() {
        const userInfo = uni.getStorageSync('userInfo') || {}
        this.form.username = userInfo.username || ''
        this.form.email = userInfo.email || ''

        try {
            const res = await userApi.getUserInfo()
            if ((res.success || res.status === 'success') && res.data) {
                this.form.username = res.data.username || ''
                this.form.email = res.data.email || ''
            }
        } catch (error) {
            console.error('profile load failed', error)
        }
    },
    methods: {
        async save() {
            if (!this.form.username.trim()) {
                uni.showToast({ title: '请输入用户名', icon: 'none' })
                return
            }

            const res = await userApi.updateProfile(this.form)
            if (res.status === 'success' || res.success) {
                await userApi.syncCurrentUser()
                uni.showToast({ title: '已保存', icon: 'success' })
            } else {
                uni.showToast({ title: res.message || '保存失败', icon: 'none' })
            }
        }
    }
}
</script>

<style scoped>
.page { min-height: 100vh; background: #f8fafc; padding: 24rpx; }
.card { background: #fff; border-radius: 20rpx; padding: 24rpx; }
.label { display: block; margin: 12rpx 0; font-size: 26rpx; color: #4b5563; }
.input { height: 84rpx; background: #f3f4f6; border-radius: 14rpx; padding: 0 20rpx; }
.hint { display: block; margin-top: 10rpx; font-size: 24rpx; color: #6b7280; }
.btn { margin-top: 24rpx; background: #4c51bf; color: #fff; text-align: center; padding: 20rpx; border-radius: 14rpx; }
</style>
