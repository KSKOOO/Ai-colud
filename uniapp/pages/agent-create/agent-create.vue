<template>
    <view class="create-page">
        <view class="nav-bar">
            <view class="nav-back" @click="goBack">
                <text class="nav-back-text">返回</text>
            </view>
            <text class="nav-title">创建智能体</text>
            <view class="nav-save" @click="saveAgent">保存</view>
        </view>

        <scroll-view scroll-y class="page-scroll">
            <view class="section-card">
                <text class="section-title">基本信息</text>

                <view class="form-item">
                    <text class="label">智能体名称 *</text>
                    <input v-model.trim="form.name" class="input" placeholder="给智能体起个名字" />
                </view>

                <view class="form-item">
                    <text class="label">图标</text>
                    <view class="icon-preview-card">
                        <view class="icon-preview">{{ form.icon }}</view>
                        <view class="icon-preview-info">
                            <text class="icon-preview-name">{{ selectedIconLabel || '未选择图标' }}</text>
                            <text class="icon-preview-desc">点击下方图标即可切换</text>
                        </view>
                    </view>
                    <input v-model.trim="iconKeyword" class="input input-compact" placeholder="搜索图标，如：写作、代码、分析" />
                    <view class="icon-grid">
                        <view
                            v-for="icon in filteredIcons"
                            :key="icon.value"
                            class="icon-tile"
                            :class="{ active: form.icon === icon.value }"
                            @click="selectIcon(icon)"
                        >
                            <text class="icon-value">{{ icon.value }}</text>
                            <text class="icon-label">{{ icon.label }}</text>
                        </view>
                    </view>
                </view>

                <view class="form-item">
                    <text class="label">分类</text>
                    <picker mode="selector" :range="categoryOptions" :value="categoryIndex" @change="onCategoryChange">
                        <view class="picker">
                            <text>{{ form.category || '选择分类' }}</text>
                            <text class="picker-arrow">></text>
                        </view>
                    </picker>
                </view>

                <view class="form-item">
                    <text class="label">简介</text>
                    <textarea v-model.trim="form.description" class="textarea" placeholder="简要介绍智能体的定位和用途" />
                </view>

                <view class="form-item">
                    <text class="label">功能标签</text>
                    <input v-model.trim="tagsInput" class="input" placeholder="用逗号分隔，如：写作,代码,分析" />
                </view>
            </view>

            <view class="section-card">
                <text class="section-title">AI 模型设置</text>

                <view class="form-item">
                    <text class="label">选择模型 *</text>
                    <view class="selected-model-card" v-if="selectedModel">
                        <view class="selected-model-main">
                            <text class="selected-model-name">{{ selectedModel.name }}</text>
                            <text class="selected-model-provider">{{ selectedModel.providerName }}</text>
                        </view>
                        <view class="selected-model-meta">{{ selectedModel.providerType }}</view>
                    </view>
                    <input v-model.trim="modelKeyword" class="input input-compact" placeholder="搜索模型或提供商" />
                    <view class="model-selector">
                        <view v-if="groupedModels.length === 0" class="empty-state">
                            <text>暂无可用模型，请先在服务端配置在线 AI 提供商</text>
                        </view>

                        <view v-for="group in groupedModels" :key="group.providerId" class="model-group">
                            <view class="model-group-header" @click="toggleProviderGroup(group.providerId)">
                                <view class="model-group-info">
                                    <text class="model-group-name">{{ group.providerName }}</text>
                                    <text class="model-group-count">{{ group.models.length }} 个模型</text>
                                </view>
                                <text class="model-group-toggle">{{ isProviderExpanded(group.providerId) ? '收起' : '展开' }}</text>
                            </view>

                            <view v-if="isProviderExpanded(group.providerId)" class="model-list">
                                <view
                                    v-for="model in group.models"
                                    :key="group.providerId + ':' + model.id"
                                    class="model-option"
                                    :class="{ active: form.model_id === model.id && form.model_provider === model.provider }"
                                    @click="selectModel(model)"
                                >
                                    <view class="model-main">
                                        <text class="model-name">{{ model.name }}</text>
                                        <text class="model-remark">{{ model.remark }}</text>
                                    </view>
                                    <text class="model-check" v-if="form.model_id === model.id && form.model_provider === model.provider">✓</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>

                <view class="form-item">
                    <text class="label">角色设定</text>
                    <textarea v-model.trim="form.role_name" class="textarea textarea-sm" placeholder="例如：专业写作助手、售前顾问、数据分析师" />
                </view>

                <view class="form-item">
                    <text class="label">系统提示词 *</text>
                    <textarea v-model.trim="form.system_prompt" class="textarea textarea-lg" placeholder="定义智能体应该如何回答、擅长什么、遵循什么限制" />
                    <text class="hint">建议写清楚身份、能力边界、回答风格和输出格式。</text>
                </view>

                <view class="form-row">
                    <view class="form-item form-item-half">
                        <text class="label">Temperature</text>
                        <slider :value="form.temperature * 100" min="0" max="200" show-value @change="onTempChange" />
                        <text class="hint">{{ form.temperature }}</text>
                    </view>
                    <view class="form-item form-item-half">
                        <text class="label">Max Tokens</text>
                        <input v-model="form.max_tokens" class="input" type="number" placeholder="4096" />
                    </view>
                </view>
            </view>

            <view class="section-card">
                <text class="section-title">高级设置</text>

                <view class="form-item">
                    <text class="label">欢迎语</text>
                    <input v-model.trim="form.welcome_message" class="input" placeholder="用户第一次打开时看到的欢迎语" />
                </view>

                <view class="form-item">
                    <text class="label">能力说明</text>
                    <textarea v-model.trim="capabilitiesInput" class="textarea" placeholder="每行一条，例如：\n擅长写作\n能够总结文档\n支持代码解释" />
                </view>
            </view>

            <view class="bottom-space"></view>
        </scroll-view>
    </view>
</template>

<script>
import agentsApi from '@/api/agents.js'

const ICON_OPTIONS = [
    { value: '🤖', label: '机器人' },
    { value: '💡', label: '灵感' },
    { value: '🧠', label: '思考' },
    { value: '📝', label: '写作' },
    { value: '💻', label: '代码' },
    { value: '📊', label: '分析' },
    { value: '🎨', label: '设计' },
    { value: '🔍', label: '搜索' },
    { value: '🚀', label: '效率' },
    { value: '⚡', label: '快速' },
    { value: '🛠️', label: '工具' },
    { value: '📚', label: '知识' },
    { value: '🎯', label: '目标' },
    { value: '💬', label: '沟通' },
    { value: '🌟', label: '通用' }
]

export default {
    data() {
        return {
            form: {
                name: '',
                icon: '🤖',
                category: '通用',
                description: '',
                tags: [],
                model_provider: '',
                model_id: '',
                role_name: '',
                system_prompt: '',
                temperature: 0.7,
                max_tokens: 4096,
                welcome_message: '',
                capabilities: []
            },
            tagsInput: '',
            capabilitiesInput: '',
            iconKeyword: '',
            modelKeyword: '',
            iconOptions: ICON_OPTIONS,
            categoryOptions: ['通用', '写作', '编程', '分析', '创意', '教育', '商务', '生活', '娱乐'],
            categoryIndex: 0,
            onlineModels: [],
            expandedProviders: {}
        }
    },

    computed: {
        filteredIcons() {
            const keyword = this.iconKeyword.trim().toLowerCase()
            if (!keyword) {
                return this.iconOptions
            }
            return this.iconOptions.filter(item =>
                item.label.toLowerCase().includes(keyword) || item.value.includes(keyword)
            )
        },

        selectedIconLabel() {
            const found = this.iconOptions.find(item => item.value === this.form.icon)
            return found ? found.label : ''
        },

        selectedModel() {
            return this.onlineModels.find(
                item => item.provider === this.form.model_provider && item.id === this.form.model_id
            ) || null
        },

        groupedModels() {
            const keyword = this.modelKeyword.trim().toLowerCase()
            const groups = {}

            this.onlineModels.forEach(model => {
                const match = !keyword ||
                    model.name.toLowerCase().includes(keyword) ||
                    model.providerName.toLowerCase().includes(keyword) ||
                    model.providerType.toLowerCase().includes(keyword)

                if (!match) return

                if (!groups[model.provider]) {
                    groups[model.provider] = {
                        providerId: model.provider,
                        providerName: model.providerName,
                        providerType: model.providerType,
                        models: []
                    }
                }

                groups[model.provider].models.push(model)
            })

            return Object.values(groups).sort((a, b) => {
                if (a.providerId === this.form.model_provider) return -1
                if (b.providerId === this.form.model_provider) return 1
                return a.providerName.localeCompare(b.providerName)
            })
        }
    },

    onLoad() {
        this.loadModels()
    },

    methods: {
        async loadModels() {
            try {
                const providerRes = await agentsApi.getProviders()
                this.onlineModels = []
                this.expandedProviders = {}

                if (providerRes.success && providerRes.data) {
                    providerRes.data.forEach(provider => {
                        const providerModels = Array.isArray(provider.models) ? provider.models : []
                        this.expandedProviders[provider.id] = !!provider.is_default

                        providerModels.forEach(modelName => {
                            this.onlineModels.push({
                                id: modelName,
                                name: modelName,
                                provider: provider.id,
                                providerName: provider.name || provider.id,
                                providerType: provider.type || 'api',
                                remark: this.getModelRemark(modelName)
                            })
                        })

                        if (!this.form.model_provider && provider.is_default && providerModels.length > 0) {
                            const defaultModel = provider.default_model || providerModels[0]
                            this.form.model_provider = provider.id
                            this.form.model_id = defaultModel
                        }
                    })
                }
            } catch (error) {
                console.error('loadModels failed:', error)
                uni.showToast({ title: '模型加载失败', icon: 'none' })
            }
        },

        getModelRemark(name) {
            const lower = String(name || '').toLowerCase()

            if (lower.includes('vl') || lower.includes('vision')) return '图文理解'
            if (lower.includes('ocr')) return 'OCR'
            if (lower.includes('image')) return '图像'
            if (lower.includes('video')) return '视频'
            if (lower.includes('r1')) return '推理模型'
            if (lower.includes('coder') || lower.includes('code')) return '代码生成'
            if (lower.includes('chat')) return '对话'
            if (lower.includes('turbo')) return '高速'
            if (lower.includes('max')) return '高性能'
            return '通用模型'
        },

        onCategoryChange(e) {
            this.categoryIndex = Number(e.detail.value)
            this.form.category = this.categoryOptions[this.categoryIndex]
        },

        onTempChange(e) {
            this.form.temperature = (Number(e.detail.value) / 100).toFixed(1)
        },

        selectIcon(icon) {
            this.form.icon = icon.value
        },

        selectModel(model) {
            this.form.model_id = model.id
            this.form.model_provider = model.provider
        },

        toggleProviderGroup(providerId) {
            this.expandedProviders = {
                ...this.expandedProviders,
                [providerId]: !this.expandedProviders[providerId]
            }
        },

        isProviderExpanded(providerId) {
            return !!this.expandedProviders[providerId]
        },

        goBack() {
            uni.navigateBack()
        },

        buildPayload() {
            return {
                ...this.form,
                tags: this.tagsInput
                    .split(/[,\n，]/)
                    .map(item => item.trim())
                    .filter(Boolean),
                capabilities: this.capabilitiesInput
                    .split(/\n+/)
                    .map(item => item.trim())
                    .filter(Boolean)
            }
        },

        async saveAgent() {
            if (!this.form.name.trim()) {
                uni.showToast({ title: '请输入智能体名称', icon: 'none' })
                return
            }

            if (!this.form.model_id || !this.form.model_provider) {
                uni.showToast({ title: '请选择 AI 模型', icon: 'none' })
                return
            }

            if (!this.form.system_prompt.trim()) {
                uni.showToast({ title: '请输入系统提示词', icon: 'none' })
                return
            }

            uni.showLoading({ title: '保存中...' })
            try {
                const res = await agentsApi.createAgent(this.buildPayload())
                if (res.success) {
                    uni.showToast({ title: '创建成功', icon: 'success' })
                    setTimeout(() => {
                        uni.navigateBack()
                    }, 800)
                } else {
                    uni.showToast({ title: res.error || '创建失败', icon: 'none' })
                }
            } catch (error) {
                console.error('saveAgent failed:', error)
                uni.showToast({ title: '创建失败', icon: 'none' })
            } finally {
                uni.hideLoading()
            }
        }
    }
}
</script>

<style scoped>
.create-page {
    min-height: 100vh;
    background: #f8fafc;
}

.nav-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24rpx 32rpx;
    background: #ffffff;
    border-bottom: 1rpx solid #e5e7eb;
}

.nav-back-text,
.nav-save {
    font-size: 28rpx;
    color: #4c51bf;
    font-weight: 500;
}

.nav-title {
    font-size: 32rpx;
    font-weight: 600;
    color: #1f2937;
}

.page-scroll {
    padding: 24rpx;
}

.section-card {
    background: #ffffff;
    border-radius: 20rpx;
    padding: 24rpx;
    margin-bottom: 24rpx;
}

.section-title {
    display: block;
    font-size: 30rpx;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 24rpx;
}

.form-item {
    margin-bottom: 24rpx;
}

.form-row {
    display: flex;
    gap: 24rpx;
}

.form-item-half {
    flex: 1;
}

.label {
    display: block;
    font-size: 28rpx;
    color: #374151;
    margin-bottom: 12rpx;
}

.input,
.textarea,
.picker {
    width: 100%;
    box-sizing: border-box;
    background: #f3f4f6;
    border-radius: 14rpx;
    font-size: 28rpx;
    color: #1f2937;
}

.input {
    height: 84rpx;
    padding: 0 24rpx;
}

.input-compact {
    height: 74rpx;
    margin-bottom: 16rpx;
}

.textarea {
    min-height: 160rpx;
    padding: 18rpx 24rpx;
}

.textarea-sm {
    min-height: 120rpx;
}

.textarea-lg {
    min-height: 240rpx;
}

.picker {
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 84rpx;
    padding: 0 24rpx;
}

.picker-arrow {
    color: #9ca3af;
}

.hint {
    display: block;
    margin-top: 8rpx;
    font-size: 24rpx;
    color: #6b7280;
}

.icon-preview-card {
    display: flex;
    align-items: center;
    gap: 20rpx;
    padding: 20rpx;
    background: #f8fafc;
    border-radius: 16rpx;
    margin-bottom: 16rpx;
}

.icon-preview {
    width: 92rpx;
    height: 92rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #eef2ff;
    border-radius: 20rpx;
    font-size: 42rpx;
}

.icon-preview-info {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.icon-preview-name {
    font-size: 28rpx;
    font-weight: 600;
    color: #1f2937;
}

.icon-preview-desc {
    margin-top: 6rpx;
    font-size: 24rpx;
    color: #6b7280;
}

.icon-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16rpx;
}

.icon-tile {
    background: #f8fafc;
    border: 2rpx solid transparent;
    border-radius: 18rpx;
    padding: 16rpx 8rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 118rpx;
}

.icon-tile.active {
    border-color: #4c51bf;
    background: #eef2ff;
}

.icon-value {
    font-size: 40rpx;
}

.icon-label {
    margin-top: 8rpx;
    font-size: 22rpx;
    color: #4b5563;
}

.selected-model-card {
    display: flex;
    justify-content: space-between;
    gap: 16rpx;
    padding: 20rpx;
    background: #eef2ff;
    border: 2rpx solid #4c51bf;
    border-radius: 16rpx;
    margin-bottom: 16rpx;
}

.selected-model-main {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.selected-model-name {
    font-size: 30rpx;
    font-weight: 700;
    color: #1f2937;
}

.selected-model-provider,
.selected-model-meta {
    margin-top: 6rpx;
    font-size: 24rpx;
    color: #4c51bf;
}

.model-selector {
    background: #f8fafc;
    border-radius: 16rpx;
    padding: 16rpx;
}

.model-group {
    margin-bottom: 16rpx;
    border-radius: 14rpx;
    background: #ffffff;
    overflow: hidden;
}

.model-group-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18rpx 20rpx;
    border-bottom: 1rpx solid #f3f4f6;
}

.model-group-info {
    display: flex;
    flex-direction: column;
}

.model-group-name {
    font-size: 28rpx;
    font-weight: 600;
    color: #1f2937;
}

.model-group-count {
    margin-top: 4rpx;
    font-size: 22rpx;
    color: #6b7280;
}

.model-group-toggle {
    font-size: 24rpx;
    color: #4c51bf;
}

.model-list {
    padding: 12rpx;
}

.model-option {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16rpx;
    padding: 16rpx 18rpx;
    border: 2rpx solid #e5e7eb;
    border-radius: 14rpx;
    margin-bottom: 12rpx;
}

.model-option:last-child {
    margin-bottom: 0;
}

.model-option.active {
    border-color: #4c51bf;
    background: #eef2ff;
}

.model-main {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.model-name {
    font-size: 28rpx;
    font-weight: 600;
    color: #1f2937;
}

.model-remark {
    margin-top: 6rpx;
    font-size: 22rpx;
    color: #6b7280;
}

.model-check {
    font-size: 32rpx;
    color: #4c51bf;
    font-weight: 700;
}

.empty-state {
    padding: 40rpx 24rpx;
    text-align: center;
    color: #6b7280;
    font-size: 24rpx;
}

.bottom-space {
    height: 60rpx;
}
</style>
