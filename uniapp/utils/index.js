/**
 * 工具函数
 */

/**
 * 格式化时间
 */
export function formatTime(date, format = 'YYYY-MM-DD HH:mm:ss') {
    if (!date) return ''
    
    const d = new Date(date)
    const year = d.getFullYear()
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    const hour = String(d.getHours()).padStart(2, '0')
    const minute = String(d.getMinutes()).padStart(2, '0')
    const second = String(d.getSeconds()).padStart(2, '0')
    
    return format
        .replace('YYYY', year)
        .replace('MM', month)
        .replace('DD', day)
        .replace('HH', hour)
        .replace('mm', minute)
        .replace('ss', second)
}

/**
 * 防抖函数
 */
export function debounce(fn, delay = 500) {
    let timer = null
    return function(...args) {
        if (timer) clearTimeout(timer)
        timer = setTimeout(() => {
            fn.apply(this, args)
        }, delay)
    }
}

/**
 * 节流函数
 */
export function throttle(fn, delay = 500) {
    let lastTime = 0
    return function(...args) {
        const now = Date.now()
        if (now - lastTime >= delay) {
            fn.apply(this, args)
            lastTime = now
        }
    }
}

/**
 * 深拷贝
 */
export function deepClone(obj) {
    if (obj === null || typeof obj !== 'object') return obj
    if (obj instanceof Date) return new Date(obj)
    if (obj instanceof RegExp) return new RegExp(obj)
    
    const clone = Array.isArray(obj) ? [] : {}
    for (let key in obj) {
        if (obj.hasOwnProperty(key)) {
            clone[key] = deepClone(obj[key])
        }
    }
    return clone
}

/**
 * 显示 Toast
 */
export function showToast(title, icon = 'none', duration = 2000) {
    uni.showToast({
        title,
        icon,
        duration
    })
}

/**
 * 显示加载
 */
export function showLoading(title = '加载中...') {
    uni.showLoading({
        title,
        mask: true
    })
}

/**
 * 隐藏加载
 */
export function hideLoading() {
    uni.hideLoading()
}

/**
 * 确认对话框
 */
export function showConfirm(content, title = '提示') {
    return new Promise((resolve, reject) => {
        uni.showModal({
            title,
            content,
            success: (res) => {
                if (res.confirm) {
                    resolve(true)
                } else {
                    resolve(false)
                }
            },
            fail: reject
        })
    })
}

/**
 * 复制到剪贴板
 */
export function copyToClipboard(text) {
    return new Promise((resolve, reject) => {
        uni.setClipboardData({
            data: text,
            success: () => {
                showToast('复制成功')
                resolve()
            },
            fail: reject
        })
    })
}

/**
 * 格式化文件大小
 */
export function formatFileSize(bytes) {
    if (bytes === 0) return '0 B'
    
    const k = 1024
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

/**
 * 解析 Markdown (简化版)
 */
export function parseMarkdown(text) {
    if (!text) return ''
    
    // 代码块
    text = text.replace(/```(\w*)\n([\s\S]*?)```/g, '<pre><code class="language-$1">$2</code></pre>')
    
    // 行内代码
    text = text.replace(/`([^`]+)`/g, '<code>$1</code>')
    
    // 标题
    text = text.replace(/^### (.+)$/gm, '<h3>$1</h3>')
    text = text.replace(/^## (.+)$/gm, '<h2>$1</h2>')
    text = text.replace(/^# (.+)$/gm, '<h1>$1</h1>')
    
    // 粗体和斜体
    text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    text = text.replace(/\*(.+?)\*/g, '<em>$1</em>')
    
    // 链接
    text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2">$1</a>')
    
    // 换行
    text = text.replace(/\n/g, '<br>')
    
    return text
}
