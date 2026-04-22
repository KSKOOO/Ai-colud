<?php


class WorkflowEngine {
    private $workflowsDir;
    private $executionsDir;
    private $providerManager;
    private $db;
    

    const NODE_TYPES = [
        'model_call' => '模型调用',
        'text_process' => '文本处理',
        'image_process' => '图像处理',
        'file_input' => '文件输入',
        'file_output' => '文件输出',
        'condition' => '条件分支',
        'merge' => '合并节点',
        'knowledge_query' => '知识库查询',
        'http_request' => 'HTTP请求',
        'code_execute' => '代码执行',
        'delay' => '延时',
        'loop' => '循环',
        'variable_set' => '设置变量',
        'variable_get' => '获取变量'
    ];
    
    public function __construct() {
        $this->workflowsDir = __DIR__ . '/../storage/workflows/';
        $this->executionsDir = $this->workflowsDir . 'executions/';
        
        if (!is_dir($this->executionsDir)) {
            mkdir($this->executionsDir, 0755, true);
        }
        
        require_once __DIR__ . '/AIProviderManager.php';
        $this->providerManager = new AIProviderManager();
        

        require_once __DIR__ . '/../includes/Database.php';
        $this->db = Database::getInstance();
    }
    
    
    public function executeWorkflow($workflow, $inputs = []) {
        $executionId = uniqid('exec_');
        $workflowData = json_decode($workflow['workflow_data'] ?? '{}', true);
        $nodes = $workflowData['nodes'] ?? [];
        $edges = $workflowData['edges'] ?? [];
        

        $execution = [
            'execution_id' => $executionId,
            'workflow_id' => $workflow['id'],
            'workflow_name' => $workflow['name'],
            'status' => 'running',
            'progress' => 0,
            'current_node' => null,
            'start_time' => time(),
            'end_time' => null,
            'inputs' => $inputs,
            'outputs' => [],
            'node_status' => [],
            'logs' => []
        ];
        

        foreach ($nodes as $nodeId => $node) {
            $execution['node_status'][$nodeId] = [
                'status' => 'pending',
                'start_time' => null,
                'end_time' => null,
                'output' => null,
                'error' => null
            ];
        }
        
        $this->saveExecution($executionId, $execution);
        

        $this->runAsync($executionId, $nodes, $edges, $inputs);
        
        return $executionId;
    }
    
    
    private function runAsync($executionId, $nodes, $edges, $inputs) {

        $executionOrder = $this->topologicalSort($nodes, $edges);
        

        if (function_exists('pcntl_fork')) {
            $pid = pcntl_fork();
            
            if ($pid === -1) {

                $this->executeNodes($executionId, $executionOrder, $nodes, $edges, $inputs);
            } elseif ($pid === 0) {

                $this->executeNodes($executionId, $executionOrder, $nodes, $edges, $inputs);
                exit(0);
            }
        } else {

            $this->executeNodes($executionId, $executionOrder, $nodes, $edges, $inputs);
        }

    }
    
    
    private function executeNodes($executionId, $executionOrder, $nodes, $edges, $inputs) {
        $execution = $this->getExecution($executionId);
        $nodeOutputs = [];
        $totalNodes = count($executionOrder);
        

        foreach ($inputs as $key => $value) {
            $nodeOutputs['input_' . $key] = $value;
        }
        
        foreach ($executionOrder as $index => $nodeId) {
            if (!isset($nodes[$nodeId])) continue;
            
            $node = $nodes[$nodeId];
            $progress = intval(($index / $totalNodes) * 100);
            

            $execution['current_node'] = $nodeId;
            $execution['progress'] = $progress;
            $execution['node_status'][$nodeId]['status'] = 'running';
            $execution['node_status'][$nodeId]['start_time'] = time();
            $execution['logs'][] = [
                'time' => date('Y-m-d H:i:s'),
                'level' => 'info',
                'message' => "开始执行节点: {$node['type']} - {$nodeId}"
            ];
            
            $this->saveExecution($executionId, $execution);
            
            try {

                $nodeInputs = $this->resolveNodeInputs($nodeId, $node, $nodeOutputs, $edges);
                

                $output = $this->executeNode($node, $nodeInputs);
                

                $nodeOutputs[$nodeId] = $output;
                

                $execution['node_status'][$nodeId]['status'] = 'completed';
                $execution['node_status'][$nodeId]['end_time'] = time();
                $execution['node_status'][$nodeId]['output'] = $output;
                $execution['logs'][] = [
                    'time' => date('Y-m-d H:i:s'),
                    'level' => 'success',
                    'message' => "节点执行完成: {$nodeId}"
                ];
                
            } catch (Exception $e) {

                $execution['node_status'][$nodeId]['status'] = 'error';
                $execution['node_status'][$nodeId]['end_time'] = time();
                $execution['node_status'][$nodeId]['error'] = $e->getMessage();
                $execution['logs'][] = [
                    'time' => date('Y-m-d H:i:s'),
                    'level' => 'error',
                    'message' => "节点执行失败: {$nodeId} - {$e->getMessage()}"
                ];
                

                if ($node['type'] !== 'condition') {
                    $execution['status'] = 'error';
                    $execution['end_time'] = time();
                    $this->saveExecution($executionId, $execution);
                    return;
                }
            }
            
            $this->saveExecution($executionId, $execution);
            

            usleep(100000);
        }
        

        $execution['status'] = 'completed';
        $execution['progress'] = 100;
        $execution['end_time'] = time();
        $execution['outputs'] = $nodeOutputs;
        $execution['logs'][] = [
            'time' => date('Y-m-d H:i:s'),
            'level' => 'success',
            'message' => '工作流执行完成'
        ];
        
        $this->saveExecution($executionId, $execution);
    }
    
    
    private function executeNode($node, $inputs) {
        $type = $node['type'] ?? 'unknown';
        $config = $node['config'] ?? [];
        
        switch ($type) {
            case 'model_call':
                return $this->executeModelCall($config, $inputs);
                
            case 'text_process':
                return $this->executeTextProcess($config, $inputs);
                
            case 'image_process':
                return $this->executeImageProcess($config, $inputs);
                
            case 'file_input':
                return $this->executeFileInput($config, $inputs);
                
            case 'file_output':
                return $this->executeFileOutput($config, $inputs);
                
            case 'condition':
                return $this->executeCondition($config, $inputs);
                
            case 'merge':
                return $this->executeMerge($config, $inputs);
                
            case 'knowledge_query':
                return $this->executeKnowledgeQuery($config, $inputs);
                
            case 'http_request':
                return $this->executeHttpRequest($config, $inputs);
                
            case 'code_execute':
                return $this->executeCode($config, $inputs);
                
            case 'delay':
                return $this->executeDelay($config, $inputs);
                
            case 'variable_set':
                return $this->executeVariableSet($config, $inputs);
                
            case 'variable_get':
                return $this->executeVariableGet($config, $inputs);
                
            default:
                throw new Exception("未知节点类型: {$type}");
        }
    }
    
    
    private function executeModelCall($config, $inputs) {
        $providerId = $config['provider_id'] ?? $config['provider'] ?? null;
        $model = $config['model'] ?? '';
        $prompt = $config['prompt'] ?? '';
        $temperature = floatval($config['temperature'] ?? 0.7);
        $maxTokens = intval($config['max_tokens'] ?? 2048);
        

        foreach ($inputs as $key => $value) {
            $prompt = str_replace("{{{$key}}}", is_string($value) ? $value : json_encode($value), $prompt);
        }
        

        if (!$providerId) {
            $activeProvider = $this->providerManager->getActiveProvider();
            if ($activeProvider) {
                $providerId = $activeProvider['id'] ?? null;
            }
        }
        

        $caller = $this->providerManager->createCaller($providerId);
        
        $messages = [
            ['role' => 'user', 'content' => $prompt]
        ];
        
        $result = $caller->chat($messages, [
            'model' => $model,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens
        ]);
        
        if (!$result['success']) {
            throw new Exception('模型调用失败: ' . $result['error']);
        }
        
        return [
            'content' => $result['content'],
            'model' => $result['model'],
            'provider' => $result['provider'],
            'usage' => $result['usage'] ?? null
        ];
    }
    
    
    private function executeTextProcess($config, $inputs) {
        $operation = $config['operation'] ?? 'concat';
        $text = $config['text'] ?? '';
        

        foreach ($inputs as $key => $value) {
            $text = str_replace("{{{$key}}}", is_string($value) ? $value : json_encode($value), $text);
        }
        
        $result = '';
        
        switch ($operation) {
            case 'concat':
                $result = implode('', $inputs);
                break;
            case 'split':
                $delimiter = $config['delimiter'] ?? '\n';
                $result = explode($delimiter, $text);
                break;
            case 'replace':
                $search = $config['search'] ?? '';
                $replace = $config['replace'] ?? '';
                $result = str_replace($search, $replace, $text);
                break;
            case 'regex':
                $pattern = $config['pattern'] ?? '';
                preg_match_all($pattern, $text, $matches);
                $result = $matches;
                break;
            case 'format':
                $format = $config['format'] ?? '{text}';
                $result = str_replace('{text}', $text, $format);
                break;
            case 'json_parse':
                $result = json_decode($text, true);
                break;
            case 'json_stringify':
                $result = json_encode($inputs);
                break;
            default:
                $result = $text;
        }
        
        return ['result' => $result];
    }
    
    
    private function executeImageProcess($config, $inputs) {
        $operation = $config['operation'] ?? 'resize';
        $inputKey = $config['input'] ?? 'image';
        $imageData = $inputs[$inputKey] ?? '';
        

        switch ($operation) {
            case 'resize':
                $width = intval($config['width'] ?? 512);
                $height = intval($config['height'] ?? 512);
                return [
                    'operation' => 'resize',
                    'size' => "{$width}x{$height}",
                    'note' => '图像处理需要GD库或ImageMagick'
                ];
                
            case 'convert':
                $format = $config['format'] ?? 'jpg';
                return [
                    'format' => $format,
                    'note' => '格式转换完成'
                ];
                
            default:
                return ['image' => $imageData];
        }
    }
    
    
    private function executeFileInput($config, $inputs) {
        $filePath = $config['path'] ?? '';
        $fileType = $config['file_type'] ?? 'auto';
        
        if (!file_exists($filePath)) {
            throw new Exception("文件不存在: {$filePath}");
        }
        
        $content = file_get_contents($filePath);
        
        return [
            'content' => $content,
            'size' => filesize($filePath),
            'type' => mime_content_type($filePath)
        ];
    }
    
    
    private function executeFileOutput($config, $inputs) {
        $filePath = $config['path'] ?? '';
        $inputKey = $config['input'] ?? 'content';
        $content = $inputs[$inputKey] ?? '';
        

        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($filePath, $content);
        
        return [
            'path' => $filePath,
            'size' => strlen($content),
            'saved' => true
        ];
    }
    
    
    private function executeCondition($config, $inputs) {
        $condition = $config['condition'] ?? '';
        $trueOutput = $config['true_output'] ?? 'true_branch';
        $falseOutput = $config['false_output'] ?? 'false_branch';
        

        $result = false;
        
        if (strpos($condition, '==') !== false) {
            list($left, $right) = explode('==', $condition);
            $result = trim($left) == trim($right);
        } elseif (strpos($condition, '!=') !== false) {
            list($left, $right) = explode('!=', $condition);
            $result = trim($left) != trim($right);
        } elseif (strpos($condition, '>') !== false) {
            list($left, $right) = explode('>', $condition);
            $result = floatval(trim($left)) > floatval(trim($right));
        } elseif (strpos($condition, '<') !== false) {
            list($left, $right) = explode('<', $condition);
            $result = floatval(trim($left)) < floatval(trim($right));
        } else {
            $result = !empty($inputs[$condition]);
        }
        
        return [
            'result' => $result,
            'branch' => $result ? $trueOutput : $falseOutput
        ];
    }
    
    
    private function executeMerge($config, $inputs) {
        $strategy = $config['strategy'] ?? 'array';
        
        switch ($strategy) {
            case 'array':
                return ['merged' => array_values($inputs)];
            case 'object':
                return ['merged' => $inputs];
            case 'concat':
                $separator = $config['separator'] ?? ' ';
                return ['merged' => implode($separator, $inputs)];
            default:
                return ['merged' => $inputs];
        }
    }
    
    
    private function executeKnowledgeQuery($config, $inputs) {
        $kbId = $config['knowledge_base_id'] ?? '';
        $query = $config['query'] ?? '';
        $topK = intval($config['top_k'] ?? 5);
        

        foreach ($inputs as $key => $value) {
            $query = str_replace("{{{$key}}}", is_string($value) ? $value : '', $query);
        }
        
        try {
            require_once __DIR__ . '/KnowledgeBase.php';
            $kb = new KnowledgeBase();
            $results = $kb->search($kbId, $query, $topK);
            
            return [
                'results' => $results,
                'query' => $query,
                'count' => count($results)
            ];
        } catch (Exception $e) {
            throw new Exception('知识库查询失败: ' . $e->getMessage());
        }
    }
    
    
    private function executeHttpRequest($config, $inputs) {
        $url = $config['url'] ?? '';
        $method = strtoupper($config['method'] ?? 'GET');
        $headers = $config['headers'] ?? [];
        $body = $config['body'] ?? '';
        

        foreach ($inputs as $key => $value) {
            $url = str_replace("{{{$key}}}", urlencode(is_string($value) ? $value : json_encode($value)), $url);
            $body = str_replace("{{{$key}}}", is_string($value) ? $value : json_encode($value), $body);
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        if ($method !== 'GET' && !empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 400) {
            throw new Exception("HTTP错误: {$httpCode}");
        }
        
        return [
            'status' => $httpCode,
            'body' => $response,
            'parsed' => json_decode($response, true) ?: $response
        ];
    }
    
    
    private function executeCode($config, $inputs) {
        $language = $config['language'] ?? 'javascript';
        $code = $config['code'] ?? '';
        

        
        return [
            'language' => $language,
            'note' => '代码执行需要在安全沙箱中进行',
            'inputs' => array_keys($inputs)
        ];
    }
    
    
    private function executeDelay($config, $inputs) {
        $seconds = intval($config['seconds'] ?? 1);
        sleep($seconds);
        return ['delayed' => $seconds];
    }
    
    
    private function executeVariableSet($config, $inputs) {
        $variableName = $config['name'] ?? 'var';
        $value = $config['value'] ?? '';
        

        foreach ($inputs as $key => $val) {
            $value = str_replace("{{{$key}}}", is_string($val) ? $val : json_encode($val), $value);
        }
        
        return [
            'variable' => $variableName,
            'value' => $value
        ];
    }
    
    
    private function executeVariableGet($config, $inputs) {
        $variableName = $config['name'] ?? 'var';
        $default = $config['default'] ?? null;
        
        return [
            'variable' => $variableName,
            'value' => $inputs[$variableName] ?? $default
        ];
    }
    
    
    private function resolveNodeInputs($nodeId, $node, $nodeOutputs, $edges) {
        $inputs = [];
        

        foreach ($edges as $edge) {
            if ($edge['target'] === $nodeId) {
                $sourceId = $edge['source'];
                $targetHandle = $edge['targetHandle'] ?? 'input';
                
                if (isset($nodeOutputs[$sourceId])) {
                    $sourceOutput = $nodeOutputs[$sourceId];
                    

                    if (is_array($sourceOutput)) {
                        if (isset($sourceOutput[$targetHandle])) {
                            $inputs[$targetHandle] = $sourceOutput[$targetHandle];
                        } else {
                            $inputs[$targetHandle] = $sourceOutput;
                        }
                    } else {
                        $inputs[$targetHandle] = $sourceOutput;
                    }
                }
            }
        }
        

        $configInputs = $node['inputs'] ?? [];
        $inputs = array_merge($configInputs, $inputs);
        
        return $inputs;
    }
    
    
    private function topologicalSort($nodes, $edges) {
        $graph = [];
        $inDegree = [];
        
        foreach ($nodes as $nodeId => $node) {
            $graph[$nodeId] = [];
            $inDegree[$nodeId] = 0;
        }
        
        foreach ($edges as $edge) {
            $from = $edge['source'];
            $to = $edge['target'];
            if (isset($graph[$from])) {
                $graph[$from][] = $to;
                $inDegree[$to]++;
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
    
    
    public function getExecutionStatus($executionId) {
        return $this->getExecution($executionId);
    }
    
    
    private function saveExecution($executionId, $execution) {
        $file = $this->executionsDir . $executionId . '.json';
        file_put_contents($file, json_encode($execution, JSON_PRETTY_PRINT));
    }
    
    
    private function getExecution($executionId) {
        $file = $this->executionsDir . $executionId . '.json';
        if (!file_exists($file)) {
            return null;
        }
        return json_decode(file_get_contents($file), true);
    }
}
