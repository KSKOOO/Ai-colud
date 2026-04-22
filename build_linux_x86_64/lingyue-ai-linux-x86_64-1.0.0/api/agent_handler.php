<?php

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/AgentManager.php';
require_once __DIR__ . '/../lib/AIProviderManager.php';

$db = Database::getInstance();
$agentManager = new AgentManager($db);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$currentUserId = $_SESSION['user']['id'] ?? null;

try {
    switch ($action) {
        case 'createAgent':
            requireLogin();
            $data = json_decode($_POST['data'] ?? '{}', true);
            $result = $agentManager->createAgent($currentUserId, $data);
            echo json_encode($result);
            break;
            
        case 'updateAgent':
            requireLogin();
            $agentId = $_POST['agent_id'] ?? 0;
            $data = json_decode($_POST['data'] ?? '{}', true);
            $result = $agentManager->updateAgent($agentId, $currentUserId, $data);
            echo json_encode($result);
            break;
            
        case 'getAgent':
            requireLogin();
            $agentId = $_GET['agent_id'] ?? 0;
            $agent = $agentManager->getAgent($agentId);
            
            if (!$agent || $agent['user_id'] != $currentUserId) {
                echo json_encode(['success' => false, 'error' => '无权访问']);
                exit;
            }
            
            echo json_encode(['success' => true, 'agent' => $agent]);
            break;
            
        case 'getMyAgents':
            requireLogin();
            $page = intval($_GET['page'] ?? 1);
            $result = $agentManager->getUserAgents($currentUserId, $page);
            echo json_encode(['success' => true] + $result);
            break;
            
        case 'deleteAgent':
            requireLogin();
            $agentId = $_POST['agent_id'] ?? 0;
            $result = $agentManager->deleteAgent($agentId, $currentUserId);
            echo json_encode($result);
            break;
            
        case 'deployAgent':
            requireLogin();
            $agentId = $_POST['agent_id'] ?? 0;
            $result = $agentManager->deployAgent($agentId, $currentUserId);
            echo json_encode($result);
            break;
            
        case 'undeployAgent':
            requireLogin();
            $agentId = $_POST['agent_id'] ?? 0;
            $result = $agentManager->undeployAgent($agentId, $currentUserId);
            echo json_encode($result);
            break;
            
        case 'chat':

            handleAgentChat($agentManager);
            break;
            
        case 'debug':

            requireLogin();
            handleDebug($agentManager);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => '未知的操作类型']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function requireLogin() {
    global $currentUserId;
    if (!$currentUserId) {
        echo json_encode(['success' => false, 'error' => '请先登录']);
        exit;
    }
}


function handleAgentChat($agentManager) {
    $agentId = $_POST['agent_id'] ?? 0;
    $token = $_POST['token'] ?? '';
    $message = $_POST['message'] ?? '';
    $sessionId = $_POST['session_id'] ?? session_id();
    
    if (!$message) {
        echo json_encode(['success' => false, 'error' => '消息不能为空']);
        return;
    }
    

    if ($token) {
        $agent = $agentManager->getAgentByToken($token);
    } else {
        $agent = $agentManager->getAgent($agentId);
    }
    
    if (!$agent) {
        echo json_encode(['success' => false, 'error' => '智能体不存在或未部署']);
        return;
    }
    

    if ($agent['status'] !== 'active' && !$token) {
        echo json_encode(['success' => false, 'error' => '智能体未部署']);
        return;
    }
    

    global $currentUserId;
    $agentManager->saveConversation(
        $agent['id'], 
        $sessionId, 
        $currentUserId, 
        $message, 
        'user'
    );
    

    $systemPrompt = $agentManager->buildSystemPrompt($agent);
    

    $history = $agentManager->getConversationHistory($agent['id'], $sessionId, 10);
    $messages = [['role' => 'system', 'content' => $systemPrompt]];
    

    foreach (array_reverse($history) as $msg) {
        $messages[] = [
            'role' => $msg['role'],
            'content' => $msg['message']
        ];
    }
    

    $messages[] = ['role' => 'user', 'content' => $message];
    

    try {
        $manager = new AIProviderManager();
        

        $providerId = $agent['model_provider'];
        $allProviders = $manager->getProviders(true);
        
        if (!isset($allProviders[$providerId])) {

            foreach ($allProviders as $id => $p) {
                if ($p['type'] === $providerId) {
                    $providerId = $id;
                    break;
                }
            }
        }
        
        $caller = $manager->createCaller($providerId);
        
        $result = $caller->chat($messages, [
            'model' => $agent['model_id'],
            'temperature' => floatval($agent['temperature']),
            'max_tokens' => intval($agent['max_tokens'])
        ]);
        
        if ($result['success']) {
            $reply = $result['content'];

            // 记录用量统计
            try {
                require_once __DIR__ . '/../lib/UsageTracker.php';
                $usageTracker = new UsageTracker();
                $inputTokens = $result['usage']['prompt_tokens'] ?? $usageTracker->estimateTokens($message);
                $outputTokens = $result['usage']['completion_tokens'] ?? $usageTracker->estimateTokens($reply);
                $usageTracker->recordUsage(
                    $currentUserId,
                    'agent_chat',
                    $result['model'] ?? $agent['model_id'],
                    $inputTokens,
                    $outputTokens,
                    ['agent_id' => $agent['id'], 'agent_name' => $agent['name'], 'session_id' => $sessionId]
                );
            } catch (Exception $e) {
                error_log("Record agent usage failed: " . $e->getMessage());
            }

            $agentManager->saveConversation(
                $agent['id'],
                $sessionId,
                $currentUserId,
                $reply,
                'assistant',
                $result['model'] ?? $agent['model_id'],
                $result['tokens'] ?? null
            );

            echo json_encode([
                'success' => true,
                'message' => $reply,
                'session_id' => $sessionId
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => $result['error'] ?? 'AI响应失败'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => '调用AI失败: ' . $e->getMessage()
        ]);
    }
}


function handleDebug($agentManager) {
    global $currentUserId;
    
    $agentId = $_POST['agent_id'] ?? 0;
    $message = $_POST['message'] ?? '';
    
    $agent = $agentManager->getAgent($agentId);
    if (!$agent || $agent['user_id'] != $currentUserId) {
        echo json_encode(['success' => false, 'error' => '无权访问']);
        return;
    }
    

    $systemPrompt = $agentManager->buildSystemPrompt($agent);
    
    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $message]
    ];
    
    try {
        $manager = new AIProviderManager();
        $caller = $manager->createCaller($agent['model_provider']);
        
        $result = $caller->chat($messages, [
            'model' => $agent['model_id'],
            'temperature' => floatval($agent['temperature']),
            'max_tokens' => intval($agent['max_tokens'])
        ]);
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => $result['content'],
                'system_prompt' => $systemPrompt,
                'model' => $result['model']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => $result['error'] ?? '调试失败'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
