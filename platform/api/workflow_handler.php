<?php


header('Content-Type: application/json; charset=utf-8');


require_once __DIR__ . '/../lib/AIProviderManager.php';


$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'execute':
            handleExecuteWorkflow();
            break;
        default:
            echo json_encode(['success' => false, 'error' => '未知操作: ' . $action]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}


function handleExecuteWorkflow() {
    $nodesJson = $_POST['nodes'] ?? '[]';
    $connectionsJson = $_POST['connections'] ?? '[]';
    
    $nodes = json_decode($nodesJson, true);
    $connections = json_decode($connectionsJson, true);
    
    if (!is_array($nodes)) {
        echo json_encode(['success' => false, 'error' => '无效的节点数据']);
        return;
    }
    

    $results = executeWorkflowNodes($nodes, $connections);
    
    echo json_encode([
        'success' => true,
        'results' => $results
    ]);
}


function executeWorkflowNodes($nodes, $connections) {
    $results = [];
    $nodeOutputs = [];
    

    $connectionMap = [];
    foreach ($connections as $conn) {
        if (!isset($connectionMap[$conn['from']])) {
            $connectionMap[$conn['from']] = [];
        }
        $connectionMap[$conn['from']][] = $conn;
    }
    

    $executionOrder = getExecutionOrder($nodes, $connections);
    
    foreach ($executionOrder as $nodeId) {
        $node = findNodeById($nodes, $nodeId);
        if (!$node) continue;
        
        $type = $node['type'] ?? '';
        $config = $node['config'] ?? [];
        

        $inputs = getNodeInputs($nodeId, $connections, $nodeOutputs);
        

        $output = executeNode($type, $config, $inputs);
        
        $nodeOutputs[$nodeId] = $output;
        $results[] = [
            'node' => $nodeId,
            'type' => $type,
            'output' => is_string($output) ? $output : json_encode($output, JSON_UNESCAPED_UNICODE)
        ];
    }
    
    return $results;
}


function getNodeInputs($nodeId, $connections, $nodeOutputs) {
    $inputs = [];
    
    foreach ($connections as $conn) {
        if ($conn['to'] === $nodeId) {
            $fromNodeId = $conn['from'];
            if (isset($nodeOutputs[$fromNodeId])) {
                $inputs[] = $nodeOutputs[$fromNodeId];
            }
        }
    }
    
    return $inputs;
}


function findNodeById($nodes, $id) {
    foreach ($nodes as $node) {
        if ($node['id'] === $id) {
            return $node;
        }
    }
    return null;
}


function getExecutionOrder($nodes, $connections) {
    $inDegree = [];
    $graph = [];
    

    foreach ($nodes as $node) {
        $inDegree[$node['id']] = 0;
        $graph[$node['id']] = [];
    }
    

    foreach ($connections as $conn) {
        $from = $conn['from'];
        $to = $conn['to'];
        if (isset($graph[$from])) {
            $graph[$from][] = $to;
            if (isset($inDegree[$to])) {
                $inDegree[$to]++;
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
        
        if (isset($graph[$current])) {
            foreach ($graph[$current] as $neighbor) {
                $inDegree[$neighbor]--;
                if ($inDegree[$neighbor] === 0) {
                    $queue[] = $neighbor;
                }
            }
        }
    }
    
    return $result;
}


function executeNode($type, $config, $inputs) {
    switch ($type) {
        case 'input':
            return $config['text'] ?? '';
            
        case 'output':
            return $inputs[0] ?? '';
            
        case 'ai':
            return callAIModel($config, $inputs);
            
        case 'code':
            return executeCode($config, $inputs);
            
        case 'image_gen':
            return '[图像生成节点：需要配置图像生成API]';
            
        case 'condition':
            return evaluateCondition($config, $inputs);
            
        case 'text_process':
            return processText($config, $inputs[0] ?? '');
            
        case 'json_process':
            return processJSON($config, $inputs[0] ?? '');
            
        case 'data_merge':
            return mergeData($config, $inputs);
            
        case 'text_splitter':
            return splitText($config, $inputs[0] ?? '');
            
        case 'prompt_template':
            return renderTemplate($config, $inputs);
            
        case 'http_request':
            return makeHTTPRequest($config, $inputs[0] ?? '');
            
        case 'variable_set':
            return setVariable($config, $inputs[0] ?? '');
            
        case 'variable_get':
            return getVariable($config);
            
        default:
            return '[错误: 未知节点类型: ' . $type . ']';
    }
}


function callAIModel($config, $inputs) {
    $providerType = $config['provider'] ?? '';
    $model = $config['model'] ?? '';
    $prompt = $config['prompt'] ?? '';
    $temperature = floatval($config['temperature'] ?? 0.7);
    $maxTokens = intval($config['max_tokens'] ?? 2048);
    

    foreach ($inputs as $index => $value) {
        $placeholder = '{{输入' . ($index + 1) . '}}';
        $prompt = str_replace($placeholder, is_string($value) ? $value : json_encode($value), $prompt);
    }
    
    if (empty($providerType)) {
        return '[错误: 未选择AI提供商]';
    }
    
    try {
        $manager = new AIProviderManager();
        $providers = $manager->getProviders(true);
        

        $provider = null;
        

        foreach ($providers as $id => $p) {
            if ($p['type'] === $providerType) {
                $provider = $p;
                break;
            }
        }
        
        if (!$provider) {
            return '[错误: 未找到提供商: ' . $providerType . ']';
        }
        

        if (empty($model) && !empty($provider['models'])) {
            $model = $provider['models'][0];
        }
        
        $apiKey = $provider['config']['api_key'] ?? '';
        $apiUrl = $provider['config']['base_url'] ?? $provider['config']['api_url'] ?? '';
        

        if ($provider['type'] !== 'ollama' && empty($apiKey)) {
            return '[错误: 提供商未配置API Key]';
        }
        
        if (empty($apiUrl)) {
            return '[错误: 提供商未配置API URL]';
        }
        

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => $temperature,
            'max_tokens' => $maxTokens
        ];
        
        $ch = curl_init(rtrim($apiUrl, '/') . '/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($response === false) {
            return '[错误: ' . curl_error($ch) . ']';
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode !== 200) {
            $errorMsg = isset($result['error']['message']) ? $result['error']['message'] : 'HTTP ' . $httpCode;
            return '[API错误: ' . $errorMsg . ']';
        }
        
        if (isset($result['choices'][0]['message']['content'])) {
            return $result['choices'][0]['message']['content'];
        }
        
        return '[错误: 无法解析API响应]';
        
    } catch (Exception $e) {
        return '[错误: ' . $e->getMessage() . ']';
    }
}


function executeCode($config, $inputs) {
    $code = $config['code'] ?? '';
    $input = $inputs[0] ?? '';
    
    if (empty($code)) {
        return '[错误: 代码为空]';
    }
    

    $result = $input;
    

    if (strpos($code, 'return') !== false) {

        preg_match('/return\s+(.+);?/', $code, $matches);
        if (isset($matches[1])) {
            $expr = trim($matches[1]);

            $expr = str_replace('$input', '"' . addslashes($input) . '"', $expr);

            if (strpos($expr, 'strtoupper') !== false) {
                $result = strtoupper($input);
            } elseif (strpos($expr, 'strtolower') !== false) {
                $result = strtolower($input);
            } elseif (strpos($expr, 'strlen') !== false) {
                $result = strlen($input);
            } elseif (strpos($expr, 'substr') !== false) {
                $result = substr($input, 0, 100);
            } else {
                $result = $expr;
            }
        }
    }
    
    return $result;
}


function evaluateCondition($config, $inputs) {
    $condition = $config['condition'] ?? '';
    $input = $inputs[0] ?? '';
    

    if (strpos($condition, 'contains') !== false) {
        preg_match('/contains\s*\(\s*["\'](.+)["\']\s*\)/', $condition, $matches);
        if (isset($matches[1])) {
            return strpos($input, $matches[1]) !== false ? 'true' : 'false';
        }
    }
    
    if (strpos($condition, 'empty') !== false) {
        return empty($input) ? 'true' : 'false';
    }
    
    if (strpos($condition, 'length') !== false) {
        preg_match('/length\s*([<>]=?)\s*(\d+)/', $condition, $matches);
        if (isset($matches[1]) && isset($matches[2])) {
            $op = $matches[1];
            $val = intval($matches[2]);
            $len = strlen($input);
            switch ($op) {
                case '>': return $len > $val ? 'true' : 'false';
                case '<': return $len < $val ? 'true' : 'false';
                case '>=': return $len >= $val ? 'true' : 'false';
                case '<=': return $len <= $val ? 'true' : 'false';
            }
        }
    }
    
    return 'true';
}


function processText($config, $input) {
    $operation = $config['operation'] ?? '大写';
    $param1 = $config['param1'] ?? '';
    $param2 = $config['param2'] ?? '';
    
    switch ($operation) {
        case '大写':
            return strtoupper($input);
        case '小写':
            return strtolower($input);
        case '反转':
            return strrev($input);
        case '截取':
            $start = intval($param1);
            $length = intval($param2);
            return substr($input, $start, $length) ?: $input;
        case '替换':
            if (!empty($param1)) {
                return str_replace($param1, $param2, $input);
            }
            return $input;
        default:
            return $input;
    }
}


function processJSON($config, $input) {
    $operation = $config['operation'] ?? '格式化';
    $field = $config['field'] ?? '';
    
    $data = json_decode($input, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        return '[错误: 无效的JSON输入]';
    }
    
    switch ($operation) {
        case '提取字段':
            if (empty($field)) return '[错误: 未指定字段]';
            $keys = explode('.', $field);
            $result = $data;
            foreach ($keys as $key) {
                if (is_array($result) && isset($result[$key])) {
                    $result = $result[$key];
                } else {
                    return '[错误: 字段不存在: ' . $field . ']';
                }
            }
            return is_string($result) ? $result : json_encode($result, JSON_PRETTY_PRINT);
            
        case '格式化':
            return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
        case '转字符串':
            return json_encode($data, JSON_UNESCAPED_UNICODE);
            
        default:
            return json_encode($data, JSON_PRETTY_PRINT);
    }
}


function mergeData($config, $inputs) {
    $mergeType = $config['mergeType'] ?? '连接';
    
    if (count($inputs) < 2) {
        return '[错误: 需要至少两个输入]';
    }
    
    $dataA = $inputs[0] ?? '';
    $dataB = $inputs[1] ?? '';
    
    switch ($mergeType) {
        case '连接':
            return $dataA . $dataB;
        case '对象合并':
            $objA = json_decode($dataA, true) ?: [];
            $objB = json_decode($dataB, true) ?: [];
            return json_encode(array_merge($objA, $objB), JSON_PRETTY_PRINT);
        case '数组追加':
            $arrA = json_decode($dataA, true) ?: [$dataA];
            $arrB = json_decode($dataB, true) ?: [$dataB];
            return json_encode(array_merge((array)$arrA, (array)$arrB), JSON_PRETTY_PRINT);
        default:
            return $dataA . $dataB;
    }
}


function splitText($config, $input) {
    $splitType = $config['splitType'] ?? '按行';
    $param = $config['param'] ?? '';
    
    switch ($splitType) {
        case '按行':
            $parts = explode("\n", $input);
            break;
        case '按字符数':
            $len = intval($param) ?: 100;
            $parts = str_split($input, $len);
            break;
        case '按段落':
            $parts = explode("\n\n", $input);
            break;
        case '按分隔符':
            $delimiter = $param ?: ',';
            $parts = explode($delimiter, $input);
            break;
        default:
            $parts = [$input];
    }
    
    return json_encode($parts, JSON_PRETTY_PRINT);
}


function renderTemplate($config, $inputs) {
    $template = $config['template'] ?? '';
    
    if (empty($template)) {
        return '[错误: 模板为空]';
    }
    
    $result = $template;
    

    foreach ($inputs as $index => $value) {
        $varName = '变量' . ($index + 1);
        $result = str_replace('{{' . $varName . '}}', $value, $result);
        $result = str_replace('{{变量' . ($index + 1) . '}}', $value, $result);
    }
    
    return $result;
}


function makeHTTPRequest($config, $input) {
    $url = $config['url'] ?? '';
    $method = $config['method'] ?? 'GET';
    $headersStr = $config['headers'] ?? '';
    
    if (empty($url)) {
        return '[错误: URL不能为空]';
    }
    

    $headers = [];
    if (!empty($headersStr)) {
        $lines = explode("\n", $headersStr);
        foreach ($lines as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $headers[trim($parts[0])] = trim($parts[1]);
            }
        }
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    if (!empty($headers)) {
        $headerArr = [];
        foreach ($headers as $k => $v) {
            $headerArr[] = "$k: $v";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArr);
    }
    
    if ($method === 'POST' || $method === 'PUT') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($response === false) {
        return '[错误: ' . curl_error($ch) . ']';
    }
    
    return "[HTTP $httpCode]\n$response";
}


function setVariable($config, $input) {
    $varName = $config['varName'] ?? '';
    
    if (empty($varName)) {
        return '[错误: 变量名不能为空]';
    }
    

    $varFile = sys_get_temp_dir() . '/workflow_var_' . md5($varName) . '.txt';
    file_put_contents($varFile, $input);
    
    return $input;
}


function getVariable($config) {
    $varName = $config['varName'] ?? '';
    
    if (empty($varName)) {
        return '[错误: 变量名不能为空]';
    }
    
    $varFile = sys_get_temp_dir() . '/workflow_var_' . md5($varName) . '.txt';
    
    if (file_exists($varFile)) {
        return file_get_contents($varFile);
    }
    
    return '[错误: 变量不存在: ' . $varName . ']';
}
