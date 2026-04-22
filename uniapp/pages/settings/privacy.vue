<template>
    <view class="privacy-container">
        <view class="nav-bar">
            <view class="nav-back" @click="goBack">
                <text class="icon">←</text>
            </view>
            <text class="nav-title">隐私政策</text>
            <view class="nav-right"></view>
        </view>
        
        <scroll-view scroll-y class="content">
            <view class="section">
                <text class="section-title">隐私政策</text>
                <text class="update-time">最后更新日期：2024年1月</text>
                
                <view class="paragraph">
                    <text class="p-title">1. 引言</text>
                    <text class="p-content">
                        凌岳AI助手（以下简称"我们"或"本应用"）非常重视用户的隐私保护。本隐私政策说明了我们如何收集、使用、存储和保护您的个人信息。请您在使用本应用前仔细阅读本政策。
                    </text>
                </view>
                
                <view class="paragraph">
                    <text class="p-title">2. 信息收集</text>
                    <text class="p-content">
                        我们可能收集以下信息：
                        • 账户信息：用户名、邮箱地址等注册信息
                        • 使用数据：对话记录、功能使用频率、Token消耗量
                        • 设备信息：设备型号、操作系统版本、IP地址
                        • 日志信息：访问时间、使用时长、错误日志
                    </text>
                </view>
                
                <view class="paragraph">
                    <text class="p-title">3. 信息使用</text>
                    <text class="p-content">
                        我们使用收集的信息用于：
                        • 提供、维护和改进本应用服务
                        • 处理您的请求和反馈
                        • 发送服务通知和更新信息
                        • 防止欺诈和滥用行为
                        • 进行数据分析和研究以改善用户体验
                    </text>
                </view>
                
                <view class="paragraph">
                    <text class="p-title">4. 本地部署说明</text>
                    <text class="p-content">
                        凌岳AI助手采用本地部署架构：
                        • AI模型运行在您的本地服务器或设备上
                        • 对话数据主要存储在本地数据库
                        • 不会将您的私人数据上传到第三方云服务
                        • 您对自己的数据拥有完全控制权
                    </text>
                </view>
                
                <view class="paragraph">
                    <text class="p-title">5. 数据安全</text>
                    <text class="p-content">
                        我们采取以下措施保护您的数据：
                        • 使用行业标准的加密技术保护数据传输
                        • 实施严格的访问控制和身份验证
                        • 定期进行安全审计和漏洞扫描
                        • 建立数据备份和恢复机制
                    </text>
                </view>
                
                <view class="paragraph">
                    <text class="p-title">6. 用户权利</text>
                    <text class="p-content">
                        您拥有以下权利：
                        • 访问和查看您的个人信息
                        • 更正不准确的个人信息
                        • 删除您的账户和相关数据
                        • 导出您的数据副本
                        • 随时撤回同意（可能影响服务使用）
                    </text>
                </view>
                
                <view class="paragraph">
                    <text class="p-title">7. 第三方服务</text>
                    <text class="p-content">
                        本应用可能集成第三方服务（如在线AI模型API），这些服务有其独立的隐私政策。我们建议您阅读相关第三方的隐私政策，了解他们如何处理您的数据。
                    </text>
                </view>
                
                <view class="paragraph">
                    <text class="p-title">8. 政策更新</text>
                    <text class="p-content">
                        我们可能会不时更新本隐私政策。更新后的政策将在本应用内公布，重大变更我们会通过适当方式通知您。继续使用本应用即表示您同意更新后的政策。
                    </text>
                </view>
                
                <view class="paragraph">
                    <text class="p-title">9. 联系我们</text>
                    <text class="p-content">
                        如果您对本隐私政策有任何疑问或建议，请通过以下方式联系我们：
                        • 邮箱：1293724438@qq.com
                        • 地址：凌岳科技有限公司
                    </text>
                </view>
                
                <view class="bottom-space"></view>
            </view>
        </scroll-view>
    </view>
</template>

<script>
export default {
    methods: {
        goBack() {
            uni.navigateBack()
        }
    }
}
</script>

<style scoped>
.privacy-container {
    min-height: 100vh;
    background: #f8fafc;
}

.nav-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24rpx 32rpx;
    background: #ffffff;
    border-bottom: 1rpx solid #e5e7eb;
}

.nav-back {
    padding: 8rpx;
}

.nav-back .icon {
    font-size: 36rpx;
    color: #374151;
}

.nav-title {
    font-size: 32rpx;
    font-weight: 600;
    color: #1f2937;
}

.nav-right {
    width: 60rpx;
}

.content {
    padding: 24rpx;
}

.section {
    background: #ffffff;
    border-radius: 20rpx;
    padding: 32rpx;
}

.section-title {
    display: block;
    font-size: 36rpx;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 8rpx;
    text-align: center;
}

.update-time {
    display: block;
    font-size: 24rpx;
    color: #6b7280;
    text-align: center;
    margin-bottom: 32rpx;
}

.paragraph {
    margin-bottom: 32rpx;
}

.p-title {
    display: block;
    font-size: 30rpx;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 12rpx;
}

.p-content {
    display: block;
    font-size: 28rpx;
    color: #4b5563;
    line-height: 1.8;
    white-space: pre-line;
}

.bottom-space {
    height: 48rpx;
}
</style>