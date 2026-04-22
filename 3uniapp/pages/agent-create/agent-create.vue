<template>
    <view class="create-container">
        <!-- 顶部导航 -->
        <view class="nav-bar">
            <view class="nav-back" @click="goBack">
                <text class="icon">←</text>
            </view>
            <text class="nav-title">创建智能体</text>
            <view class="nav-right">
                <text class="save-btn" @click="saveAgent">保存</text>
            </view>
        </view>
        
        <scroll-view scroll-y class="form-container">
            <!-- 基本信息 -->
            <view class="section">
                <view class="section-title">基本信息</view>
                
                <view class="form-item">
                    <text class="label">智能体名称 *</text>
                    <input 
                        class="input" 
                        v-model="form.name" 
                        placeholder="给智能体起个名字"
                    />
                </view>
                
                <view class="form-item">
                    <text class="label">图标</text>
                    <view class="icon-selector">
                        <view 
                            v-for="(icon, index) in iconOptions" 
                            :key="index"
                            class="icon-option"
                            :class="{ active: form.icon === icon }"
                            @click="form.icon = icon"
                        >
                            <text class="icon-text">{{ icon }}</text>
                        </view>
                    </view>
                </view>
                
                <view class="form-item">
                    <text class="label">分类</text>
                    <picker mode="selector" :range="categoryOptions" :value="categoryIndex" @change="onCategoryChange">
                        <view class="picker">
                            <text>{{ form.category || '选择分类' }}</text>
                            <text class="arrow">></text>
                        </view>
                    </picker>
                </view>
                
                <view class="form-item">
                    <text class="label">简介</text>
                    <textarea 
                        class="textarea" 
                        v-model="form.description" 
                        placeholder="简单介绍这个智能体的功能"
                    />
                </view>
                
                <view class="form-item">
                    <text class="label">功能标签</text>
                    <input 
                        class="input" 
                        v-model="tagsInput" 
                        placeholder="用逗号分隔，如：写作, 编程, 分析"
                    />
                </view>
            </view>
            
            <!-- AI模型设置 -->
            <view class="section">
                <view class="section-title">AI模型设置</view>
                
                <view class="form-item">
                    <text class="label">选择模型 *</text>
                    <view class="model-selector">
                        <!-- 在线API模型 -->
                        <view v-if="onlineModels.length > 0" class="model-group">
                            <text class="model-group-title">☁️ 在线API模型</text>
                            <view 
                                v-for="model in onlineModels" 
                                :key="model.id"
                                class="model-option"
                                :class="{ active: form.model_id === model.id && form.model_provider === model.provider }"
                                @click="selectModel(model)"
                            >
                                <view class="model-info">
                                    <text class="model-name">{{ model.name }}</text>
                                    <text class="model-provider">{{ model.provider_name }}</text>
                                </view>
                                <text v-if="form.model_id === model.id" class="check">✓</text>
                            </view>
                        </view>
                        
                        <!-- 本地模型 -->
                        <view v-if="localModels.length > 0" class="model-group">
                            <text class="model-group-title">💻 本地模型</text>
                            <view 
                                v-for="model in localModels" 
                                :key="model.id"
                                class="model-option"
                                :class="{ active: form.model_id === model.id && form.model_provider === 'ollama' }"
                                @click="selectModel(model)"
                            >
                                <view class="model-info">
                                    <text class="model-name">{{ model.name }}</text>
                                    <text v-if="model.size" class="model-size">{{ model.size }}</text>
                                </view>
                                <text v-if="form.model_id === model.id" class="check">✓</text>
                            </view>
                        </view>
                        
                        <!-- 无模型提示 -->
                        <view v-if="onlineModels.length === 0 && localModels.length === 0" class="empty-models">
                            <text>暂无可用模型，请先导入模型</text>
                        </view>
                    </view>
                </view>
                
                <view class="form-item">
                    <text class="label">角色设定</text>
                    <textarea 
                        class="textarea" 
                        v-model="form.role_name" 
                        placeholder="例如：专业写作助手"
                    />
                </view>
                
                <view class="form-item">
                    <text class="label">系统提示词 *</text>
                    <textarea 
                        class="textarea system-prompt" 
                        v-model="form.system_prompt" 
                        placeholder="定义智能体的行为和回答方式..."
                    />
                    <text class="hint">提示词会显著影响智能体的表现，建议详细描述</text>
                </view>
                
                <view class="form-row">
                    <view class="form-item half">
                        <text class="label">Temperature</text>
                        <slider 
                            :value="form.temperature * 100" 
                            @change="onTempChange"
                            min="0" 
                            max="200" 
                            show-value
                        />
                        <text class="value">{{ form.temperature }}</text>
                    </view>
                    
                    <view class="form-item half">
                        <text class="label">Max Tokens</text>
                        <input 
                            class="input" 
                            type="number"
                            v-model="form.max_tokens" 
                            placeholder="4096"
                        />
                    </view>
                </view>
            </view>
            
            <!-- 高级设置 -->
            <view class="section">
                <view class="section-title">高级设置</view>
                
                <view class="form-item">
                    <text class="label">欢迎语</text>
                    <input 
                        class="input" 
                        v-model="form.welcome_message" 
                        placeholder="用户首次进入时显示的欢迎语"
                    />
                </view>
                
                <view class="form-item">
                    <text class="label">能力说明</text>
                    <textarea 
                        class="textarea" 
                        v-model="form.capabilities" 
                        placeholder="描述智能体的能力范围，每行一个"
                    />
                </view>
            </view>
            
            <!-- 底部间距 -->
            <view class="bottom-space"></view>
        </scroll-view>
    </view>
</template>

<script>
import agentsApi from '@/api/agents.js'

export default {
    data() {
        return {
            form: {
                name: '',
                icon: '🤖',
                category: '',
                description: '',
                tags: '',
                model_provider: '',
                model_id: '',
                role_name: '',
                system_prompt: '',
                temperature: 0.7,
                max_tokens: 4096,
                welcome_message: '',
                capabilities: ''
            },
            tagsInput: '',
            iconOptions: ['🤖', '🦞', '🧠', '💻', '📝', '🎨', '📊', '🔍', '💡', '🚀', '⚡', '🔧', '📚', '🎯', '💬'],
            categoryOptions: ['通用', '写作', '编程', '分析', '创意', '教育', '商务', '生活', '娱乐'],
            categoryIndex: 0,
            onlineModels: [],
            localModels: []
        }
    },
    
    onLoad() {
        this.loadModels()
    },
    
    methods: {
        // 加载可用模型
        async loadModels() {
            try {
                // 获取本地模型
                const localRes = await agentsApi.getAvailableModels()
                if (localRes.status === 'success' && localRes.models) {
                    this.localModels = Object.entries(localRes.models).map(([id, model]) => ({
                        id: id,
                        name: typeof model === 'object' ? (model.name || id) : model,
                        size: typeof model === 'object' ? model.parameter_size : '',
                        provider: 'ollama',
                        type: 'local'
                    }))
                }
                
                // 获取在线API模型
                const providerRes = await agentsApi.getProviders()
                if (providerRes.success && providerRes.data) {
                    providerRes.data.forEach(provider => {
                        if (provider.models && provider.models.length > 0) {
                            provider.models.forEach(modelName => {
                                this.onlineModels.push({
                                    id: modelName,
                                    name: modelName,
                                    provider: provider.id,
                                    provider_name: provider.name,
                                    type: 'online'
                                })
                            })
                        }
                    })
                }
            } catch (error) {
                console.error('加载模型失败:', error)
                uni.showToast({ title: '模型加载失败', icon: 'none' })
            }
        },
        
        // 选择模型
        selectModel(model) {
            this.form.model_id = model.id
            this.form.model_provider = model.provider
        },
        
        // 分类选择
        onCategoryChange(e) {
            this.categoryIndex = e.detail.value
            this.form.category = this.categoryOptions[this.categoryIndex]
        },
        
        // 温度调节
        onTempChange(e) {
            this.form.temperature = (e.detail.value / 100).toFixed(1)
        },
        
        // 返回
        goBack() {
            uni.navigateBack()
        },
        
        // 保存智能体
        async saveAgent() {
            // 验证必填项
            if (!this.form.name.trim()) {
                uni.showToast({ title: '请输入智能体名称', icon: 'none' })
                return
            }
            if (!this.form.model_id) {
                uni.showToast({ title: '请选择AI模型', icon: 'none' })
                return
            }
            if (!this.form.system_prompt.trim()) {
                uni.showToast({ title: '请输入系统提示词', icon: 'none' })
                return
            }
            
            // 处理标签
            this.form.tags = this.tagsInput
            
            uni.showLoading({ title: '保存中...' })
            
            try {
                const res = await agentsApi.createAgent(this.form)
                
                if (res.success) {
                    uni.showToast({ title: '创建成功', icon: 'success' })
                    setTimeout(() => {
                        uni.navigateBack()
                    }, 1500)
                } else {
                    uni.showToast({ title: res.error || '创建失败', icon: 'none' })
                }
            } catch (error) {
                console.error('创建智能体失败:', error)
                uni.showToast({ title: '创建失败', icon: 'none' })
            } finally {
                uni.hideLoading()
            }
        }
    }
}
</script>

<style scoped>
.create-container {
    min-height: 100vh;
    background: #f8fafc;
}

/* 导航栏 */
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
    padding: 8rpx 16rpx;
}

.save-btn {
    font-size: 28rpx;
    color: #4c51bf;
    font-weight: 500;
}

/* 表单容器 */
.form-container {
    padding: 24rpx;
}

.section {
    background: #ffffff;
    border-radius: 20rpx;
    padding: 24rpx;
    margin-bottom: 24rpx;
}

.section-title {
    font-size: 30rpx;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 24rpx;
    padding-bottom: 16rpx;
    border-bottom: 2rpx solid #f3f4f6;
}

.form-item {
    margin-bottom: 24rpx;
}

.form-item.half {
    flex: 1;
}

.form-row {
    display: flex;
    gap: 24rpx;
}

.label {
    display: block;
    font-size: 28rpx;
    color: #374151;
    margin-bottom: 12rpx;
}

.input {
    width: 100%;
    height: 80rpx;
    padding: 0 24rpx;
    background: #f3f4f6;
    border-radius: 12rpx;
    font-size: 28rpx;
    color: #1f2937;
}

.textarea {
    width: 100%;
    height: 160rpx;
    padding: 16rpx 24rpx;
    background: #f3f4f6;
    border-radius: 12rpx;
    font-size: 28rpx;
    color: #1f2937;
}

.textarea.system-prompt {
    height: 240rpx;
}

.hint {
    display: block;
    font-size: 24rpx;
    color: #6b7280;
    margin-top: 8rpx;
}

.value {
    font-size: 26rpx;
    color: #4c51bf;
    margin-top: 8rpx;
}

/* 图标选择器 */
.icon-selector {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
}

.icon-option {
    width: 80rpx;
    height: 80rpx;
    background: #f3f4f6;
    border-radius: 16rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2rpx solid transparent;
}

.icon-option.active {
    border-color: #4c51bf;
    background: #ede9fe;
}

.icon-text {
    font-size: 40rpx;
}

/* 选择器 */
.picker {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 80rpx;
    padding: 0 24rpx;
    background: #f3f4f6;
    border-radius: 12rpx;
    font-size: 28rpx;
    color: #1f2937;
}

.arrow {
    color: #9ca3af;
}

/* 模型选择器 */
.model-selector {
    background: #f8fafc;
    border-radius: 12rpx;
    padding: 16rpx;
}

.model-group {
    margin-bottom: 24rpx;
}

.model-group-title {
    display: block;
    font-size: 26rpx;
    color: #6b7280;
    margin-bottom: 12rpx;
}

.model-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16rpx 20rpx;
    background: #ffffff;
    border-radius: 12rpx;
    margin-bottom: 12rpx;
    border: 2rpx solid #e5e7eb;
}

.model-option.active {
    border-color: #4c51bf;
    background: #ede9fe;
}

.model-info {
    display: flex;
    flex-direction: column;
}

.model-name {
    font-size: 28rpx;
    color: #1f2937;
    font-weight: 500;
}

.model-provider, .model-size {
    font-size: 24rpx;
    color: #6b7280;
    margin-top: 4rpx;
}

.check {
    color: #4c51bf;
    font-size: 32rpx;
    font-weight: bold;
}

.empty-models {
    text-align: center;
    padding: 48rpx;
    color: #6b7280;
}

/* 底部间距 */
.bottom-space {
    height: 48rpx;
}
</style>