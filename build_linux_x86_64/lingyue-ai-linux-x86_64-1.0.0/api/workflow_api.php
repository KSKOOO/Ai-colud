<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');


// 检查用户是否登录
if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']) {
    echo json_encode(['success' => false, 'error' => '请先登录']);
    exit;
}

require_once __DIR__ . '/../includes/Database.php';

class WorkflowAPI {
    private $db;
    private $workflowsDir;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->workflowsDir = __DIR__ . '/../storage/workflows/';
        

        if (!is_dir($this->workflowsDir)) {
            mkdir($this->workflowsDir, 0755, true);
        }
    }
    
    
    public function handleRequest() {
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        
        try {
            switch ($action) {
                case 'getWorkflows':
                    return $this->getWorkflows();
                case 'getWorkflow':
                    return $this->getWorkflow($_GET['id'] ?? 0);
                case 'saveWorkflow':
                    return $this->saveWorkflow();
                case 'deleteWorkflow':
                    return $this->deleteWorkflow();
                case 'executeWorkflow':
                    return $this->executeWorkflow();
                case 'runWorkflow':
                    return $this->runWorkflowEngine();
                case 'getExecutionStatus':
                    return $this->getExecutionStatus();
                case 'getQueue':
                    return $this->getQueue();
                case 'cancelWorkflow':
                    return $this->cancelWorkflow();
                case 'uploadFile':
                    return $this->uploadFile();
                case 'getHistory':
                    return $this->getHistory();
                default:
                    return ['status' => 'error', 'message' => '未知操作'];
            }
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    
    private function getWorkflows() {
        $userId = $_SESSION['user']['id'] ?? 0;
        
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, description, created_at, updated_at, 
                       (SELECT COUNT(*) FROM workflow_nodes WHERE workflow_id = w.id) as node_count
                FROM workflows w 
                WHERE user_id = ? OR is_public = 1
                ORDER BY updated_at DESC
            ");
            $stmt->execute([$userId]);
            $workflows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['status' => 'success', 'workflows' => $workflows];
        } catch (PDOException $e) {

            return ['status' => 'success', 'workflows' => $this->getSampleWorkflows()];
        }
    }
    
    
    private function getWorkflow($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM workflows WHERE id = ?");
            $stmt->execute([$id]);
            $workflow = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$workflow) {
                return ['status' => 'error', 'message' => '工作流不存在'];
            }
            

            $stmt = $this->db->prepare("SELECT * FROM workflow_nodes WHERE workflow_id = ?");
            $stmt->execute([$id]);
            $nodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            

            $prompt = $this->convertToComfyFormat($nodes);
            
            return [
                'status' => 'success',
                'workflow' => $workflow,
                'nodes' => $nodes,
                'prompt' => $prompt
            ];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => '数据库错误: ' . $e->getMessage()];
        }
    }
    
    
    private function saveWorkflow() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            $data = $_POST;
        }
        
        $id = $data['id'] ?? null;
        $name = $data['name'] ?? '未命名工作流';
        $description = $data['description'] ?? '';
        $nodes = $data['nodes'] ?? [];
        $connections = $data['connections'] ?? [];
        $userId = $_SESSION['user']['id'] ?? 0;
        
        try {
            if ($id) {

                $stmt = $this->db->prepare("
                    UPDATE workflows 
                    SET name = ?, description = ?, nodes = ?, connections = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$name, $description, json_encode($nodes), json_encode($connections), $id, $userId]);
            } else {

                $stmt = $this->db->prepare("
                    INSERT INTO workflows (name, description, nodes, connections, user_id, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$name, $description, json_encode($nodes), json_encode($connections), $userId]);
                $id = $this->db->lastInsertId();
            }
            

            $workflowData = [
                'id' => $id,
                'name' => $name,
                'description' => $description,
                'nodes' => $nodes,
                'connections' => $connections,
                'last_saved' => date('Y-m-d H:i:s')
            ];
            file_put_contents(
                $this->workflowsDir . 'workflow_' . $id . '.json',
                json_encode($workflowData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
            
            return ['status' => 'success', 'id' => $id, 'message' => '工作流保存成功'];
        } catch (PDOException $e) {

            $id = $id ?? time();
            $workflowData = [
                'id' => $id,
                'name' => $name,
                'description' => $description,
                'nodes' => $nodes,
                'connections' => $connections,
                'last_saved' => date('Y-m-d H:i:s')
            ];
            file_put_contents(
                $this->workflowsDir . 'workflow_' . $id . '.json',
                json_encode($workflowData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
            
            return ['status' => 'success', 'id' => $id, 'message' => '工作流已保存到文件'];
        }
    }
    
    
    private function deleteWorkflow() {
        $id = $_POST['id'] ?? 0;
        $userId = $_SESSION['user']['id'] ?? 0;
        
        try {
            $stmt = $this->db->prepare("DELETE FROM workflows WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            

            $filePath = $this->workflowsDir . 'workflow_' . $id . '.json';
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            return ['status' => 'success', 'message' => '工作流已删除'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    
    private function executeWorkflow() {
        $data = json_decode(file_get_contents('php://input'), true);
        $prompt = $data['prompt'] ?? [];
        $workflowId = $data['workflow_id'] ?? null;
        
        if (empty($prompt)) {
            return ['status' => 'error', 'message' => '工作流为空'];
        }
        

        $promptId = uniqid('prompt_');
        

        $queueItem = [
            'prompt_id' => $promptId,
            'workflow_id' => $workflowId,
            'prompt' => $prompt,
            'status' => 'queued',
            'created_at' => time()
        ];
        

        $queueFile = $this->workflowsDir . 'queue.json';
        $queue = [];
        if (file_exists($queueFile)) {
            $queue = json_decode(file_get_contents($queueFile), true) ?: [];
        }
        $queue[] = $queueItem;
        file_put_contents($queueFile, json_encode($queue));
        

        $this->simulateExecution($promptId, $prompt);
        
        return [
            'status' => 'success',
            'prompt_id' => $promptId,
            'message' => '工作流已加入执行队列'
        ];
    }
    
    
    private function simulateExecution($promptId, $prompt) {

        $this->updateQueueStatus($promptId, 'running');
        

        $executionOrder = $this->topologicalSort($prompt);
        

        $historyFile = $this->workflowsDir . 'history/' . $promptId . '.json';
        if (!is_dir(dirname($historyFile))) {
            mkdir(dirname($historyFile), 0755, true);
        }
        
        $history = [
            'prompt_id' => $promptId,
            'prompt' => $prompt,
            'execution_order' => $executionOrder,
            'status' => 'completed',
            'outputs' => [],
            'completed_at' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($historyFile, json_encode($history, JSON_PRETTY_PRINT));
        

        $this->updateQueueStatus($promptId, 'completed');
    }
    
    
    private function topologicalSort($prompt) {
        $graph = [];
        $inDegree = [];
        

        foreach ($prompt as $nodeId => $node) {
            $graph[$nodeId] = [];
            $inDegree[$nodeId] = 0;
        }
        
        foreach ($prompt as $nodeId => $node) {
            if (isset($node['inputs'])) {
                foreach ($node['inputs'] as $input) {
                    if (is_array($input) && count($input) === 2 && is_string($input[0])) {
                        $fromNode = $input[0];
                        if (isset($graph[$fromNode])) {
                            $graph[$fromNode][] = $nodeId;
                            $inDegree[$nodeId]++;
                        }
                    }
                }
            }
        }
        

        $queue = [];
        $result = [];
        
        foreach ($inDegree as $nodeId => $degree) {
            if ($degree === 0) {
                $queue[] = $nodeId;
            }
        }
        
        while (!empty($queue)) {
            $current = array_shift($queue);
            $result[] = $current;
            
            foreach ($graph[$current] as $neighbor) {
                $inDegree[$neighbor]--;
                if ($inDegree[$neighbor] === 0) {
                    $queue[] = $neighbor;
                }
            }
        }
        
        return $result;
    }
    
    
    private function updateQueueStatus($promptId, $status) {
        $queueFile = $this->workflowsDir . 'queue.json';
        if (!file_exists($queueFile)) return;
        
        $queue = json_decode(file_get_contents($queueFile), true) ?: [];
        foreach ($queue as &$item) {
            if ($item['prompt_id'] === $promptId) {
                $item['status'] = $status;
                break;
            }
        }
        file_put_contents($queueFile, json_encode($queue));
    }
    
    
    private function getQueue() {
        $queueFile = $this->workflowsDir . 'queue.json';
        $queue = [];
        if (file_exists($queueFile)) {
            $queue = json_decode(file_get_contents($queueFile), true) ?: [];
        }
        
        return ['status' => 'success', 'queue' => $queue];
    }
    
    
    private function cancelWorkflow() {
        $promptId = $_POST['prompt_id'] ?? '';
        
        $queueFile = $this->workflowsDir . 'queue.json';
        if (!file_exists($queueFile)) {
            return ['status' => 'error', 'message' => '队列为空'];
        }
        
        $queue = json_decode(file_get_contents($queueFile), true) ?: [];
        $queue = array_filter($queue, function($item) use ($promptId) {
            return $item['prompt_id'] !== $promptId;
        });
        
        file_put_contents($queueFile, json_encode(array_values($queue)));
        
        return ['status' => 'success', 'message' => '工作流已取消'];
    }
    
    
    private function uploadFile() {
        if (!isset($_FILES['file'])) {
            return ['status' => 'error', 'message' => '没有上传文件'];
        }
        
        $uploadDir = __DIR__ . '/../storage/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $file = $_FILES['file'];
        $filename = time() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return [
                'status' => 'success',
                'filename' => $filename,
                'url' => 'storage/uploads/' . $filename
            ];
        }
        
        return ['status' => 'error', 'message' => '文件上传失败'];
    }
    
    
    private function getHistory() {
        $historyDir = $this->workflowsDir . 'history/';
        $history = [];
        
        if (is_dir($historyDir)) {
            $files = glob($historyDir . '*.json');
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                if ($data) {
                    $history[] = $data;
                }
            }
        }
        

        usort($history, function($a, $b) {
            return strtotime($b['completed_at'] ?? 'now') - strtotime($a['completed_at'] ?? 'now');
        });
        
        return ['status' => 'success', 'history' => $history];
    }
    
    
    private function getExecutionStatus() {
        $executionId = $_GET['execution_id'] ?? '';
        
        if (empty($executionId)) {
            return ['status' => 'error', 'message' => '缺少execution_id参数'];
        }
        
        $executionFile = $this->workflowsDir . 'executions/' . $executionId . '.json';
        
        if (!file_exists($executionFile)) {
            return ['status' => 'error', 'message' => '执行记录不存在'];
        }
        
        $execution = json_decode(file_get_contents($executionFile), true);
        
        return [
            'status' => 'success',
            'execution' => $execution
        ];
    }
    
    
    private function runWorkflowEngine() {
        $data = json_decode(file_get_contents('php://input'), true);
        $workflowId = $data['workflow_id'] ?? '';
        $workflowData = $data['workflow_data'] ?? null;
        $inputs = $data['inputs'] ?? [];
        
        if (empty($workflowId) && empty($workflowData)) {
            return ['status' => 'error', 'message' => '缺少workflow_id或workflow_data'];
        }
        
        $workflow = null;
        

        if ($workflowData) {
            $workflow = [
                'id' => $workflowId ?: 'temp_' . uniqid(),
                'name' => '临时工作流',
                'workflow_data' => is_array($workflowData) ? json_encode($workflowData) : $workflowData
            ];
        } else {

            $stmt = $this->db->prepare("SELECT * FROM workflows WHERE id = ?");
            $stmt->execute([$workflowId]);
            $workflow = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$workflow) {
                return ['status' => 'error', 'message' => '工作流不存在'];
            }
        }
        

        require_once __DIR__ . '/../lib/ComfyUIWorkflowEngine.php';
        $engine = new ComfyUIWorkflowEngine();
        $executionId = $engine->executeWorkflow($workflow, $inputs);
        
        return [
            'status' => 'success',
            'execution_id' => $executionId,
            'message' => '工作流已启动'
        ];
    }
    
    
    private function convertToComfyFormat($nodes) {
        $prompt = [];
        
        foreach ($nodes as $node) {
            $nodeId = $node['id'];
            $prompt[$nodeId] = [
                'class_type' => $node['type'],
                'inputs' => json_decode($node['config'] ?? '{}', true)
            ];
        }
        
        return $prompt;
    }
    
    
    private function getSampleWorkflows() {
        return [
            [
                'id' => 1,
                'name' => 'AI 文本生成工作流',
                'description' => '使用 GPT 模型生成文本内容',
                'node_count' => 3,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'name' => '图像处理工作流',
                'description' => '上传图像并进行 AI 处理',
                'node_count' => 4,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];
    }
}


$api = new WorkflowAPI();
$response = $api->handleRequest();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
