<template>
    <view class="page">
        <view class="card">
            <text class="label">旧密码</text>
            <input password class="input" v-model="oldPassword" placeholder="请输入旧密码" />
            <text class="label">新密码</text>
            <input password class="input" v-model="newPassword" placeholder="请输入新密码" />
            <text class="label">确认新密码</text>
            <input password class="input" v-model="confirmPassword" placeholder="请再次输入新密码" />
            <view class="btn" @click="save">提交</view>
        </view>
    </view>
</template>

<script>
import userApi from '@/api/user.js'

export default {
    data() {
        return {
            oldPassword: '',
            newPassword: '',
            confirmPassword: ''
        }
    },
    methods: {
        async save() {
            if (!this.oldPassword || !this.newPassword) {
                uni.showToast({ title: '请填写完整', icon: 'none' })
                return
            }
            if (this.newPassword !== this.confirmPassword) {
                uni.showToast({ title: '两次密码不一致', icon: 'none' })
                return
            }

            const res = await userApi.changePassword({
                old_password: this.oldPassword,
                new_password: this.newPassword
            })

            if (res.status === 'success' || res.success) {
                uni.showToast({ title: '密码修改成功', icon: 'success' })
                setTimeout(() => uni.navigateBack(), 800)
            } else {
                uni.showToast({ title: res.message || '修改失败', icon: 'none' })
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
.btn { margin-top: 24rpx; background: #4c51bf; color: #fff; text-align: center; padding: 20rpx; border-radius: 14rpx; }
</style>
