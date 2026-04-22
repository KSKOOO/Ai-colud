<?php
/**
 * OpenClaw 龙虾智能体外部调用API
 * 支持通过Token外部访问养成的龙虾
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../lib/AIProviderManager.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$token = $_GET['token'] ?? $_POST['token'] ?? '';

try {
    $db = Database::getInstance();
    
    switch ($action) {
        case 'info':
            getLobsterInfo($db, $token);
            break;
        case 'chat':
            chatWithLobster($db, $token);
            break;
        case 'history':
            getChatHistory($db, $token);
            break;
        default:
            echo json_encode(['success' => false, 'error' => '未知操作']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * 获取龙虾信息
 */
function getLobsterInfo($db, $token) {
    $agent = $db->fetch("
        SELECT a.*, u.username as owner_name 
        FROM openclaw_agents a 
        JOIN openclaw_deployments d ON a.id = d.agent_id
        JOIN users u ON a.user_id = u.id
        WHERE d.token = ? AND d.status = 'active'
    ", [$token]);
    
    if (!$agent) {
        echo json_encode(['success' => false, 'error' => '无效的Token或龙虾未部署']);
        return;
    }
    
    // 获取技能
    $skills = $db->fetchAll("
        SELECT s.name, s.description, a_s.proficiency
        FROM openclaw_skills s
        JOIN openclaw_agent_skills a_s ON s.id = a_s.skill_id
        WHERE a_s.agent_id = ?
    ", [$agent['id']]);
    
    echo json_encode([
        'success' => true,
        'lobster' => [
            'name' => $agent['name'],
            'avatar' => $agent['avatar'],
            'level' => $agent['level'],
            'personality' => $agent['personality'],
            'intelligence' => $agent['intelligence'],
            'owner' => $agent['owner_name'],
            'skills' => $skills
        ]
    ]);
}

/**
 * 与龙虾对话
 */
function chatWithLobster($db, $token) {
    $message = $_POST['message'] ?? '';
    $sessionId = $_POST['session_id'] ?? uniqid('lobster_');
    
    if (empty($message)) {
        echo json_encode(['success' => false, 'error' => '消息不能为空']);
        return;
    }
    
    // 获取龙虾信息
    $agent = $db->fetch("
        SELECT a.*, d.id as deployment_id 
        FROM openclaw_agents a 
        JOIN openclaw_deployments d ON a.id = d.agent_id
        WHERE d.token = ? AND d.status = 'active'
    ", [$token]);
    
    if (!$agent) {
        echo json_encode(['success' => false, 'error' => '无效的Token或龙虾未部署']);
        return;
    }
    
    // 获取相关记忆
    $memories = $db->fetchAll("
        SELECT content, importance 
        FROM openclaw_memory 
        WHERE agent_id = ? 
        ORDER BY importance DESC, created_at DESC 
        LIMIT 5
    ", [$agent['id']]);
    
    $memoryContext = '';
    if (!empty($memories)) {
        $memoryContext = "\n\n相关记忆：\n";
        foreach ($memories as $m) {
            $memoryContext .= "- " . $m['content'] . "\n";
        }
    }
    
    // 构建系统提示词
    $systemPrompt = $agent['system_prompt'] ?? '你是一个AI助手。';
    $systemPrompt .= "\n\n你的性格特点：" . ($agent['personality'] ?? '友好、乐于助人');
    $systemPrompt .= "\n你的等级：Lv." . $agent['level'];
    $systemPrompt .= "\n你的智力值：" . $agent['intelligence'];
    
    if ($memoryContext) {
        $systemPrompt .= $memoryContext;
    }
    
    // 调用AI模型
    try {
        $providerManager = new AIProviderManager();
        $caller = $providerManager->createCaller();
        
        $result = $caller->chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $message]
        ], [
            'model' => $agent['model'] ?? 'gpt-3.5-turbo',
            'temperature' => floatval($agent['temperature'] ?? 0.7),
            'max_tokens' => intval($agent['max_tokens'] ?? 2048)
        ]);
        
        if ($result['success']) {
            $response = $result['content'];
            
            // 记录对话到记忆
            $db->insert('openclaw_memory', [
                'agent_id' => $agent['id'],
                'memory_type' => 'conversation',
                'content' => "用户: $message\n回复: $response",
                'importance' => 0.7,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // 记录任务
            $db->insert('openclaw_tasks', [
                'user_id' => $agent['user_id'],
                'agent_id' => $agent['id'],
                'task_type' => 'external_chat',
                'task_input' => $message,
                'task_output' => $response,
                'status' => 'completed',
                'started_at' => date('Y-m-d H:i:s'),
                'completed_at' => date('Y-m-d H:i:s'),
                'success' => 1,
                'tokens_used' => $result['usage']['total_tokens'] ?? 0
            ]);
            
            // 更新部署统计
            $db->exec("
                UPDATE openclaw_deployments 
                SET usage_count = usage_count + 1, last_used_at = ?
                WHERE id = ?
            ", [date('Y-m-d H:i:s'), $agent['deployment_id']]);
            
            echo json_encode([
                'success' => true,
                'response' => $response,
                'session_id' => $sessionId,
                'lobster_name' => $agent['name'],
                'lobster_avatar' => $agent['avatar']
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'AI调用失败: ' . ($result['error'] ?? '未知错误')]);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => '调用失败: ' . $e->getMessage()]);
    }
}

/**
 * 获取对话历史
 */
function getChatHistory($db, $token) {
    $agent = $db->fetch("
        SELECT a.id 
        FROM openclaw_agents a 
        JOIN openclaw_deployments d ON a.id = d.agent_id
        WHERE d.token = ? AND d.status = 'active'
    ", [$token]);
    
    if (!$agent) {
        echo json_encode(['success' => false, 'error' => '无效的Token']);
        return;
    }
    
    $memories = $db->fetchAll("
        SELECT content, created_at 
        FROM openclaw_memory 
        WHERE agent_id = ? AND memory_type = 'conversation'
        ORDER BY created_at DESC
        LIMIT 20
    ", [$agent['id']]);
    
    echo json_encode(['success' => true, 'history' => $memories]);
}
