<?php
/**
 * 系统检查脚本 - 检查程序完整性和潜在问题
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>系统检查报告</h1>";
echo "<pre>\n";

$errors = [];
$warnings = [];
$success = [];

// 检查PHP版本
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    $errors[] = "PHP版本过低: " . PHP_VERSION . " (需要 >= 7.4.0)";
} else {
    $success[] = "PHP版本: " . PHP_VERSION;
}

// 检查必需的扩展
$requiredExtensions = ['pdo', 'pdo_sqlite', 'json', 'curl', 'mbstring'];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $errors[] = "缺少PHP扩展: {$ext}";
    } else {
        $success[] = "PHP扩展已加载: {$ext}";
    }
}

// 检查目录权限
$directories = [
    'data' => __DIR__ . '/data',
    'logs' => __DIR__ . '/logs',
    'uploads' => __DIR__ . '/uploads',
    'config' => __DIR__ . '/config',
    'storage' => __DIR__ . '/storage'
];

foreach ($directories as $name => $path) {
    if (!is_dir($path)) {
        $warnings[] = "目录不存在: {$name} ({$path})";
        if (@mkdir($path, 0755, true)) {
            $success[] = "已创建目录: {$name}";
        } else {
            $errors[] = "无法创建目录: {$name}";
        }
    } elseif (!is_writable($path)) {
        $warnings[] = "目录不可写: {$name} ({$path})";
    } else {
        $success[] = "目录正常: {$name}";
    }
}

// 检查关键文件
$requiredFiles = [
    'index.php' => __DIR__ . '/index.php',
    'config.php' => __DIR__ . '/config/config.php',
    'Database.php' => __DIR__ . '/includes/Database.php',
    'AIProviderManager.php' => __DIR__ . '/lib/AIProviderManager.php',
    'AgentManager.php' => __DIR__ . '/includes/AgentManager.php',
    'api_handler.php' => __DIR__ . '/api/api_handler.php',
    'agent_api.php' => __DIR__ . '/api/agent_api.php',
    'agent_sdk.js' => __DIR__ . '/api/agent_sdk.js'
];

foreach ($requiredFiles as $name => $path) {
    if (!file_exists($path)) {
        $errors[] = "缺少关键文件: {$name}";
    } else {
        $success[] = "文件存在: {$name}";
    }
}

// 检查配置文件
$configFile = __DIR__ . '/config/config.php';
if (file_exists($configFile)) {
    $config = require $configFile;
    if (empty($config['gpustack_api']['base_url'])) {
        $warnings[] = "GPUStack API地址未配置";
    }
    if (empty($config['ollama_api']['base_url'])) {
        $warnings[] = "Ollama API地址未配置";
    }
}

// 检查数据库
$dbFile = __DIR__ . '/data/database.sqlite';
if (file_exists($dbFile)) {
    try {
        $pdo = new PDO("sqlite:" . $dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 检查关键表
        $tables = ['users', 'agents', 'agent_conversations', 'workflows', 'chat_history'];
        foreach ($tables as $table) {
            // 使用参数化查询防止SQL注入
            $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=:table");
            $stmt->execute([':table' => $table]);
            if ($stmt->fetch()) {
                $success[] = "数据库表存在: {$table}";
            } else {
                $warnings[] = "数据库表不存在: {$table}";
            }
        }
    } catch (Exception $e) {
        $errors[] = "数据库连接失败: " . $e->getMessage();
    }
} else {
    $warnings[] = "数据库文件不存在，需要运行安装程序";
}

// 检查providers.json
$providersFile = __DIR__ . '/config/providers.json';
if (!file_exists($providersFile)) {
    $warnings[] = "providers.json不存在，将使用默认配置";
} else {
    $content = file_get_contents($providersFile);
    $providers = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $errors[] = "providers.json格式错误: " . json_last_error_msg();
    } else {
        $success[] = "providers.json格式正确";
    }
}

// 输出结果
echo "\n=== 错误 (" . count($errors) . ") ===\n";
foreach ($errors as $error) {
    echo "❌ {$error}\n";
}

echo "\n=== 警告 (" . count($warnings) . ") ===\n";
foreach ($warnings as $warning) {
    echo "⚠️ {$warning}\n";
}

echo "\n=== 正常 (" . count($success) . ") ===\n";
foreach ($success as $s) {
    echo "✅ {$s}\n";
}

echo "\n=== 检查完成 ===\n";
echo "</pre>";

// 修复建议
if (count($errors) > 0 || count($warnings) > 0) {
    echo "<h2>修复建议</h2>";
    echo "<ol>";
    if (in_array("缺少PHP扩展: pdo_sqlite", $errors) || in_array("缺少PHP扩展: pdo", $errors)) {
        echo "<li>安装PDO和SQLite扩展: 编辑php.ini，取消注释 <code>extension=pdo_sqlite</code> 和 <code>extension=pdo</code></li>";
    }
    if (in_array("缺少PHP扩展: curl", $errors)) {
        echo "<li>安装cURL扩展: 编辑php.ini，取消注释 <code>extension=curl</code></li>";
    }
    if (!file_exists($dbFile)) {
        echo "<li>运行 <a href='install.php'>安装程序</a> 初始化数据库</li>";
    }
    echo "<li>确保以下目录有写入权限: data, logs, uploads, config</li>";
    echo "</ol>";
} else {
    echo "<p style='color: green; font-size: 18px;'>✅ 所有检查通过，系统运行正常！</p>";
}

// 显示系统信息
echo "<h2>系统信息</h2>";
echo "<pre>";
echo "操作系统: " . PHP_OS . "\n";
echo "PHP版本: " . PHP_VERSION . "\n";
echo "服务器软件: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "文档根目录: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
echo "当前工作目录: " . getcwd() . "\n";
echo "内存限制: " . ini_get('memory_limit') . "\n";
echo "上传限制: " . ini_get('upload_max_filesize') . "\n";
echo "执行时间限制: " . ini_get('max_execution_time') . "秒\n";
echo "</pre>";
