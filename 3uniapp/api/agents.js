/**
 * 智能体相关 API
 */
import http from '@/utils/request.js'
import config from '@/config/index.js'

export default {
    /**
     * 获取智能体列表
     */
    getAgents(params = {}) {
        return http.get(config.api.agents, {
            action: 'getAgents',
            page: params.page || 1,
            limit: params.limit || 10,
            category: params.category || '',
            keyword: params.keyword || ''
        })
    },
    
    /**
     * 获取我的智能体列表
     */
    getMyAgents(params = {}) {
        return http.get(config.api.agents, {
            action: 'getMyAgents',
            page: params.page || 1,
            limit: params.limit || 10
        })
    },
    
    /**
     * 获取智能体详情
     */
    getAgentDetail(agentId) {
        return http.get(config.api.agents, {
            action: 'getAgent',
            agent_id: agentId
        })
    },
    
    /**
     * 创建智能体
     */
    createAgent(data) {
        return http.post(config.api.agents, {
            action: 'saveAgent',
            ...data
        })
    },
    
    /**
     * 更新智能体
     */
    updateAgent(agentId, data) {
        return http.post(config.api.agents, {
            action: 'saveAgent',
            agent_id: agentId,
            ...data
        })
    },
    
    /**
     * 删除智能体
     */
    deleteAgent(agentId) {
        return http.post(config.api.agents, {
            action: 'deleteAgent',
            agent_id: agentId
        })
    },
    
    /**
     * 部署智能体 - 生成外部访问token
     */
    deployAgent(agentId) {
        return http.post(config.api.agents, {
            action: 'deployAgent',
            agent_id: agentId
        })
    },
    
    /**
     * 取消部署智能体
     */
    undeployAgent(agentId) {
        return http.post(config.api.agents, {
            action: 'undeployAgent',
            agent_id: agentId
        })
    },
    
    /**
     * 与智能体对话 - 使用agent_chat端点
     */
    chatWithAgent(data) {
        return http.post('/index.php?route=agent_chat', {
            token: data.token || '',
            message: data.message,
            session_id: data.session_id || ''
        })
    },
    
    /**
     * 获取智能体聊天历史
     */
    getChatHistory(agentId, sessionId = '') {
        return http.get(config.api.agents, {
            action: 'getChatHistory',
            agent_id: agentId,
            session_id: sessionId
        })
    },
    
    /**
     * 获取可用模型列表（本地+在线）
     */
    getAvailableModels() {
        return http.get(config.api.models, {
            request: 'models'
        })
    },
    
    /**
     * 获取在线API提供商列表
     */
    getProviders() {
        return http.get(config.api.providers, {
            action: 'get_providers',
            enabled: 1
        })
    }
}
