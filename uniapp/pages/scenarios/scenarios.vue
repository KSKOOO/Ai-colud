<template>
    <view class="page">
        <view class="header">
            <text class="title">场景列表</text>
            <text class="desc">选择一个常用场景并直接带入聊天页。</text>
        </view>

        <view class="list">
            <view v-for="item in scenarios" :key="item.title" class="card" @click="openScenario(item)">
                <text class="icon">{{ item.icon }}</text>
                <view class="body">
                    <text class="name">{{ item.title }}</text>
                    <text class="text">{{ item.desc }}</text>
                </view>
            </view>
        </view>
    </view>
</template>

<script>
export default {
    data() {
        return {
            scenarios: [
                { icon: '✍', title: '智能写作', desc: '快速生成文案、文章和总结', prompt: '请帮我写一篇关于人工智能的短文。', mode: 'normal' },
                { icon: '🧠', title: '深度思考', desc: '复杂问题推理和分析', prompt: '请深入分析 AI 产品的核心竞争力。', mode: 'deep_think' },
                { icon: '🌐', title: '联网搜索', desc: '需要结合网络信息回答', prompt: '请帮我搜索并总结这个问题。', mode: 'web_search' },
                { icon: '🖼', title: '图像分析', desc: '进入聊天页后可切到图像理解模式', prompt: '请帮我分析上传的图片。', mode: 'vision_analysis' }
            ]
        }
    },
    methods: {
        openScenario(item) {
            uni.switchTab({
                url: '/pages/chat/chat',
                success: () => {
                    uni.$emit('setChatInput', item.prompt)
                    if (item.mode && item.mode !== 'normal') {
                        uni.$emit('setChatMode', item.mode)
                    }
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

.header {
    margin-bottom: 24rpx;
}

.title {
    display: block;
    font-size: 36rpx;
    font-weight: 700;
    color: #1f2937;
}

.desc {
    display: block;
    margin-top: 8rpx;
    font-size: 26rpx;
    color: #6b7280;
}

.list {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.card {
    display: flex;
    gap: 20rpx;
    background: #ffffff;
    padding: 24rpx;
    border-radius: 20rpx;
    box-shadow: 0 2rpx 10rpx rgba(0, 0, 0, 0.05);
}

.icon {
    width: 72rpx;
    height: 72rpx;
    background: #eef2ff;
    border-radius: 18rpx;
    color: #4c51bf;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 34rpx;
    text-align: center;
    line-height: 72rpx;
}

.body {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.name {
    font-size: 30rpx;
    font-weight: 600;
    color: #1f2937;
}

.text {
    margin-top: 8rpx;
    font-size: 25rpx;
    color: #6b7280;
}
</style>
