<?php
/**
 * 常见问题修复脚本
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>常见问题修复</h1>";

$fixes = [];

// 1. 确保目录存在且有正确权限
$directories = [
    'data' => __DIR__ . '/data',
    'logs' => __DIR__ . '/logs',
    'uploads' => __DIR__ . '/uploads',
    'storage' => __DIR__ . '/storage',
    'config' => __DIR__ . '/config'
];

foreach ($directories as $name => $path) {
    if (!is_dir($path)) {
        if (@mkdir($path, 0755, true)) {
            $fixes[] = "✅ 创建目录: {$name}";
        } else {
            $fixes[] = "❌ 无法创建目录: {$name} - 请手动创建并设置权限";
        }
    } else {
        // 尝试设置权限
        @chmod($path, 0755);
        $fixes[] = "✅ 目录存在: {$name}";
    }
}

// 2. 创建默认providers.json（如果不存在）
$providersFile = __DIR__ . '/config/providers.json';
if (!file_exists($providersFile)) {
    $defaultProviders = [
        'ollama' => [
            'id' => 'ollama',
            'type' => 'ollama',
            'name' => 'Ollama本地服务',
            'enabled' => true,
            'is_default' => true,
            'api_url' => 'http://localhost:11434',
            'api_key' => '',
            'default_model' => 'llama2',
            'models' => ['llama2', 'llama3', 'mistral', 'qwen'],
            'temperature' => 0.7,
            'max_tokens' => 2048,
            'timeout' => 120
        ]
    ];
    
    if (@file_put_contents($providersFile, json_encode($defaultProviders, JSON_PRETTY_PRINT))) {
        $fixes[] = "✅ 创建默认providers.json";
    } else {
        $fixes[] = "❌ 无法创建providers.json";
    }
} else {
    $fixes[] = "✅ providers.json已存在";
}

// 3. 检查并修复.htaccess（用于Apache重写）
$htaccessFile = __DIR__ . '/.htaccess';
if (!file_exists($htaccessFile)) {
    $htaccessContent = <<<'HTACCESS'
# Apache重写规则
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>

# PHP设置
php_value upload_max_filesize 64M
php_value post_max_size 64M
php_value max_execution_time 300
php_value max_input_time 300

# 禁止访问敏感文件
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "\.(sqlite|db|log|ini)$">
    Order allow,deny
    Deny from all
</FilesMatch>
HTACCESS;
    
    if (@file_put_contents($htaccessFile, $htaccessContent)) {
        $fixes[] = "✅ 创建.htaccess文件";
    } else {
        $fixes[] = "⚠️ 无法创建.htaccess（可选）";
    }
} else {
    $fixes[] = "✅ .htaccess已存在";
}

// 4. 创建web.config（用于IIS重写）
$webConfigFile = __DIR__ . '/web.config';
if (!file_exists($webConfigFile)) {
    $webConfigContent = <<<'WEBCONFIG'
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <rule name="Rewrite to index.php" stopProcessing="true">
                    <match url="^(.*)$" />
                    <conditions>
                        <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
                        <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
                    </conditions>
                    <action type="Rewrite" url="index.php" />
                </rule>
            </rules>
        </rewrite>
        <defaultDocument>
            <files>
                <add value="index.php" />
            </files>
        </defaultDocument>
    </system.webServer>
</configuration>
WEBCONFIG;
    
    if (@file_put_contents($webConfigFile, $webConfigContent)) {
        $fixes[] = "✅ 创建web.config文件（IIS）";
    } else {
        $fixes[] = "⚠️ 无法创建web.config（可选，仅IIS需要）";
    }
} else {
    $fixes[] = "✅ web.config已存在";
}

// 5. 检查数据库并尝试修复
$dbFile = __DIR__ . '/data/database.sqlite';
if (file_exists($dbFile)) {
    try {
        $pdo = new PDO("sqlite:" . $dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 检查agents表是否有deploy_token字段
        $stmt = $pdo->query("PRAGMA table_info(agents)");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
        
        $requiredColumns = [
            'deploy_token' => 'VARCHAR(64)',
            'deploy_url' => 'VARCHAR(255)',
            'usage_count' => 'INTEGER DEFAULT 0',
            'status' => "VARCHAR(20) DEFAULT 'draft'",
            'capabilities' => 'TEXT',
            'knowledge_base_ids' => 'TEXT',
            'tools' => 'TEXT'
        ];
        
        foreach ($requiredColumns as $col => $type) {
            if (!in_array($col, $columns)) {
                try {
                    $pdo->exec("ALTER TABLE agents ADD COLUMN {$col} {$type}");
                    $fixes[] = "✅ 添加agents表字段: {$col}";
                } catch (Exception $e) {
                    $fixes[] = "⚠️ 无法添加字段 {$col}: " . $e->getMessage();
                }
            }
        }
        
        // 检查agent_conversations表
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='agent_conversations'");
        if (!$stmt->fetch()) {
            $pdo->exec("CREATE TABLE agent_conversations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                agent_id INTEGER NOT NULL,
                session_id VARCHAR(64) NOT NULL,
                user_id INTEGER,
                message TEXT NOT NULL,
                role VARCHAR(20) NOT NULL,
                model VARCHAR(100),
                tokens_used INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            $pdo->exec("CREATE INDEX idx_conv_agent ON agent_conversations(agent_id)");
            $pdo->exec("CREATE INDEX idx_conv_session ON agent_conversations(session_id)");
            $fixes[] = "✅ 创建agent_conversations表";
        }
        
        $fixes[] = "✅ 数据库检查完成";
    } catch (Exception $e) {
        $fixes[] = "❌ 数据库错误: " . $e->getMessage();
    }
} else {
    $fixes[] = "⚠️ 数据库不存在，请运行 install.php 进行安装";
}

// 6. 清理日志文件（如果太大）
$logsDir = __DIR__ . '/logs';
if (is_dir($logsDir)) {
    $logFiles = glob($logsDir . '/*.log');
    foreach ($logFiles as $logFile) {
        $size = filesize($logFile);
        if ($size > 10 * 1024 * 1024) { // 10MB
            // 备份并清空
            $backupName = $logFile . '.old';
            @rename($logFile, $backupName);
            @touch($logFile);
            $fixes[] = "✅ 清理大日志文件: " . basename($logFile);
        }
    }
}

// 7. 检查PHP配置
$phpIssues = [];
if (ini_get('display_errors')) {
    $phpIssues[] = "display_errors 应该设置为 Off（生产环境）";
}
if (intval(ini_get('max_execution_time')) < 60) {
    $phpIssues[] = "max_execution_time 建议设置为 60 或更高";
}
if (intval(ini_get('memory_limit')) < 128) {
    $phpIssues[] = "memory_limit 建议设置为 128M 或更高";
}

// 显示结果
echo "<h2>修复结果</h2>";
echo "<ul>";
foreach ($fixes as $fix) {
    echo "<li>{$fix}</li>";
}
echo "</ul>";

if (count($phpIssues) > 0) {
    echo "<h2>PHP配置建议</h2>";
    echo "<ul>";
    foreach ($phpIssues as $issue) {
        echo "<li>⚠️ {$issue}</li>";
    }
    echo "</ul>";
}

echo "<h2>下一步</h2>";
echo "<ol>";
echo "<li><a href='system_check.php'>运行系统检查</a> 验证修复结果</li>";
echo "<li>如果数据库不存在，<a href='install.php'>运行安装程序</a></li>";
echo "<li><a href='index.php'>返回首页</a></li>";
echo "</ol>";
