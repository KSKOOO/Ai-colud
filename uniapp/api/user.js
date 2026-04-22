import http from '@/utils/request.js'
import config from '@/config/index.js'

function saveUserSession(user) {
    uni.setStorageSync('token', 'session_active')
    uni.setStorageSync('userInfo', user || {})
    if (!uni.getStorageSync('registerTime')) {
        uni.setStorageSync('registerTime', new Date().toISOString())
    }
}

function clearUserSession() {
    uni.removeStorageSync('token')
    uni.removeStorageSync('userInfo')
}

function normalizeUserPayload(payload) {
    const user = payload?.user || payload?.data || payload || {}
    return {
        id: user.id || '',
        username: user.username || '',
        email: user.email || '',
        role: user.role || 'user',
        created_at: user.created_at || ''
    }
}

function formRequest(url, data = {}, method = 'POST') {
    return new Promise((resolve, reject) => {
        uni.request({
            url,
            method,
            data,
            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            withCredentials: true,
            success: (res) => resolve(res),
            fail: reject
        })
    })
}

async function fetchCurrentUserFromServer() {
    const res = await http.get(config.api.user, {
        action: 'getCurrentUser'
    }, {
        loading: false,
        withCredentials: true
    })

    if (res.status !== 'success' || !res.user) {
        throw new Error(res.message || '未获取到用户信息')
    }

    const user = normalizeUserPayload(res)
    saveUserSession(user)
    return user
}

export default {
    async login(data) {
        const url = config.baseURL + config.api.login
        uni.showLoading({ title: '登录中...', mask: true })

        try {
            const res = await formRequest(url, {
                username: data.username,
                password: data.password
            })

            const responseText = typeof res.data === 'string' ? res.data : JSON.stringify(res.data)
            if (
                res.statusCode >= 400 ||
                responseText.includes('用户名或密码错误') ||
                responseText.includes('请填写用户名和密码') ||
                responseText.includes('账号已被禁用')
            ) {
                throw new Error('用户名或密码错误')
            }

            const user = await fetchCurrentUserFromServer()
            uni.showToast({ title: '登录成功', icon: 'success' })

            return {
                status: 'success',
                message: '登录成功',
                token: 'session_active',
                user
            }
        } finally {
            uni.hideLoading()
        }
    },

    async register(data) {
        const url = config.baseURL + config.api.register
        uni.showLoading({ title: '注册中...', mask: true })

        try {
            const res = await formRequest(url, {
                username: data.username,
                password: data.password,
                email: data.email
            })

            const responseText = typeof res.data === 'string' ? res.data : JSON.stringify(res.data)
            if (responseText.includes('用户名已存在')) {
                throw new Error('用户名已存在')
            }
            if (responseText.includes('邮箱已被注册')) {
                throw new Error('邮箱已被注册')
            }
            if (responseText.includes('注册失败')) {
                throw new Error('注册失败')
            }

            uni.showToast({ title: '注册成功', icon: 'success' })
            return {
                status: 'success',
                message: '注册成功'
            }
        } finally {
            uni.hideLoading()
        }
    },

    logout() {
        return new Promise((resolve) => {
            const url = config.baseURL + config.api.logout
            uni.request({
                url,
                method: 'GET',
                withCredentials: true,
                complete: () => {
                    clearUserSession()
                    resolve()
                }
            })
        })
    },

    checkLogin() {
        const token = uni.getStorageSync('token')
        const userInfo = uni.getStorageSync('userInfo')
        return !!(token && userInfo && userInfo.username)
    },

    async syncCurrentUser() {
        try {
            return await fetchCurrentUserFromServer()
        } catch (error) {
            clearUserSession()
            throw error
        }
    },

    getCurrentUser() {
        return http.get(config.api.user, {
            action: 'getCurrentUser'
        }, {
            loading: false,
            withCredentials: true
        })
    },

    getUserStats() {
        return http.get(config.api.user, {
            action: 'get_usage_stats'
        }, {
            withCredentials: true
        })
    },

    getUserInfo() {
        return http.get(config.api.user, {
            action: 'get_profile'
        }, {
            loading: false,
            withCredentials: true
        })
    },

    updateProfile(data) {
        return http.post(config.api.user, {
            action: 'updateProfile',
            username: data.username || '',
            email: data.email || ''
        }, {
            withCredentials: true
        })
    },

    changePassword(data) {
        return http.post(config.api.user, {
            action: 'changePassword',
            old_password: data.old_password || '',
            new_password: data.new_password || ''
        }, {
            withCredentials: true
        })
    }
}
