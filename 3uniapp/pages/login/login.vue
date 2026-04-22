<template>
    <view class="login-container">
        <!-- 背景装饰 -->
        <view class="bg-decoration">
            <view class="circle circle-1"></view>
            <view class="circle circle-2"></view>
            <view class="circle circle-3"></view>
        </view>
        
        <!-- Logo 区域 -->
        <view class="logo-area">
            <view class="logo-icon">
                <text class="logo-text">凌</text>
            </view>
            <text class="app-name">凌岳AI助手</text>
            <text class="app-slogan">智能对话，无限可能</text>
        </view>
        
        <!-- 表单区域 -->
        <view class="form-area">
            <!-- 登录/注册切换 -->
            <view class="tab-bar">
                <view 
                    class="tab-item" 
                    :class="{ active: isLogin }" 
                    @click="isLogin = true"
                >
                    登录
                </view>
                <view 
                    class="tab-item" 
                    :class="{ active: !isLogin }" 
                    @click="isLogin = false"
                >
                    注册
                </view>
            </view>
            
            <!-- 登录表单 -->
            <view v-if="isLogin" class="form-content">
                <view class="input-group">
                    <view class="input-icon">
                        <text>👤</text>
                    </view>
                    <input 
                        type="text" 
                        v-model="loginForm.username" 
                        placeholder="请输入用户名"
                        placeholder-class="placeholder"
                    />
                </view>
                <view class="input-group">
                    <view class="input-icon">
                        <text>🔒</text>
                    </view>
                    <input 
                        type="password" 
                        v-model="loginForm.password" 
                        placeholder="请输入密码"
                        placeholder-class="placeholder"
                    />
                </view>
                <button class="btn-submit" @click="handleLogin" :loading="loading">
                    登录
                </button>
            </view>
            
            <!-- 注册表单 -->
            <view v-else class="form-content">
                <view class="input-group">
                    <view class="input-icon">
                        <text>👤</text>
                    </view>
                    <input 
                        type="text" 
                        v-model="registerForm.username" 
                        placeholder="请输入用户名"
                        placeholder-class="placeholder"
                    />
                </view>
                <view class="input-group">
                    <view class="input-icon">
                        <text>📧</text>
                    </view>
                    <input 
                        type="text" 
                        v-model="registerForm.email" 
                        placeholder="请输入邮箱"
                        placeholder-class="placeholder"
                    />
                </view>
                <view class="input-group">
                    <view class="input-icon">
                        <text>🔒</text>
                    </view>
                    <input 
                        type="password" 
                        v-model="registerForm.password" 
                        placeholder="请输入密码"
                        placeholder-class="placeholder"
                    />
                </view>
                <view class="input-group">
                    <view class="input-icon">
                        <text>🔒</text>
                    </view>
                    <input 
                        type="password" 
                        v-model="registerForm.confirmPassword" 
                        placeholder="请确认密码"
                        placeholder-class="placeholder"
                    />
                </view>
                <button class="btn-submit" @click="handleRegister" :loading="loading">
                    注册
                </button>
            </view>
        </view>
        
        <!-- 底部信息 -->
        <view class="footer">
            <text class="copyright">© 2024 巨神兵API辅助平台</text>
        </view>
    </view>
</template>

<script>
import userApi from '@/api/user.js'

export default {
    data() {
        return {
            isLogin: true,
            loading: false,
            logoError: false,
            loginForm: {
                username: '',
                password: ''
            },
            registerForm: {
                username: '',
                email: '',
                password: '',
                confirmPassword: ''
            }
        }
    },
    methods: {
        // LOGO加载失败
        onLogoError() {
            this.logoError = true
        },
        
        // 登录
        async handleLogin() {
            if (!this.loginForm.username) {
                uni.showToast({ title: '请输入用户名', icon: 'none' })
                return
            }
            if (!this.loginForm.password) {
                uni.showToast({ title: '请输入密码', icon: 'none' })
                return
            }
            
            this.loading = true
            try {
                await userApi.login(this.loginForm)
                
                // 跳转首页
                setTimeout(() => {
                    uni.switchTab({
                        url: '/pages/index/index'
                    })
                }, 1000)
            } catch (error) {
                console.error('登录失败:', error)
            } finally {
                this.loading = false
            }
        },
        
        // 注册
        async handleRegister() {
            if (!this.registerForm.username) {
                uni.showToast({ title: '请输入用户名', icon: 'none' })
                return
            }
            if (!this.registerForm.email) {
                uni.showToast({ title: '请输入邮箱', icon: 'none' })
                return
            }
            if (!this.registerForm.password) {
                uni.showToast({ title: '请输入密码', icon: 'none' })
                return
            }
            if (this.registerForm.password !== this.registerForm.confirmPassword) {
                uni.showToast({ title: '两次密码不一致', icon: 'none' })
                return
            }
            
            this.loading = true
            try {
                await userApi.register(this.registerForm)
                uni.showToast({ title: '注册成功，请登录', icon: 'success' })
                this.isLogin = true
                this.loginForm.username = this.registerForm.username
            } catch (error) {
                console.error('注册失败:', error)
            } finally {
                this.loading = false
            }
        }
    }
}
</script>

<style scoped>
.login-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 80rpx 48rpx;
    position: relative;
    overflow: hidden;
}

/* 背景装饰 */
.bg-decoration {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
}

.circle {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
}

.circle-1 {
    width: 400rpx;
    height: 400rpx;
    top: -100rpx;
    right: -100rpx;
}

.circle-2 {
    width: 300rpx;
    height: 300rpx;
    bottom: 200rpx;
    left: -150rpx;
}

.circle-3 {
    width: 200rpx;
    height: 200rpx;
    bottom: -50rpx;
    right: 100rpx;
}

/* Logo 区域 */
.logo-area {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 80rpx;
    z-index: 1;
}

.logo-icon {
    width: 160rpx;
    height: 160rpx;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 40rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 32rpx;
    backdrop-filter: blur(10px);
    padding: 20rpx;
    position: relative;
}

.logo-text {
    font-size: 72rpx;
    font-weight: 800;
    color: #ffffff;
}

.app-name {
    font-size: 48rpx;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 16rpx;
}

.app-slogan {
    font-size: 28rpx;
    color: rgba(255, 255, 255, 0.8);
}

/* 表单区域 */
.form-area {
    width: 100%;
    background: #ffffff;
    border-radius: 32rpx;
    padding: 48rpx 40rpx;
    box-shadow: 0 20rpx 60rpx rgba(0, 0, 0, 0.15);
    z-index: 1;
}

/* 标签栏 */
.tab-bar {
    display: flex;
    margin-bottom: 48rpx;
    background: #f3f4f6;
    border-radius: 16rpx;
    padding: 8rpx;
}

.tab-item {
    flex: 1;
    text-align: center;
    padding: 20rpx 0;
    font-size: 28rpx;
    color: #6b7280;
    border-radius: 12rpx;
    transition: all 0.3s;
}

.tab-item.active {
    background: #ffffff;
    color: #4c51bf;
    font-weight: 600;
    box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.08);
}

/* 表单内容 */
.form-content {
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

/* 输入框组 */
.input-group {
    display: flex;
    align-items: center;
    background: #f9fafb;
    border: 2rpx solid #e5e7eb;
    border-radius: 16rpx;
    padding: 0 24rpx;
    margin-bottom: 24rpx;
    transition: all 0.3s;
}

.input-group:focus-within {
    border-color: #4c51bf;
    background: #ffffff;
    box-shadow: 0 0 0 4rpx rgba(76, 81, 191, 0.1);
}

.input-icon {
    font-size: 36rpx;
    margin-right: 16rpx;
}

.input-group input {
    flex: 1;
    height: 96rpx;
    font-size: 28rpx;
    color: #1f2937;
}

.placeholder {
    color: #9ca3af;
}

/* 提交按钮 */
.btn-submit {
    width: 100%;
    height: 96rpx;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 16rpx;
    color: #ffffff;
    font-size: 32rpx;
    font-weight: 600;
    margin-top: 24rpx;
    transition: all 0.3s;
}

.btn-submit:active {
    transform: scale(0.98);
    opacity: 0.9;
}

/* 底部 */
.footer {
    margin-top: auto;
    padding-top: 48rpx;
    z-index: 1;
}

.copyright {
    font-size: 24rpx;
    color: rgba(255, 255, 255, 0.6);
}
</style>
