<?php
/**
 * AI场景演示中心 - 全态模型版本
 * 支持文件上传和全态模型选择
 */
require_once __DIR__ . '/../lib/AIProviderManager.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/PermissionManager.php';

// 检查场景演示权限
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userId = $_SESSION['user']['id'] ?? 0;
$isAdmin = ($_SESSION['user']['role'] ?? '') === 'admin';

if (!$isAdmin && $userId) {
    $db = Database::getInstance();
    $permManager = new PermissionManager($db);
    if (!$permManager->hasModulePermission($userId, 'scenarios')) {
        header('Location: ?route=home&error=permission_denied');
        exit;
    }
}

$config = require __DIR__ . '/../config/config.php';

// 获取已配置的AI提供商
$manager = new AIProviderManager();
$configuredProviders = $manager->getProviders(true);

// 全态模型定义（支持图像、视频、文件的多模态模型）
$multimodalModelDefinitions = [
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

// 构建实际可用的多模态模型列表（只包含已配置的提供商）
$multimodalModels = [];
foreach ($configuredProviders as $providerId => $provider) {
    $providerType = $provider['type'] ?? '';
    
    // 检查是否是多模态提供商
    if (isset($multimodalModelDefinitions[$providerType])) {
        $availableModels = $multimodalModelDefinitions[$providerType];
        $configuredModels = $provider['models'] ?? [];
        
        // 过滤出已配置的模型
        $providerModels = [];
        foreach ($availableModels as $modelDef) {
            // 如果提供商配置了特定模型，则只显示那些模型
            // 否则显示所有该类型的多模态模型
            if (empty($configuredModels) || in_array($modelDef['id'], $configuredModels)) {
                $modelDef['provider_id'] = $providerId;
                $modelDef['provider_name'] = $provider['name'] ?? $providerType;
                $providerModels[] = $modelDef;
            }
        }
        
        if (!empty($providerModels)) {
            $multimodalModels[$providerType] = $providerModels;
        }
    }
}

// 如果没有配置任何多模态提供商，显示所有定义供用户参考
if (empty($multimodalModels)) {
    $multimodalModels = $multimodalModelDefinitions;
    $noProvidersConfigured = true;
}

// 场景与模型能力匹配 - 根据实际功能需求定义
$scenarioModelMapping = [
    // ========== AI员工（支持图像+文本）==========
    'ai-employee' => ['capabilities' => ['image', 'text'], 'providers' => ['ollama', 'openai', 'qwen', 'gemini', 'hunyuan', 'anthropic', 'zhipu']],
    'sales-assistant' => ['capabilities' => ['image', 'text'], 'providers' => ['ollama', 'openai', 'qwen', 'gemini', 'hunyuan', 'anthropic', 'zhipu']],
    
    // ========== 智能剪辑（必须视频理解能力）==========
    // 注意：只有真正支持视频分析的模型才能使用
    // 支持视频的模型：qwen-vl-plus, qwen-vl-max, qwen-omni-turbo, gemini-1.5-pro, gemini-1.5-flash
    // 不支持视频的模型（已屏蔽）：gpt-4o系列(仅图片)、gemini-pro-vision(仅图片)等
    'video-edit' => ['capabilities' => ['video'], 'providers' => ['qwen', 'gemini'], 'require_video' => true],
    'live-highlight' => ['capabilities' => ['video'], 'providers' => ['qwen', 'gemini'], 'require_video' => true],
    
    // ========== 文案生成（支持图像+文本）==========
    'douyin-copy' => ['capabilities' => ['image', 'text'], 'providers' => ['ollama', 'openai', 'qwen', 'gemini', 'hunyuan', 'anthropic', 'zhipu']],
    'xiaohongshu-copy' => ['capabilities' => ['image', 'text'], 'providers' => ['ollama', 'openai', 'qwen', 'gemini', 'hunyuan', 'anthropic', 'zhipu']],
    
    // ========== 电商零售 ==========
    'product-desc' => ['capabilities' => ['image'], 'providers' => ['ollama', 'openai', 'qwen', 'gemini', 'hunyuan', 'anthropic', 'zhipu']],
    'review-reply' => ['capabilities' => ['image', 'text'], 'providers' => ['ollama', 'openai', 'qwen', 'gemini', 'hunyuan', 'anthropic', 'zhipu']],
    
    // ========== 教育培训 ==========
    'course-material' => ['capabilities' => ['file'], 'providers' => ['openai', 'gemini', 'anthropic']],
    'essay-correct' => ['capabilities' => ['image', 'file'], 'providers' => ['ollama', 'openai', 'qwen', 'gemini', 'anthropic']],
    
    // ========== 医疗健康 ==========
    'medical-report' => ['capabilities' => ['image', 'file'], 'providers' => ['openai', 'qwen', 'gemini', 'anthropic']],
    'health-consult' => ['capabilities' => ['image', 'text'], 'providers' => ['ollama', 'openai', 'qwen', 'gemini', 'hunyuan', 'anthropic', 'zhipu']],
    
    // ========== 金融保险 ==========
    'financial-analysis' => ['capabilities' => ['file'], 'providers' => ['openai', 'gemini', 'anthropic']],
    'insurance-claim' => ['capabilities' => ['image', 'file'], 'providers' => ['openai', 'qwen', 'gemini', 'anthropic']],
    
    // ========== 自动办公 ==========
    'data-analysis' => ['capabilities' => ['file'], 'providers' => ['openai', 'gemini', 'anthropic']],
    'data-export' => ['capabilities' => ['file'], 'providers' => ['openai', 'gemini', 'anthropic']],
    'excel-assistant' => ['capabilities' => ['file'], 'providers' => ['openai', 'gemini', 'anthropic']],
    
    // ========== 法律法务 ==========
    'contract-review' => ['capabilities' => ['image', 'file'], 'providers' => ['openai', 'qwen', 'gemini', 'anthropic']],
    'legal-doc' => ['capabilities' => ['image', 'file'], 'providers' => ['openai', 'qwen', 'gemini', 'anthropic']],
];

// 真正支持视频分析的模型列表（严格限制）
$videoCapableModels = [
    // 通义千问 - 支持视频
    'qwen-vl-plus',
    'qwen-vl-max',
    'qwen-omni-turbo',
    // Gemini - 支持视频
    'gemini-1.5-pro',
    'gemini-1.5-flash',
];

// 获取推荐模型
function getRecommendedModels($scenarioType, $multimodalModels, $scenarioModelMapping, $videoCapableModels = []) {
    if (!isset($scenarioModelMapping[$scenarioType])) {
        return [];
    }

    $mapping = $scenarioModelMapping[$scenarioType];
    $recommended = [];
    $requireVideo = $mapping['require_video'] ?? false;

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

                // 如果场景需要视频能力，严格检查模型是否在白名单中
                if ($requireVideo && $hasCapability) {
                    $modelId = strtolower($model['id']);
                    $isVideoCapable = false;
                    foreach ($videoCapableModels as $vm) {
                        $vmLower = strtolower($vm);
                        // 完全匹配、前缀匹配或包含匹配
                        if ($modelId === $vmLower ||
                            strpos($modelId, $vmLower) === 0 ||
                            strpos($modelId, $vmLower) !== false) {
                            $isVideoCapable = true;
                            break;
                        }
                    }
                    if (!$isVideoCapable) {
                        // 该模型不支持真正的视频分析，跳过
                        continue;
                    }
                }

                if ($hasCapability) {
                    $model['provider'] = $provider;
                    $recommended[] = $model;
                }
            }
        }
    }

    return $recommended;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI场景演示中心 - <?php echo $config['app']['name'] ?? '巨神兵API辅助平台API辅助平台'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            color: #1a202c;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
        }
        
        /* 头部 */
        .header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 24px 30px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 28px;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .header p {
            color: #64748b;
            font-size: 16px;
        }
        
        /* 控制面板 */
        .control-panel {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
        }
        
        .control-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
            min-width: 200px;
        }
        
        .control-group label {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
        }
        
        .control-group select {
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .control-group select:hover, .control-group select:focus {
            border-color: #667eea;
            outline: none;
        }
        
        /* 场景卡片网格 */
        .scenarios-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .scenario-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .scenario-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.12);
        }
        
        .scenario-card.disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .scenario-icon {
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .scenario-icon.ai-employee { background: #4c51bf; }
        .scenario-icon.video-edit { background: #e53e3e; }
        .scenario-icon.copywriting { background: #3182ce; }
        .scenario-icon.ecommerce { background: #38a169; }
        .scenario-icon.education { background: #d69e2e; }
        .scenario-icon.healthcare { background: #319795; }
        .scenario-icon.finance { background: #805ad5; }
        .scenario-icon.legal { background: #dd6b20; }
        .scenario-icon.office { background: #4c51bf; }
        
        .scenario-content {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .scenario-category {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .category-ai { background: #e0e7ff; color: #4c51bf; }
        .category-video { background: #fce7f3; color: #be185d; }
        .category-copy { background: #dbeafe; color: #1e40af; }
        .category-commerce { background: #d1fae5; color: #047857; }
        .category-edu { background: #fef3c7; color: #b45309; }
        .category-health { background: #cffafe; color: #0e7490; }
        .category-finance { background: #f3e8ff; color: #6b21a8; }
        .category-legal { background: #ffe4e6; color: #9f1239; }
        .category-office { background: #e0e7ff; color: #4c51bf; }
        
        .scenario-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #1a202c;
        }
        
        .scenario-desc {
            font-size: 13px;
            color: #64748b;
            line-height: 1.5;
            margin-bottom: 12px;
            flex: 1;
        }
        
        .scenario-features {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        
        .feature-tag {
            padding: 3px 8px;
            background: #f3f4f6;
            border-radius: 4px;
            font-size: 11px;
            color: #4b5563;
        }
        
        /* 演示模态框 */
        .demo-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .demo-modal.show {
            display: flex;
        }
        
        .demo-content {
            background: white;
            border-radius: 24px;
            width: 95%;
            max-width: 1400px;
            max-height: 95vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
        
        .demo-header {
            padding: 24px 30px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .demo-header h2 {
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .demo-close {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            transition: all 0.2s;
        }
        
        .demo-close:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .demo-body {
            flex: 1;
            overflow-y: auto;
            padding: 30px;
        }
        
        /* 模型选择区域 */
        .model-select-section {
            background: #f9fafb;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .model-select-section h3 {
            font-size: 16px;
            margin-bottom: 16px;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .model-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
        }
        
        .model-option {
            padding: 12px 16px;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .model-option:hover {
            border-color: #667eea;
        }
        
        .model-option.selected {
            border-color: #667eea;
            background: #eff6ff;
        }
        
        .model-option.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .model-option-name {
            font-weight: 600;
            color: #1a202c;
            font-size: 14px;
        }
        
        .model-option-desc {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
        
        .model-capabilities {
            display: flex;
            gap: 6px;
            margin-top: 8px;
        }
        
        .capability-tag {
            padding: 2px 8px;
            background: #e0e7ff;
            color: #4c51bf;
            border-radius: 12px;
            font-size: 10px;
        }
        
        /* 输入区域 */
        .input-section {
            background: #f9fafb;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .input-section h3 {
            font-size: 16px;
            margin-bottom: 16px;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* 文件上传区域 */
        .file-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 32px;
            text-align: center;
            background: white;
            transition: all 0.2s;
            cursor: pointer;
            margin-bottom: 16px;
        }
        
        .file-upload-area:hover {
            border-color: #667eea;
            background: #f5f7ff;
        }
        
        .file-upload-area.has-file {
            border-color: #10b981;
            background: #ecfdf5;
        }
        
        .file-upload-area i {
            font-size: 48px;
            color: #9ca3af;
            margin-bottom: 12px;
        }
        
        .file-upload-area.has-file i {
            color: #10b981;
        }
        
        .file-upload-area p {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .file-upload-area .file-name {
            color: #1f2937;
            font-weight: 600;
            font-size: 14px;
            word-break: break-all;
        }
        
        .file-upload-area input[type="file"] {
            display: none;
        }
        
        .file-types {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 8px;
        }
        
        .input-section textarea {
            width: 100%;
            min-height: 120px;
            padding: 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            resize: vertical;
            font-family: inherit;
        }
        
        .input-section textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .input-section .hint {
            margin-top: 8px;
            font-size: 13px;
            color: #6b7280;
        }
        
        .btn-generate {
            background: #4c51bf;
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            margin-top: 16px;
        }
        
        .btn-generate:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 81, 191, 0.3);
        }
        
        .btn-generate:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        /* 输出区域 */
        .output-section {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            padding: 24px;
            min-height: 200px;
        }
        
        .output-section h3 {
            font-size: 16px;
            margin-bottom: 16px;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .output-content {
            line-height: 1.8;
            color: #1f2937;
            white-space: pre-wrap;
        }
        
        .output-content.loading {
            text-align: center;
            color: #9ca3af;
            padding: 40px;
        }
        
        /* 视频预览区域 */
        .video-preview-section {
            margin-top: 20px;
            background: #f9fafb;
            border-radius: 16px;
            padding: 20px;
        }
        
        .video-preview-section h4 {
            font-size: 16px;
            margin-bottom: 16px;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .video-player-wrapper {
            position: relative;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .video-player-wrapper video {
            width: 100%;
            height: auto;
            max-height: 500px;
            display: block;
        }
        
        .video-info-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            background: #1f2937;
            color: white;
            font-size: 13px;
        }
        
        .video-info-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .video-info-item i {
            color: #667eea;
        }
        
        /* 视频文件预览（上传后） */
        .video-file-preview {
            margin-top: 12px;
            padding: 16px;
            background: #f3f4f6;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .video-file-preview i {
            font-size: 40px;
            color: #667eea;
        }
        
        .video-file-info {
            flex: 1;
        }
        
        .video-file-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .video-file-meta {
            font-size: 13px;
            color: #6b7280;
        }
        
        .video-preview-btn {
            padding: 8px 16px;
            background: #4c51bf;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        
        .video-preview-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        /* 返回按钮 */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.2s;
            margin-bottom: 20px;
        }
        
        .back-btn:hover {
            transform: translateX(-4px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        /* 行业筛选标签 */
        .industry-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
        }
        
        .filter-tag {
            padding: 8px 16px;
            background: rgba(255,255,255,0.9);
            border: 2px solid transparent;
            border-radius: 25px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .filter-tag:hover {
            border-color: #667eea;
            color: #667eea;
        }
        
        .filter-tag.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }
        
        .capability-filter {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }
        
        .capability-filter label {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: white;
            border-radius: 20px;
            font-size: 13px;
            cursor: pointer;
            border: 1px solid #e5e7eb;
        }
        
        .capability-filter label:hover {
            border-color: #667eea;
        }
        
        .capability-filter input:checked + span {
            color: #667eea;
            font-weight: 600;
        }

        /* 移动端响应式设计 */
        @media screen and (max-width: 768px) {
            body {
                padding: 10px;
            }

            .container {
                padding: 16px;
            }

            .header {
                flex-direction: column;
                gap: 12px;
                padding-bottom: 16px;
                margin-bottom: 20px;
            }

            .header h1 {
                font-size: 22px;
                flex-wrap: wrap;
            }

            .header-actions {
                width: 100%;
                justify-content: center;
            }

            .filter-bar {
                flex-direction: column;
                gap: 12px;
                align-items: stretch;
            }

            .filter-group {
                justify-content: center;
            }

            .scenarios-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .scenario-card {
                padding: 20px;
            }

            .scenario-icon {
                width: 48px;
                height: 48px;
                font-size: 22px;
            }

            .scenario-card h3 {
                font-size: 16px;
            }

            /* 模态框移动端适配 */
            .modal-content {
                width: 95%;
                margin: 20px auto;
                max-height: 90vh;
            }

            .modal-header {
                padding: 16px 20px;
            }

            .modal-body {
                padding: 20px;
            }

            .demo-layout {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .model-dropdown-wrapper {
                flex-direction: column;
            }

            .model-select-dropdown {
                width: 100%;
            }

            .file-upload-area {
                padding: 24px 16px;
            }

            .input-area textarea {
                min-height: 100px;
            }
        }

        @media screen and (max-width: 480px) {
            .container {
                padding: 12px;
            }

            .header h1 {
                font-size: 18px;
            }

            .header-actions .btn {
                padding: 8px 12px;
                font-size: 13px;
            }

            .filter-btn {
                padding: 6px 10px;
                font-size: 12px;
            }

            .scenario-card {
                padding: 16px;
            }

            .modal-title {
                font-size: 16px;
            }

            .modal-close {
                width: 32px;
                height: 32px;
            }

            .input-area textarea {
                font-size: 16px; /* 防止iOS缩放 */
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="?route=home" class="back-btn">
            <i class="fas fa-arrow-left"></i> 返回首页
        </a>
        
        <div class="header">
            <h1><i class="fas fa-magic"></i> AI场景演示中心</h1>
            <p>基于全态模型，支持图像、视频、文档的智能分析处理</p>
        </div>
        
        <?php if (isset($noProvidersConfigured) && $noProvidersConfigured): ?>
        <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
            <i class="fas fa-exclamation-triangle" style="color: #f59e0b; font-size: 20px;"></i>
            <div>
                <div style="font-weight: 600; color: #92400e;">未配置多模态AI提供商</div>
                <div style="font-size: 14px; color: #a16207;">请先前往 <a href="?route=admin&tab=ai_providers" style="color: #4c51bf; text-decoration: underline;">AI提供商管理</a> 配置您的多模态模型API密钥</div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 控制面板 -->
        <div class="control-panel">
            <div class="control-group" style="flex: 2;">
                <label><i class="fas fa-filter"></i> 行业筛选</label>
                <select id="industrySelect">
                    <option value="all">全部行业</option>
                    <option value="general">通用场景</option>
                    <option value="ecommerce">电商零售</option>
                    <option value="education">教育培训</option>
                    <option value="healthcare">医疗健康</option>
                    <option value="finance">金融保险</option>
                    <option value="office">自动办公</option>
                    <option value="legal">法律法务</option>
                </select>
            </div>
            
            <div class="control-group" style="flex: 2;">
                <label><i class="fas fa-bolt"></i> 能力筛选</label>
                <div class="capability-filter">
                    <label>
                        <input type="checkbox" id="filterImage" checked>
                        <span><i class="fas fa-image"></i> 图像</span>
                    </label>
                    <label>
                        <input type="checkbox" id="filterVideo" checked>
                        <span><i class="fas fa-video"></i> 视频</span>
                    </label>
                    <label>
                        <input type="checkbox" id="filterFile" checked>
                        <span><i class="fas fa-file"></i> 文档</span>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- 场景卡片 -->
        <div class="scenarios-grid" id="scenariosGrid">
            <!-- 场景卡片将通过JS动态生成 -->
        </div>
    </div>
    
    <!-- 演示模态框 -->
    <div class="demo-modal" id="demoModal">
        <div class="demo-content">
            <div class="demo-header">
                <h2><i class="fas fa-magic"></i> <span id="demoTitle">场景演示</span></h2>
                <button class="demo-close" onclick="closeDemo()">&times;</button>
            </div>
            <div class="demo-body">
                <!-- 模型选择 -->
                <div class="model-select-section">
                    <h3><i class="fas fa-brain"></i> 选择全态模型</h3>
                    <div class="model-dropdown-wrapper" style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <select id="providerSelect" class="model-select-dropdown" style="flex: 1; min-width: 150px; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 14px; background: white; cursor: pointer;">
                            <option value="">选择AI提供商...</option>
                        </select>
                        <select id="modelSelect" class="model-select-dropdown" style="flex: 2; min-width: 200px; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 14px; background: white; cursor: pointer;">
                            <option value="">选择模型...</option>
                        </select>
                    </div>
                    <div id="modelCapabilities" style="margin-top: 12px; display: flex; gap: 8px; flex-wrap: wrap;"></div>
                </div>
                
                <!-- 输入区域 -->
                <div class="input-section">
                    <h3><i class="fas fa-edit"></i> 输入信息</h3>
                    
                    <!-- 文件上传区域 -->
                    <div class="file-upload-area" id="fileUploadArea" onclick="document.getElementById('demoFile').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>点击或拖拽上传文件</p>
                        <div class="file-name" id="fileName" style="display: none;"></div>
                        <div class="file-types" id="fileTypes">支持：图片(JPG/PNG)、视频(MP4)、PDF、Word、Excel</div>
                        <input type="file" id="demoFile" onchange="handleFileSelect(this)">
                    </div>
                    
                    <textarea id="demoInput" placeholder="请输入相关信息..."></textarea>
                    <div class="hint" id="demoHint">提示：输入越详细，生成效果越好</div>
                    <button class="btn-generate" onclick="generateContent()">
                        <i class="fas fa-wand-magic-sparkles"></i> 开始生成
                    </button>
                </div>
                
                <!-- 输出区域 -->
                <div class="output-section">
                    <h3><i class="fas fa-scroll"></i> 生成结果</h3>
                    <div class="output-content" id="demoOutput">
                        点击"开始生成"按钮查看AI生成的内容...
                    </div>
                    <!-- 视频预览区域 -->
                    <div class="video-preview-section" id="videoPreviewSection" style="display: none;">
                        <h4><i class="fas fa-play-circle"></i> 视频预览</h4>
                        <div class="video-player-wrapper">
                            <video id="videoPlayer" controls playsinline>
                                您的浏览器不支持视频播放
                            </video>
                            <div class="video-info-bar">
                                <div class="video-info-item">
                                    <i class="fas fa-film"></i>
                                    <span id="videoDuration">--:--</span>
                                </div>
                                <div class="video-info-item">
                                    <i class="fas fa-download"></i>
                                    <a id="videoDownloadLink" href="#" download style="color: white; text-decoration: none;">下载视频</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // 多模态模型数据
        const multimodalModels = <?php echo json_encode($multimodalModels); ?>;
        const scenarioModelMapping = <?php echo json_encode($scenarioModelMapping); ?>;
        
        // 场景配置
        const scenarioConfigs = {
            'ai-employee': {
                title: '智能客服专员',
                hint: '请输入客户咨询的问题或场景描述',
                placeholder: '客户问题：我昨天下的订单什么时候能发货？\n订单号：DD20240315001\n商品：iPhone 15 Pro',
                category: 'category-ai',
                icon: 'ai-employee',
                industry: 'general',
                supports: ['text'], // 纯文本模块，可使用所有文本模型
                fileTypes: '', // 不需要文件上传
                fileDesc: ''
            },
            'sales-assistant': {
                title: '销售助手',
                hint: '请输入客户信息和需求',
                placeholder: '客户：某互联网公司CTO\n需求：寻找企业级AI解决方案\n预算：50-100万',
                category: 'category-ai',
                icon: 'ai-employee',
                industry: 'general',
                supports: ['text'], // 纯文本模块，可使用所有文本模型
                fileTypes: '', // 不需要文件上传
                fileDesc: ''
            },
            'video-edit': {
                title: '短视频智能剪辑',
                hint: '【全态模型可直接查看视频】上传视频文件，AI将自动分析画面内容并剪辑精彩片段',
                placeholder: '请描述你的剪辑需求，例如：\n• 时长：剪辑成30秒的短视频\n• 重点：保留产品展示和讲解部分\n• 风格：快节奏、配背景音乐\n\n使用qwen-omni、qwen-vl或gemini等全态模型，可以直接分析视频画面内容',
                category: 'category-video',
                icon: 'video-edit',
                industry: 'general',
                supports: ['video', 'text'],
                fileTypes: 'video/*',
                fileDesc: '支持：MP4、MOV、AVI、MKV视频文件（全态模型可直接分析视频内容）'
            },
            'live-highlight': {
                title: '直播高光时刻',
                hint: '【全态模型可直接查看视频】上传直播录像，AI自动提取精彩瞬间和高光时刻',
                placeholder: '请描述直播内容和需求，例如：\n• 直播主题：产品发布会\n• 剪辑时长：提取3分钟精华\n• 重点关注：互动问答、产品演示\n\n使用qwen-omni、qwen-vl或gemini等全态模型，可以直接观看并分析直播录像',
                category: 'category-video',
                icon: 'video-edit',
                industry: 'general',
                supports: ['video', 'text'],
                fileTypes: 'video/*',
                fileDesc: '支持：MP4、MOV、AVI视频文件（全态模型可直接分析视频内容）'
            },
            'douyin-copy': {
                title: '抖音爆款文案',
                hint: '请输入视频主题和风格要求',
                placeholder: '主题：分享学习AI的心得\n风格：励志、干货\n目标：获得高点赞和评论',
                category: 'category-copy',
                icon: 'copywriting',
                industry: 'general',
                supports: ['text'], // 纯文本模块
                fileTypes: '',
                fileDesc: ''
            },
            'xiaohongshu-copy': {
                title: '小红书种草笔记',
                hint: '请输入产品名称和使用体验',
                placeholder: '产品：某某品牌面膜\n使用感受：补水效果好、膜布服帖\n适合人群：干皮、敏感肌',
                category: 'category-copy',
                icon: 'copywriting',
                industry: 'general',
                supports: ['text'], // 纯文本模块
                fileTypes: '',
                fileDesc: ''
            },
            'product-desc': {
                title: '商品详情页生成',
                hint: '请上传商品图片，AI将自动生成详情描述',
                placeholder: '商品：智能扫地机器人\n特点：自动导航、大吸力、静音\n价格：2999元',
                category: 'category-commerce',
                icon: 'ecommerce',
                industry: 'ecommerce',
                supports: ['image'],
                fileTypes: 'image/*',
                fileDesc: '支持：JPG、PNG、WEBP图片'
            },
            'review-reply': {
                title: '评价智能回复',
                hint: '请输入客户评价内容，可上传截图',
                placeholder: '评价：东西不错，就是物流有点慢，等了3天才到。\n评分：4星',
                category: 'category-commerce',
                icon: 'ecommerce',
                industry: 'ecommerce',
                supports: ['image', 'text'],
                fileTypes: '.jpg,.jpeg,.png,.webp',
                fileDesc: '支持：图片'
            },
            'course-material': {
                title: '课件内容生成',
                hint: '请上传课件文档或输入课程主题',
                placeholder: '课程：Python入门编程\n课时：8课时\n目标：零基础学会编程',
                category: 'category-edu',
                icon: 'education',
                industry: 'education',
                supports: ['file', 'text'],
                fileTypes: '.pdf,.doc,.docx,.ppt,.pptx,.txt',
                fileDesc: '支持：PDF、Word、PPT、TXT文档'
            },
            'essay-correct': {
                title: '作文智能批改',
                hint: '请上传作文图片或文档',
                placeholder: '作文题目：我的理想\n字数要求：不少于500字',
                category: 'category-edu',
                icon: 'education',
                industry: 'education',
                supports: ['image', 'file', 'text'],
                fileTypes: 'image/*,.pdf,.doc,.docx,.txt',
                fileDesc: '支持：图片、PDF、Word、TXT文档'
            },
            'medical-report': {
                title: '病历报告生成',
                hint: '请上传检查报告图片或文档',
                placeholder: '患者：张三，男，45岁\n主诉：头痛、发热3天\n检查：血压140/90，体温38.5℃',
                category: 'category-health',
                icon: 'healthcare',
                industry: 'healthcare',
                supports: ['image', 'file', 'text'],
                fileTypes: 'image/*,.pdf,.doc,.docx,.dcm',
                fileDesc: '支持：图片、PDF、Word、DICOM医学影像'
            },
            'health-consult': {
                title: '健康咨询助手',
                hint: '请输入健康相关问题，可上传检查单图片',
                placeholder: '问题：最近总是失眠，有什么改善方法吗？',
                category: 'category-health',
                icon: 'healthcare',
                industry: 'healthcare',
                supports: ['image', 'text'],
                fileTypes: '.jpg,.jpeg,.png,.webp',
                fileDesc: '支持：图片'
            },
            'financial-analysis': {
                title: '财报智能分析',
                hint: '请上传财报PDF或Excel文件',
                placeholder: '营收：1亿元（同比+20%）\n净利润：2000万（同比+15%）\n现金流：正值',
                category: 'category-finance',
                icon: 'finance',
                industry: 'finance',
                supports: ['file', 'text'],
                fileTypes: '.pdf,.xlsx,.xls,.csv,.txt',
                fileDesc: '支持：PDF、Excel、CSV、TXT文件'
            },
            'insurance-claim': {
                title: '理赔智能审核',
                hint: '请上传理赔材料图片或文档',
                placeholder: '案件：车险理赔\n出险时间：2024-03-15\n事故类型：追尾',
                category: 'category-finance',
                icon: 'finance',
                industry: 'finance',
                supports: ['image', 'file', 'text'],
                fileTypes: 'image/*,.pdf,.doc,.docx',
                fileDesc: '支持：图片、PDF、Word文档'
            },
            'data-analysis': {
                title: '智能数据分析',
                hint: '请上传数据文件',
                placeholder: '示例数据：\n月份,销售额,成本\n1月,100万,80万\n2月,120万,85万\n3月,150万,90万',
                category: 'category-office',
                icon: 'office',
                industry: 'office',
                supports: ['file', 'text'],
                fileTypes: '.csv,.xlsx,.xls,.json,.txt',
                fileDesc: '支持：CSV、Excel、JSON、TXT文件'
            },
            'data-export': {
                title: '数据导入导出',
                hint: '请上传需要转换的数据文件',
                placeholder: '请描述你的需求，例如：\n1. 将CSV转换为Excel格式\n2. 清洗数据，去除重复项\n3. 按指定字段排序',
                category: 'category-office',
                icon: 'office',
                industry: 'office',
                supports: ['file', 'text'],
                fileTypes: '.csv,.xlsx,.xls,.json,.xml,.txt',
                fileDesc: '支持：CSV、Excel、JSON、XML、TXT文件'
            },
            'excel-assistant': {
                title: 'Excel智能助手',
                hint: '请上传Excel文件',
                placeholder: '需求示例：\n- 根据A列和B列计算C列的和\n- 创建按月份汇总的数据透视表\n- 根据销售数据生成柱状图',
                category: 'category-office',
                icon: 'office',
                industry: 'office',
                supports: ['file', 'text'],
                fileTypes: '.xlsx,.xls,.csv',
                fileDesc: '支持：Excel、CSV文件'
            },
            'contract-review': {
                title: '合同智能审查',
                hint: '请上传合同文档或图片',
                placeholder: '合同类型：采购合同\n金额：100万元\n付款方式：分期付款',
                category: 'category-legal',
                icon: 'legal',
                industry: 'legal',
                supports: ['image', 'file', 'text'],
                fileTypes: 'image/*,.pdf,.doc,.docx,.txt',
                fileDesc: '支持：图片、PDF、Word、TXT'
            },
            'legal-doc': {
                title: '法律文书生成',
                hint: '请上传相关材料图片或文档',
                placeholder: '案件：借款纠纷\n原告：李某\n被告：王某\n金额：10万元',
                category: 'category-legal',
                icon: 'legal',
                industry: 'legal',
                supports: ['image', 'file', 'text'],
                fileTypes: 'image/*,.pdf,.doc,.docx,.txt',
                fileDesc: '支持：图片、PDF、Word、TXT'
            }
        };
        
        let currentScenario = null;
        let currentFile = null;
        let selectedModel = null;
        let configuredProvidersList = []; // 存储提供商列表
        
        // 解析模型名称，生成用途说明
        function getModelRemark(modelName) {
            if (!modelName) return '';
            const lowerName = modelName.toLowerCase();
            
            if (lowerName.includes('vl') || lowerName.includes('vision')) {
                return '【图文理解】';
            } else if (lowerName.includes('omni')) {
                return '【全模态】';
            } else if (lowerName.includes('ocr')) {
                return '【文字识别】';
            } else if (lowerName.includes('instruct') || lowerName.includes('chat')) {
                return '【对话问答】';
            } else if (lowerName.includes('turbo')) {
                return '【快速响应】';
            } else if (lowerName.includes('max') || lowerName.includes('pro')) {
                return '【高性能】';
            } else if (lowerName.includes('flash')) {
                return '【轻量快速】';
            } else if (lowerName.includes('audio') || lowerName.includes('tts')) {
                return '【音频处理】';
            } else if (lowerName.includes('embedding')) {
                return '【向量嵌入】';
            } else if (lowerName.includes('code')) {
                return '【代码生成】';
            } else if (lowerName.includes('translate') || lowerName.includes('livetranslate')) {
                return '【翻译】';
            } else if (lowerName.includes('s2s')) {
                return '【语音对话】';
            } else {
                return '【通用】';
            }
        }
        
        // 提供商选择事件处理
        function handleProviderChange() {
            const providerSelect = document.getElementById('providerSelect');
            const modelSelect = document.getElementById('modelSelect');
            const capabilitiesDiv = document.getElementById('modelCapabilities');
            const providerId = providerSelect.value;
            const selectedOption = providerSelect.options[providerSelect.selectedIndex];
            
            modelSelect.innerHTML = '<option value="">选择模型...</option>';
            capabilitiesDiv.innerHTML = '';
            selectedModel = null;
            
            if (providerId && selectedOption.dataset.models) {
                const models = JSON.parse(selectedOption.dataset.models);
                const providerType = selectedOption.dataset.type;
                const needsMultimodal = selectedOption.dataset.needsMultimodal === 'true';
                
                // 检查是否是视频剪辑场景
                const isVideoEdit = selectedOption.dataset.isVideoEdit === 'true';
                
                models.forEach(modelName => {
                    const caps = getModelCapabilities(modelName);
                    const isMultimodal = caps.includes('image') || caps.includes('video') || caps.includes('file');

                    // 检查是否是图像生成专用模型
                    const isImageGen = isImageGenerationModel(modelName);

                    // 视频剪辑场景：严格只使用真正支持视频分析的模型
                    if (isVideoEdit) {
                        // 严格检查：只有白名单中的模型才能用于视频分析
                        if (isVideoCapableModel(modelName) && !isImageGen) {
                            const option = document.createElement('option');
                            option.value = modelName;
                            option.textContent = modelName + ' ' + getModelRemark(modelName);
                            option.dataset.provider = providerId;
                            option.dataset.providerType = providerType;
                            option.dataset.supports = JSON.stringify(caps);
                            modelSelect.appendChild(option);
                        }
                    } else if (needsMultimodal) {
                        // 需要多模态：只显示多模态模型，排除图像生成模型
                        if (isMultimodal && !isImageGen) {
                            const option = document.createElement('option');
                            option.value = modelName;
                            option.textContent = modelName + ' ' + getModelRemark(modelName);
                            option.dataset.provider = providerId;
                            option.dataset.providerType = providerType;
                            option.dataset.supports = JSON.stringify(caps);
                            modelSelect.appendChild(option);
                        }
                    } else {
                        // 纯文本模块：只显示支持文本的模型，排除图像生成模型
                        if (!isImageGen && caps.includes('text')) {
                            const option = document.createElement('option');
                            option.value = modelName;
                            option.textContent = modelName + ' ' + getModelRemark(modelName);
                            option.dataset.provider = providerId;
                            option.dataset.providerType = providerType;
                            option.dataset.supports = JSON.stringify(caps);
                            modelSelect.appendChild(option);
                        }
                    }
                });
                
                // 如果没有可用的模型，显示提示
                if (modelSelect.options.length === 1) {
                    const option = document.createElement('option');
                    option.value = "";
                    if (isVideoEdit) {
                        option.textContent = "该提供商没有可用的全态模型";
                    } else {
                        option.textContent = needsMultimodal ? "该提供商没有多模态模型" : "该提供商没有可用模型";
                    }
                    option.disabled = true;
                    modelSelect.appendChild(option);
                }
            }
        }
        
        // 模型选择事件处理
        function handleModelChange() {
            const modelSelect = document.getElementById('modelSelect');
            const capabilitiesDiv = document.getElementById('modelCapabilities');
            const selectedOption = modelSelect.options[modelSelect.selectedIndex];
            
            capabilitiesDiv.innerHTML = '';
            
            if (modelSelect.value && selectedOption.dataset.supports) {
                const supports = JSON.parse(selectedOption.dataset.supports);
                const providerId = selectedOption.dataset.provider;
                const providerType = selectedOption.dataset.providerType;
                
                selectedModel = {
                    provider: providerType || providerId,
                    provider_id: providerId,
                    id: modelSelect.value
                };
                
                // 显示模型能力标签
                supports.forEach(cap => {
                    const tag = document.createElement('span');
                    tag.className = 'capability-tag';
                    tag.innerHTML = `<i class="fas ${getCapabilityIcon(cap)}"></i> ${getCapabilityName(cap)}`;
                    capabilitiesDiv.appendChild(tag);
                });
                
                // 显示模型描述（根据能力推断）
                const desc = document.createElement('span');
                desc.style.cssText = 'color: #6b7280; font-size: 12px; margin-left: auto;';
                const capNames = supports.map(c => getCapabilityName(c)).join('、');
                desc.textContent = `支持：${capNames}`;
                capabilitiesDiv.appendChild(desc);
            } else {
                selectedModel = null;
            }
        }
        
        // 渲染场景卡片
        function renderScenarios() {
            const container = document.getElementById('scenariosGrid');
            const industry = document.getElementById('industrySelect').value;
            const filterImage = document.getElementById('filterImage').checked;
            const filterVideo = document.getElementById('filterVideo').checked;
            const filterFile = document.getElementById('filterFile').checked;
            
            let html = '';
            
            Object.entries(scenarioConfigs).forEach(([type, config]) => {
                // 行业筛选
                if (industry !== 'all' && config.industry !== industry) {
                    return;
                }
                
                // 能力筛选
                const hasImage = config.supports.includes('image');
                const hasVideo = config.supports.includes('video');
                const hasFile = config.supports.includes('file');
                
                if ((hasImage && !filterImage) && (hasVideo && !filterVideo) && (hasFile && !filterFile)) {
                    return;
                }
                
                html += `
                    <div class="scenario-card" data-industry="${config.industry}" data-type="${type}" onclick="openDemo('${type}')">
                        <div class="scenario-icon ${config.icon}">
                            <i class="fas ${getIconForScenario(type)}"></i>
                        </div>
                        <div class="scenario-content">
                            <span class="scenario-category ${config.category}">${getCategoryName(config.category)}</span>
                            <h3 class="scenario-title">${config.title}</h3>
                            <p class="scenario-desc">${config.hint}</p>
                            <div class="scenario-features">
                                ${config.supports.map(s => `<span class="feature-tag"><i class="fas ${getCapabilityIcon(s)}"></i> ${getCapabilityName(s)}</span>`).join('')}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html || '<div style="text-align: center; padding: 60px; color: #6b7280;">没有找到匹配的场景</div>';
        }
        
        function getIconForScenario(type) {
            const icons = {
                'ai-employee': 'fa-user-tie',
                'sales-assistant': 'fa-phone-alt',
                'video-edit': 'fa-video',
                'live-highlight': 'fa-broadcast-tower',
                'douyin-copy': 'fa-music',
                'xiaohongshu-copy': 'fa-book-open',
                'product-desc': 'fa-shopping-bag',
                'review-reply': 'fa-comments',
                'course-material': 'fa-graduation-cap',
                'essay-correct': 'fa-spell-check',
                'medical-report': 'fa-user-md',
                'health-consult': 'fa-heartbeat',
                'financial-analysis': 'fa-chart-line',
                'insurance-claim': 'fa-file-invoice-dollar',
                'data-analysis': 'fa-chart-bar',
                'data-export': 'fa-file-export',
                'excel-assistant': 'fa-table',
                'contract-review': 'fa-file-contract',
                'legal-doc': 'fa-gavel'
            };
            return icons[type] || 'fa-magic';
        }
        
        function getCategoryName(category) {
            const names = {
                'category-ai': 'AI员工',
                'category-video': '智能剪辑',
                'category-copy': '文案生成',
                'category-commerce': '电商零售',
                'category-edu': '教育培训',
                'category-health': '医疗健康',
                'category-finance': '金融保险',
                'category-legal': '法律法务',
                'category-office': '自动办公'
            };
            return names[category] || '其他';
        }
        
        function getCapabilityIcon(cap) {
            const icons = {
                'text': 'fa-font',
                'image': 'fa-image',
                'video': 'fa-video',
                'file': 'fa-file'
            };
            return icons[cap] || 'fa-circle';
        }
        
        function getCapabilityName(cap) {
            const names = {
                'text': '文本',
                'image': '图像',
                'video': '视频',
                'file': '文档'
            };
            return names[cap] || cap;
        }
        
        // 打开演示
        function openDemo(type) {
            currentScenario = type;
            const config = scenarioConfigs[type];
            
            document.getElementById('demoTitle').textContent = config.title;
            document.getElementById('demoInput').value = ''; // 清空输入框
            document.getElementById('demoInput').placeholder = config.placeholder;
            document.getElementById('demoHint').textContent = '提示：' + config.hint;
            document.getElementById('demoOutput').innerHTML = '点击"开始生成"按钮查看AI生成的内容...';
            
            // 隐藏视频预览
            document.getElementById('videoPreviewSection').style.display = 'none';
            const videoPlayer = document.getElementById('videoPlayer');
            if (videoPlayer) {
                videoPlayer.pause();
                videoPlayer.src = '';
            }
            
            // 重置文件
            currentFile = null;
            document.getElementById('fileName').style.display = 'none';
            document.getElementById('fileUploadArea').classList.remove('has-file');
            document.getElementById('demoFile').value = '';
            
            // 设置文件上传限制 - 纯文本模块隐藏文件上传
            const needsFileUpload = config.supports.includes('image') || config.supports.includes('video') || config.supports.includes('file');
            if (needsFileUpload && config.fileTypes) {
                document.getElementById('demoFile').accept = config.fileTypes;
                document.getElementById('fileTypes').textContent = config.fileDesc || '支持：' + config.fileTypes;
                document.getElementById('fileUploadArea').style.display = 'block';
            } else {
                // 纯文本模块隐藏文件上传区域
                document.getElementById('fileUploadArea').style.display = 'none';
            }
            
            // 渲染模型选择 - 根据模块需求
            renderModelSelection(config.supports, config.title);
            
            document.getElementById('demoModal').classList.add('show');
        }
        
        // 配置的多模态模型能力映射
        const modelCapabilities = {
            // Ollama 视觉模型
            'llava': ['image', 'text'],
            'llava-phi3': ['image', 'text'],
            'bakllava': ['image', 'text'],
            'moondream': ['image', 'text'],
            'bunny-llama': ['image', 'text'],
            // OpenAI 多模态模型
            'gpt-4o': ['image', 'text', 'file'],
            'gpt-4o-mini': ['image', 'text', 'file'],
            'gpt-4-vision-preview': ['image', 'text'],
            'gpt-4-turbo': ['image', 'text', 'file'],
            'gpt-4': ['image', 'text', 'file'],
            // 通义千问多模态模型
            'qwen-vl-plus': ['image', 'text', 'video'],
            'qwen-vl-max': ['image', 'text', 'video'],
            'qwen-omni-turbo': ['image', 'text', 'video', 'audio'],
            'qwen-turbo': ['text'],
            'qwen-plus': ['text'],
            'qwen-max': ['text'],
            // Gemini 多模态模型
            'gemini-1.5-pro': ['image', 'text', 'video', 'file'],
            'gemini-1.5-flash': ['image', 'text', 'video', 'file'],
            'gemini-pro-vision': ['image', 'text'],
            'gemini-pro': ['text'],
            // 混元视觉模型
            'hunyuan-vision': ['image', 'text'],
            'hunyuan-pro': ['text'],
            'hunyuan-standard': ['text'],
            'hunyuan-lite': ['text'],
            // Claude 多模态模型
            'claude-3-opus': ['image', 'text', 'file'],
            'claude-3-sonnet': ['image', 'text', 'file'],
            'claude-3-haiku': ['image', 'text', 'file'],
            // 智谱视觉模型
            'glm-4v': ['image', 'text'],
            'glm-4': ['text'],
            'glm-3-turbo': ['text']
        };
        
        // 图像生成专用模型列表（这些模型只用于生成图像，不能用于文本对话）
        const imageGenerationModels = [
            'qwen-image',
            'wanx',
            'wanx2.1',
            'dall-e',
            'dall-e-2',
            'dall-e-3',
            'sd',
            'stable-diffusion',
            'midjourney',
            'kandinsky',
            'deepfloyd'
        ];
        
        // 检查是否是图像生成专用模型
        function isImageGenerationModel(modelName) {
            if (!modelName) return false;
            const lowerName = modelName.toLowerCase();
            return imageGenerationModels.some(imgModel => 
                lowerName.includes(imgModel.toLowerCase())
            );
        }
        
        // 获取模型能力
        function getModelCapabilities(modelName) {
            if (!modelName) return ['text'];
            const lowerName = modelName.toLowerCase();
            
            // 检查是否是图像生成专用模型
            if (isImageGenerationModel(modelName)) {
                return ['image-gen']; // 图像生成模型标记
            }
            
            // 精确匹配
            if (modelCapabilities[lowerName]) {
                return modelCapabilities[lowerName];
            }
            
            // 模糊匹配
            for (const [key, caps] of Object.entries(modelCapabilities)) {
                if (lowerName.includes(key) || key.includes(lowerName)) {
                    return caps;
                }
            }
            
            // 根据关键词推断
            if (lowerName.includes('vision') || lowerName.includes('vl')) {
                return ['image', 'text'];
            }
            if (lowerName.includes('omni')) {
                return ['image', 'text', 'video', 'audio'];
            }
            
            return ['text'];
        }
        
        // 从服务器加载真实配置的模型
        async function loadConfiguredProviders() {
            try {
                const response = await fetch('api/providers_handler.php?action=get_providers&enabled=1');
                const data = await response.json();
                
                if (data.success && data.data) {
                    // 过滤出有模型的提供商
                    return data.data.filter(p => p.enabled && p.models && p.models.length > 0);
                }
            } catch (error) {
                console.error('加载提供商失败:', error);
            }
            return [];
        }
        
        // 真正支持视频分析的模型白名单（严格限制）
        const videoCapableModels = [
            'qwen-vl-plus',
            'qwen-vl-max',
            'qwen-omni-turbo',
            'gemini-1.5-pro',
            'gemini-1.5-flash'
        ];

        // 检查是否为全态模型
        function isMultimodalModel(modelName) {
            if (!modelName) return false;
            const lowerName = modelName.toLowerCase();
            const keywords = ['vl', 'omni', 'vision', 'gpt-4o', 'claude-3', 'gemini', 'glm-4v', 'qwen-vl', 'qwen-omni'];
            return keywords.some(kw => lowerName.includes(kw.toLowerCase()));
        }

        // 检查模型是否真正支持视频分析（严格检查）
        function isVideoCapableModel(modelName) {
            if (!modelName) return false;
            const lowerName = modelName.toLowerCase();

            // 精确匹配或前缀匹配（处理版本号后缀如 qwen-vl-plus-xxx）
            return videoCapableModels.some(vm => {
                const vmLower = vm.toLowerCase();
                // 完全匹配
                if (lowerName === vmLower) return true;
                // 前缀匹配（模型名以白名单模型名开头）
                if (lowerName.startsWith(vmLower)) return true;
                // 包含匹配（白名单模型名包含在模型名中）
                if (lowerName.includes(vmLower)) return true;
                return false;
            });
        }
        
        // 渲染模型选择 - 下拉菜单方式（使用真实模型名称）
        async function renderModelSelection(requiredCapabilities, scenarioTitle, scenarioType = '') {
            const providerSelect = document.getElementById('providerSelect');
            const modelSelect = document.getElementById('modelSelect');
            const capabilitiesDiv = document.getElementById('modelCapabilities');
            const modelSelectLabel = document.querySelector('.model-select-section h3');
            
            // 判断是否需要多模态模型
            const needsMultimodal = requiredCapabilities.includes('image') || 
                                   requiredCapabilities.includes('video') || 
                                   requiredCapabilities.includes('file');
            
            // 视频剪辑场景强制只使用全态模型
            const isVideoEdit = scenarioType === 'video-edit' || scenarioType === 'live-highlight';
            
            // 更新标签文字
            if (modelSelectLabel) {
                if (isVideoEdit) {
                    modelSelectLabel.innerHTML = '<i class="fas fa-video"></i> 选择全态模型（必须支持视频分析）';
                } else if (needsMultimodal) {
                    modelSelectLabel.innerHTML = '<i class="fas fa-brain"></i> 选择全态模型（支持图像/视频/文档）';
                } else {
                    modelSelectLabel.innerHTML = '<i class="fas fa-robot"></i> 选择AI模型';
                }
            }
            
            // 清空选择
            providerSelect.innerHTML = '<option value="">选择AI提供商...</option>';
            modelSelect.innerHTML = '<option value="">选择模型...</option>';
            capabilitiesDiv.innerHTML = '';
            selectedModel = null;
            
            // 从服务器加载配置（缓存）
            if (configuredProvidersList.length === 0) {
                configuredProvidersList = await loadConfiguredProviders();
            }
            
            // 过滤出支持视频分析的模型提供商（视频剪辑场景）
            let filteredProviders = configuredProvidersList;
            if (isVideoEdit) {
                filteredProviders = configuredProvidersList.filter(provider => {
                    // 严格检查：提供商必须配置了真正支持视频的模型
                    const hasVideoCapable = provider.models.some(m => isVideoCapableModel(m));
                    return hasVideoCapable;
                });

                if (filteredProviders.length === 0) {
                    providerSelect.innerHTML = '<option value="">未配置视频分析模型</option>';
                    modelSelect.innerHTML = '<option value="">请先配置支持视频分析的AI模型</option>';

                    // 显示警告信息
                    const warningDiv = document.createElement('div');
                    warningDiv.className = 'model-warning';
                    warningDiv.style.cssText = 'background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; padding: 12px; border-radius: 8px; margin-top: 12px; font-size: 14px;';
                    warningDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> <strong>视频剪辑功能需要使用支持视频分析的模型</strong><br>请先配置以下AI模型之一：<br>• 通义千问VL (qwen-vl-plus, qwen-vl-max)<br>• 通义千问Omni (qwen-omni-turbo)<br>• Gemini (gemini-1.5-pro, gemini-1.5-flash)<br><br><small>注意：GPT-4o、Gemini Vision等模型仅支持图片，不支持视频分析</small>';
                    capabilitiesDiv.appendChild(warningDiv);
                    return;
                }
            }
            
            if (configuredProvidersList.length === 0) {
                providerSelect.innerHTML = '<option value="">未配置AI提供商</option>';
                return;
            }
            
            // 填充提供商下拉菜单
            filteredProviders.forEach(provider => {
                const option = document.createElement('option');
                option.value = provider.id;
                option.textContent = provider.name;
                // 视频剪辑场景：只传递真正支持视频分析的模型
                if (isVideoEdit) {
                    const videoCapableModels = provider.models.filter(m => isVideoCapableModel(m));
                    option.dataset.models = JSON.stringify(videoCapableModels);
                } else {
                    const multimodalModels = provider.models.filter(m => isMultimodalModel(m));
                    option.dataset.models = JSON.stringify(multimodalModels);
                }
                option.dataset.type = provider.type;
                option.dataset.needsMultimodal = needsMultimodal;
                option.dataset.isVideoEdit = isVideoEdit;
                providerSelect.appendChild(option);
            });
        }
        
        // 处理文件选择
        function handleFileSelect(input) {
            const file = input.files[0];
            if (file) {
                currentFile = file;
                document.getElementById('fileName').textContent = file.name + ' (' + formatFileSize(file.size) + ')';
                document.getElementById('fileName').style.display = 'block';
                document.getElementById('fileUploadArea').classList.add('has-file');
                
                // 如果是视频文件，显示预览
                if (file.type.startsWith('video/')) {
                    showUploadedVideoPreview(file);
                }
            }
        }
        
        // 显示上传视频的预览
        function showUploadedVideoPreview(file) {
            const videoPreviewSection = document.getElementById('videoPreviewSection');
            const videoPlayer = document.getElementById('videoPlayer');
            const videoDownloadLink = document.getElementById('videoDownloadLink');
            const videoDuration = document.getElementById('videoDuration');
            
            const videoUrl = URL.createObjectURL(file);
            videoPlayer.src = videoUrl;
            videoDownloadLink.href = videoUrl;
            videoDownloadLink.download = file.name;
            
            // 等待视频加载完成后获取时长
            videoPlayer.onloadedmetadata = function() {
                const seconds = Math.floor(videoPlayer.duration);
                const mins = Math.floor(seconds / 60);
                const secs = seconds % 60;
                videoDuration.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            };
            
            videoPreviewSection.style.display = 'block';
        }
        
        // 格式化文件大小
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // 显示视频预览
        function showVideoPreview(videoUrl, fileName, duration) {
            const videoPreviewSection = document.getElementById('videoPreviewSection');
            const videoPlayer = document.getElementById('videoPlayer');
            const videoDownloadLink = document.getElementById('videoDownloadLink');
            const videoDuration = document.getElementById('videoDuration');
            
            videoPlayer.src = videoUrl;
            videoDownloadLink.href = videoUrl;
            videoDownloadLink.download = fileName || 'edited_video.mp4';
            
            // 格式化时长显示
            if (duration) {
                if (typeof duration === 'string' && duration.includes(':')) {
                    videoDuration.textContent = duration;
                } else {
                    const seconds = parseInt(duration);
                    const mins = Math.floor(seconds / 60);
                    const secs = seconds % 60;
                    videoDuration.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                }
            } else {
                videoDuration.textContent = '--:--';
            }
            
            videoPreviewSection.style.display = 'block';
            
            // 自动播放
            videoPlayer.load();
            videoPlayer.play().catch(e => {
                console.log('自动播放被阻止:', e);
            });
        }
        
        // 关闭演示
        function closeDemo() {
            // 停止视频播放
            const videoPlayer = document.getElementById('videoPlayer');
            if (videoPlayer) {
                videoPlayer.pause();
                videoPlayer.src = '';
            }
            document.getElementById('videoPreviewSection').style.display = 'none';
            
            document.getElementById('demoModal').classList.remove('show');
            currentScenario = null;
            selectedModel = null;
            currentFile = null;
        }
        
        // 生成内容
        function generateContent() {
            if (!selectedModel) {
                alert('请先选择一个全态模型');
                return;
            }
            
            const input = document.getElementById('demoInput').value;
            const outputDiv = document.getElementById('demoOutput');
            const videoPreviewSection = document.getElementById('videoPreviewSection');
            
            // 隐藏视频预览
            videoPreviewSection.style.display = 'none';
            
            outputDiv.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> AI正在处理，请稍候...</div>';
            
            // 构建FormData
            const formData = new FormData();
            formData.append('action', 'scenario_chat');
            formData.append('provider_id', selectedModel.provider_id);
            formData.append('model', selectedModel.id);
            formData.append('scenario', currentScenario);
            formData.append('input', input);
            if (currentFile) {
                formData.append('file', currentFile);
            }
            
            // 发送请求
            $.ajax({
                url: 'api/scenario_handler.php?t=' + Date.now(),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                cache: false,
                success: function(response) {
                    if (response.success) {
                        let html = '<div style="white-space: pre-wrap; line-height: 1.8;">' + response.message + '</div>';
                        
                        // 如果是视频剪辑场景，添加分段导出按钮
                        if (response.type === 'video' && response.download_url) {
                            // 保存剪辑方案供分段导出使用
                            window.currentEditPlan = response.edit_plan || null;
                            window.currentVideoUrl = response.download_url;
                            window.currentVideoFileName = response.file_name;
                            
                            html += '<div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0;">';
                            html += '<div style="display: flex; gap: 12px; flex-wrap: wrap;">';
                            html += '<a href="' + response.download_url + '" download="' + (response.file_name || 'edited_video.mp4') + '" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px; text-decoration: none; font-weight: 500;">';
                            html += '<i class="fas fa-download"></i> 下载完整视频</a>';
                            
                            // 如果有片段信息，显示分段导出按钮
                            if (response.edit_plan && response.edit_plan.highlights && response.edit_plan.highlights.length > 0) {
                                html += '<button onclick="showSegmentExport()" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border-radius: 8px; border: none; cursor: pointer; font-weight: 500;">';
                                html += '<i class="fas fa-layer-group"></i> 分段导出 (' + response.edit_plan.highlights.length + '段)</button>';
                            }
                            
                            html += '</div></div>';
                        }
                        
                        outputDiv.innerHTML = html;
                        
                        // 如果是视频剪辑场景，显示视频预览
                        if (response.type === 'video' && response.download_url) {
                            showVideoPreview(response.download_url, response.file_name, response.duration);
                        }
                    } else {
                        outputDiv.innerHTML = '<span style="color: #ef4444;">生成失败：' + (response.error || '未知错误') + '</span>';
                    }
                },
                error: function() {
                    outputDiv.innerHTML = '<span style="color: #ef4444;">请求失败，请检查网络连接</span>';
                }
            });
        }
        
        // 显示分段导出对话框
        function showSegmentExport() {
            if (!window.currentEditPlan || !window.currentEditPlan.highlights) {
                alert('没有可用的片段信息');
                return;
            }
            
            const highlights = window.currentEditPlan.highlights;
            let html = '<div style="background: #f8fafc; border-radius: 12px; padding: 20px; margin-top: 16px;">';
            html += '<h4 style="margin: 0 0 16px 0; color: #1e293b;"><i class="fas fa-layer-group"></i> 分段导出</h4>';
            html += '<p style="color: #64748b; font-size: 14px; margin-bottom: 16px;">点击下方按钮导出各个精彩片段：</p>';
            html += '<div style="display: flex; flex-direction: column; gap: 12px;">';
            
            highlights.forEach((segment, index) => {
                const startTime = formatTime(segment.start_time || 0);
                const endTime = formatTime(segment.end_time || 0);
                const desc = segment.description || '精彩片段' + (index + 1);
                
                html += '<div style="display: flex; align-items: center; justify-content: space-between; background: white; padding: 12px 16px; border-radius: 8px; border: 1px solid #e2e8f0;">';
                html += '<div style="display: flex; align-items: center; gap: 12px;">';
                html += '<span style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600;">' + (index + 1) + '</span>';
                html += '<div>';
                html += '<div style="font-weight: 500; color: #1e293b;">' + desc + '</div>';
                html += '<div style="font-size: 13px; color: #64748b;">' + startTime + ' - ' + endTime + '</div>';
                html += '</div></div>';
                html += '<button onclick="exportSegment(' + index + ')" style="padding: 8px 16px; background: #4c51bf; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: 6px;">';
                html += '<i class="fas fa-download"></i> 导出</button>';
                html += '</div>';
            });
            
            html += '</div>';
            html += '<div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0;">';
            html += '<button onclick="exportAllSegments()" style="width: 100%; padding: 12px; background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 8px;">';
            html += '<i class="fas fa-download"></i> 一键导出所有片段 (ZIP)</button>';
            html += '</div></div>';
            
            // 插入到输出区域
            const outputDiv = document.getElementById('demoOutput');
            const existingExport = outputDiv.querySelector('.segment-export-panel');
            if (existingExport) {
                existingExport.remove();
            }
            
            const exportPanel = document.createElement('div');
            exportPanel.className = 'segment-export-panel';
            exportPanel.innerHTML = html;
            outputDiv.appendChild(exportPanel);
        }
        
        // 格式化时间
        function formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return mins.toString().padStart(2, '0') + ':' + secs.toString().padStart(2, '0');
        }
        
        // 导出单个片段
        function exportSegment(index) {
            if (!window.currentEditPlan || !window.currentEditPlan.highlights[index]) {
                alert('片段信息不可用');
                return;
            }
            
            const segment = window.currentEditPlan.highlights[index];
            const formData = new FormData();
            formData.append('action', 'export_segment');
            formData.append('video_url', window.currentVideoUrl);
            formData.append('start_time', segment.start_time || 0);
            formData.append('end_time', segment.end_time || 0);
            formData.append('description', segment.description || '片段' + (index + 1));
            
            // 显示加载状态
            const btn = event.target.closest('button');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 处理中...';
            btn.disabled = true;
            
            $.ajax({
                url: 'api/scenario_handler.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                    
                    if (response.success && response.download_url) {
                        // 创建临时链接下载
                        const a = document.createElement('a');
                        a.href = response.download_url;
                        a.download = response.file_name || 'segment_' + (index + 1) + '.mp4';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    } else {
                        alert('导出失败：' + (response.error || '未知错误'));
                    }
                },
                error: function() {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                    alert('请求失败，请检查网络连接');
                }
            });
        }
        
        // 一键导出所有片段
        function exportAllSegments() {
            if (!window.currentEditPlan || !window.currentEditPlan.highlights) {
                alert('没有可用的片段信息');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'export_all_segments');
            formData.append('video_url', window.currentVideoUrl);
            formData.append('edit_plan', JSON.stringify(window.currentEditPlan));
            
            const btn = event.target.closest('button');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 打包中...';
            btn.disabled = true;
            
            $.ajax({
                url: 'api/scenario_handler.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                    
                    if (response.success && response.download_url) {
                        const a = document.createElement('a');
                        a.href = response.download_url;
                        a.download = response.file_name || 'all_segments.zip';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    } else {
                        alert('打包失败：' + (response.error || '未知错误'));
                    }
                },
                error: function() {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                    alert('请求失败，请检查网络连接');
                }
            });
        }
        
        // 事件监听
        document.getElementById('industrySelect').addEventListener('change', renderScenarios);
        document.getElementById('filterImage').addEventListener('change', renderScenarios);
        document.getElementById('filterVideo').addEventListener('change', renderScenarios);
        document.getElementById('filterFile').addEventListener('change', renderScenarios);
        
        // 提供商和模型选择事件 - 只初始化一次
        document.getElementById('providerSelect').addEventListener('change', handleProviderChange);
        document.getElementById('modelSelect').addEventListener('change', handleModelChange);
        
        // 点击模态框背景关闭
        document.getElementById('demoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDemo();
            }
        });
        
        // 初始化
        renderScenarios();
    </script>
</body>
</html>
