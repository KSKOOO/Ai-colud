<?php
return array (
  'app' => 
  array (
    'name' => '巨神兵API辅助平台API辅助平台',
    'version' => '1.0.0',
    'debug' => true,
    'timezone' => 'Asia/Shanghai',
    'language' => 'zh-CN',
  ),
  'database' => 
  array (
    'driver' => 'sqlite',
    'path' => 'E:\\巨神兵本地包\\gpustack_platform\\config/../data/database.sqlite',
  ),
  'gpustack_api' => 
  array (
    'base_url' => 'http://localhost:8080/v1',
    'api_key' => '',
    'timeout' => 120,
    'max_retries' => 3,
  ),
  'ollama_api' => 
  array (
    'base_url' => 'http://localhost:11434',
    'timeout' => 60,
  ),
  'models' => 
  array (
    'default' => 'llama2',
    'available' => 
    array (
      'llama2' => 
      array (
        'name' => 'Llama 2',
        'description' => 'Meta开发的大型语言模型',
        'max_tokens' => 4096,
      ),
      'qwen' => 
      array (
        'name' => '通义千问',
        'description' => '阿里云开发的中文大模型',
        'max_tokens' => 8192,
      ),
    ),
  ),
  'features' => 
  array (
    'chat' => true,
    'agents' => true,
    'workflows' => true,
    'scenarios' => true,
    'user_center' => true,
    'recharge' => false,
    'api_key' => true,
  ),
  'recharge' => 
  array (
    'enabled' => false,
    'message' => '充值功能开发中，敬请期待',
  ),
  'upload' =>
  array (
    'max_size' => -1,
    'allowed_types' =>
    array (
      0 => 'image/jpeg',
      1 => 'image/png',
      2 => 'image/gif',
      3 => 'application/pdf',
    ),
    'path' => 'E:\\巨神兵本地包\\gpustack_platform\\config/../uploads',
  ),
  'log' => 
  array (
    'enabled' => true,
    'path' => 'E:\\巨神兵本地包\\gpustack_platform\\config/../logs',
    'level' => 'info',
  ),
  'security' => 
  array (
    'session_lifetime' => 7200,
    'password_min_length' => 6,
    'max_login_attempts' => 5,
    'login_lockout_time' => 900,
  ),
  'billing' => 
  array (
    'enabled' => true,
    'free_quota' => 999999,
    'price_per_1k' => 0.02,
    'cycle' => 'monthly',
  ),
);
?>