<?php

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// 开启输出缓冲
ob_start();

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

        case 'getAgents':
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

        case 'getChatHistory':
            requireLogin();
            $agentId = $_GET['agent_id'] ?? 0;
            $sessionId = $_GET['session_id'] ?? '';
            $agent = $agentManager->getAgent($agentId);

            if (!$agent || $agent['user_id'] != $currentUserId) {
                echo json_encode(['success' => false, 'error' => '鏃犳潈璁块棶']);
                break;
            }

            $history = $agentManager->getConversationHistory($agentId, $sessionId, 50);
            echo json_encode([
                'success' => true,
                'history' => array_reverse($history),
                'session_id' => $sessionId
            ]);
            break;
            
        case 'debug':

            requireLogin();
            handleDebug($agentManager);
            break;
            
        // ==================== 任务管理接口 ====================
        
        case 'createTask':
            requireLogin();
            $data = json_decode($_POST['data'] ?? '{}', true);
            $agentId = $_POST['agent_id'] ?? 0;
            $result = $agentManager->createTask($agentId, $currentUserId, $data);
            echo json_encode($result);
            break;
            
        case 'getTasks':
            requireLogin();
            $agentId = $_GET['agent_id'] ?? 0;
            $status = $_GET['status'] ?? null;
            $tasks = $agentManager->getAgentTasks($agentId, $status);
            echo json_encode(['success' => true, 'tasks' => $tasks]);
            break;
            
        case 'getTask':
            requireLogin();
            $taskId = $_GET['task_id'] ?? 0;
            $task = $agentManager->getTask($taskId);
            $logs = $agentManager->getTaskLogs($taskId);
            echo json_encode(['success' => true, 'task' => $task, 'logs' => $logs]);
            break;
            
        case 'startTask':
            requireLogin();
            $taskId = $_POST['task_id'] ?? 0;
            $agentId = $_POST['agent_id'] ?? 0;
            $result = $agentManager->startTask($taskId, $agentId);
            echo json_encode($result);
            break;
            
        case 'updateTaskProgress':
            requireLogin();
            $taskId = $_POST['task_id'] ?? 0;
            $progress = $_POST['progress'] ?? 0;
            $message = $_POST['message'] ?? '';
            $result = $agentManager->updateTaskProgress($taskId, $progress, $message);
            echo json_encode($result);
            break;
            
        case 'completeTask':
            requireLogin();
            $taskId = $_POST['task_id'] ?? 0;
            $outputData = json_decode($_POST['output_data'] ?? '{}', true);
            $result = $agentManager->completeTask($taskId, $outputData);
            echo json_encode($result);
            break;
            
        case 'failTask':
            requireLogin();
            $taskId = $_POST['task_id'] ?? 0;
            $errorMessage = $_POST['error_message'] ?? '任务执行失败';
            $result = $agentManager->failTask($taskId, $errorMessage);
            echo json_encode($result);
            break;
            
        // ==================== 绩效评估接口 ====================
        
        case 'addPerformanceReview':
            requireLogin();
            $agentId = $_POST['agent_id'] ?? 0;
            $data = json_decode($_POST['data'] ?? '{}', true);
            $result = $agentManager->addPerformanceReview($agentId, $currentUserId, $data);
            echo json_encode($result);
            break;
            
        case 'getPerformanceStats':
            requireLogin();
            $agentId = $_GET['agent_id'] ?? 0;
            $stats = $agentManager->getAgentPerformanceStats($agentId);
            echo json_encode(['success' => true] + $stats);
            break;
            
        // ==================== AI员工执行接口 ====================
        
        case 'executeTask':
            requireLogin();
            handleExecuteTask($agentManager);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => '未知的操作类型']);
    }
} catch (Exception $e) {
    ob_end_clean();
    error_log("Agent handler error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
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
    $fileCount = intval($_POST['file_count'] ?? 0);

    // 允许空消息（如果有文件）
    if (!$message && $fileCount === 0) {
        echo json_encode(['success' => false, 'error' => '消息不能为空']);
        return;
    }

    // 处理上传的文件
    $fileContents = [];
    if ($fileCount > 0) {
        for ($i = 0; $i < $fileCount; $i++) {
            $fileKey = "file_{$i}";
            if (isset($_FILES[$fileKey])) {
                $file = $_FILES[$fileKey];
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $content = file_get_contents($file['tmp_name']);
                    $fileContents[] = [
                        'name' => $file['name'],
                        'type' => $file['type'],
                        'content' => $content,
                        'tmp_name' => $file['tmp_name']
                    ];
                } else {
                    error_log("File upload error for {$fileKey}: " . $file['error']);
                }
            }
        }
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
    
    // 如果没有token且没有登录，返回错误
    if (!$token && !$currentUserId) {
        echo json_encode(['success' => false, 'error' => '请先登录']);
        return;
    }

    // 构建包含文件信息的消息
    $fullMessage = $message ?: '请分析这个文件';
    if (!empty($fileContents)) {
        $fileInfo = "\n\n【上传文件信息】\n";
        foreach ($fileContents as $idx => $file) {
            $fileInfo .= ($idx + 1) . ". {$file['name']} ({$file['type']})\n";
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            // 对于文本文件，直接添加内容
            if (strpos($file['type'], 'text/') === 0 || $ext === 'txt') {
                $fileInfo .= "文件内容:\n" . substr($file['content'], 0, 5000) . (strlen($file['content']) > 5000 ? "..." : "") . "\n\n";
            }
            // 对于Word文档，尝试提取文本
            elseif ($ext === 'docx') {
                // 使用已保存的临时文件
                $extractedText = extractDocxText($file['tmp_name']);
                if ($extractedText) {
                    $fileInfo .= "文档内容:\n" . substr($extractedText, 0, 5000) . (strlen($extractedText) > 5000 ? "..." : "") . "\n\n";
                } else {
                    $fileInfo .= "【注意：这是一个Word文档，但无法提取内容。请转换为文本格式上传】\n\n";
                }
            }
            // 对于PDF，提示用户
            elseif ($ext === 'pdf') {
                $fileInfo .= "【注意：这是一个PDF文档。请转换为文本格式或截图上传】\n\n";
            }
            // 图片文件
            elseif (strpos($file['type'], 'image/') === 0) {
                $fileInfo .= "【这是一个图片文件，请在支持图像分析的模型中使用】\n\n";
            }
        }
        $fullMessage .= $fileInfo;
    }

    $agentManager->saveConversation(
        $agent['id'],
        $sessionId,
        $currentUserId,
        $fullMessage,
        'user'
    );

    $systemPrompt = $agentManager->buildSystemPrompt($agent);

    // 无视上下文限制：只获取最近3条历史记录，减少token消耗
    $history = $agentManager->getConversationHistory($agent['id'], $sessionId, 3);
    $messages = [['role' => 'system', 'content' => $systemPrompt]];

    foreach (array_reverse($history) as $msg) {
        $messages[] = [
            'role' => $msg['role'],
            'content' => $msg['message']
        ];
    }
    

    $messages[] = ['role' => 'user', 'content' => $fullMessage];
    

    try {
        $manager = new AIProviderManager();
        
        $providerId = $agent['model_provider'];
        if (empty($providerId)) {
            echo json_encode(['success' => false, 'error' => '智能体未配置AI提供商']);
            return;
        }
        
        $allProviders = $manager->getProviders(true);
        
        // 如果 providerId 不是键，尝试通过 type 查找
        if (!isset($allProviders[$providerId])) {
            $foundId = null;
            foreach ($allProviders as $id => $p) {
                if (($p['type'] ?? '') === $providerId) {
                    $foundId = $id;
                    break;
                }
            }
            if ($foundId) {
                $providerId = $foundId;
            } else {
                echo json_encode(['success' => false, 'error' => 'AI提供商不存在: ' . $agent['model_provider']]);
                return;
            }
        }
        
        try {
            $caller = $manager->createCaller($providerId);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => '创建AI调用器失败: ' . $e->getMessage()]);
            return;
        }
        
        $result = $caller->chat($messages, [
            'model' => $agent['model_id'],
            'temperature' => floatval($agent['temperature'] ?? 0.7),
            'max_tokens' => intval($agent['max_tokens'] ?? 2048)
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
        error_log("Agent chat error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
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


/**
 * 处理AI员工任务执行
 * 让AI Agent作为员工自动执行任务
 */
function handleExecuteTask($agentManager) {
    global $currentUserId;
    
    $agentId = $_POST['agent_id'] ?? 0;
    $taskDescription = $_POST['task_description'] ?? '';
    $taskType = $_POST['task_type'] ?? 'general';
    
    if (empty($taskDescription)) {
        echo json_encode(['success' => false, 'error' => '任务描述不能为空']);
        return;
    }
    
    // 获取智能体信息
    $agent = $agentManager->getAgent($agentId);
    if (!$agent || $agent['user_id'] != $currentUserId) {
        echo json_encode(['success' => false, 'error' => '无权访问该智能体']);
        return;
    }
    
    // 创建任务
    $taskData = [
        'title' => substr($taskDescription, 0, 50) . (strlen($taskDescription) > 50 ? '...' : ''),
        'description' => $taskDescription,
        'task_type' => $taskType,
        'priority' => 'medium',
        'input_data' => ['original_request' => $taskDescription]
    ];
    
    $taskResult = $agentManager->createTask($agentId, $currentUserId, $taskData);
    if (!$taskResult['success']) {
        echo json_encode($taskResult);
        return;
    }
    
    $taskId = $taskResult['task_id'];
    
    // 开始执行任务
    $agentManager->startTask($taskId, $agentId);
    
    // 构建任务执行提示词
    $systemPrompt = $agentManager->buildSystemPrompt($agent);
    $systemPrompt .= "\n\n【当前任务】\n";
    $systemPrompt .= "任务ID: {$taskId}\n";
    $systemPrompt .= "任务描述: {$taskDescription}\n";
    $systemPrompt .= "任务类型: {$taskType}\n\n";
    $systemPrompt .= "【任务执行要求】\n";
    $systemPrompt .= "1. 请认真分析任务需求，制定执行计划\n";
    $systemPrompt .= "2. 按照计划逐步执行任务\n";
    $systemPrompt .= "3. 记录执行过程中的关键步骤和思考\n";
    $systemPrompt .= "4. 完成后提供完整的执行结果和总结\n";
    $systemPrompt .= "5. 如遇到问题，说明原因并提供解决方案\n";
    
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
        
        // 第一阶段：制定执行计划
        $agentManager->updateTaskProgress($taskId, 10, '正在分析任务并制定执行计划...');
        
        $planMessages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => "请为以下任务制定详细的执行计划：\n\n{$taskDescription}\n\n请按以下格式回复：\n【执行计划】\n1. ...\n2. ...\n\n【预期产出】\n..."]
        ];
        
        $planResult = $caller->chat($planMessages, [
            'model' => $agent['model_id'],
            'temperature' => floatval($agent['temperature']),
            'max_tokens' => intval($agent['max_tokens'])
        ]);
        
        if (!$planResult['success']) {
            $agentManager->failTask($taskId, '制定执行计划失败: ' . ($planResult['error'] ?? '未知错误'));
            echo json_encode(['success' => false, 'error' => '任务执行失败']);
            return;
        }
        
        $executionPlan = $planResult['content'];
        $agentManager->addTaskLog($taskId, 'info', '执行计划已制定', ['plan' => $executionPlan]);
        
        // 第二阶段：执行任务
        $agentManager->updateTaskProgress($taskId, 30, '正在执行任务...');
        
        $executeMessages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'assistant', 'content' => $executionPlan],
            ['role' => 'user', 'content' => "请按照上述计划执行任务：\n\n{$taskDescription}\n\n请详细记录执行过程，并提供最终结果。"]
        ];
        
        $executeResult = $caller->chat($executeMessages, [
            'model' => $agent['model_id'],
            'temperature' => floatval($agent['temperature']),
            'max_tokens' => intval($agent['max_tokens'])
        ]);
        
        if (!$executeResult['success']) {
            $agentManager->failTask($taskId, '任务执行失败: ' . ($executeResult['error'] ?? '未知错误'));
            echo json_encode(['success' => false, 'error' => '任务执行失败']);
            return;
        }
        
        $executionResult = $executeResult['content'];
        $agentManager->updateTaskProgress($taskId, 80, '正在整理执行结果...');
        
        // 第三阶段：总结和复盘
        $summaryMessages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'assistant', 'content' => $executionPlan . "\n\n" . $executionResult],
            ['role' => 'user', 'content' => "请对以上执行过程和结果进行总结，包括：\n1. 任务完成度评估\n2. 关键成果\n3. 遇到的问题及解决方案\n4. 改进建议"]
        ];
        
        $summaryResult = $caller->chat($summaryMessages, [
            'model' => $agent['model_id'],
            'temperature' => floatval($agent['temperature']),
            'max_tokens' => intval($agent['max_tokens'])
        ]);
        
        $summary = $summaryResult['success'] ? $summaryResult['content'] : '';
        
        // 完成任务
        $outputData = [
            'execution_plan' => $executionPlan,
            'execution_result' => $executionResult,
            'summary' => $summary,
            'task_type' => $taskType
        ];
        
        $agentManager->completeTask($taskId, $outputData);
        
        // 记录用量
        try {
            require_once __DIR__ . '/../lib/UsageTracker.php';
            $usageTracker = new UsageTracker();
            $totalInput = $executionPlan . $executionResult . $summary;
            $totalTokens = $usageTracker->estimateTokens($totalInput);
            $usageTracker->recordUsage(
                $currentUserId,
                'agent_task_execution',
                $agent['model_id'],
                intval($totalTokens * 0.4),
                intval($totalTokens * 0.6),
                ['agent_id' => $agentId, 'task_id' => $taskId, 'task_type' => $taskType]
            );
        } catch (Exception $e) {
            error_log("Record agent task usage failed: " . $e->getMessage());
        }

        echo json_encode([
            'success' => true,
            'task_id' => $taskId,
            'execution_plan' => $executionPlan,
            'execution_result' => $executionResult,
            'summary' => $summary,
            'message' => '任务执行完成'
        ]);
        
    } catch (Exception $e) {
        $agentManager->failTask($taskId, '执行异常: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => '任务执行失败: ' . $e->getMessage()
        ]);
    }
}

/**
 * 从DOCX文件中提取文本内容
 * @param string $filePath DOCX文件路径
 * @return string|false 提取的文本内容或false
 */
function extractDocxText($filePath) {
    try {
        // 检查文件是否存在
        if (!file_exists($filePath)) {
            return false;
        }

        // 使用ZipArchive读取docx文件
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return false;
        }

        // 读取word/document.xml
        $xmlContent = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!$xmlContent) {
            return false;
        }

        // 解析XML提取文本
        $xml = new SimpleXMLElement($xmlContent);
        $xml->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        // 提取所有文本节点
        $textNodes = $xml->xpath('//w:t');
        $text = '';
        foreach ($textNodes as $node) {
            $text .= (string)$node . ' ';
        }

        // 清理文本
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    } catch (Exception $e) {
        error_log("Extract DOCX text failed: " . $e->getMessage());
        return false;
    }
}
