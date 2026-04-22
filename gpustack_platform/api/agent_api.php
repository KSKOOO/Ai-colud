<?php
/**
 * 智能体API - 支持外部网页调用
 * 
 * 使用方式:
 * 1. JavaScript SDK: <script src="https://your-domain.com/api/agent_sdk.js?token=AGENT_TOKEN"></script>
 * 2. REST API: POST /api/agent_api.php?token=AGENT_TOKEN
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/AgentManager.php';
require_once __DIR__ . '/../lib/AIProviderManager.php';

// 获取Token
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$action = $_GET['action'] ?? $_POST['action'] ?? 'chat';

if (empty($token)) {
    echo json_encode([
        'success' => false,
        'error' => '缺少智能体Token'
    ]);
    exit;
}

try {
    $db = Database::getInstance();
    $agentManager = new AgentManager($db);
    $aiManager = new AIProviderManager($db);
    
    // 获取智能体信息
    $agent = $agentManager->getAgentByToken($token);
    
    if (!$agent) {
        echo json_encode([
            'success' => false,
            'error' => '智能体不存在或已停用'
        ]);
        exit;
    }
    
    // 检查是否需要配置模型
    if (empty($agent['model_provider']) || empty($agent['model_id'])) {
        echo json_encode([
            'success' => false,
            'error' => '智能体未配置AI模型'
        ]);
        exit;
    }
    
    switch ($action) {
        case 'info':
            // 获取智能体基本信息（用于外部展示）
            echo json_encode([
                'success' => true,
                'agent' => [
                    'name' => $agent['name'],
                    'description' => $agent['description'],
                    'icon' => $agent['icon'],
                    'color' => $agent['color'],
                    'welcome_message' => $agent['welcome_message'],
                    'role_name' => $agent['role_name'],
                    'capabilities' => $agent['capabilities']
                ]
            ]);
            break;
            
        case 'chat':
            // 处理聊天请求
            $input = $_POST['message'] ?? '';
            $sessionId = $_POST['session_id'] ?? uniqid('sess_');
            $context = $_POST['context'] ?? '[]';
            
            if (empty($input)) {
                echo json_encode([
                    'success' => false,
                    'error' => '消息内容不能为空'
                ]);
                exit;
            }
            
            // 保存用户消息
            $agentManager->saveConversation(
                $agent['id'],
                $sessionId,
                null, // 外部用户没有user_id
                $input,
                'user',
                null,
                null
            );
            
            // 构建系统提示词
            $systemPrompt = $agentManager->buildSystemPrompt($agent);
            
            // 获取历史对话
            $history = $agentManager->getConversationHistory($agent['id'], $sessionId, 10);
            $history = array_reverse($history); // 按时间正序
            
            // 构建消息数组
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt]
            ];
            
            // 添加历史消息
            foreach ($history as $msg) {
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['message']
                ];
            }
            
            // 添加当前消息
            $messages[] = ['role' => 'user', 'content' => $input];
            
            // 调用AI模型
            $caller = $aiManager->createCaller($agent['model_provider']);
            
            if (!$caller) {
                echo json_encode([
                    'success' => false,
                    'error' => '无法创建AI调用器'
                ]);
                exit;
            }
            
            $result = $caller->chat($messages, [
                'model' => $agent['model_id'],
                'temperature' => floatval($agent['temperature'] ?? 0.7),
                'max_tokens' => intval($agent['max_tokens'] ?? 2048)
            ]);
            
            if ($result['success']) {
                $response = $result['content'];
                $tokens = $result['usage']['total_tokens'] ?? null;
                
                // 保存AI回复
                $agentManager->saveConversation(
                    $agent['id'],
                    $sessionId,
                    null,
                    $response,
                    'assistant',
                    $agent['model_id'],
                    $tokens
                );
                
                echo json_encode([
                    'success' => true,
                    'response' => $response,
                    'session_id' => $sessionId,
                    'agent_name' => $agent['name'],
                    'tokens_used' => $tokens
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => $result['error'] ?? 'AI调用失败'
                ]);
            }
            break;
            
        case 'history':
            // 获取对话历史
            $sessionId = $_GET['session_id'] ?? '';
            if (empty($sessionId)) {
                echo json_encode([
                    'success' => false,
                    'error' => '缺少会话ID'
                ]);
                exit;
            }
            
            $history = $agentManager->getConversationHistory($agent['id'], $sessionId, 50);
            
            echo json_encode([
                'success' => true,
                'history' => array_reverse($history),
                'session_id' => $sessionId
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'error' => '未知的操作类型'
            ]);
    }
    
} catch (Exception $e) {
    error_log('智能体API错误: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => '服务器内部错误'
    ]);
}
