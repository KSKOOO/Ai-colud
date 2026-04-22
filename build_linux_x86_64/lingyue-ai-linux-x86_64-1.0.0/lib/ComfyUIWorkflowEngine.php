<?php


class ComfyUIWorkflowEngine {
    private $executionsDir;
    private $outputDir;
    private $cacheDir;
    private $db;
    private $providerManager;
    

    private $nodeCache = [];
    

    private $currentExecution = null;
    
    public function __construct() {
        $this->executionsDir = __DIR__ . '/../storage/workflows/executions/';
        $this->outputDir = __DIR__ . '/../storage/workflows/outputs/';
        $this->cacheDir = __DIR__ . '/../storage/workflows/cache/';
        
        foreach ([$this->executionsDir, $this->outputDir, $this->cacheDir] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        

        require_once __DIR__ . '/../includes/Database.php';
        $this->db = Database::getInstance();
        

        require_once __DIR__ . '/AIProviderManager.php';
        $this->providerManager = new AIProviderManager();
    }
    
    
    public function executeWorkflow($workflow, $inputs = []) {
        $executionId = 'comfy_' . uniqid();
        $workflowData = is_string($workflow['workflow_data']) 
            ? json_decode($workflow['workflow_data'], true) 
            : ($workflow['workflow_data'] ?? []);
        
        $nodes = $workflowData['nodes'] ?? [];
        $edges = $workflowData['edges'] ?? [];
        

        $this->currentExecution = [
            'execution_id' => $executionId,
            'workflow_id' => $workflow['id'] ?? null,
            'workflow_name' => $workflow['name'] ?? '未命名工作流',
            'status' => 'running',
            'progress' => 0,
            'current_node' => null,
            'start_time' => time(),
            'end_time' => null,
            'inputs' => $inputs,
            'outputs' => [],
            'node_status' => [],
            'logs' => [],
            'generated_images' => []
        ];
        

        foreach ($nodes as $nodeId => $node) {
            $this->currentExecution['node_status'][$nodeId] = [
                'status' => 'pending',
                'start_time' => null,
                'end_time' => null,
                'output' => null,
                'error' => null,
                'execution_time' => 0
            ];
        }
        
        $this->saveExecution($executionId, $this->currentExecution);
        

        $this->runAsync($executionId, $nodes, $edges, $inputs);
        
        return $executionId;
    }
    
    
    private function runAsync($executionId, $nodes, $edges, $inputs) {

        $executionOrder = $this->topologicalSort($nodes, $edges);
        

        if (function_exists('pcntl_fork')) {
            $pid = pcntl_fork();
            if ($pid === 0) {

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
            $startTime = microtime(true);
            

            $execution['current_node'] = $nodeId;
            $execution['progress'] = $progress;
            $execution['node_status'][$nodeId]['status'] = 'running';
            $execution['node_status'][$nodeId]['start_time'] = $startTime;
            $execution['logs'][] = [
                'time' => date('Y-m-d H:i:s'),
                'level' => 'info',
                'node_id' => $nodeId,
                'node_type' => $node['type'],
                'message' => "开始执行节点: {$node['type']}"
            ];
            
            $this->saveExecution($executionId, $execution);
            
            try {

                $nodeInputs = $this->resolveNodeInputs($nodeId, $node, $nodeOutputs, $edges);
                

                $cacheKey = $this->getNodeCacheKey($node, $nodeInputs);
                if (isset($this->nodeCache[$cacheKey])) {
                    $output = $this->nodeCache[$cacheKey];
                    $execution['logs'][] = [
                        'time' => date('Y-m-d H:i:s'),
                        'level' => 'info',
                        'node_id' => $nodeId,
                        'message' => "使用缓存结果"
                    ];
                } else {

                    $output = $this->executeComfyNode($node, $nodeInputs);
                    $this->nodeCache[$cacheKey] = $output;
                }
                

                $nodeOutputs[$nodeId] = $output;
                

                if (isset($output['images'])) {
                    foreach ($output['images'] as $img) {
                        $execution['generated_images'][] = [
                            'node_id' => $nodeId,
                            'filename' => $img['filename'],
                            'url' => $img['url'],
                            'type' => $img['type'] ?? 'output'
                        ];
                    }
                }
                

                $executionTime = microtime(true) - $startTime;
                $execution['node_status'][$nodeId]['status'] = 'completed';
                $execution['node_status'][$nodeId]['end_time'] = microtime(true);
                $execution['node_status'][$nodeId]['output'] = $output;
                $execution['node_status'][$nodeId]['execution_time'] = round($executionTime, 2);
                $execution['logs'][] = [
                    'time' => date('Y-m-d H:i:s'),
                    'level' => 'success',
                    'node_id' => $nodeId,
                    'message' => "节点执行完成 (耗时: {$executionTime}s)"
                ];
                
            } catch (Exception $e) {
                $executionTime = microtime(true) - $startTime;
                $execution['node_status'][$nodeId]['status'] = 'error';
                $execution['node_status'][$nodeId]['end_time'] = microtime(true);
                $execution['node_status'][$nodeId]['error'] = $e->getMessage();
                $execution['node_status'][$nodeId]['execution_time'] = round($executionTime, 2);
                $execution['logs'][] = [
                    'time' => date('Y-m-d H:i:s'),
                    'level' => 'error',
                    'node_id' => $nodeId,
                    'message' => "节点执行失败: {$e->getMessage()}"
                ];
                

                if ($node['type'] === 'KSampler' || $node['type'] === 'CheckpointLoaderSimple') {

                    $execution['status'] = 'error';
                    $execution['error'] = $e->getMessage();
                    $execution['end_time'] = time();
                    $this->saveExecution($executionId, $execution);
                    return;
                }
            }
            
            $this->saveExecution($executionId, $execution);
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
    
    
    private function executeComfyNode($node, $inputs) {
        $type = $node['type'] ?? 'unknown';
        $config = $node['config'] ?? $node['widgets'] ?? [];
        
        switch ($type) {

            case 'CheckpointLoaderSimple':
            case 'CheckpointLoader':
                return $this->executeCheckpointLoader($config);
                
            case 'VAELoader':
                return $this->executeVAELoader($config);
                
            case 'LoraLoader':
                return $this->executeLoraLoader($config, $inputs);
                

            case 'CLIPTextEncode':
                return $this->executeCLIPTextEncode($config, $inputs);
                
            case 'CLIPTextEncodeFlux':
                return $this->executeCLIPTextEncodeFlux($config, $inputs);
                

            case 'EmptyLatentImage':
                return $this->executeEmptyLatentImage($config);
                
            case 'LatentUpscale':
                return $this->executeLatentUpscale($config, $inputs);
                

            case 'KSampler':
                return $this->executeKSampler($config, $inputs);
                
            case 'KSamplerAdvanced':
                return $this->executeKSamplerAdvanced($config, $inputs);
                

            case 'VAEDecode':
                return $this->executeVAEDecode($config, $inputs);
                
            case 'SaveImage':
                return $this->executeSaveImage($config, $inputs);
                
            case 'PreviewImage':
                return $this->executePreviewImage($config, $inputs);
                
            case 'LoadImage':
                return $this->executeLoadImage($config);
                
            case 'ImageScale':
                return $this->executeImageScale($config, $inputs);
                

            case 'Note':
                return ['text' => $config['text'] ?? ''];
                
            case 'PrimitiveNode':
                return ['value' => $config['value'] ?? null];
                
            default:

                return $this->executeGenericNode($type, $config, $inputs);
        }
    }
    
    
    private function executeCheckpointLoader($config) {
        $ckptName = $config['ckpt_name'] ?? 'default';
        

        $providers = $this->providerManager->getProviders(true);
        $activeProvider = $this->providerManager->getActiveProvider();
        
        return [
            'model' => [
                'name' => $ckptName,
                'provider' => $activeProvider['id'] ?? 'default',
                'type' => 'checkpoint'
            ],
            'clip' => ['type' => 'clip', 'ready' => true],
            'vae' => ['type' => 'vae', 'ready' => true]
        ];
    }
    
    
    private function executeVAELoader($config) {
        $vaeName = $config['vae_name'] ?? 'default';
        return ['vae' => ['name' => $vaeName, 'type' => 'vae', 'ready' => true]];
    }
    
    
    private function executeLoraLoader($config, $inputs) {
        $loraName = $config['lora_name'] ?? '';
        $strengthModel = floatval($config['strength_model'] ?? 1.0);
        $strengthClip = floatval($config['strength_clip'] ?? 1.0);
        $model = $inputs['model'] ?? null;
        $clip = $inputs['clip'] ?? null;
        
        return [
            'model' => array_merge($model ?? [], ['lora' => ['name' => $loraName, 'strength' => $strengthModel]]),
            'clip' => array_merge($clip ?? [], ['lora' => ['name' => $loraName, 'strength' => $strengthClip]])
        ];
    }
    
    
    private function executeCLIPTextEncode($config, $inputs) {
        $text = $config['text'] ?? '';
        $clip = $inputs['clip'] ?? null;
        

        $processedText = $this->processPrompt($text);
        
        return [
            'conditioning' => [
                'text' => $text,
                'processed' => $processedText,
                'tokens' => $this->estimateTokens($text),
                'clip' => $clip
            ]
        ];
    }
    
    
    private function executeCLIPTextEncodeFlux($config, $inputs) {
        $clipL = $config['clip_l'] ?? '';
        $clipG = $config['clip_g'] ?? '';
        $t5xxl = $config['t5xxl'] ?? '';
        
        return [
            'conditioning' => [
                'type' => 'flux',
                'clip_l' => $clipL,
                'clip_g' => $clipG,
                't5xxl' => $t5xxl
            ]
        ];
    }
    
    
    private function executeEmptyLatentImage($config) {
        $width = intval($config['width'] ?? 512);
        $height = intval($config['height'] ?? 512);
        $batchSize = intval($config['batch_size'] ?? 1);
        
        return [
            'latent' => [
                'width' => $width,
                'height' => $height,
                'batch_size' => $batchSize,
                'channels' => 4,
                'data' => null
            ]
        ];
    }
    
    
    private function executeKSampler($config, $inputs) {
        $seed = intval($config['seed'] ?? rand(0, 999999999));
        $steps = intval($config['steps'] ?? 20);
        $cfg = floatval($config['cfg'] ?? 7.0);
        $samplerName = $config['sampler_name'] ?? 'euler';
        $scheduler = $config['scheduler'] ?? 'normal';
        $denoise = floatval($config['denoise'] ?? 1.0);
        
        $model = $inputs['model'] ?? null;
        $positive = $inputs['positive'] ?? null;
        $negative = $inputs['negative'] ?? null;
        $latentImage = $inputs['latent_image'] ?? null;
        

        $width = $latentImage['width'] ?? 512;
        $height = $latentImage['height'] ?? 512;
        

        $positivePrompt = $positive['text'] ?? 'masterpiece, best quality';
        $negativePrompt = $negative['text'] ?? 'low quality, blurry';
        

        $fullPrompt = $positivePrompt;
        if ($negativePrompt) {
            $fullPrompt .= "\nNegative prompt: " . $negativePrompt;
        }
        $fullPrompt .= "\nSteps: {$steps}, Sampler: {$samplerName}, CFG scale: {$cfg}, Seed: {$seed}, Size: {$width}x{$height}";
        

        $providerId = $model['provider'] ?? null;
        $modelName = $model['name'] ?? 'llama2';
        
        try {

            $provider = null;
            if ($providerId) {

                $provider = $this->providerManager->getProvider($providerId);
                if (!$provider) {
                    $provider = $this->providerManager->getActiveProvider();
                }
            } else {
                $provider = $this->providerManager->getActiveProvider();
            }
            $providerType = $provider['type'] ?? '';
            $actualProviderId = $provider['id'] ?? null;
            
            $caller = $this->providerManager->createCaller($actualProviderId);
            
            $chatOptions = [
                'model' => $modelName,
                'temperature' => 0.7
            ];
            

            if ($providerType !== 'hunyuan') {
                $chatOptions['max_tokens'] = 500;
            }
            

            $result = $caller->chat([
                ['role' => 'system', 'content' => '你是一个AI绘画助手。根据提示词生成详细的图像描述，描述图像的视觉内容、风格、构图等。'],
                ['role' => 'user', 'content' => "请根据以下提示词生成图像描述：\n{$positivePrompt}\n\n图像尺寸：{$width}x{$height}"]
            ], $chatOptions);
            
            if (!$result['success']) {
                throw new Exception('模型调用失败: ' . $result['error']);
            }
            
            $imageDescription = $result['content'];
            
        } catch (Exception $e) {

            $imageDescription = "Generated image based on: {$positivePrompt}\nTechnical details: Steps={$steps}, CFG={$cfg}, Seed={$seed}";
        }
        

        $outputFile = $this->generatePlaceholderImage($width, $height, $seed, $positivePrompt);
        
        return [
            'latent' => array_merge($latentImage ?? [], [
                'seed' => $seed,
                'steps' => $steps,
                'cfg' => $cfg,
                'sampler' => $samplerName,
                'denoise' => $denoise
            ]),
            'images' => [
                [
                    'filename' => basename($outputFile),
                    'url' => 'storage/workflows/outputs/' . basename($outputFile),
                    'width' => $width,
                    'height' => $height,
                    'description' => $imageDescription,
                    'type' => 'generated'
                ]
            ]
        ];
    }
    
    
    private function executeKSamplerAdvanced($config, $inputs) {

        $addNoise = $config['add_noise'] ?? 'enable';
        $startAtStep = intval($config['start_at_step'] ?? 0);
        $endAtStep = intval($config['end_at_step'] ?? 10000);
        $returnWithLeftoverNoise = $config['return_with_leftover_noise'] ?? 'disable';
        

        return $this->executeKSampler($config, $inputs);
    }
    
    
    private function generatePlaceholderImage($width, $height, $seed, $prompt) {
        $filename = 'comfy_' . uniqid() . '.png';
        $filepath = $this->outputDir . $filename;
        

        $image = imagecreatetruecolor($width, $height);
        

        srand($seed);
        $r1 = rand(50, 200);
        $g1 = rand(50, 200);
        $b1 = rand(50, 200);
        $r2 = rand(50, 200);
        $g2 = rand(50, 200);
        $b2 = rand(50, 200);
        srand();
        

        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            $r = intval($r1 * (1 - $ratio) + $r2 * $ratio);
            $g = intval($g1 * (1 - $ratio) + $g2 * $ratio);
            $b = intval($b1 * (1 - $ratio) + $b2 * $ratio);
            $color = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $y, $width, $y, $color);
        }
        

        $textColor = imagecolorallocate($image, 255, 255, 255);
        $shortPrompt = substr($prompt, 0, 50) . '...';
        imagestring($image, 2, 10, 10, 'AI Generated', $textColor);
        imagestring($image, 1, 10, 25, $shortPrompt, $textColor);
        imagestring($image, 1, 10, $height - 20, "{$width}x{$height} | Seed: {$seed}", $textColor);
        
        imagepng($image, $filepath);
        imagedestroy($image);
        
        return $filepath;
    }
    
    
    private function executeVAEDecode($config, $inputs) {
        $samples = $inputs['samples'] ?? null;
        $vae = $inputs['vae'] ?? null;
        

        return [
            'image' => $samples['images'][0] ?? null
        ];
    }
    
    
    private function executeSaveImage($config, $inputs) {
        $filenamePrefix = $config['filename_prefix'] ?? 'ComfyUI';
        $images = $inputs['images'] ?? [];
        
        $savedImages = [];
        foreach ($images as $index => $image) {
            if (is_array($image)) {
                $savedImages[] = $image;
            }
        }
        
        return [
            'saved' => $savedImages,
            'filename_prefix' => $filenamePrefix
        ];
    }
    
    
    private function executePreviewImage($config, $inputs) {
        $images = $inputs['images'] ?? [];
        return [
            'ui' => ['images' => $images],
            'result' => $images
        ];
    }
    
    
    private function executeLoadImage($config) {
        $imagePath = $config['image'] ?? '';
        $uploadDir = __DIR__ . '/../storage/uploads/';
        
        $fullPath = $uploadDir . basename($imagePath);
        
        if (!file_exists($fullPath)) {
            throw new Exception("图像文件不存在: {$imagePath}");
        }
        
        $info = getimagesize($fullPath);
        
        return [
            'image' => [
                'filename' => basename($imagePath),
                'url' => 'storage/uploads/' . basename($imagePath),
                'width' => $info[0] ?? 512,
                'height' => $info[1] ?? 512,
                'type' => $info['mime'] ?? 'image/png'
            ]
        ];
    }
    
    
    private function executeImageScale($config, $inputs) {
        $image = $inputs['image'] ?? null;
        $width = intval($config['width'] ?? 512);
        $height = intval($config['height'] ?? 512);
        $mode = $config['mode'] ?? 'bicubic';
        
        return [
            'image' => array_merge($image ?? [], [
                'width' => $width,
                'height' => $height,
                'scale_mode' => $mode
            ])
        ];
    }
    
    
    private function executeLatentUpscale($config, $inputs) {
        $samples = $inputs['samples'] ?? null;
        $scaleBy = floatval($config['scale_by'] ?? 2.0);
        
        $width = intval(($samples['width'] ?? 512) * $scaleBy);
        $height = intval(($samples['height'] ?? 512) * $scaleBy);
        
        return [
            'latent' => array_merge($samples ?? [], [
                'width' => $width,
                'height' => $height,
                'scale' => $scaleBy
            ])
        ];
    }
    
    
    private function executeGenericNode($type, $config, $inputs) {

        $prompt = "执行工作流节点 {$type}，配置: " . json_encode($config, JSON_UNESCAPED_UNICODE);
        
        try {

            $activeProvider = $this->providerManager->getActiveProvider();
            $providerType = $activeProvider['type'] ?? '';
            
            $caller = $this->providerManager->createCaller();
            
            $chatOptions = [
                'temperature' => 0.7
            ];
            

            if ($providerType !== 'hunyuan') {
                $chatOptions['max_tokens'] = 200;
            }
            
            $result = $caller->chat([
                ['role' => 'user', 'content' => $prompt]
            ], $chatOptions);
            
            return [
                'type' => $type,
                'config' => $config,
                'inputs' => array_keys($inputs),
                'ai_response' => $result['success'] ? $result['content'] : null,
                'inputs_received' => $inputs
            ];
        } catch (Exception $e) {
            return [
                'type' => $type,
                'config' => $config,
                'inputs' => array_keys($inputs),
                'error' => $e->getMessage()
            ];
        }
    }
    
    
    private function processPrompt($text) {

        $text = preg_replace_callback('/\(([^:]+):([\d.]+)\)/', function($matches) {
            $word = $matches[1];
            $weight = $matches[2];
            return $word;
        }, $text);
        

        $text = preg_replace_callback('/\[([^|]+)\|([^]]+)\]/', function($matches) {
            return $matches[1];
        }, $text);
        
        return trim($text);
    }
    
    
    private function estimateTokens($text) {
        if (empty($text)) return 0;
        $chineseChars = preg_match_all('/[\x{4e00}-\x{9fff}]/u', $text, $matches);
        $englishWords = str_word_count(preg_replace('/[\x{4e00}-\x{9fff}]/u', '', $text));
        return intval($chineseChars * 2 + $englishWords * 1.3);
    }
    
    
    private function getNodeCacheKey($node, $inputs) {
        return md5($node['type'] . json_encode($node['config']) . json_encode($inputs));
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
        

        $configInputs = $node['config'] ?? $node['widgets'] ?? [];
        foreach ($configInputs as $key => $value) {
            if (!isset($inputs[$key])) {
                $inputs[$key] = $value;
            }
        }
        
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
    
    
    public function getGeneratedImages($executionId) {
        $execution = $this->getExecution($executionId);
        return $execution['generated_images'] ?? [];
    }
}
