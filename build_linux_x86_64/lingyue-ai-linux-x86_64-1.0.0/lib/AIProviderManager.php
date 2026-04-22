<?php


class AIProviderManager {
    private $providers = [];
    private $activeProvider = null;
    private $db;
    

    const PROVIDER_TYPES = [
        'ollama' => [
            'name' => 'Ollama',
            'description' => '本地Ollama部署',
            'icon' => 'fa-server',
            'supports_streaming' => true,
            'requires_api_key' => false,
            'default_url' => 'http://localhost:11434',
            'api_format' => 'ollama',
            'supports_image' => true
        ],
        'openai' => [
            'name' => 'OpenAI',
            'description' => 'OpenAI API (GPT-3.5/GPT-4)',
            'icon' => 'fa-cloud',
            'supports_streaming' => true,
            'requires_api_key' => true,
            'default_url' => 'https://api.openai.com/v1',
            'api_format' => 'openai',
            'supports_image' => true
        ],
        'azure_openai' => [
            'name' => 'Azure OpenAI',
            'description' => '微软Azure OpenAI服务',
            'icon' => 'fa-microsoft',
            'supports_streaming' => true,
            'requires_api_key' => true,
            'default_url' => 'https://{resource}.openai.azure.com',
            'api_format' => 'azure'
        ],
        'anthropic' => [
            'name' => 'Anthropic Claude',
            'description' => 'Claude API',
            'icon' => 'fa-brain',
            'supports_streaming' => true,
            'requires_api_key' => true,
            'default_url' => 'https://api.anthropic.com/v1',
            'api_format' => 'anthropic'
        ],
        'gemini' => [
            'name' => 'Google Gemini',
            'description' => 'Google Gemini API',
            'icon' => 'fa-google',
            'supports_streaming' => true,
            'requires_api_key' => true,
            'default_url' => 'https://generativelanguage.googleapis.com/v1',
            'api_format' => 'gemini'
        ],
        'deepseek' => [
            'name' => 'DeepSeek',
            'description' => 'DeepSeek API (支持DeepSeek-V3/R1)',
            'icon' => 'fa-water',
            'supports_streaming' => true,
            'requires_api_key' => true,
            'default_url' => 'https://api.deepseek.com/v1',
            'api_format' => 'openai',
            'default_models' => ['deepseek-chat', 'deepseek-reasoner']
        ],
        'hunyuan' => [
            'name' => '腾讯混元',
            'description' => '腾讯混元大模型 API',
            'icon' => 'fa-qq',
            'supports_streaming' => true,
            'requires_api_key' => true,
            'default_url' => 'https://hunyuan.tencentcloudapi.com/v1',
            'api_format' => 'hunyuan',
            'default_models' => ['hunyuan-pro', 'hunyuan-standard', 'hunyuan-lite', 'hunyuan-turbo']
        ],
        'zhipu' => [
            'name' => '智谱AI',
            'description' => '智谱GLM API',
            'icon' => 'fa-comment',
            'supports_streaming' => true,
            'requires_api_key' => true,
            'default_url' => 'https://open.bigmodel.cn/api/paas/v4',
            'api_format' => 'openai'
        ],
        'qwen' => [
            'name' => '通义千问',
            'description' => '阿里云通义千问（支持文本、图像、视频生成）',
            'icon' => 'fa-cloud-upload',
            'supports_streaming' => true,
            'requires_api_key' => true,
            'default_url' => 'https://dashscope.aliyuncs.com/api/v1',
            'api_format' => 'openai',
            'supports_image' => true,
            'supports_video' => true,
            'video_model' => 'wanx2.1-t2v-turbo'
        ],
        'moonshot' => [
            'name' => 'Moonshot',
            'description' => '月之暗面Kimi',
            'icon' => 'fa-moon',
            'supports_streaming' => true,
            'requires_api_key' => true,
            'default_url' => 'https://api.moonshot.cn/v1',
            'api_format' => 'openai'
        ],
        'llamacpp' => [
            'name' => 'llama.cpp',
            'description' => '本地llama.cpp服务',
            'icon' => 'fa-microchip',
            'supports_streaming' => true,
            'requires_api_key' => false,
            'default_url' => 'http://localhost:8080',
            'api_format' => 'openai'
        ],
        'vllm' => [
            'name' => 'vLLM',
            'description' => 'vLLM推理服务',
            'icon' => 'fa-bolt',
            'supports_streaming' => true,
            'requires_api_key' => false,
            'default_url' => 'http://localhost:8000/v1',
            'api_format' => 'openai'
        ],
        'xinference' => [
            'name' => 'Xinference',
            'description' => 'Xorbits推理平台',
            'icon' => 'fa-rocket',
            'supports_streaming' => true,
            'requires_api_key' => false,
            'default_url' => 'http://localhost:9997/v1',
            'api_format' => 'openai'
        ],
        'custom_openai' => [
            'name' => '自定义OpenAI',
            'description' => '兼容OpenAI API格式的自定义服务',
            'icon' => 'fa-cog',
            'supports_streaming' => true,
            'requires_api_key' => true,
            'default_url' => 'http://localhost:8000/v1',
            'api_format' => 'openai'
        ],
        'gpustack' => [
            'name' => 'GPUStack',
            'description' => 'GPUStack模型服务',
            'icon' => 'fa-server',
            'supports_streaming' => true,
            'requires_api_key' => false,
            'default_url' => 'http://localhost:8080',
            'api_format' => 'openai'
        ]
    ];
    
    public function __construct($database = null) {
        $this->db = $database;
        $this->loadProviders();
    }
    
    
    public function loadProviders() {
        $configFile = __DIR__ . '/../config/providers.json';
        
        if (file_exists($configFile)) {
            $content = file_get_contents($configFile);
            $this->providers = json_decode($content, true) ?: [];
        } else {

            $this->providers = $this->getDefaultProviders();
            $this->saveProviders();
        }
        

        foreach ($this->providers as $id => $provider) {
            if ($provider['enabled'] && $provider['is_default']) {
                $this->activeProvider = $id;
                break;
            }
        }
        

        if (!$this->activeProvider) {
            foreach ($this->providers as $id => $provider) {
                if ($provider['enabled']) {
                    $this->activeProvider = $id;
                    break;
                }
            }
        }
    }
    
    
    private function getDefaultProviders() {
        return [
            'ollama_local' => [
                'id' => 'ollama_local',
                'type' => 'ollama',
                'name' => '本地Ollama',
                'enabled' => true,
                'is_default' => true,
                'config' => [
                    'base_url' => 'http://localhost:11434',
                    'api_key' => '',
                    'default_model' => 'llama2',
                    'temperature' => 0.7,
                    'max_tokens' => 2048,
                    'timeout' => 120
                ],
                'models' => [],
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
    }
    
    
    public function saveProviders() {
        $configFile = __DIR__ . '/../config/providers.json';
        $dir = dirname($configFile);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($configFile, json_encode($this->providers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    
    public function getProviderTypes() {
        return self::PROVIDER_TYPES;
    }
    
    
    public function getProviders($onlyEnabled = false) {
        if ($onlyEnabled) {
            return array_filter($this->providers, function($p) {
                return $p['enabled'];
            });
        }
        return $this->providers;
    }
    
    
    public function getImageProviders($onlyEnabled = false) {
        $providers = $this->getProviders($onlyEnabled);
        return array_filter($providers, function($p) {
            $type = $p['type'] ?? '';

            if (isset(self::PROVIDER_TYPES[$type]['supports_image'])) {
                return self::PROVIDER_TYPES[$type]['supports_image'];
            }

            $imageSupportedTypes = ['ollama', 'openai', 'azure_openai', 'gemini', 'qwen'];
            return in_array($type, $imageSupportedTypes);
        });
    }
    
    
    public function getProvider($id) {
        return $this->providers[$id] ?? null;
    }
    
    
    public function getActiveProvider() {
        if ($this->activeProvider && isset($this->providers[$this->activeProvider])) {
            return $this->providers[$this->activeProvider];
        }
        return null;
    }
    
    
    public function addProvider($data) {
        $id = $data['id'] ?? uniqid('provider_');
        
        if (isset($this->providers[$id])) {
            throw new Exception("提供商ID已存在: {$id}");
        }
        
        $type = $data['type'] ?? 'custom_openai';
        $typeInfo = self::PROVIDER_TYPES[$type] ?? self::PROVIDER_TYPES['custom_openai'];
        
        $config = [
            'base_url' => rtrim($data['base_url'] ?? $typeInfo['default_url'], '/'),
            'api_key' => $data['api_key'] ?? '',
            'default_model' => $data['default_model'] ?? '',
            'temperature' => floatval($data['temperature'] ?? 0.7),
            'max_tokens' => intval($data['max_tokens'] ?? 2048),
            'timeout' => intval($data['timeout'] ?? 120)
        ];
        

        if ($type === 'hunyuan') {
            if (!empty($data['secret_id'])) {
                $config['secret_id'] = $data['secret_id'];
            }
            if (!empty($data['secret_key'])) {
                $config['secret_key'] = $data['secret_key'];
            }
            $config['region'] = $data['region'] ?? 'ap-guangzhou';
        }
        
        $provider = [
            'id' => $id,
            'type' => $type,
            'name' => $data['name'] ?? $typeInfo['name'],
            'enabled' => $data['enabled'] ?? true,
            'is_default' => $data['is_default'] ?? false,
            'config' => $config,
            'models' => $data['models'] ?? [],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        

        if ($provider['is_default']) {
            foreach ($this->providers as &$p) {
                $p['is_default'] = false;
            }
        }
        
        $this->providers[$id] = $provider;
        $this->saveProviders();
        

        if ($provider['is_default'] || !$this->activeProvider) {
            $this->activeProvider = $id;
        }
        
        return $provider;
    }
    
    
    public function updateProvider($id, $data) {
        if (!isset($this->providers[$id])) {
            throw new Exception("提供商不存在: {$id}");
        }
        
        $provider = &$this->providers[$id];
        

        if (isset($data['name'])) $provider['name'] = $data['name'];
        if (isset($data['enabled'])) $provider['enabled'] = (bool)$data['enabled'];
        if (isset($data['models'])) $provider['models'] = $data['models'];
        

        if (isset($data['base_url'])) {
            $provider['config']['base_url'] = rtrim($data['base_url'], '/');
        }
        if (isset($data['api_key'])) {
            $provider['config']['api_key'] = $data['api_key'];
        }
        if (isset($data['default_model'])) {
            $provider['config']['default_model'] = $data['default_model'];
        }
        if (isset($data['temperature'])) {
            $provider['config']['temperature'] = floatval($data['temperature']);
        }
        if (isset($data['max_tokens'])) {
            $provider['config']['max_tokens'] = intval($data['max_tokens']);
        }
        if (isset($data['timeout'])) {
            $provider['config']['timeout'] = intval($data['timeout']);
        }
        

        if ($provider['type'] === 'hunyuan') {
            if (isset($data['secret_id'])) {
                $provider['config']['secret_id'] = $data['secret_id'];
            }
            if (isset($data['secret_key'])) {
                $provider['config']['secret_key'] = $data['secret_key'];
            }
            if (isset($data['region'])) {
                $provider['config']['region'] = $data['region'];
            }
        }
        

        if (isset($data['is_default']) && $data['is_default']) {
            foreach ($this->providers as $pid => &$p) {
                if ($pid !== $id) {
                    $p['is_default'] = false;
                }
            }
            $provider['is_default'] = true;
            $this->activeProvider = $id;
        }
        
        $provider['updated_at'] = date('Y-m-d H:i:s');
        $this->saveProviders();
        
        return $provider;
    }
    
    
    public function deleteProvider($id) {
        if (!isset($this->providers[$id])) {
            throw new Exception("提供商不存在: {$id}");
        }
        

        $enabledCount = count(array_filter($this->providers, function($p) {
            return $p['enabled'];
        }));
        
        if ($enabledCount <= 1 && $this->providers[$id]['enabled']) {
            throw new Exception("至少需要一个启用的API提供商");
        }
        
        unset($this->providers[$id]);
        

        if ($this->activeProvider === $id) {
            $this->activeProvider = null;
            foreach ($this->providers as $pid => $p) {
                if ($p['enabled']) {
                    $this->activeProvider = $pid;
                    break;
                }
            }
        }
        
        $this->saveProviders();
        return true;
    }
    
    
    public function setActiveProvider($id) {
        if (!isset($this->providers[$id])) {
            throw new Exception("提供商不存在: {$id}");
        }
        
        if (!$this->providers[$id]['enabled']) {
            throw new Exception("提供商未启用: {$id}");
        }
        
        $this->activeProvider = $id;
        return $this->providers[$id];
    }
    
    
    public function testProvider($id) {
        $provider = $this->getProvider($id);
        if (!$provider) {
            throw new Exception("提供商不存在: {$id}");
        }
        
        $typeInfo = self::PROVIDER_TYPES[$provider['type']] ?? null;
        if (!$typeInfo) {
            throw new Exception("未知的提供商类型: {$provider['type']}");
        }
        
        $apiFormat = $typeInfo['api_format'];
        $baseUrl = $provider['config']['base_url'];
        $apiKey = $provider['config']['api_key'];
        
        try {
            switch ($apiFormat) {
                case 'ollama':
                    return $this->testOllama($baseUrl);
                case 'openai':
                    return $this->testOpenAI($baseUrl, $apiKey);
                case 'azure':
                    return $this->testAzure($provider['config']);
                case 'anthropic':
                    return $this->testAnthropic($baseUrl, $apiKey);
                case 'gemini':
                    return $this->testGemini($baseUrl, $apiKey);
                case 'hunyuan':
                    return $this->testHunyuan($provider['config']);
                default:
                    return $this->testOpenAI($baseUrl, $apiKey);
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    
    private function testOllama($baseUrl) {
        $ch = curl_init($baseUrl . '/api/tags');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("连接失败: {$error}");
        }
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP错误: {$httpCode}");
        }
        
        $data = json_decode($response, true);
        $models = $data['models'] ?? [];
        
        return [
            'success' => true,
            'message' => '连接成功',
            'models' => array_map(function($m) {
                return $m['name'] ?? $m['model'] ?? 'unknown';
            }, $models),
            'model_count' => count($models)
        ];
    }
    
    
    private function testOpenAI($baseUrl, $apiKey) {
        $headers = ['Content-Type: application/json'];
        if ($apiKey) {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        }
        
        $ch = curl_init($baseUrl . '/models');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("连接失败: {$error}");
        }
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP错误: {$httpCode}");
        }
        
        $data = json_decode($response, true);
        $models = $data['data'] ?? [];
        
        return [
            'success' => true,
            'message' => '连接成功',
            'models' => array_map(function($m) {
                return $m['id'] ?? $m['name'] ?? 'unknown';
            }, $models),
            'model_count' => count($models)
        ];
    }
    
    
    private function testAzure($config) {

        return $this->testOpenAI($config['base_url'], $config['api_key']);
    }
    
    
    private function testAnthropic($baseUrl, $apiKey) {
        $headers = [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01'
        ];
        
        $ch = curl_init($baseUrl . '/models');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP错误: {$httpCode}");
        }
        
        return [
            'success' => true,
            'message' => '连接成功'
        ];
    }
    
    
    private function testGemini($baseUrl, $apiKey) {
        $url = $baseUrl . '/models?key=' . $apiKey;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP错误: {$httpCode}");
        }
        
        $data = json_decode($response, true);
        $models = $data['models'] ?? [];
        
        return [
            'success' => true,
            'message' => '连接成功',
            'models' => array_map(function($m) {
                return $m['name'] ?? 'unknown';
            }, $models),
            'model_count' => count($models)
        ];
    }
    
    
    private function testHunyuan($config) {
        $baseUrl = $config['base_url'];
        $apiKey = $config['api_key'] ?? '';
        $secretId = $config['secret_id'] ?? '';
        $secretKey = $config['secret_key'] ?? '';
        

        $useOpenAICompatible = strpos($baseUrl, '/v1') !== false;
        
        if ($useOpenAICompatible && !empty($apiKey)) {

            return $this->testOpenAI($baseUrl, $apiKey);
        }
        

        if (empty($secretId) || empty($secretKey)) {
            return [
                'success' => false,
                'message' => '需要提供 SecretId 和 SecretKey 进行腾讯云API认证'
            ];
        }
        

        $host = 'hunyuan.tencentcloudapi.com';
        $service = 'hunyuan';
        $action = 'ChatCompletions';
        $version = '2023-09-01';
        $region = $config['region'] ?? 'ap-guangzhou';
        
        $payload = [
            'Model' => 'hunyuan-lite',
            'Messages' => [
                ['Role' => 'user', 'Content' => '你好']
            ]
        ];
        
        $timestamp = time();
        $date = gmdate('Y-m-d', $timestamp);
        
        $payloadJson = json_encode($payload);
        $payloadHash = hash('sha256', $payloadJson);
        
        $canonicalRequest = "POST\n/\n\ncontent-type:application/json\nhost:{$host}\n\ncontent-type;host\n" . $payloadHash;
        $credentialScope = $date . '/' . $service . '/tc3_request';
        $hashedCanonicalRequest = hash('sha256', $canonicalRequest);
        $stringToSign = "TC3-HMAC-SHA256\n" . $timestamp . "\n" . $credentialScope . "\n" . $hashedCanonicalRequest;
        
        $secretDate = hash_hmac('sha256', $date, 'TC3' . $secretKey, true);
        $secretService = hash_hmac('sha256', $service, $secretDate, true);
        $secretSigning = hash_hmac('sha256', 'tc3_request', $secretService, true);
        $signature = hash_hmac('sha256', $stringToSign, $secretSigning);
        
        $authorization = "TC3-HMAC-SHA256 Credential=" . $secretId . "/" . $credentialScope . ", SignedHeaders=content-type;host, Signature=" . $signature;
        
        $headers = [
            'Content-Type: application/json',
            'Host: ' . $host,
            'Authorization: ' . $authorization,
            'X-TC-Action: ' . $action,
            'X-TC-Version: ' . $version,
            'X-TC-Timestamp: ' . $timestamp,
            'X-TC-Region: ' . $region
        ];
        
        $ch = curl_init('https://' . $host);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("连接失败: {$error}");
        }
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP错误: {$httpCode}");
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['Response']['Error'])) {
            throw new Exception($data['Response']['Error']['Message'] ?? '腾讯云API错误');
        }
        

        $defaultModels = ['hunyuan-pro', 'hunyuan-standard', 'hunyuan-lite', 'hunyuan-turbo', 'hunyuan-turbo-latest'];
        
        return [
            'success' => true,
            'message' => '腾讯混元连接成功',
            'models' => $defaultModels,
            'model_count' => count($defaultModels)
        ];
    }
    
    
    public function fetchModels($providerId) {
        $result = $this->testProvider($providerId);
        
        if ($result['success'] && isset($result['models'])) {

            $this->providers[$providerId]['models'] = $result['models'];
            $this->saveProviders();
        }
        
        return $result;
    }
    
    
    public function createCaller($providerId = null) {
        if (!$providerId) {
            $providerId = $this->activeProvider;
        }
        

        if (isset($this->providers[$providerId])) {
            $provider = $this->providers[$providerId];
        } else {

            $found = false;
            foreach ($this->providers as $id => $p) {
                if (($p['type'] ?? '') === $providerId) {
                    $provider = $p;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                throw new Exception("Provider does not exist: {$providerId}");
            }
        }
        
        $typeInfo = self::PROVIDER_TYPES[$provider['type']] ?? null;
        
        if (!$typeInfo) {
            throw new Exception("未知的提供商类型: {$provider['type']}");
        }
        
        require_once __DIR__ . '/AIProviderCaller.php';
        return new AIProviderCaller($provider, $typeInfo);
    }
}
