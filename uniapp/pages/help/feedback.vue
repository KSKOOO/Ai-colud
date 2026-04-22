<template>
    <view class="page">
        <view class="card">
            <text class="title">意见反馈</text>
            <textarea v-model="content" class="textarea" placeholder="请输入你的建议或问题"></textarea>
            <view class="btn" @click="submit">提交反馈</view>
        </view>
    </view>
</template>

<script>
export default {
    data() {
        return {
            content: ''
        }
    },
    methods: {
        submit() {
            if (!this.content.trim()) {
                uni.showToast({ title: '请输入反馈内容', icon: 'none' })
                return
            }
            uni.showToast({ title: '反馈已提交', icon: 'success' })
            this.content = ''
        }
    }
}
</script>

<style scoped>
.page { min-height: 100vh; background: #f8fafc; padding: 24rpx; }
.card { background: #fff; border-radius: 20rpx; padding: 24rpx; }
.title { display: block; font-size: 32rpx; font-weight: 700; color: #1f2937; margin-bottom: 16rpx; }
.textarea { width: 100%; height: 260rpx; background: #f3f4f6; border-radius: 14rpx; padding: 20rpx; box-sizing: border-box; }
.btn { margin-top: 24rpx; background: #4c51bf; color: #fff; text-align: center; padding: 20rpx; border-radius: 14rpx; }
</style>
