<?php

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/PermissionManager.php';
require_once __DIR__ . '/../lib/AIProviderManager.php';


$currentUserId = $_SESSION['user']['id'] ?? null;
$isAdmin = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';

$permManager = null;
$userPermissions = null;

if ($currentUserId) {
    $db = Database::getInstance();
    $permManager = new PermissionManager($db);
    $userPermissions = $permManager->getUserPermissions($currentUserId);
}

try {
    $db = Database::getInstance();
    $manager = new AIProviderManager($db);
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'getModels':

            $provider = $_GET['provider'] ?? '';
            
            if (!$provider) {
                echo json_encode(['success' => false, 'error' => '未指定提供商']);
                exit;
            }
            

            $providers = $manager->getProviders(true);
            $targetProvider = null;
            

            if (isset($providers[$provider])) {
                $targetProvider = $providers[$provider];
            } else {

                foreach ($providers as $p) {
                    if ($p['type'] === $provider) {
                        $targetProvider = $p;
                        break;
                    }
                }
            }
            
            if (!$targetProvider) {
                echo json_encode(['success' => false, 'error' => '提供商不存在: ' . $provider]);
                exit;
            }
            

            $models = [];
            

            $providerConfig = AIProviderManager::PROVIDER_TYPES[$provider] ?? [];
            if (!empty($providerConfig['default_models'])) {
                foreach ($providerConfig['default_models'] as $modelId) {
                    $models[] = [
                        'id' => $modelId,
                        'name' => $modelId,
                        'provider' => $provider
                    ];
                }
            }
            

            if ($provider === 'ollama' && !empty($targetProvider['api_url'])) {
                try {
                    $ch = curl_init($targetProvider['api_url'] . '/api/tags');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                    $response = curl_exec($ch);
                    curl_close($ch);
                    
                    if ($response) {
                        $data = json_decode($response, true);
                        if (!empty($data['models'])) {
                            $models = [];
                            foreach ($data['models'] as $model) {
                                $models[] = [
                                    'id' => $model['name'],
                                    'name' => $model['name'],
                                    'provider' => $provider
                                ];
                            }
                        }
                    }
                } catch (Exception $e) {

                }
            }
            

            if (empty($models)) {
                $defaultModels = [
                    'ollama' => ['llama2', 'llama3', 'mistral', 'codellama', 'qwen'],
                    'openai' => ['gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo', 'gpt-4o'],
                    'azure_openai' => ['gpt-35-turbo', 'gpt-4', 'gpt-4o'],
                    'anthropic' => ['claude-3-opus', 'claude-3-sonnet', 'claude-3-haiku'],
                    'gemini' => ['gemini-pro', 'gemini-pro-vision', 'gemini-ultra'],
                    'deepseek' => ['deepseek-chat', 'deepseek-reasoner'],
                    'hunyuan' => ['hunyuan-pro', 'hunyuan-standard', 'hunyuan-lite', 'hunyuan-turbo'],
                    'zhipu' => ['glm-4', 'glm-4v', 'glm-3-turbo'],
                    'qwen' => ['qwen-turbo', 'qwen-plus', 'qwen-max', 'wanx2.1-t2v-turbo'],
                    'moonshot' => ['moonshot-v1-8k', 'moonshot-v1-32k', 'moonshot-v1-128k']
                ];
                
                if (isset($defaultModels[$provider])) {
                    foreach ($defaultModels[$provider] as $modelId) {
                        $models[] = [
                            'id' => $modelId,
                            'name' => $modelId,
                            'provider' => $provider
                        ];
                    }
                }
            }
            

            if ($currentUserId && $permManager && !$isAdmin && !empty($userPermissions['models'])) {
                $filteredModels = [];
                foreach ($models as $model) {
                    $hasPermission = false;
                    foreach ($userPermissions['models'] as $perm) {
                        if ($perm['provider_id'] === $provider && 
                            $perm['model_id'] === $model['id'] && 
                            $perm['allowed'] == 1) {
                            $hasPermission = true;
                            break;
                        }
                    }
                    if ($hasPermission) {
                        $filteredModels[] = $model;
                    }
                }
                $models = $filteredModels;
            }
            
            echo json_encode([
                'success' => true,
                'models' => $models,
                'provider' => $provider
            ]);
            break;
            
        case 'getAllProviders':

            $providers = $manager->getProviders(true);
            $result = [];
            
            foreach ($providers as $provider) {
                $result[] = [
                    'type' => $provider['type'],
                    'name' => $provider['name'],
                    'icon' => $provider['icon'] ?? 'fa-cloud',
                    'description' => $provider['description'] ?? ''
                ];
            }
            
            echo json_encode([
                'success' => true,
                'providers' => $result
            ]);
            break;
            
        case 'getImageProviders':

            $providers = $manager->getImageProviders(true);
            $result = [];
            
            foreach ($providers as $provider) {
                $typeInfo = AIProviderManager::PROVIDER_TYPES[$provider['type']] ?? [];
                $result[] = [
                    'type' => $provider['type'],
                    'name' => $provider['name'],
                    'icon' => $provider['icon'] ?? 'fa-cloud',
                    'description' => $provider['description'] ?? '',
                    'supports_image' => $typeInfo['supports_image'] ?? false,
                    'supports_video' => $typeInfo['supports_video'] ?? false
                ];
            }
            
            echo json_encode([
                'success' => true,
                'providers' => $result
            ]);
            break;
            
        case 'refreshModels':

            $provider = $_GET['provider'] ?? '';
            
            if (!$provider) {
                echo json_encode(['success' => false, 'error' => '未指定提供商']);
                exit;
            }
            

            $providers = $manager->getProviders(true);
            $targetProvider = null;
            $providerType = '';
            

            if (isset($providers[$provider])) {
                $targetProvider = $providers[$provider];
                $providerType = $targetProvider['type'];
            } else {

                foreach ($providers as $p) {
                    if ($p['type'] === $provider) {
                        $targetProvider = $p;
                        $providerType = $p['type'];
                        break;
                    }
                }
            }
            
            if (!$targetProvider) {
                echo json_encode(['success' => false, 'error' => '提供商不存在: ' . $provider]);
                exit;
            }
            
            $models = [];
            

            if ($providerType === 'ollama' && !empty($targetProvider['api_url'])) {
                try {
                    $ch = curl_init($targetProvider['api_url'] . '/api/tags');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($response && $httpCode === 200) {
                        $data = json_decode($response, true);
                        if (!empty($data['models'])) {
                            foreach ($data['models'] as $model) {
                                $models[] = [
                                    'id' => $model['name'],
                                    'name' => $model['name'],
                                    'provider' => $provider
                                ];
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log('刷新Ollama模型失败: ' . $e->getMessage());
                }
            }
            

            if (in_array($providerType, ['openai', 'hunyuan', 'deepseek', 'qwen']) && !empty($targetProvider['api_key'])) {
                try {
                    $apiUrl = rtrim($targetProvider['api_url'] ?? '', '/') . '/v1/models';
                    $ch = curl_init($apiUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer ' . $targetProvider['api_key']
                    ]);
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($response && $httpCode === 200) {
                        $data = json_decode($response, true);
                        if (!empty($data['data'])) {
                            $models = [];
                            foreach ($data['data'] as $model) {
                                $models[] = [
                                    'id' => $model['id'],
                                    'name' => $model['id'],
                                    'provider' => $provider
                                ];
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log('刷新API模型失败: ' . $e->getMessage());
                }
            }
            

            if (empty($models)) {
                $defaultModels = [
                    'ollama' => ['llama2', 'llama3', 'mistral', 'codellama', 'qwen'],
                    'openai' => ['gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo', 'gpt-4o'],
                    'azure_openai' => ['gpt-35-turbo', 'gpt-4', 'gpt-4o'],
                    'anthropic' => ['claude-3-opus', 'claude-3-sonnet', 'claude-3-haiku'],
                    'gemini' => ['gemini-pro', 'gemini-pro-vision', 'gemini-ultra'],
                    'deepseek' => ['deepseek-chat', 'deepseek-reasoner'],
                    'hunyuan' => ['hunyuan-pro', 'hunyuan-standard', 'hunyuan-lite', 'hunyuan-turbo'],
                    'zhipu' => ['glm-4', 'glm-4v', 'glm-3-turbo'],
                    'qwen' => ['qwen-turbo', 'qwen-plus', 'qwen-max', 'wanx2.1-t2v-turbo'],
                    'moonshot' => ['moonshot-v1-8k', 'moonshot-v1-32k', 'moonshot-v1-128k']
                ];
                
                if (isset($defaultModels[$providerType])) {
                    foreach ($defaultModels[$providerType] as $modelId) {
                        $models[] = [
                            'id' => $modelId,
                            'name' => $modelId,
                            'provider' => $provider
                        ];
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'models' => $models,
                'provider' => $provider,
                'refreshed' => true
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => '未知的操作类型']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
