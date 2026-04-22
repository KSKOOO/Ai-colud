/**
 * 用户相关 API
 */
import http from '@/utils/request.js'
import config from '@/config/index.js'

export default {
    /**
     * 用户登录 - 表单提交方式
     * 后端使用 Session 认证，需要提交表单数据
     */
    login(data) {
        return new Promise((resolve, reject) => {
            const url = config.baseURL + '/index.php?route=login'
            
            uni.showLoading({ title: '登录中...', mask: true })
            
            uni.request({
                url: url,
                method: 'POST',
                data: {
                    username: data.username,
                    password: data.password
                },
                header: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                withCredentials: true, // 支持携带 Cookie
                success: (res) => {
                    uni.hideLoading()
                    
                    // 检查是否登录成功
                    // 后端登录成功会重定向到 home 页面
                    if (res.statusCode === 200 || res.statusCode === 302) {
                        // 检查返回内容是否包含登录错误
                        const responseText = typeof res.data === 'string' ? res.data : JSON.stringify(res.data)
                        
                        if (responseText.includes('用户名或密码错误') || 
                            responseText.includes('请填写用户名和密码')) {
                            uni.showToast({
                                title: '用户名或密码错误',
                                icon: 'none'
                            })
                            reject(new Error('用户名或密码错误'))
                        } else {
                            // 登录成功
                            const token = 'session_' + Date.now() // 模拟 token
                            uni.setStorageSync('token', token)
                            uni.setStorageSync('userInfo', {
                                username: data.username
                            })
                            
                            uni.showToast({ title: '登录成功', icon: 'success' })
                            resolve({
                                status: 'success',
                                message: '登录成功',
                                token: token,
                                user: { username: data.username }
                            })
                        }
                    } else {
                        uni.showToast({
                            title: '登录失败，请重试',
                            icon: 'none'
                        })
                        reject(new Error('登录失败'))
                    }
                },
                fail: (err) => {
                    uni.hideLoading()
                    console.error('登录请求失败:', err)
                    uni.showToast({
                        title: '网络请求失败',
                        icon: 'none'
                    })
                    reject(err)
                }
            })
        })
    },
    
    /**
     * 用户注册
     */
    register(data) {
        return new Promise((resolve, reject) => {
            const url = config.baseURL + '/index.php?route=register'
            
            uni.showLoading({ title: '注册中...', mask: true })
            
            uni.request({
                url: url,
                method: 'POST',
                data: {
                    username: data.username,
                    password: data.password,
                    email: data.email
                },
                header: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                withCredentials: true,
                success: (res) => {
                    uni.hideLoading()
                    
                    const responseText = typeof res.data === 'string' ? res.data : JSON.stringify(res.data)
                    
                    if (responseText.includes('用户名已存在')) {
                        uni.showToast({ title: '用户名已存在', icon: 'none' })
                        reject(new Error('用户名已存在'))
                    } else if (responseText.includes('邮箱已被注册')) {
                        uni.showToast({ title: '邮箱已被注册', icon: 'none' })
                        reject(new Error('邮箱已被注册'))
                    } else if (responseText.includes('注册失败')) {
                        uni.showToast({ title: '注册失败', icon: 'none' })
                        reject(new Error('注册失败'))
                    } else {
                        // 注册成功
                        uni.setStorageSync('token', 'session_' + Date.now())
                        uni.setStorageSync('userInfo', { username: data.username })
                        
                        uni.showToast({ title: '注册成功', icon: 'success' })
                        resolve({
                            status: 'success',
                            message: '注册成功'
                        })
                    }
                },
                fail: (err) => {
                    uni.hideLoading()
                    uni.showToast({ title: '网络请求失败', icon: 'none' })
                    reject(err)
                }
            })
        })
    },
    
    /**
     * 退出登录
     */
    logout() {
        return new Promise((resolve) => {
            const url = config.baseURL + '/index.php?route=logout'
            
            uni.request({
                url: url,
                method: 'GET',
                withCredentials: true,
                complete: () => {
                    uni.removeStorageSync('token')
                    uni.removeStorageSync('userInfo')
                    resolve()
                }
            })
        })
    },
    
    /**
     * 检查登录状态
     */
    checkLogin() {
        const token = uni.getStorageSync('token')
        const userInfo = uni.getStorageSync('userInfo')
        return !!(token && userInfo)
    },
    
    /**
     * 获取用户统计数据
     */
    getUserStats() {
        return http.get(config.api.user, {
            action: 'get_usage_stats'
        })
    },
    
    /**
     * 获取用户信息
     */
    getUserInfo() {
        return http.get(config.api.user, {
            action: 'get_profile'
        })
    }
}
