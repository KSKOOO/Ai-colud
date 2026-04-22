<?php


header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../lib/AIProviderManager.php';
require_once __DIR__ . '/../includes/Database.php';

class ExternalAPI {
    private $providerManager;
    private $db;
    private $apiKey;
    private $userId;
    
    public function __construct() {
        $this->providerManager = new AIProviderManager();
        $this->db = Database::getInstance();
        

        $this->authenticate();
    }
    
    
    private function authenticate() {
        $headers = getallheaders();
        $this->apiKey = $headers['X-API-Key'] ?? $headers['x-api-key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
        
        if (empty($this->apiKey)) {
            $this->error('缺少API Key，请在Header中提供 X-API-Key', 401);
        }
        

        try {
            $stmt = $this->db->prepare("SELECT user_id, name, permissions, quota_remaining, rate_limit FROM api_keys WHERE api_key = ? AND enabled = 1 AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)");
            $stmt->execute([$this->apiKey]);
            $keyInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$keyInfo) {
                $this->error('无效的API Key或已过期', 401);
            }
            
            $this->userId = $keyInfo['user_id'];
            

            if ($keyInfo['quota_remaining'] !== null && $keyInfo['quota_remaining'] <= 0) {
                $this->error('API配额已用完', 429);
            }
            

            $this->recordApiCall();
            
        } catch (PDOException $e) {

            $this->userId = 0;
        }
    }
    
    
    private function recordApiCall() {
        try {
            $stmt = $this->db->prepare("UPDATE api_keys SET usage_count = usage_count + 1, last_used_at = CURRENT_TIMESTAMP WHERE api_key = ?");
            $stmt->execute([$this->apiKey]);
        } catch (PDOException $e) {

        }
    }
    
    
    private function deductQuota($tokens) {
        try {
            $stmt = $this->db->prepare("UPDATE api_keys SET quota_remaining = GREATEST(0, quota_remaining - ?) WHERE api_key = ? AND quota_remaining IS NOT NULL");
            $stmt->execute([$tokens, $this->apiKey]);
        } catch (PDOException $e) {

        }
    }
    
    
    public function handleRequest() {
        $path = $_GET['path'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'];
        
        try {
            switch ($path) {

                case 'models':
                    return $this->getModels();
                    
                case 'chat/completions':
                    return $this->chatCompletions();
                    
                case 'completions':
                    return $this->completions();
                    
                case 'embeddings':
                    return $this->embeddings();
                    

                case 'finetuned/models':
                    return $this->getFinetunedModels();
                    
                case 'finetuned/chat':
                    return $this->finetunedChat();
                    

                case 'workflow/run':
                    return $this->runWorkflow();
                    
                case 'workflow/status':
                    return $this->getWorkflowStatus();
                    

                case 'files/upload':
                    return $this->uploadFile();
                    
                case 'files/content':
                    return $this->getFileContent();
                    

                case 'knowledge/query':
                    return $this->queryKnowledge();
                    

                case 'status':
                    return $this->getStatus();
                    
                default:
                    return $this->error('未知接口: ' . $path, 404);
            }
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
    
    
    private function getModels() {
        $providers = $this->providerManager->getProviders(true);
        $models = [];
        
        foreach ($providers as $providerId => $provider) {
            $providerModels = $provider['models'] ?? [];
            
            if (empty($providerModels)) {

                $defaultModel = $provider['config']['default_model'] ?? 'default';
                $providerModels = [$defaultModel];
            }
            
            foreach ($providerModels as $modelName) {
                $models[] = [
                    'id' => $providerId . '/' . $modelName,
                    'object' => 'model',
                    'created' => strtotime($provider['created_at'] ?? 'now'),
                    'owned_by' => $provider['name'],
                    'permission' => []
                ];
            }
        }
        

        $finetunedModels = $this->getFinetunedModelsList();
        $models = array_merge($models, $finetunedModels);
        
        return [
            'object' => 'list',
            'data' => $models
        ];
    }
    
    
    private function getFinetunedModelsList() {
        try {
            $stmt = $this->db->prepare("SELECT id, name, base_model, created_at FROM fine_tuned_models WHERE status = 'completed' AND (user_id = ? OR is_public = 1)");
            $stmt->execute([$this->userId]);
            $models = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $result = [];
            foreach ($models as $model) {
                $result[] = [
                    'id' => 'finetuned:' . $model['id'],
                    'object' => 'model',
                    'created' => strtotime($model['created_at']),
                    'owned_by' => 'platform',
                    'permission' => []
                ];
            }
            return $result;
        } catch (PDOException $e) {
            return [];
        }
    }
    
    
    private function chatCompletions() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $messages = $data['messages'] ?? [];
        $model = $data['model'] ?? '';
        $temperature = $data['temperature'] ?? 0.7;
        $maxTokens = $data['max_tokens'] ?? 2048;
        $stream = $data['stream'] ?? false;
        
        if (empty($messages)) {
            return $this->error('messages不能为空');
        }
        

        $providerId = null;
        $modelName = $model;
        
        if (strpos($model, '/') !== false) {
            list($providerId, $modelName) = explode('/', $model, 2);
        }
        

        if (strpos($model, 'finetuned:') === 0) {
            return $this->callFinetunedModel($model, $messages, $temperature, $maxTokens);
        }
        
        try {

            $caller = $this->providerManager->createCaller($providerId);
            

            $provider = $this->providerManager->getProvider($providerId);
            $providerType = $provider['type'] ?? '';
            
            $chatOptions = [
                'model' => $modelName,
                'temperature' => $temperature
            ];
            

            if ($providerType !== 'hunyuan') {
                $chatOptions['max_tokens'] = $maxTokens;
            }
            

            $result = $caller->chat($messages, $chatOptions);
            
            if (!$result['success']) {
                return $this->error('模型调用失败: ' . $result['error']);
            }
            

            $inputText = '';
            foreach ($messages as $msg) {
                $inputText .= $msg['content'] ?? '';
            }
            $inputTokens = $this->estimateTokens($inputText);
            $outputTokens = $this->estimateTokens($result['content']);
            $totalTokens = $inputTokens + $outputTokens;
            

            $this->deductQuota($totalTokens);
            

            return [
                'id' => 'chatcmpl-' . uniqid(),
                'object' => 'chat.completion',
                'created' => time(),
                'model' => $model,
                'choices' => [
                    [
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => $result['content']
                        ],
                        'finish_reason' => 'stop'
                    ]
                ],
                'usage' => [
                    'prompt_tokens' => $inputTokens,
                    'completion_tokens' => $outputTokens,
                    'total_tokens' => $totalTokens
                ]
            ];
            
        } catch (Exception $e) {
            return $this->error('调用失败: ' . $e->getMessage());
        }
    }
    
    
    private function callFinetunedModel($modelId, $messages, $temperature, $maxTokens) {

        $finetunedId = str_replace('finetuned:', '', $modelId);
        
        try {

            $stmt = $this->db->prepare("SELECT * FROM fine_tuned_models WHERE id = ? AND status = 'completed'");
            $stmt->execute([$finetunedId]);
            $modelInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$modelInfo) {
                return $this->error('训练模型不存在或未完成');
            }
            

            $systemPrompt = $modelInfo['system_prompt'] ?? '你是一个 helpful 的AI助手。';
            $promptTemplate = $modelInfo['prompt_template'] ?? "{system}\n\nUser: {input}\nAssistant:";
            

            $userInput = '';
            foreach ($messages as $msg) {
                if ($msg['role'] === 'user') {
                    $userInput = $msg['content'];
                    break;
                }
            }
            

            $prompt = str_replace(['{system}', '{input}'], [$systemPrompt, $userInput], $promptTemplate);
            

            $baseModel = $modelInfo['base_model'];
            $providerId = $modelInfo['provider_id'] ?? null;
            
            $caller = $this->providerManager->createCaller($providerId);
            $result = $caller->chat([
                ['role' => 'user', 'content' => $prompt]
            ], [
                'model' => $baseModel,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens
            ]);
            
            if (!$result['success']) {
                return $this->error('模型调用失败: ' . $result['error']);
            }
            
            return [
                'id' => 'chatcmpl-' . uniqid(),
                'object' => 'chat.completion',
                'created' => time(),
                'model' => $modelId,
                'choices' => [
                    [
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => $result['content']
                        ],
                        'finish_reason' => 'stop'
                    ]
                ],
                'usage' => [
                    'prompt_tokens' => $this->estimateTokens($prompt),
                    'completion_tokens' => $this->estimateTokens($result['content']),
                    'total_tokens' => 0
                ]
            ];
            
        } catch (PDOException $e) {
            return $this->error('数据库错误: ' . $e->getMessage());
        }
    }
    
    
    private function completions() {
        $data = json_decode(file_get_contents('php://input'), true);
        $prompt = $data['prompt'] ?? '';
        

        $messages = [['role' => 'user', 'content' => $prompt]];
        $data['messages'] = $messages;
        
        $result = $this->chatCompletions();
        

        if (isset($result['choices'])) {
            foreach ($result['choices'] as &$choice) {
                $choice['text'] = $choice['message']['content'] ?? '';
                unset($choice['message']);
            }
        }
        
        return $result;
    }
    
    
    private function embeddings() {
        $data = json_decode(file_get_contents('php://input'), true);
        $input = $data['input'] ?? '';
        $model = $data['model'] ?? 'text-embedding-ada-002';
        

        $embedding = [];
        for ($i = 0; $i < 1536; $i++) {
            $embedding[] = mt_rand(-1000, 1000) / 10000;
        }
        
        return [
            'object' => 'list',
            'data' => [
                [
                    'object' => 'embedding',
                    'embedding' => $embedding,
                    'index' => 0
                ]
            ],
            'model' => $model,
            'usage' => [
                'prompt_tokens' => $this->estimateTokens($input),
                'total_tokens' => $this->estimateTokens($input)
            ]
        ];
    }
    
    
    private function getFinetunedModels() {
        $models = $this->getFinetunedModelsList();
        return [
            'object' => 'list',
            'data' => $models
        ];
    }
    
    
    private function finetunedChat() {
        $data = json_decode(file_get_contents('php://input'), true);
        $model = $data['model'] ?? '';
        
        if (strpos($model, 'finetuned:') !== 0) {
            $model = 'finetuned:' . $model;
        }
        
        $data['model'] = $model;
        $_POST = $data;
        
        return $this->chatCompletions();
    }
    
    
    private function runWorkflow() {
        $data = json_decode(file_get_contents('php://input'), true);
        $workflowId = $data['workflow_id'] ?? '';
        $inputs = $data['inputs'] ?? [];
        
        if (empty($workflowId)) {
            return $this->error('workflow_id不能为空');
        }
        

        try {
            $stmt = $this->db->prepare("SELECT * FROM workflows WHERE id = ? AND (user_id = ? OR is_public = 1)");
            $stmt->execute([$workflowId, $this->userId]);
            $workflow = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$workflow) {
                return $this->error('工作流不存在');
            }
            

            require_once __DIR__ . '/../lib/WorkflowEngine.php';
            $engine = new WorkflowEngine();
            

            $executionId = $engine->executeWorkflow($workflow, $inputs);
            
            return [
                'status' => 'success',
                'execution_id' => $executionId,
                'message' => '工作流已启动'
            ];
            
        } catch (PDOException $e) {
            return $this->error('数据库错误: ' . $e->getMessage());
        } catch (Exception $e) {
            return $this->error('执行失败: ' . $e->getMessage());
        }
    }
    
    
    private function getWorkflowStatus() {
        $executionId = $_GET['execution_id'] ?? '';
        
        if (empty($executionId)) {
            return $this->error('execution_id不能为空');
        }
        
        try {
            require_once __DIR__ . '/../lib/WorkflowEngine.php';
            $engine = new WorkflowEngine();
            $status = $engine->getExecutionStatus($executionId);
            
            return $status;
            
        } catch (Exception $e) {
            return $this->error('获取状态失败: ' . $e->getMessage());
        }
    }
    
    
    private function uploadFile() {
        if (empty($_FILES['file'])) {
            return $this->error('没有上传文件');
        }
        
        $file = $_FILES['file'];
        $uploadDir = __DIR__ . '/../storage/uploads/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileId = uniqid('file_');
        $fileName = $fileId . '_' . basename($file['name']);
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return [
                'id' => $fileId,
                'object' => 'file',
                'bytes' => filesize($filePath),
                'created_at' => time(),
                'filename' => $file['name'],
                'purpose' => $_POST['purpose'] ?? 'general'
            ];
        } else {
            return $this->error('文件上传失败');
        }
    }
    
    
    private function getFileContent() {
        $fileId = $_GET['file_id'] ?? '';
        
        if (empty($fileId)) {
            return $this->error('file_id不能为空');
        }
        
        $uploadDir = __DIR__ . '/../storage/uploads/';
        $files = glob($uploadDir . $fileId . '_*');
        
        if (empty($files)) {
            return $this->error('文件不存在');
        }
        
        $content = file_get_contents($files[0]);
        
        return [
            'file_id' => $fileId,
            'content' => base64_encode($content)
        ];
    }
    
    
    private function queryKnowledge() {
        $data = json_decode(file_get_contents('php://input'), true);
        $kbId = $data['knowledge_base_id'] ?? '';
        $query = $data['query'] ?? '';
        $topK = $data['top_k'] ?? 5;
        
        if (empty($kbId) || empty($query)) {
            return $this->error('knowledge_base_id和query不能为空');
        }
        
        try {

            require_once __DIR__ . '/../lib/KnowledgeBase.php';
            $kb = new KnowledgeBase();
            $results = $kb->search($kbId, $query, $topK);
            
            return [
                'status' => 'success',
                'results' => $results
            ];
            
        } catch (Exception $e) {
            return $this->error('查询失败: ' . $e->getMessage());
        }
    }
    
    
    private function getStatus() {
        return [
            'status' => 'operational',
            'version' => '1.0.0',
            'timestamp' => time(),
            'models_available' => count($this->getModels()['data'] ?? []),
            'features' => [
                'chat' => true,
                'completions' => true,
                'embeddings' => true,
                'finetuned' => true,
                'workflows' => true,
                'files' => true,
                'knowledge' => true
            ]
        ];
    }
    
    
    private function estimateTokens($text) {
        if (empty($text)) return 0;
        

        $chineseChars = preg_match_all('/[\x{4e00}-\x{9fff}]/u', $text, $matches);

        $englishWords = str_word_count(preg_replace('/[\x{4e00}-\x{9fff}]/u', '', $text));
        

        return intval($chineseChars * 2 + $englishWords * 1.3);
    }
    
    
    private function error($message, $code = 400) {
        http_response_code($code);
        return [
            'error' => [
                'message' => $message,
                'type' => 'api_error',
                'code' => $code
            ]
        ];
    }
}


$api = new ExternalAPI();
$response = $api->handleRequest();
echo json_encode($response);
