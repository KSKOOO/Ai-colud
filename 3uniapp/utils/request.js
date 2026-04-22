/**
 * HTTP 请求封装
 */
import config from '@/config/index.js'

class HttpRequest {
    constructor() {
        this.baseURL = config.baseURL || ''
        this.timeout = config.timeout || 30000
    }
    
    /**
     * 构建完整URL
     */
    buildURL(url) {
        // 如果已经是完整URL，直接返回
        if (url.startsWith('http')) {
            return url
        }
        
        // #ifdef H5
        // H5环境使用相对路径
        if (!this.baseURL && typeof window !== 'undefined') {
            return url
        }
        // #endif
        
        // App/小程序环境使用完整URL
        return this.baseURL + url
    }
    
    /**
     * 发送请求
     */
    request(options) {
        return new Promise((resolve, reject) => {
            // 获取 token
            const token = uni.getStorageSync('token')
            
            // 构建完整 URL
            const url = this.buildURL(options.url)
            
            console.log('请求URL:', url)
            
            // 显示加载提示
            if (options.loading !== false) {
                uni.showLoading({
                    title: options.loadingText || '加载中...',
                    mask: true
                })
            }
            
            uni.request({
                url: url,
                method: options.method || 'GET',
                data: options.data || {},
                header: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Authorization': token ? `Bearer ${token}` : '',
                    ...options.header
                },
                timeout: options.timeout || this.timeout,
                success: (res) => {
                    if (options.loading !== false) {
                        uni.hideLoading()
                    }
                    
                    // 请求成功
                    if (res.statusCode === 200) {
                        // 检查业务状态
                        if (res.data && (res.data.status === 'success' || res.data.success)) {
                            resolve(res.data)
                        } else if (typeof res.data === 'object') {
                            // 返回数据但没有success标记，直接返回
                            resolve(res.data)
                        } else {
                            // 业务错误
                            const msg = res.data?.message || res.data?.error || '请求失败'
                            uni.showToast({
                                title: msg,
                                icon: 'none',
                                duration: 2000
                            })
                            reject(res.data)
                        }
                    } else if (res.statusCode === 401) {
                        // 未授权，跳转登录
                        uni.removeStorageSync('token')
                        uni.removeStorageSync('userInfo')
                        uni.redirectTo({
                            url: '/pages/login/login'
                        })
                        reject(res)
                    } else if (res.statusCode === 404) {
                        // 404错误
                        console.error('404错误:', url)
                        uni.showToast({
                            title: '接口不存在(404)',
                            icon: 'none',
                            duration: 2000
                        })
                        reject({ statusCode: 404, message: '接口不存在' })
                    } else {
                        // 其他错误
                        uni.showToast({
                            title: `请求错误: ${res.statusCode}`,
                            icon: 'none',
                            duration: 2000
                        })
                        reject(res)
                    }
                },
                fail: (err) => {
                    if (options.loading !== false) {
                        uni.hideLoading()
                    }
                    
                    console.error('请求失败:', err, 'URL:', url)
                    uni.showToast({
                        title: '网络请求失败，请检查网络连接',
                        icon: 'none',
                        duration: 2000
                    })
                    reject(err)
                }
            })
        })
    }
    
    /**
     * GET 请求
     */
    get(url, data = {}, options = {}) {
        return this.request({
            url,
            method: 'GET',
            data,
            ...options
        })
    }
    
    /**
     * POST 请求
     */
    post(url, data = {}, options = {}) {
        return this.request({
            url,
            method: 'POST',
            data,
            ...options
        })
    }
    
    /**
     * 上传文件
     */
    upload(url, filePath, formData = {}) {
        return new Promise((resolve, reject) => {
            const token = uni.getStorageSync('token')
            const fullUrl = url.startsWith('http') ? url : this.baseURL + url
            
            uni.uploadFile({
                url: fullUrl,
                filePath: filePath,
                name: 'file',
                formData: formData,
                header: {
                    'Authorization': token ? `Bearer ${token}` : ''
                },
                success: (res) => {
                    const data = JSON.parse(res.data)
                    if (data.status === 'success' || data.success) {
                        resolve(data)
                    } else {
                        uni.showToast({
                            title: data.message || data.error || '上传失败',
                            icon: 'none'
                        })
                        reject(data)
                    }
                },
                fail: (err) => {
                    uni.showToast({
                        title: '上传失败',
                        icon: 'none'
                    })
                    reject(err)
                }
            })
        })
    }
}

// 创建实例
const http = new HttpRequest()

export default http
