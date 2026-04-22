<?php

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../lib/AIProviderManager.php';
require_once __DIR__ . '/../lib/UsageTracker.php';
require_once __DIR__ . '/../includes/PermissionManager.php';
require_once __DIR__ . '/../includes/Database.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$manager = new AIProviderManager();
$usageTracker = new UsageTracker();


$currentUserId = $_SESSION['user']['id'] ?? null;
$isAdmin = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';


$permManager = null;
if ($currentUserId) {
    $db = Database::getInstance();
    $permManager = new PermissionManager($db);
}


$action = $_POST['action'] ?? $_GET['action'] ?? '';
$providerId = $_POST['provider_id'] ?? $_GET['provider_id'] ?? '';

try {
    switch ($action) {

        case 'get_types':
            $types = $manager->getProviderTypes();
            echo json_encode([
                'success' => true,
                'data' => $types
            ]);
            break;
            

        case 'get_providers':
            $onlyEnabled = isset($_GET['enabled']) && $_GET['enabled'] === '1';
            $providers = $manager->getProviders($onlyEnabled);
            

            if ($currentUserId && $permManager && !$isAdmin) {
                $userPermissions = $permManager->getUserPermissions($currentUserId);
                $hasModelRestrictions = !empty($userPermissions['models']);
                
                foreach ($providers as &$provider) {
                    if (!empty($provider['models']) && $hasModelRestrictions) {

                        $filteredModels = [];
                        foreach ($provider['models'] as $modelName) {

                            $hasPermission = false;
                            foreach ($userPermissions['models'] as $perm) {
                                if ($perm['provider_id'] === $provider['id'] && 
                                    $perm['model_id'] === $modelName && 
                                    $perm['allowed'] == 1) {
                                    $hasPermission = true;
                                    break;
                                }
                            }
                            if ($hasPermission) {
                                $filteredModels[] = $modelName;
                            }
                        }
                        $provider['models'] = $filteredModels;
                    }
                    

                    if (!empty($provider['config']['api_key'])) {
                        $key = $provider['config']['api_key'];
                        $provider['config']['api_key'] = substr($key, 0, 4) . '****' . substr($key, -4);
                    }
                }
            } else {

                foreach ($providers as &$provider) {
                    if (!empty($provider['config']['api_key'])) {
                        $key = $provider['config']['api_key'];
                        $provider['config']['api_key'] = substr($key, 0, 4) . '****' . substr($key, -4);
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => array_values($providers),
                'active_provider' => $manager->getActiveProvider()['id'] ?? null
            ]);
            break;
            

        case 'get_provider':
            if (empty($providerId)) {
                throw new Exception('缺少provider_id参数');
            }
            $provider = $manager->getProvider($providerId);
            if (!$provider) {
                throw new Exception('提供商不存在');
            }
            

            if (!empty($provider['config']['api_key'])) {
                $key = $provider['config']['api_key'];
                $provider['config']['api_key'] = substr($key, 0, 4) . '****' . substr($key, -4);
            }
            
            echo json_encode([
                'success' => true,
                'data' => $provider
            ]);
            break;
            

        case 'add_provider':
            $data = [
                'id' => $_POST['id'] ?? uniqid('provider_'),
                'type' => $_POST['type'] ?? 'custom_openai',
                'name' => $_POST['name'] ?? '',
                'base_url' => $_POST['base_url'] ?? '',
                'api_key' => $_POST['api_key'] ?? '',
                'default_model' => $_POST['default_model'] ?? '',
                'temperature' => $_POST['temperature'] ?? 0.7,
                'max_tokens' => $_POST['max_tokens'] ?? 2048,
                'timeout' => $_POST['timeout'] ?? 120,
                'enabled' => isset($_POST['enabled']) ? (bool)$_POST['enabled'] : true,
                'is_default' => isset($_POST['is_default']) ? (bool)$_POST['is_default'] : false
            ];
            

            if ($_POST['type'] === 'hunyuan') {
                if (!empty($_POST['secret_id'])) {
                    $data['secret_id'] = $_POST['secret_id'];
                }
                if (!empty($_POST['secret_key'])) {
                    $data['secret_key'] = $_POST['secret_key'];
                }
                if (!empty($_POST['region'])) {
                    $data['region'] = $_POST['region'];
                }
            }
            
            if (empty($data['name'])) {
                throw new Exception('提供商名称不能为空');
            }
            
            if (empty($data['base_url'])) {
                throw new Exception('API地址不能为空');
            }
            
            $provider = $manager->addProvider($data);
            

            if (!empty($provider['config']['api_key'])) {
                $key = $provider['config']['api_key'];
                $provider['config']['api_key'] = substr($key, 0, 4) . '****' . substr($key, -4);
            }
            
            echo json_encode([
                'success' => true,
                'message' => '提供商添加成功',
                'data' => $provider
            ]);
            break;
            

        case 'update_provider':
            if (empty($providerId)) {
                throw new Exception('缺少provider_id参数');
            }
            
            $data = [];
            $fields = ['name', 'base_url', 'api_key', 'default_model', 'temperature', 'max_tokens', 'timeout', 'enabled', 'is_default', 'models'];
            
            foreach ($fields as $field) {
                if (isset($_POST[$field])) {
                    if ($field === 'enabled' || $field === 'is_default') {
                        $data[$field] = (bool)$_POST[$field];
                    } elseif (in_array($field, ['temperature', 'max_tokens', 'timeout'])) {
                        $data[$field] = floatval($_POST[$field]);
                    } elseif ($field === 'models') {
                        $data[$field] = is_string($_POST[$field]) ? json_decode($_POST[$field], true) : $_POST[$field];
                    } else {
                        $data[$field] = $_POST[$field];
                    }
                }
            }
            

            if (isset($_POST['type']) && $_POST['type'] === 'hunyuan') {
                if (isset($_POST['secret_id'])) {
                    $data['secret_id'] = $_POST['secret_id'];
                }
                if (isset($_POST['secret_key'])) {
                    $data['secret_key'] = $_POST['secret_key'];
                }
                if (isset($_POST['region'])) {
                    $data['region'] = $_POST['region'];
                }
            }
            
            $provider = $manager->updateProvider($providerId, $data);
            

            if (!empty($provider['config']['api_key'])) {
                $key = $provider['config']['api_key'];
                $provider['config']['api_key'] = substr($key, 0, 4) . '****' . substr($key, -4);
            }
            
            echo json_encode([
                'success' => true,
                'message' => '提供商更新成功',
                'data' => $provider
            ]);
            break;
            

        case 'delete_provider':
            if (empty($providerId)) {
                throw new Exception('缺少provider_id参数');
            }
            
            $manager->deleteProvider($providerId);
            
            echo json_encode([
                'success' => true,
                'message' => '提供商删除成功'
            ]);
            break;
            

        case 'test_provider':
            if (empty($providerId)) {
                throw new Exception('缺少provider_id参数');
            }
            
            $result = $manager->testProvider($providerId);
            
            echo json_encode($result);
            break;
            

        case 'fetch_models':
            if (empty($providerId)) {
                throw new Exception('缺少provider_id参数');
            }
            
            $result = $manager->fetchModels($providerId);
            
            echo json_encode($result);
            break;
            

        case 'set_active':
            if (empty($providerId)) {
                throw new Exception('缺少provider_id参数');
            }
            
            $provider = $manager->setActiveProvider($providerId);
            
            echo json_encode([
                'success' => true,
                'message' => '活动提供商已切换',
                'data' => [
                    'id' => $provider['id'],
                    'name' => $provider['name']
                ]
            ]);
            break;
            

        case 'get_active':
            $provider = $manager->getActiveProvider();
            
            if ($provider) {

                if (!empty($provider['config']['api_key'])) {
                    $key = $provider['config']['api_key'];
                    $provider['config']['api_key'] = substr($key, 0, 4) . '****' . substr($key, -4);
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => $provider
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => '没有配置的活动提供商'
                ]);
            }
            break;
            

        case 'chat':
            $providerId = $_POST['provider_id'] ?? null;
            $input = $_POST['input'] ?? '';
            $model = $_POST['model'] ?? '';
            $mode = $_POST['mode'] ?? 'normal';
            

            $context = [];
            if (!empty($_POST['context'])) {
                $context = json_decode($_POST['context'], true) ?: [];
            }
            
            if (empty($input)) {
                throw new Exception('输入内容不能为空');
            }
            

            if ($currentUserId && $permManager && !$isAdmin) {
                if (!$permManager->hasModelPermission($currentUserId, $providerId, $model)) {
                    throw new Exception('您没有权限使用此模型');
                }
            }
            

            $systemPrompt = buildSystemPrompt($mode);
            

            $messages = [];
            if ($systemPrompt) {
                $messages[] = ['role' => 'system', 'content' => $systemPrompt];
            }
            
            foreach ($context as $msg) {
                if (isset($msg['role']) && isset($msg['content'])) {
                    $messages[] = $msg;
                }
            }
            
            $messages[] = ['role' => 'user', 'content' => $input];
            

            $caller = $manager->createCaller($providerId);
            

            $provider = $manager->getProvider($providerId);
            $providerType = $provider['type'] ?? '';
            
            $chatOptions = [
                'model' => $model,
                'temperature' => 0.7
            ];
            

            if ($providerType !== 'hunyuan') {
                $chatOptions['max_tokens'] = 2048;
            }
            
            $result = $caller->chat($messages, $chatOptions);
            
            if ($result['success']) {

                if ($currentUserId) {
                    $inputTokens = $result['usage']['prompt_tokens'] ?? $usageTracker->estimateTokens($input);
                    $outputTokens = $result['usage']['completion_tokens'] ?? $usageTracker->estimateTokens($result['content']);
                    
                    $usageTracker->recordUsage(
                        $currentUserId,
                        'chat',
                        $result['model'] ?? $model,
                        $inputTokens,
                        $outputTokens,
                        ['provider' => $result['provider'] ?? $providerId]
                    );
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => $result['content'],
                    'model' => $result['model'],
                    'provider' => $result['provider'],
                    'usage' => $result['usage'] ?? null
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => $result['error'],
                    'provider' => $result['provider']
                ]);
            }
            break;
            

        case 'get_stats':
            $providers = $manager->getProviders();
            $types = $manager->getProviderTypes();
            
            $stats = [
                'total' => count($providers),
                'enabled' => count(array_filter($providers, fn($p) => $p['enabled'])),
                'by_type' => []
            ];
            
            foreach ($providers as $provider) {
                $type = $provider['type'];
                if (!isset($stats['by_type'][$type])) {
                    $stats['by_type'][$type] = [
                        'name' => $types[$type]['name'] ?? $type,
                        'count' => 0,
                        'enabled' => 0
                    ];
                }
                $stats['by_type'][$type]['count']++;
                if ($provider['enabled']) {
                    $stats['by_type'][$type]['enabled']++;
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        default:
            throw new Exception('未知的操作: ' . $action);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}


function buildSystemPrompt($mode) {
    $multimodalPrompt = "你是一位强大的多模态AI助手，能够理解和分析文本、图像和视频内容。\n\n";
    $multimodalPrompt .= "## 能力范围\n";
    $multimodalPrompt .= "1. **文本理解**：理解自然语言，回答问题，进行对话\n";
    $multimodalPrompt .= "2. **图像识别**：分析图片内容，识别物体、场景、文字、人脸等\n";
    $multimodalPrompt .= "3. **视频理解**：理解视频内容，描述动作、场景变化、时间线等\n";
    $multimodalPrompt .= "4. **多媒体输出**：你可以生成图片和文件的引用\n\n";
    $multimodalPrompt .= "## 响应规则\n";
    $multimodalPrompt .= "- 当用户上传图片时，详细描述图像内容，包括视觉元素、场景、物体等\n";
    $multimodalPrompt .= "- 当用户上传视频时，描述视频的主要内容和关键帧\n";
    $multimodalPrompt .= "- 当用户同时提供文字和图片/视频时，结合所有信息给出综合回答\n";
    $multimodalPrompt .= "- 如果无法确定某些内容，请诚实说明\n\n";
    $multimodalPrompt .= "## 多媒体输出格式（如需引用外部资源）\n";
    $multimodalPrompt .= "- 图片: [IMAGE:图片URL]\n";
    $multimodalPrompt .= "- 多张图片: [IMAGES:URL1,URL2,URL3]\n";
    $multimodalPrompt .= "- 文件: [FILE:文件URL|文件名|文件大小|图标类名]\n";
    $multimodalPrompt .= "- 多个文件: [FILES:URL1|名称1|大小1|图标1;URL2|名称2|大小2|图标2]\n";
    $multimodalPrompt .= "- 图标类名可选值: fa-image, fa-file-pdf, fa-file-word, fa-file-excel, fa-file-code, fa-file-archive, fa-file\n\n";
    
    switch ($mode) {
        case 'deep_think':
            return $multimodalPrompt . "## 深度思考模式\n请深入分析问题，展示你的推理过程，然后给出全面、详细的回答。";
        case 'web_search':
            return $multimodalPrompt . "## 联网搜索模式\n请基于最新信息回答问题，如果涉及实时信息，请说明这是基于搜索结果的回答。";
        case 'vision_analysis':
            return $multimodalPrompt . "## 视觉分析模式\n用户上传了图片或视频文件，请详细分析这些视觉内容。描述你'看到'的内容。";
        default:
            return $multimodalPrompt . "请根据用户的输入提供有帮助的回答。";
    }
}
