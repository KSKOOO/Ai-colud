<?php
/**
 * 多模态模型处理程序
 * 提供多模态模型列表和能力查询
 */

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../lib/AIProviderManager.php';

$currentUserId = $_SESSION['user']['id'] ?? null;

// 多模态模型定义（支持图像、视频、文件的多模态模型）
$multimodalModels = [
    // Ollama 多模态模型
    'ollama' => [
        ['id' => 'llava', 'name' => 'LLaVA', 'desc' => '图像理解', 'supports' => ['image', 'text']],
        ['id' => 'llava-phi3', 'name' => 'LLaVA-Phi3', 'desc' => '图像理解轻量版', 'supports' => ['image', 'text']],
        ['id' => 'bakllava', 'name' => 'BakLLaVA', 'desc' => '增强图像理解', 'supports' => ['image', 'text']],
        ['id' => 'moondream', 'name' => 'Moondream', 'desc' => '轻量图像模型', 'supports' => ['image', 'text']],
        ['id' => 'bunny-llama', 'name' => 'Bunny-Llama', 'desc' => '高效图像理解', 'supports' => ['image', 'text']],
    ],
    // 云端API多模态模型
    'openai' => [
        ['id' => 'gpt-4o', 'name' => 'GPT-4o', 'desc' => '全能多模态', 'supports' => ['image', 'text', 'file']],
        ['id' => 'gpt-4o-mini', 'name' => 'GPT-4o Mini', 'desc' => '轻量多模态', 'supports' => ['image', 'text', 'file']],
        ['id' => 'gpt-4-vision-preview', 'name' => 'GPT-4 Vision', 'desc' => '视觉增强', 'supports' => ['image', 'text']],
    ],
    'qwen' => [
        ['id' => 'qwen-vl-plus', 'name' => '通义千问VL', 'desc' => '视觉语言模型', 'supports' => ['image', 'text', 'video']],
        ['id' => 'qwen-vl-max', 'name' => '通义千问VL Max', 'desc' => '增强视觉语言', 'supports' => ['image', 'text', 'video']],
        ['id' => 'qwen-omni-turbo', 'name' => '通义千问Omni', 'desc' => '全模态理解', 'supports' => ['image', 'text', 'video', 'audio']],
    ],
    'gemini' => [
        ['id' => 'gemini-1.5-pro', 'name' => 'Gemini 1.5 Pro', 'desc' => '多模态Pro', 'supports' => ['image', 'text', 'video', 'file']],
        ['id' => 'gemini-1.5-flash', 'name' => 'Gemini 1.5 Flash', 'desc' => '快速多模态', 'supports' => ['image', 'text', 'video', 'file']],
        ['id' => 'gemini-pro-vision', 'name' => 'Gemini Vision', 'desc' => '视觉专用', 'supports' => ['image', 'text']],
    ],
    'hunyuan' => [
        ['id' => 'hunyuan-vision', 'name' => '混元Vision', 'desc' => '腾讯视觉模型', 'supports' => ['image', 'text']],
    ],
    'anthropic' => [
        ['id' => 'claude-3-opus', 'name' => 'Claude 3 Opus', 'desc' => '最强多模态', 'supports' => ['image', 'text', 'file']],
        ['id' => 'claude-3-sonnet', 'name' => 'Claude 3 Sonnet', 'desc' => '平衡多模态', 'supports' => ['image', 'text', 'file']],
        ['id' => 'claude-3-haiku', 'name' => 'Claude 3 Haiku', 'desc' => '快速多模态', 'supports' => ['image', 'text', 'file']],
    ],
    'zhipu' => [
        ['id' => 'glm-4v', 'name' => 'GLM-4V', 'desc' => '智谱视觉模型', 'supports' => ['image', 'text']],
    ]
];

// 场景与模型能力匹配
$scenarioModelMapping = [
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
];

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'getMultimodalModels':
            // 获取所有多模态模型
            $capability = $_GET['capability'] ?? null;
            
            $result = [];
            foreach ($multimodalModels as $provider => $models) {
                foreach ($models as $model) {
                    if ($capability && !in_array($capability, $model['supports'])) {
                        continue;
                    }
                    $model['provider'] = $provider;
                    $result[] = $model;
                }
            }
            
            echo json_encode([
                'success' => true,
                'models' => $result,
                'total' => count($result)
            ]);
            break;
            
        case 'getModelsByProvider':
            // 按提供商获取多模态模型
            $provider = $_GET['provider'] ?? '';
            
            if (!$provider || !isset($multimodalModels[$provider])) {
                echo json_encode(['success' => false, 'error' => '未找到该提供商的多模态模型']);
                exit;
            }
            
            $models = $multimodalModels[$provider];
            foreach ($models as &$model) {
                $model['provider'] = $provider;
            }
            
            echo json_encode([
                'success' => true,
                'models' => $models,
                'provider' => $provider
            ]);
            break;
            
        case 'getModelsForScenario':
            // 获取适合特定场景的多模态模型
            $scenario = $_GET['scenario'] ?? '';
            
            if (!$scenario || !isset($scenarioModelMapping[$scenario])) {
                // 返回所有多模态模型
                $allModels = [];
                foreach ($multimodalModels as $provider => $models) {
                    foreach ($models as $model) {
                        $model['provider'] = $provider;
                        $allModels[] = $model;
                    }
                }
                echo json_encode([
                    'success' => true,
                    'models' => $allModels,
                    'scenario' => $scenario,
                    'note' => '未找到特定场景配置，返回所有多模态模型'
                ]);
                exit;
            }
            
            $mapping = $scenarioModelMapping[$scenario];
            $recommended = [];
            
            foreach ($multimodalModels as $provider => $models) {
                if (in_array('all', $mapping['providers']) || in_array($provider, $mapping['providers'])) {
                    foreach ($models as $model) {
                        // 检查模型是否支持所需能力
                        $hasCapability = false;
                        foreach ($mapping['capabilities'] as $cap) {
                            if (in_array($cap, $model['supports'])) {
                                $hasCapability = true;
                                break;
                            }
                        }
                        if ($hasCapability) {
                            $model['provider'] = $provider;
                            $model['matched_capabilities'] = array_intersect($mapping['capabilities'], $model['supports']);
                            $recommended[] = $model;
                        }
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'models' => $recommended,
                'scenario' => $scenario,
                'required_capabilities' => $mapping['capabilities']
            ]);
            break;
            
        case 'getAvailableProviders':
            // 获取已配置的多模态提供商
            $manager = new AIProviderManager();
            $allProviders = $manager->getProviders(true);
            
            $availableProviders = [];
            foreach ($allProviders as $id => $provider) {
                $type = $provider['type'] ?? '';
                if (isset($multimodalModels[$type])) {
                    $availableProviders[] = [
                        'id' => $id,
                        'type' => $type,
                        'name' => $provider['name'] ?? $type,
                        'models' => $multimodalModels[$type]
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'providers' => $availableProviders
            ]);
            break;
            
        case 'getCapabilities':
            // 获取所有能力类型
            echo json_encode([
                'success' => true,
                'capabilities' => [
                    ['id' => 'text', 'name' => '文本', 'icon' => 'fa-font'],
                    ['id' => 'image', 'name' => '图像', 'icon' => 'fa-image'],
                    ['id' => 'video', 'name' => '视频', 'icon' => 'fa-video'],
                    ['id' => 'file', 'name' => '文档', 'icon' => 'fa-file'],
                    ['id' => 'audio', 'name' => '音频', 'icon' => 'fa-music']
                ]
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => '未知的操作类型']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
