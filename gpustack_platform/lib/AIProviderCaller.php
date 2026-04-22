<?php


class AIProviderCaller {
    private $provider;
    private $typeInfo;
    private $config;
    
    public function __construct($provider, $typeInfo) {
        $this->provider = $provider;
        $this->typeInfo = $typeInfo;
        $this->config = $provider['config'];
    }
    
    
    public function chat($messages, $options = []) {
        $apiFormat = $this->typeInfo['api_format'];
        
        switch ($apiFormat) {
            case 'ollama':
                return $this->callOllama($messages, $options);
            case 'openai':
                return $this->callOpenAI($messages, $options);
            case 'azure':
                return $this->callAzure($messages, $options);
            case 'anthropic':
                return $this->callAnthropic($messages, $options);
            case 'gemini':
                return $this->callGemini($messages, $options);
            case 'hunyuan':
                return $this->callHunyuan($messages, $options);
            default:
                return $this->callOpenAI($messages, $options);
        }
    }
    
    
    public function chatStream($messages, $options = []) {
        $options['stream'] = true;
        return $this->chat($messages, $options);
    }
    
    
    private function callOllama($messages, $options) {
        $baseUrl = $this->config['base_url'];
        $model = $options['model'] ?? $this->config['default_model'] ?? 'llama2';
        

        $ollamaMessages = [];
        $systemPrompt = '';
        
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemPrompt = $msg['content'];
            } else {
                $ollamaMessages[] = $msg;
            }
        }
        
        $payload = [
            'model' => $model,
            'messages' => $ollamaMessages,
            'stream' => $options['stream'] ?? false,
            'options' => [
                'temperature' => $options['temperature'] ?? $this->config['temperature'] ?? 0.7,
                'num_predict' => $options['max_tokens'] ?? $this->config['max_tokens'] ?? 2048
            ]
        ];
        
        if ($systemPrompt) {
            $payload['system'] = $systemPrompt;
        }
        
        $response = $this->httpPost($baseUrl . '/api/chat', $payload, [], $this->config['timeout'] ?? 120);
        
        if ($response['error']) {
            return $this->formatError($response['error']);
        }
        
        $result = json_decode($response['body'], true);
        
        if (isset($result['message']['content'])) {
            return $this->formatSuccess($result['message']['content'], $model, $result);
        }
        
        return $this->formatError('Invalid response format', $response['body']);
    }
    
    
    private function callOpenAI($messages, $options) {
        $baseUrl = $this->config['base_url'];
        $model = $options['model'] ?? $this->config['default_model'] ?? 'gpt-3.5-turbo';
        
        $headers = ['Content-Type: application/json'];
        if (!empty($this->config['api_key'])) {
            $headers[] = 'Authorization: Bearer ' . $this->config['api_key'];
        }
        
        // 检查是否是千问模型 - 千问API不支持system角色
        $isQwen = (strpos($model, 'qwen') !== false) || 
                  (strpos($baseUrl, 'dashscope') !== false) ||
                  ($this->provider['type'] ?? '') === 'qwen';
        
        // 处理消息格式
        $processedMessages = $messages;
        if ($isQwen) {
            $processedMessages = $this->convertMessagesForQwen($messages);
            // 千问API使用不同的多模态格式
            $processedMessages = $this->convertToQwenMultimodalFormat($processedMessages);
        }
        
        $payload = [
            'model' => $model,
            'messages' => $processedMessages,
            'temperature' => $options['temperature'] ?? $this->config['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? $this->config['max_tokens'] ?? 2048,
            'stream' => $options['stream'] ?? false
        ];
        

        if ($payload['stream']) {
            return $this->handleStreamRequest($baseUrl . '/chat/completions', $payload, $headers);
        }
        
        $response = $this->httpPost($baseUrl . '/chat/completions', $payload, $headers, $this->config['timeout'] ?? 120);
        
        if ($response['error']) {
            return $this->formatError($response['error']);
        }
        
        $result = json_decode($response['body'], true);
        
        if (isset($result['choices'][0]['message']['content'])) {
            return $this->formatSuccess(
                $result['choices'][0]['message']['content'],
                $model,
                $result,
                $result['usage'] ?? null
            );
        }
        
        if (isset($result['error'])) {
            return $this->formatError($result['error']['message'] ?? 'API Error', $result);
        }
        
        return $this->formatError('Invalid response format', $response['body']);
    }
    
    
    /**
     * 转换消息格式以适应千问API
     * 千问API不支持system角色，需要将system消息合并到user消息中
     * 同时支持多模态内容（图片）
     */
    private function convertMessagesForQwen($messages) {
        $systemContent = '';
        $result = [];
        
        // 第一步：收集所有system消息
        foreach ($messages as $msg) {
            if (isset($msg['role']) && $msg['role'] === 'system') {
                $systemContent .= ($systemContent ? "\n" : "") . ($msg['content'] ?? '');
            }
        }
        
        // 第二步：处理非system消息
        $firstUser = true;
        foreach ($messages as $msg) {
            if (!isset($msg['role']) || $msg['role'] === 'system') {
                continue;
            }
            
            $role = $msg['role'];
            $content = $msg['content'] ?? '';
            
            // 处理多模态内容（数组格式）
            if (is_array($content)) {
                // 已经是多模态格式，直接保留
                $newMsg = [
                    'role' => $role,
                    'content' => $content
                ];
                
                // 如果是第一条user消息且system内容不为空，在文本前添加system
                if ($role === 'user' && $firstUser && !empty($systemContent)) {
                    // 找到第一个text类型并添加system内容
                    foreach ($newMsg['content'] as &$item) {
                        if ($item['type'] === 'text') {
                            $item['text'] = $systemContent . "\n\n" . $item['text'];
                            break;
                        }
                    }
                    $firstUser = false;
                }
                
                $result[] = $newMsg;
                continue;
            }
            
            // 确保content不为空
            if (empty($content)) {
                $content = ' ';
            }
            
            // 如果是第一条user消息且system内容不为空，合并它们
            if ($role === 'user' && $firstUser && !empty($systemContent)) {
                $content = $systemContent . "\n\n" . $content;
                $firstUser = false;
            }
            
            $result[] = [
                'role' => $role,
                'content' => $content
            ];
        }
        
        // 如果没有user消息，但有system内容，创建一个user消息
        if (empty($result) && !empty($systemContent)) {
            $result[] = [
                'role' => 'user',
                'content' => $systemContent
            ];
        }
        
        // 确保结果不为空
        if (empty($result)) {
            $result[] = [
                'role' => 'user',
                'content' => '请回复'
            ];
        }
        
        return $result;
    }
    
    
    /**
     * 将多模态消息转换为千问API格式
     * 千问使用 content 数组格式，图片使用 image_url 类型
     */
    private function convertToQwenMultimodalFormat($messages) {
        $result = [];
        
        foreach ($messages as $msg) {
            $role = $msg['role'];
            $content = $msg['content'];
            
            // 如果content是数组（多模态格式）
            if (is_array($content)) {
                $newContent = [];
                foreach ($content as $item) {
                    if (!isset($item['type'])) {
                        continue;
                    }
                    
                    if ($item['type'] === 'text' && isset($item['text'])) {
                        $newContent[] = [
                            'type' => 'text',
                            'text' => $item['text']
                        ];
                    } elseif ($item['type'] === 'image_url' && isset($item['image_url'])) {
                        // 千问支持OpenAI格式的image_url
                        $newContent[] = [
                            'type' => 'image_url',
                            'image_url' => $item['image_url']
                        ];
                    }
                }
                
                // 确保content不为空
                if (empty($newContent)) {
                    $newContent = [['type' => 'text', 'text' => '请分析']];
                }
                
                $result[] = [
                    'role' => $role,
                    'content' => $newContent
                ];
            } else {
                // 纯文本消息
                $result[] = $msg;
            }
        }
        
        return $result;
    }
    
    
    private function callAzure($messages, $options) {
        $baseUrl = $this->config['base_url'];
        $model = $options['model'] ?? $this->config['default_model'] ?? 'gpt-35-turbo';
        $apiVersion = $this->config['api_version'] ?? '2024-02-01';
        
        $headers = [
            'Content-Type: application/json',
            'api-key: ' . $this->config['api_key']
        ];
        
        $payload = [
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? $this->config['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? $this->config['max_tokens'] ?? 2048,
            'stream' => $options['stream'] ?? false
        ];
        
        $url = $baseUrl . '/openai/deployments/' . $model . '/chat/completions?api-version=' . $apiVersion;
        
        $response = $this->httpPost($url, $payload, $headers, $this->config['timeout'] ?? 120);
        
        if ($response['error']) {
            return $this->formatError($response['error']);
        }
        
        $result = json_decode($response['body'], true);
        
        if (isset($result['choices'][0]['message']['content'])) {
            return $this->formatSuccess(
                $result['choices'][0]['message']['content'],
                $model,
                $result,
                $result['usage'] ?? null
            );
        }
        
        return $this->formatError('Invalid response format', $response['body']);
    }
    
    
    private function callAnthropic($messages, $options) {
        $baseUrl = $this->config['base_url'];
        $model = $options['model'] ?? $this->config['default_model'] ?? 'claude-3-sonnet-20240229';
        

        $systemPrompt = '';
        $anthropicMessages = [];
        
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemPrompt = $msg['content'];
            } else {
                $anthropicMessages[] = [
                    'role' => $msg['role'] === 'assistant' ? 'assistant' : 'user',
                    'content' => $msg['content']
                ];
            }
        }
        
        $headers = [
            'Content-Type: application/json',
            'x-api-key: ' . $this->config['api_key'],
            'anthropic-version: 2023-06-01'
        ];
        
        $payload = [
            'model' => $model,
            'max_tokens' => $options['max_tokens'] ?? $this->config['max_tokens'] ?? 2048,
            'messages' => $anthropicMessages,
            'stream' => $options['stream'] ?? false
        ];
        
        if ($systemPrompt) {
            $payload['system'] = $systemPrompt;
        }
        
        if ($options['temperature']) {
            $payload['temperature'] = $options['temperature'];
        }
        
        $response = $this->httpPost($baseUrl . '/messages', $payload, $headers, $this->config['timeout'] ?? 120);
        
        if ($response['error']) {
            return $this->formatError($response['error']);
        }
        
        $result = json_decode($response['body'], true);
        
        if (isset($result['content'][0]['text'])) {
            return $this->formatSuccess(
                $result['content'][0]['text'],
                $model,
                $result,
                $result['usage'] ?? null
            );
        }
        
        return $this->formatError('Invalid response format', $response['body']);
    }
    
    
    private function callGemini($messages, $options) {
        $baseUrl = $this->config['base_url'];
        $model = $options['model'] ?? $this->config['default_model'] ?? 'gemini-pro';
        $apiKey = $this->config['api_key'];
        

        $geminiContents = [];
        $systemPrompt = '';
        
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemPrompt = $msg['content'];
            } else {
                $geminiContents[] = [
                    'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                    'parts' => [['text' => $msg['content']]]
                ];
            }
        }
        
        $payload = [
            'contents' => $geminiContents,
            'generationConfig' => [
                'temperature' => $options['temperature'] ?? $this->config['temperature'] ?? 0.7,
                'maxOutputTokens' => $options['max_tokens'] ?? $this->config['max_tokens'] ?? 2048
            ]
        ];
        
        if ($systemPrompt) {
            $payload['systemInstruction'] = ['parts' => [['text' => $systemPrompt]]];
        }
        
        $url = $baseUrl . '/models/' . $model . ':generateContent?key=' . $apiKey;
        
        $response = $this->httpPost($url, $payload, ['Content-Type: application/json'], $this->config['timeout'] ?? 120);
        
        if ($response['error']) {
            return $this->formatError($response['error']);
        }
        
        $result = json_decode($response['body'], true);
        
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return $this->formatSuccess(
                $result['candidates'][0]['content']['parts'][0]['text'],
                $model,
                $result
            );
        }
        
        if (isset($result['error'])) {
            return $this->formatError($result['error']['message'] ?? 'API Error', $result);
        }
        
        return $this->formatError('Invalid response format', $response['body']);
    }
    
    
    private function callHunyuan($messages, $options) {
        $baseUrl = $this->config['base_url'];
        

        $useNativeAPI = strpos($baseUrl, 'tencentcloudapi.com') !== false && strpos($baseUrl, '/v1') === false;
        

        $hasSecretKeys = !empty($this->config['secret_id']) && !empty($this->config['secret_key']);
        
        if ($useNativeAPI && $hasSecretKeys) {
            return $this->callHunyuanNative($messages, $options);
        }
        

        return $this->callHunyuanOpenAICompatible($messages, $options);
    }
    
    
    private function callHunyuanOpenAICompatible($messages, $options) {
        $baseUrl = $this->config['base_url'];
        $model = $options['model'] ?? $this->config['default_model'] ?? 'hunyuan-pro';
        

        $apiKey = $this->config['api_key'];
        $secretId = $this->config['secret_id'] ?? '';
        $secretKey = $this->config['secret_key'] ?? '';
        

        if ($secretId && $secretKey) {
            return $this->callHunyuanWithSignature($messages, $options);
        }
        

        $headers = ['Content-Type: application/json'];
        if (!empty($apiKey)) {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        }
        

        $payload = [
            'model' => $model,
            'messages' => $messages
        ];
        

        if (isset($options['temperature']) || isset($this->config['temperature'])) {
            $payload['temperature'] = floatval($options['temperature'] ?? $this->config['temperature'] ?? 0.7);
        }
        

        
        if (isset($options['stream'])) {
            $payload['stream'] = (bool)$options['stream'];
        }
        

        if (isset($options['top_p'])) {
            $payload['top_p'] = floatval($options['top_p']);
        }
        
        if ($payload['stream']) {
            return $this->handleStreamRequest($baseUrl . '/chat/completions', $payload, $headers);
        }
        
        $response = $this->httpPost($baseUrl . '/chat/completions', $payload, $headers, $this->config['timeout'] ?? 120);
        
        if ($response['error']) {
            return $this->formatError($response['error']);
        }
        
        $result = json_decode($response['body'], true);
        
        if (isset($result['choices'][0]['message']['content'])) {
            return $this->formatSuccess(
                $result['choices'][0]['message']['content'],
                $model,
                $result,
                $result['usage'] ?? null
            );
        }
        
        if (isset($result['error'])) {
            return $this->formatError($result['error']['message'] ?? 'API Error', $result);
        }
        
        if (isset($result['Response']['Error'])) {
            return $this->formatError($result['Response']['Error']['Message'] ?? 'Tencent API Error', $result);
        }
        
        return $this->formatError('Invalid response format', $response['body']);
    }
    
    
    private function callHunyuanNative($messages, $options) {
        $secretId = $this->config['secret_id'] ?? $this->config['api_key'] ?? '';
        $secretKey = $this->config['secret_key'] ?? '';
        $region = $this->config['region'] ?? 'ap-guangzhou';
        
        if (empty($secretId) || empty($secretKey)) {
            return $this->formatError('腾讯混元需要提供 SecretId 和 SecretKey');
        }
        
        $service = 'hunyuan';
        $host = 'hunyuan.tencentcloudapi.com';
        $action = 'ChatCompletions';
        $version = '2023-09-01';
        

        $hunyuanMessages = [];
        foreach ($messages as $msg) {
            $hunyuanMessages[] = [
                'Role' => $msg['role'],
                'Content' => $msg['content']
            ];
        }
        

        $payload = [
            'Model' => $options['model'] ?? $this->config['default_model'] ?? 'hunyuan-pro',
            'Messages' => $hunyuanMessages
        ];
        

        if (isset($options['temperature']) || isset($this->config['temperature'])) {
            $payload['Temperature'] = floatval($options['temperature'] ?? $this->config['temperature'] ?? 0.7);
        }
        
        if (isset($options['top_p'])) {
            $payload['TopP'] = floatval($options['top_p']);
        }
        

        
        if (isset($options['stream'])) {
            $payload['Stream'] = (bool)$options['stream'];
        }
        

        $timestamp = time();
        $date = gmdate('Y-m-d', $timestamp);
        
        $payloadJson = json_encode($payload);
        $payloadHash = hash('sha256', $payloadJson);
        

        $httpRequestMethod = 'POST';
        $canonicalUri = '/';
        $canonicalQueryString = '';
        $canonicalHeaders = "content-type:application/json\nhost:{$host}\n";
        $signedHeaders = 'content-type;host';
        
        $canonicalRequest = $httpRequestMethod . "\n" .
            $canonicalUri . "\n" .
            $canonicalQueryString . "\n" .
            $canonicalHeaders . "\n" .
            $signedHeaders . "\n" .
            $payloadHash;
        

        $credentialScope = $date . '/' . $service . '/tc3_request';
        $hashedCanonicalRequest = hash('sha256', $canonicalRequest);
        $stringToSign = "TC3-HMAC-SHA256\n" .
            $timestamp . "\n" .
            $credentialScope . "\n" .
            $hashedCanonicalRequest;
        

        $secretDate = hash_hmac('sha256', $date, 'TC3' . $secretKey, true);
        $secretService = hash_hmac('sha256', $service, $secretDate, true);
        $secretSigning = hash_hmac('sha256', 'tc3_request', $secretService, true);
        $signature = hash_hmac('sha256', $stringToSign, $secretSigning);
        

        $authorization = "TC3-HMAC-SHA256 " .
            "Credential=" . $secretId . "/" . $credentialScope . ", " .
            "SignedHeaders=" . $signedHeaders . ", " .
            "Signature=" . $signature;
        
        $headers = [
            'Content-Type: application/json',
            'Host: ' . $host,
            'Authorization: ' . $authorization,
            'X-TC-Action: ' . $action,
            'X-TC-Version: ' . $version,
            'X-TC-Timestamp: ' . $timestamp,
            'X-TC-Region: ' . $region
        ];
        
        $url = 'https://' . $host;
        $response = $this->httpPost($url, $payload, $headers, $this->config['timeout'] ?? 120);
        
        if ($response['error']) {
            return $this->formatError($response['error']);
        }
        
        $result = json_decode($response['body'], true);
        
        if (isset($result['Response']['Choices'][0]['Message']['Content'])) {
            return $this->formatSuccess(
                $result['Response']['Choices'][0]['Message']['Content'],
                $payload['Model'],
                $result['Response']
            );
        }
        
        if (isset($result['Response']['Error'])) {
            return $this->formatError(
                $result['Response']['Error']['Message'] ?? 'Tencent API Error',
                $result['Response']['Error']
            );
        }
        
        return $this->formatError('Invalid response format', $response['body']);
    }
    
    
    private function callHunyuanWithSignature($messages, $options) {

        return $this->callHunyuanNative($messages, $options);
    }
    
    
    private function handleStreamRequest($url, $payload, $headers) {

        return [
            'stream' => true,
            'url' => $url,
            'payload' => $payload,
            'headers' => $headers,
            'timeout' => $this->config['timeout'] ?? 120,
            'callback' => [$this, 'processStreamChunk']
        ];
    }
    
    
    public function processStreamChunk($chunk) {
        $lines = explode("\n", $chunk);
        $results = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || !str_starts_with($line, 'data: ')) {
                continue;
            }
            
            $data = substr($line, 6);
            
            if ($data === '[DONE]') {
                $results[] = ['done' => true];
                continue;
            }
            
            $json = json_decode($data, true);
            if (!$json) continue;
            

            if (isset($json['choices'][0]['delta']['content'])) {
                $results[] = [
                    'content' => $json['choices'][0]['delta']['content'],
                    'done' => $json['choices'][0]['finish_reason'] !== null
                ];
            }
        }
        
        return $results;
    }
    
    
    private function httpPost($url, $data, $headers = [], $timeout = 120) {
        $ch = curl_init($url);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        return [
            'body' => $body,
            'http_code' => $httpCode,
            'error' => $error
        ];
    }
    
    
    private function formatSuccess($content, $model, $raw, $usage = null) {
        return [
            'success' => true,
            'content' => $content,
            'model' => $model,
            'provider' => $this->provider['id'],
            'usage' => $usage,
            'raw' => $raw
        ];
    }
    
    
    private function formatError($message, $raw = null) {
        return [
            'success' => false,
            'error' => $message,
            'provider' => $this->provider['id'],
            'raw' => $raw
        ];
    }
    
    
    public function getModels() {
        $baseUrl = $this->config['base_url'];
        $headers = [];
        
        if (!empty($this->config['api_key'])) {
            $headers[] = 'Authorization: Bearer ' . $this->config['api_key'];
        }
        
        $ch = curl_init($baseUrl . '/models');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if (isset($result['data'])) {
            return array_map(function($m) {
                return $m['id'];
            }, $result['data']);
        }
        
        return [];
    }
}
