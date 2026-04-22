/**
 * API 配置文件
 */

// 服务器配置 - 请根据实际情况修改
const SERVER_CONFIG = {
    // 内网穿透/外网域名地址（生产环境）
    // 默认使用花生壳域名，可替换为实际域名或IP
    host: 'demogod.online',
    port: '80',
    protocol: 'http'
}

// 构建基础URL
const getBaseURL = () => {
    // #ifdef H5
    // H5环境使用相对路径，自动适配当前域名
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        return `http://${SERVER_CONFIG.host}`
    }
    return ''
    // #endif
    
    // App/小程序环境使用完整URL
    return `${SERVER_CONFIG.protocol}://${SERVER_CONFIG.host}`
}

// 当前环境配置
const config = {
    baseURL: getBaseURL(),
    timeout: 30000,
    
    // API 端点
    api: {
        // 登录和注册通过表单提交，不走 JSON API
        login: '/index.php?route=login',
        register: '/index.php?route=register',
        logout: '/index.php?route=logout',
        
        // JSON API 端点
        chat: '/api/api_handler.php',
        models: '/api/api_handler.php',
        providers: '/api/providers_handler.php',
        agents: '/api/agent_api.php',
        user: '/api/user_handler.php',
        scenarios: '/api/scenario_handler.php'
    },
    
    // 服务器配置
    server: SERVER_CONFIG,
    
    // 环境标识
    isDev: process.env.NODE_ENV === 'development'
}

export default config
