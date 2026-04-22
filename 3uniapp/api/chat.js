/**
 * 聊天相关 API
 */
import http from '@/utils/request.js'
import config from '@/config/index.js'

export default {
    /**
     * 发送聊天消息
     */
    sendMessage(data) {
        return http.post(config.api.chat, {
            request: 'chat',
            input: data.input,
            model: data.model || '',
            mode: data.mode || 'normal',
            provider_id: data.provider_id || '',
            context: data.context || '[]'
        }, {
            loadingText: '思考中...'
        })
    },

    /**
     * 获取场景演示列表
     */
    getScenarios() {
        return http.get(config.api.scenarios, {
            action: 'list'
        }, {
            loading: false
        })
    },
    
    /**
     * 深度思考模式
     */
    deepThink(data) {
        return http.post(config.api.chat, {
            request: 'deep_think',
            input: data.input,
            model: data.model || '',
            mode: 'deep_think',
            provider_id: data.provider_id || '',
            context: data.context || '[]'
        }, {
            loadingText: '深度思考中...'
        })
    },
    
    /**
     * 联网搜索模式
     */
    webSearch(data) {
        return http.post(config.api.chat, {
            request: 'web_search',
            input: data.input,
            model: data.model || '',
            mode: 'web_search',
            provider_id: data.provider_id || '',
            context: data.context || '[]'
        }, {
            loadingText: '搜索中...'
        })
    },
    
    /**
     * 获取可用模型列表
     */
    getModels() {
        return http.get(config.api.models, {}, {
            loading: false
        })
    },
    
    /**
     * 获取提供商列表
     */
    getProviders(enabled = 1) {
        return http.get(config.api.providers, {
            action: 'get_providers',
            enabled
        }, {
            loading: false
        })
    },
    
    /**
     * 获取提供商模型
     */
    getProviderModels(providerId) {
        return http.get(config.api.providers, {
            action: 'fetch_models',
            provider_id: providerId
        }, {
            loading: false
        })
    }
}
