<template>
    <view class="terms-container">
        <view class="nav-bar">
            <view class="nav-back" @click="goBack">
                <text class="icon">←</text>
            </view>
            <text class="nav-title">用户协议</text>
            <view class="nav-right"></view>
        </view>
        
        <scroll-view scroll-y class="content">
            <view class="section">
                <text class="section-title">用户协议</text>
                <text class="update-time">最后更新日期：2024年1月</text>
                
                <view class="paragraph">
                    <text class="p-title">1. 协议接受</text>
                    <text class="p-content">
                        欢迎使用凌岳AI助手！请您在使用本应用前仔细阅读本用户协议（以下简称"本协议"）。通过下载、安装、注册或使用本应用，即表示您已阅读、理解并同意接受本协议的所有条款。如果您不同意本协议的任何内容，请立即停止使用本应用。
                    </text>
                </view>
                
                <view class="paragraph">
                    <text class="p-title">2. 服务说明</text>
                    <text class="p-content">
                        凌岳AI助手是一款基于人工智能技术的智能对话应用，提供以下服务：
                        • AI智能对话和问答
                        • 文本生成和内容创作
                        • 代码辅助和编程支持
                        • 智能体创建和管理
                        • 多模型切换和对比
                        • 文件分析和处理
                        
                        本应用支持本地部署和在线API两种模式，用户可根据需求选择合适的使用方式。
                    </text>
                </view>
                
                <view class="paragraph">
                    <text class="p-title">3. 账户注册与安全</text>
                    <text class="p-content">
                        3.1 您需要注册账户才能使用本应用的全部功能。注册时您需要提供真实、准确、完整的个人信息，并及时更新以保持信息的有效性。
                        
                        3.2 您有责任保护账户安全，妥善保管登录密码。如发现账户异常，请立即通知我们。
                        
                        3.3 禁止转让、借用或共享账户。每个用户只能拥有一个主账户。
                    </text>
                </view>
                
                <view class="paragraph">
                    <text class="p-title">4. 使用规范</text>
                    <text class="p-content">
                        您同意在使用本应用时遵守以下规范：
                        • 遵守所有适用的法律法规
                        • 尊重他人的知识产权和隐私权
                        • 不生成、传播违法、有害、侵权内容
                        • 不进行任何形式的网络攻击或滥用
                        • 不干扰或破坏本应用的正常运行
                        • 不使用自动化工具批量访问API
                        
                        违反上述规范可能导致账户暂停或终止。
                    </text>
                </view>
                
                <view class="paragraph">
                    <text class="p-title">5. 知识产权</text>
                    <text class="p-content">
                        5.1 本应用的界面设计、代码、文档等知识产权归巨神兵API辅助平台有限公司所有。
                        
                        5.2 您使用本应用生成的内容，其知识产权归您所有。但请注意：
                        • AI生成内容可能受第三方知识产权约束
                        • 请勿将生成内容用于非法或侵权用途
                        • 商业使用前建议进行知识产权审查
                    </text>
                </view>
                
                <view class="paragraph">
                    <text class="p-title">6. 服务变更与中断</text>
                    <text class="p-content">
                        6.1 我们保留随时修改、暂停或终止部分或全部服务的权利，会尽可能提前通知用户。
                        
                        6.2 以下情况可能导致服务中断：
                        • 系统维护或升级
                        • 不可抗力因素
                        • 违反本协议导致的账户封禁
                        
                        对于非我们原因导致的服务中断，我们不承担责任。
                    </text>
                </view>
                
                <view class="paragraph">
                    <text class="p-title">7. 免责声明</text>
                    <text class="p-content">
                        7.1 AI生成内容的准确性和适用性无法完全保证，仅供参考，不构成专业建议。
                        
                        7.2 本地部署模式下，数据安全由用户自行负责。请妥善保管服务器访问权限。
                        
                        7.3 因用户自身原因（如密码泄露、设备丢失）造成的损失，我们不承担责任。
                        
                        7.4 在法律允许的最大范围内，我们对间接、附带、特殊损失不承担责任。
                    </text>
                </view>
                
                <view class="paragraph">
                    <text class="p-title">8. 协议修改</text>
                    <text class="p-content">
                        我们可能会不时修改本协议。修改后的协议将在本应用内公布，重大变更我们会通过适当方式通知您。继续使用本应用即表示您接受修改后的协议。如您不同意修改内容，请停止使用本应用。
                    </text>
                </view>
                
                <view class="paragraph">
                    <text class="p-title">9. 争议解决</text>
                    <text class="p-content">
                        9.1 本协议的订立、执行和解释均适用中华人民共和国法律。
                        
                        9.2 如发生争议，双方应友好协商解决；协商不成的，任何一方可向被告所在地人民法院提起诉讼。
                    </text>
                </view>
                
                <view class="paragraph">
                    <text class="p-title">10. 其他条款</text>
                    <text class="p-content">
                        10.1 本协议构成双方之间关于本应用使用的完整协议，取代之前的所有口头或书面协议。
                        
                        10.2 如本协议任何条款被认定为无效或不可执行，不影响其他条款的效力。
                        
                        10.3 如有任何问题或建议，请联系我们：
                        • 邮箱：1293724438@qq.com
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
.terms-container {
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