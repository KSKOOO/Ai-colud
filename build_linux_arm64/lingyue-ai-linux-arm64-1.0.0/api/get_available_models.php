<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../lib/AIProviderManager.php';

session_start();


if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => '无权访问']);
    exit;
}

try {
    $manager = new AIProviderManager();
    $providers = $manager->getProviders(true);
    
    $models = [];
    
    foreach ($providers as $providerId => $provider) {

        $providerModels = $provider['models'] ?? [];
        

        if (empty($providerModels) && !empty($provider['model'])) {
            $providerModels = [$provider['model']];
        }
        

        foreach ($providerModels as $modelId) {
            $models[] = [
                'provider_id' => $providerId,
                'model_id' => $modelId,
                'name' => $provider['name'] . ' - ' . $modelId,
                'type' => $provider['type'] ?? 'unknown'
            ];
        }
        

        if (empty($providerModels)) {
            $models[] = [
                'provider_id' => $providerId,
                'model_id' => '*',
                'name' => $provider['name'] . ' - 所有模型',
                'type' => $provider['type'] ?? 'unknown'
            ];
        }
    }
    

    if (empty($models)) {
        $models = [
            ['provider_id' => 'ollama', 'model_id' => 'llama2', 'name' => 'Ollama - Llama 2', 'type' => 'ollama'],
            ['provider_id' => 'ollama', 'model_id' => 'mistral', 'name' => 'Ollama - Mistral', 'type' => 'ollama'],
            ['provider_id' => 'openai', 'model_id' => 'gpt-3.5-turbo', 'name' => 'OpenAI - GPT-3.5', 'type' => 'openai'],
            ['provider_id' => 'openai', 'model_id' => 'gpt-4', 'name' => 'OpenAI - GPT-4', 'type' => 'openai'],
        ];
    }
    
    echo json_encode([
        'success' => true,
        'models' => $models,
        'count' => count($models)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
