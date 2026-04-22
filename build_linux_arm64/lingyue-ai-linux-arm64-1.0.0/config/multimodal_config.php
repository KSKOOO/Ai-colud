<?php
/**
 * 多模态模型配置文件
 * 定义系统中可用的全态模型及其能力
 */

return [
    // 多模态模型定义（支持图像、视频、文件的多模态模型）
    'multimodal_models' => [
        // Ollama 多模态模型（本地部署）
        'ollama' => [
            ['id' => 'llava', 'name' => 'LLaVA', 'desc' => '图像理解', 'supports' => ['image', 'text']],
            ['id' => 'llava-phi3', 'name' => 'LLaVA-Phi3', 'desc' => '图像理解轻量版', 'supports' => ['image', 'text']],
            ['id' => 'bakllava', 'name' => 'BakLLaVA', 'desc' => '增强图像理解', 'supports' => ['image', 'text']],
            ['id' => 'moondream', 'name' => 'Moondream', 'desc' => '轻量图像模型', 'supports' => ['image', 'text']],
            ['id' => 'bunny-llama', 'name' => 'Bunny-Llama', 'desc' => '高效图像理解', 'supports' => ['image', 'text']],
        ],
        // OpenAI 多模态模型
        'openai' => [
            ['id' => 'gpt-4o', 'name' => 'GPT-4o', 'desc' => '全能多模态', 'supports' => ['image', 'text', 'file']],
            ['id' => 'gpt-4o-mini', 'name' => 'GPT-4o Mini', 'desc' => '轻量多模态', 'supports' => ['image', 'text', 'file']],
            ['id' => 'gpt-4-vision-preview', 'name' => 'GPT-4 Vision', 'desc' => '视觉增强', 'supports' => ['image', 'text']],
        ],
        // 通义千问多模态模型
        'qwen' => [
            ['id' => 'qwen-vl-plus', 'name' => '通义千问VL', 'desc' => '视觉语言模型', 'supports' => ['image', 'text', 'video']],
            ['id' => 'qwen-vl-max', 'name' => '通义千问VL Max', 'desc' => '增强视觉语言', 'supports' => ['image', 'text', 'video']],
            ['id' => 'qwen-omni-turbo', 'name' => '通义千问Omni', 'desc' => '全模态理解', 'supports' => ['image', 'text', 'video', 'audio']],
        ],
        // Google Gemini 多模态模型
        'gemini' => [
            ['id' => 'gemini-1.5-pro', 'name' => 'Gemini 1.5 Pro', 'desc' => '多模态Pro', 'supports' => ['image', 'text', 'video', 'file']],
            ['id' => 'gemini-1.5-flash', 'name' => 'Gemini 1.5 Flash', 'desc' => '快速多模态', 'supports' => ['image', 'text', 'video', 'file']],
            ['id' => 'gemini-pro-vision', 'name' => 'Gemini Vision', 'desc' => '视觉专用', 'supports' => ['image', 'text']],
        ],
        // 腾讯混元多模态模型
        'hunyuan' => [
            ['id' => 'hunyuan-vision', 'name' => '混元Vision', 'desc' => '腾讯视觉模型', 'supports' => ['image', 'text']],
        ],
        // Anthropic Claude 多模态模型
        'anthropic' => [
            ['id' => 'claude-3-opus', 'name' => 'Claude 3 Opus', 'desc' => '最强多模态', 'supports' => ['image', 'text', 'file']],
            ['id' => 'claude-3-sonnet', 'name' => 'Claude 3 Sonnet', 'desc' => '平衡多模态', 'supports' => ['image', 'text', 'file']],
            ['id' => 'claude-3-haiku', 'name' => 'Claude 3 Haiku', 'desc' => '快速多模态', 'supports' => ['image', 'text', 'file']],
        ],
        // 智谱AI多模态模型
        'zhipu' => [
            ['id' => 'glm-4v', 'name' => 'GLM-4V', 'desc' => '智谱视觉模型', 'supports' => ['image', 'text']],
        ]
    ],

    // 场景与模型能力匹配配置
    'scenario_mapping' => [
        // 视频相关场景
        'video-edit' => ['capabilities' => ['video', 'image'], 'providers' => ['qwen', 'gemini']],
        'live-highlight' => ['capabilities' => ['video', 'image'], 'providers' => ['qwen', 'gemini']],
        // 图像相关场景
        'product-desc' => ['capabilities' => ['image'], 'providers' => ['ollama', 'openai', 'qwen', 'gemini', 'hunyuan', 'anthropic', 'zhipu']],
        'medical-report' => ['capabilities' => ['image', 'file'], 'providers' => ['openai', 'qwen', 'gemini', 'anthropic']],
        'insurance-claim' => ['capabilities' => ['image', 'file'], 'providers' => ['openai', 'qwen', 'gemini', 'anthropic']],
        'contract-review' => ['capabilities' => ['image', 'file'], 'providers' => ['openai', 'qwen', 'gemini', 'anthropic']],
        'legal-doc' => ['capabilities' => ['image', 'file'], 'providers' => ['openai', 'qwen', 'gemini', 'anthropic']],
        // 文档分析场景
        'financial-analysis' => ['capabilities' => ['file'], 'providers' => ['openai', 'gemini', 'anthropic']],
        'data-analysis' => ['capabilities' => ['file'], 'providers' => ['openai', 'gemini', 'anthropic']],
        'data-export' => ['capabilities' => ['file'], 'providers' => ['openai', 'gemini', 'anthropic']],
        'excel-assistant' => ['capabilities' => ['file'], 'providers' => ['openai', 'gemini', 'anthropic']],
        'course-material' => ['capabilities' => ['file'], 'providers' => ['openai', 'qwen', 'gemini', 'anthropic']],
        'essay-correct' => ['capabilities' => ['image', 'file'], 'providers' => ['ollama', 'openai', 'qwen', 'gemini', 'anthropic']],
        // 通用场景（支持图像理解）
        'ai-employee' => ['capabilities' => ['text', 'image'], 'providers' => ['all']],
        'sales-assistant' => ['capabilities' => ['text', 'image'], 'providers' => ['all']],
        'douyin-copy' => ['capabilities' => ['text', 'image'], 'providers' => ['all']],
        'xiaohongshu-copy' => ['capabilities' => ['text', 'image'], 'providers' => ['all']],
        'review-reply' => ['capabilities' => ['text', 'image'], 'providers' => ['all']],
        'health-consult' => ['capabilities' => ['text', 'image'], 'providers' => ['all']],
    ],

    // 能力类型定义
    'capabilities' => [
        'text' => ['name' => '文本', 'icon' => 'fa-font', 'color' => '#3b82f6'],
        'image' => ['name' => '图像', 'icon' => 'fa-image', 'color' => '#10b981'],
        'video' => ['name' => '视频', 'icon' => 'fa-video', 'color' => '#f59e0b'],
        'file' => ['name' => '文档', 'icon' => 'fa-file', 'color' => '#8b5cf6'],
        'audio' => ['name' => '音频', 'icon' => 'fa-music', 'color' => '#ec4899']
    ],

    // 文件上传配置
    'file_upload' => [
        'max_size' => -1,
        'allowed_types' => [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'video' => ['mp4', 'mov', 'avi', 'mkv'],
            'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv']
        ]
    ]
];
