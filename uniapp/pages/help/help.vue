<template>
    <view class="help-container">
        <!-- 搜索栏 -->
        <view class="search-section">
            <view class="search-box">
                <text class="search-icon">🔍</text>
                <input 
                    type="text" 
                    v-model="searchKeyword" 
                    placeholder="搜索常见问题..."
                    placeholder-class="placeholder"
                    @input="searchFAQ"
                />
            </view>
        </view>
        
        <!-- 常见问题分类 -->
        <view class="category-section" v-if="!searchKeyword">
            <view class="category-grid">
                <view 
                    class="category-item" 
                    v-for="cat in categories" 
                    :key="cat.id"
                    @click="selectCategory(cat)"
                >
                    <text class="category-icon">{{ cat.icon }}</text>
                    <text class="category-name">{{ cat.name }}</text>
                </view>
            </view>
        </view>
        
        <!-- FAQ列表 -->
        <view class="faq-section">
            <view class="section-title">{{ searchKeyword ? '搜索结果' : '热门问题' }}</view>
            <view class="faq-list">
                <view 
                    class="faq-item" 
                    v-for="(item, index) in filteredFAQ" 
                    :key="index"
                    @click="toggleFAQ(index)"
                >
                    <view class="faq-question">
                        <text class="q-badge">Q</text>
                        <text class="question-text">{{ item.question }}</text>
                        <text class="expand-icon" :class="{ expanded: item.expanded }">▼</text>
                    </view>
                    <view class="faq-answer" v-if="item.expanded">
                        <text class="a-badge">A</text>
                        <text class="answer-text">{{ item.answer }}</text>
                    </view>
                </view>
            </view>
        </view>
        
        <!-- 联系客服 -->
        <view class="contact-section">
            <view class="contact-title">还没找到答案？</view>
            <view class="contact-buttons">
                <view class="contact-btn" @click="contactOnline">
                    <text class="btn-icon">💬</text>
                    <text class="btn-text">在线客服</text>
                </view>
                <view class="contact-btn" @click="contactPhone">
                    <text class="btn-icon">📞</text>
                    <text class="btn-text">电话客服</text>
                </view>
                <view class="contact-btn" @click="contactEmail">
                    <text class="btn-icon">📧</text>
                    <text class="btn-text">邮件反馈</text>
                </view>
            </view>
        </view>
        
        <!-- 反馈入口 -->
        <view class="feedback-section" @click="goFeedback">
            <text class="feedback-text">意见反馈</text>
            <text class="feedback-arrow">›</text>
        </view>
    </view>
</template>

<script>
export default {
    data() {
        return {
            searchKeyword: '',
            categories: [
                { id: 'account', name: '账号问题', icon: '👤' },
                { id: 'chat', name: '对话功能', icon: '💬' },
                { id: 'model', name: '模型相关', icon: '🧠' },
                { id: 'payment', name: '充值付费', icon: '💰' },
                { id: 'agent', name: '智能体', icon: '🤖' },
                { id: 'other', name: '其他问题', icon: '❓' }
            ],
            faqList: [
                {
                    question: '如何修改个人信息？',
                    answer: '进入"我的"页面，点击"设置"，选择"编辑资料"即可修改个人信息。',
                    category: 'account',
                    expanded: false
                },
                {
                    question: '如何切换AI模型？',
                    answer: '在聊天页面，点击底部的"选择模型"按钮，即可选择不同的AI模型进行对话。',
                    category: 'chat',
                    expanded: false
                },
                {
                    question: '对话历史可以保存多久？',
                    answer: '对话历史默认保存在本地，不会自动删除。您可以在"历史记录"中查看和管理。',
                    category: 'chat',
                    expanded: false
                },
                {
                    question: '如何使用智能体？',
                    answer: '在首页或智能体页面选择感兴趣的智能体，点击进入后即可开始对话。每个智能体都有特定的功能和场景。',
                    category: 'agent',
                    expanded: false
                },
                {
                    question: '支持哪些AI模型？',
                    answer: '目前支持GPT系列、Claude系列、Llama系列、通义千问等多种模型。具体可用的模型取决于您的账号权限。',
                    category: 'model',
                    expanded: false
                },
                {
                    question: '如何充值？',
                    answer: '进入"我的"页面，点击"充值"按钮，选择充值金额和支付方式即可完成充值。',
                    category: 'payment',
                    expanded: false
                }
            ],
            filteredFAQ: []
        }
    },
    onLoad() {
        this.filteredFAQ = this.faqList
    },
    methods: {
        searchFAQ() {
            if (!this.searchKeyword) {
                this.filteredFAQ = this.faqList
                return
            }
            const keyword = this.searchKeyword.toLowerCase()
            this.filteredFAQ = this.faqList.filter(item => 
                item.question.toLowerCase().includes(keyword) ||
                item.answer.toLowerCase().includes(keyword)
            )
        },
        toggleFAQ(index) {
            this.filteredFAQ[index].expanded = !this.filteredFAQ[index].expanded
        },
        selectCategory(cat) {
            this.filteredFAQ = this.faqList.filter(item => item.category === cat.id)
        },
        contactOnline() {
            uni.showToast({ title: '客服系统接入中', icon: 'none' })
        },
        contactPhone() {
            uni.makePhoneCall({
                phoneNumber: '400-888-8888',
                fail: () => {
                    uni.showModal({
                        title: '客服电话',
                        content: '400-888-8888',
                        showCancel: false
                    })
                }
            })
        },
        contactEmail() {
            uni.setClipboardData({
                data: 'support@lingyue-ai.com',
                success: () => {
                    uni.showToast({ title: '邮箱已复制', icon: 'success' })
                }
            })
        },
        goFeedback() {
            uni.navigateTo({ url: '/pages/help/feedback' })
        }
    }
}
</script>

<style scoped>
.help-container {
    min-height: 100vh;
    background: #f8fafc;
    padding-bottom: 32rpx;
}

.search-section {
    padding: 24rpx;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.search-box {
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 32rpx;
    padding: 16rpx 24rpx;
}

.search-icon {
    font-size: 28rpx;
    margin-right: 12rpx;
    color: #ffffff;
}

.search-box input {
    flex: 1;
    font-size: 28rpx;
    color: #ffffff;
}

.placeholder {
    color: rgba(255, 255, 255, 0.7);
}

.category-section {
    padding: 24rpx;
}

.category-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16rpx;
}

.category-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 24rpx;
    background: #ffffff;
    border-radius: 16rpx;
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.04);
}

.category-icon {
    font-size: 40rpx;
    margin-bottom: 12rpx;
}

.category-name {
    font-size: 24rpx;
    color: #1f2937;
}

.faq-section {
    padding: 0 24rpx;
}

.section-title {
    padding: 24rpx 0 16rpx;
    font-size: 28rpx;
    font-weight: 600;
    color: #1f2937;
}

.faq-list {
    background: #ffffff;
    border-radius: 16rpx;
    overflow: hidden;
}

.faq-item {
    border-bottom: 1rpx solid #f3f4f6;
}

.faq-item:last-child {
    border-bottom: none;
}

.faq-question {
    display: flex;
    align-items: center;
    padding: 24rpx;
}

.q-badge {
    width: 36rpx;
    height: 36rpx;
    background: #4c51bf;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22rpx;
    color: #ffffff;
    font-weight: 600;
    margin-right: 16rpx;
}

.question-text {
    flex: 1;
    font-size: 28rpx;
    color: #1f2937;
}

.expand-icon {
    font-size: 24rpx;
    color: #9ca3af;
    transition: transform 0.3s;
}

.expand-icon.expanded {
    transform: rotate(180deg);
}

.faq-answer {
    display: flex;
    padding: 0 24rpx 24rpx;
    background: #f8fafc;
}

.a-badge {
    width: 36rpx;
    height: 36rpx;
    background: #22c55e;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22rpx;
    color: #ffffff;
    font-weight: 600;
    margin-right: 16rpx;
    flex-shrink: 0;
}

.answer-text {
    flex: 1;
    font-size: 26rpx;
    color: #4b5563;
    line-height: 1.6;
}

.contact-section {
    padding: 32rpx 24rpx;
}

.contact-title {
    text-align: center;
    font-size: 28rpx;
    color: #6b7280;
    margin-bottom: 24rpx;
}

.contact-buttons {
    display: flex;
    justify-content: space-around;
}

.contact-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.btn-icon {
    width: 96rpx;
    height: 96rpx;
    background: #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40rpx;
    margin-bottom: 12rpx;
    box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.08);
}

.btn-text {
    font-size: 24rpx;
    color: #1f2937;
}

.feedback-section {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 0 24rpx;
    padding: 28rpx 32rpx;
    background: #ffffff;
    border-radius: 16rpx;
}

.feedback-text {
    font-size: 28rpx;
    color: #1f2937;
}

.feedback-arrow {
    font-size: 28rpx;
    color: #9ca3af;
}
</style>
