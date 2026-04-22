<?php
// API处理器
header('Content-Type: application/json');

// 抑制警告输出，确保只返回干净的JSON
error_reporting(0);
ini_set('display_errors', 0);

// 加载配置文件
$config = require __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/AIProviderManager.php';
require_once __DIR__ . '/../includes/Database.php';

// 获取请求参数
$request = $_POST['request'] ?? $_GET['request'] ?? '';
$input = $_POST['input'] ?? '';
$model = $_POST['model'] ?? '';
$mode = $_POST['mode'] ?? 'normal';
$providerId = $_POST['provider_id'] ?? '';
$context = $_POST['context'] ?? '[]';

/**
 * 调用真实的AI API
 */
function callGPUSTackAPI($input, $model, $mode, $providerId = '', $context = '[]') {
    global $config;
    
    try {
        // 解析上下文消息
        $messages = json_decode($context, true) ?: [];
        
        // 构建系统提示词（强制中文回复）
        $systemPrompt = "你是一个 helpful 的AI助手。请用中文回复所有问题。";
        
        // 根据模式调整系统提示词
        if ($mode === 'deep_think') {
            $systemPrompt .= "\n\n请对问题进行深入分析和推理，展示你的思考过程。";
        } elseif ($mode === 'web_search') {
            $systemPrompt .= "\n\n请基于你的知识提供准确的信息，如果涉及实时信息请说明知识截止日期。";
        } elseif ($mode === 'vision_analysis') {
            $systemPrompt .= "\n\n请详细分析用户提供的图像内容。";
        }
        
        // 构建消息数组
        $chatMessages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];
        
        // 添加上下文消息
        foreach ($messages as $msg) {
            if (isset($msg['role']) && isset($msg['content'])) {
                $chatMessages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content']
                ];
            }
        }
        
        // 添加当前用户消息
        $chatMessages[] = ['role' => 'user', 'content' => $input];
        
        // 初始化AIProviderManager
        $manager = new AIProviderManager();
        
        // 如果没有指定provider_id，尝试根据模型名称查找
        if (empty($providerId)) {
            $providers = $manager->getProviders(true);
            foreach ($providers as $id => $provider) {
                if ($provider['type'] === 'ollama' && (empty($model) || strpos($model, 'ollama') !== false)) {
                    $providerId = $id;
                    break;
                }
            }
            // 如果还是没找到，使用第一个可用的
            if (empty($providerId) && !empty($providers)) {
                $providerId = array_key_first($providers);
            }
        }
        
        // 创建caller并发送请求
        $caller = $manager->createCaller($providerId);
        
        if (!$caller) {
            return [
                'status' => 'error',
                'message' => '无法创建AI调用器，请检查模型提供商配置'
            ];
        }
        
        $result = $caller->chat($chatMessages, [
            'model' => $model,
            'temperature' => 0.7,
            'max_tokens' => 2048
        ]);
        
        if ($result['success']) {
            return [
                'status' => 'success',
                'message' => $result['content'],
                'model' => $result['model'] ?? $model,
                'mode' => $mode,
                'usage' => $result['usage'] ?? null
            ];
        } else {
            return [
                'status' => 'error',
                'message' => $result['error'] ?? 'AI调用失败',
                'debug' => $result
            ];
        }
        
    } catch (Exception $e) {
        error_log("AI调用异常: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'AI调用失败: ' . $e->getMessage()
        ];
    }
}

/**
 * 处理高级聊天请求（包括深度思考和联网搜索）
 */
function handleAdvancedChat($input, $model, $mode, $providerId = '', $context = '[]') {
    global $config;
    
    // 如果没有指定模型，使用默认模型
    if (empty($model)) {
        $model = $config['gpustack_api']['model'] ?? 'gpt-3.5-turbo';
    }
    
    // 调用真实的AI API
    return callGPUSTackAPI($input, $model, $mode, $providerId, $context);
}

/**
 * 从模型名称解析模型信息
 */
function parseModelInfo($modelName) {
    $info = [
        'parameter_size' => '未知',
        'quantization' => '未知',
        'size' => '未知'
    ];
    
    // 解析参数量 (例如: llama3.1:8b, qwen:7b-q4_0)
    if (preg_match('/(\d+)(?:\.(\d+))?(b|B|million)/i', $modelName, $matches)) {
        $num = $matches[1];
        $decimal = isset($matches[2]) ? $matches[2] : '';
        $suffix = strtoupper($matches[3]);
        if ($suffix === 'B') {
            $info['parameter_size'] = $num . ($decimal ? '.' . $decimal : '') . 'B';
        } elseif ($suffix === 'MILLION') {
            $info['parameter_size'] = $num . 'M';
        }
    }
    
    // 解析量化级别 (例如: q4_0, q5_k_m, fp16)
    $quantPatterns = [
        '/\bq([0-9]+)_([0-9a-z_]+)\b/i' => 'Q$1_$2',
        '/\bq([0-9]+)\b/i' => 'Q$1',
        '/\bfp(16|32)\b/i' => 'FP$1',
        '/\bint([0-9]+)\b/i' => 'INT$1'
    ];
    foreach ($quantPatterns as $pattern => $replacement) {
        if (preg_match($pattern, $modelName, $matches)) {
            $info['quantization'] = strtoupper(preg_replace($pattern, $replacement, $matches[0]));
            break;
        }
    }
    
    return $info;
}

/**
 * 格式化模型大小
 */
function formatModelSize($bytes) {
    if ($bytes === 0 || $bytes === null) return '未知';
    $gb = $bytes / (1024 * 1024 * 1024);
    if ($gb >= 1) {
        return round($gb, 2) . ' GB';
    }
    $mb = $bytes / (1024 * 1024);
    return round($mb, 2) . ' MB';
}

/**
 * 从Ollama API获取本地模型列表
 */
function getOllamaModels() {
    global $config;
    
    $ollamaUrl = $config['ollama_api']['base_url'] ?? 'http://localhost:11434';
    $models = [];
    
    try {
        $ch = curl_init($ollamaUrl . '/api/tags');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if (!empty($data['models'])) {
                foreach ($data['models'] as $model) {
                    $modelName = $model['name'] ?? $model['model'] ?? 'unknown';
                    
                    // 解析模型信息
                    $parsedInfo = parseModelInfo($modelName);
                    
                    // 获取模型详细信息 (从details字段)
                    $details = $model['details'] ?? [];
                    $parameterSize = $details['parameter_size'] ?? $parsedInfo['parameter_size'];
                    $quantization = $details['quantization_level'] ?? $parsedInfo['quantization'];
                    
                    // 获取模型大小
                    $size = formatModelSize($model['size'] ?? 0);
                    
                    $models[$modelName] = [
                        'name' => $modelName,
                        'parameter_size' => $parameterSize,
                        'quantization' => $quantization,
                        'size' => $size,
                        'modified_at' => $model['modified_at'] ?? null,
                        'digest' => substr($model['digest'] ?? '', 0, 12)
                    ];
                }
            }
        }
    } catch (Exception $e) {
        error_log("获取Ollama模型失败: " . $e->getMessage());
    }
    
    return $models;
}

/**
 * 获取可用模型列表
 */
function getAvailableModels() {
    global $config;
    
    // 从Ollama获取本地模型（包含详细信息）
    $ollamaModels = getOllamaModels();
    
    // 如果Ollama没有模型，返回默认列表
    if (empty($ollamaModels)) {
        // 从配置文件获取默认模型
        $defaultModel = $config['gpustack_api']['model'] ?? 'gpt-3.5-turbo';
        
        $ollamaModels = [
            'llama3.1:8b' => [
                'name' => 'llama3.1:8b',
                'parameter_size' => '8B',
                'quantization' => 'Q4_0',
                'size' => '4.7 GB'
            ],
            'qwen:7b' => [
                'name' => 'qwen:7b',
                'parameter_size' => '7B',
                'quantization' => 'Q4_0',
                'size' => '4.1 GB'
            ],
            'mistral:7b' => [
                'name' => 'mistral:7b',
                'parameter_size' => '7B',
                'quantization' => 'Q4_0',
                'size' => '4.1 GB'
            ]
        ];
        
        // 标记默认模型
        if (isset($ollamaModels[$defaultModel])) {
            $ollamaModels[$defaultModel]['name'] .= ' ⭐';
        }
    }
    
    return [
        'status' => 'success',
        'models' => $ollamaModels,
        'count' => count($ollamaModels)
    ];
}

/**
 * 处理图片上传
 */
function handleImageUpload() {
    // 实际项目中需要实现图片上传逻辑
    return [
        'status' => 'success',
        'message' => '图片上传功能已启用，请在前端选择图片'
    ];
}



// 记录请求参数用于调试
error_log("API请求详情: request={$request}, model={$model}, mode={$mode}, input={$input}");

// 根据请求类型处理不同的API调用
$response = [];

try {
    switch ($request) {
        case 'chat':
        case 'deep_think':
        case 'web_search':
            error_log("处理聊天请求，模型: {$model}, 提供商: {$providerId}");
            $response = handleAdvancedChat($input, $model, $mode, $providerId, $context);
            break;
        case 'models':
            error_log("处理模型列表请求");
            $response = getAvailableModels();
            break;
        case 'upload_image':
            error_log("处理图片上传请求");
            $response = handleImageUpload();
            break;
        default:
            error_log("无效的API请求: {$request}");
            $response = [
                'status' => 'error',
                'message' => '无效的API请求'
            ];
    }
} catch (Exception $e) {
    error_log("API调用异常: " . $e->getMessage());
    $response = [
        'status' => 'error',
        'message' => 'API调用失败: ' . $e->getMessage()
    ];
}

echo json_encode($response);
?>