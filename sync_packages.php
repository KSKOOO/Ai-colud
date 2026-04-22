<?php
/**
 * 同步GPUStack Platform代码到Linux和Windows包
 */

header('Content-Type: text/html; charset=utf-8');

$source = 'E:/巨神兵本地包/gpustack_platform';
$linuxTarget = 'E:/巨神兵本地包/build/lingyue-ai-linux-1.0.0';
$windowsTarget = 'E:/巨神兵本地包/build/lingyue-ai-windows-1.0.0';

echo "<h1>同步GPUStack Platform代码</h1>";
echo "<pre>\n";

function copyFile($src, $dst) {
    $dir = dirname($dst);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (copy($src, $dst)) {
        return true;
    }
    return false;
}

function copyDirectory($src, $dst, &$results) {
    $dir = opendir($src);
    @mkdir($dst, 0755, true);
    
    while (($file = readdir($dir)) !== false) {
        if ($file == '.' || $file == '..') continue;
        
        $srcFile = $src . '/' . $file;
        $dstFile = $dst . '/' . $file;
        
        if (is_dir($srcFile)) {
            copyDirectory($srcFile, $dstFile, $results);
        } else {
            if (copy($srcFile, $dstFile)) {
                $results['copied'][] = str_replace($GLOBALS['source'], '', $srcFile);
            } else {
                $results['failed'][] = str_replace($GLOBALS['source'], '', $srcFile);
            }
        }
    }
    closedir($dir);
}

// 同步列表
$syncItems = [
    // API文件
    'api' => ['*.php', '*.js'],
    // 核心文件
    'includes' => ['*.php'],
    'lib' => ['*.php'],
    'config' => ['*.php'],
    // 模板
    'templates' => ['*.php'],
    // 资源
    'assets' => ['*'],
    // 根目录文件
    'root' => [
        'index.php', 'install.php', 'repair_db.php', 'upgrade_db.php',
        'system_check.php', 'repair_common_issues.php', 'agent_embed_demo.html',
        '*.md', '*.sh', '*.bat', 'Dockerfile', 'docker-compose.yml'
    ]
];

$results = ['copied' => [], 'failed' => [], 'skipped' => []];

// 同步到Linux包
echo "=== 同步到 Linux 包 ===\n";

// 1. API文件
$apiFiles = glob($source . '/api/*.php');
$apiJsFiles = glob($source . '/api/*.js');
foreach (array_merge($apiFiles, $apiJsFiles) as $file) {
    $targetFile = $linuxTarget . '/api/' . basename($file);
    if (copyFile($file, $targetFile)) {
        $results['copied'][] = 'api/' . basename($file);
        echo "✓ api/" . basename($file) . "\n";
    }
}

// 2. includes文件
$includesFiles = glob($source . '/includes/*.php');
foreach ($includesFiles as $file) {
    $targetFile = $linuxTarget . '/includes/' . basename($file);
    if (copyFile($file, $targetFile)) {
        echo "✓ includes/" . basename($file) . "\n";
    }
}

// 3. lib文件
$libFiles = glob($source . '/lib/*.php');
foreach ($libFiles as $file) {
    $targetFile = $linuxTarget . '/lib/' . basename($file);
    if (copyFile($file, $targetFile)) {
        echo "✓ lib/" . basename($file) . "\n";
    }
}

// 4. config文件
$configFiles = glob($source . '/config/*.php');
foreach ($configFiles as $file) {
    $targetFile = $linuxTarget . '/config/' . basename($file);
    if (copyFile($file, $targetFile)) {
        echo "✓ config/" . basename($file) . "\n";
    }
}

// 5. templates文件
$templateFiles = glob($source . '/templates/*.php');
foreach ($templateFiles as $file) {
    $targetFile = $linuxTarget . '/templates/' . basename($file);
    if (copyFile($file, $targetFile)) {
        echo "✓ templates/" . basename($file) . "\n";
    }
}

// 6. 根目录文件
$rootFiles = [
    'index.php', 'install.php', 'repair_db.php', 'upgrade_db.php',
    'system_check.php', 'repair_common_issues.php', 'agent_embed_demo.html',
    'install.sh', 'uninstall.sh', 'Dockerfile', 'docker-compose.yml'
];
foreach ($rootFiles as $file) {
    $srcFile = $source . '/' . $file;
    if (file_exists($srcFile)) {
        $targetFile = $linuxTarget . '/' . $file;
        if (copyFile($srcFile, $targetFile)) {
            echo "✓ " . $file . "\n";
        }
    }
}

// 7. MD文档
$mdFiles = glob($source . '/*.md');
foreach ($mdFiles as $file) {
    $targetFile = $linuxTarget . '/' . basename($file);
    if (copyFile($file, $targetFile)) {
        echo "✓ " . basename($file) . "\n";
    }
}

// 8. assets目录
if (is_dir($source . '/assets')) {
    copyDirectory($source . '/assets', $linuxTarget . '/assets', $results);
    echo "✓ assets/ 目录\n";
}

echo "\n=== 同步到 Windows 包 ===\n";

// 同步到Windows包
// 1. API文件
foreach (array_merge($apiFiles, $apiJsFiles) as $file) {
    $targetFile = $windowsTarget . '/api/' . basename($file);
    if (copyFile($file, $targetFile)) {
        echo "✓ api/" . basename($file) . "\n";
    }
}

// 2. includes文件
foreach ($includesFiles as $file) {
    $targetFile = $windowsTarget . '/includes/' . basename($file);
    if (copyFile($file, $targetFile)) {
        echo "✓ includes/" . basename($file) . "\n";
    }
}

// 3. lib文件
foreach ($libFiles as $file) {
    $targetFile = $windowsTarget . '/lib/' . basename($file);
    if (copyFile($file, $targetFile)) {
        echo "✓ lib/" . basename($file) . "\n";
    }
}

// 4. config文件
foreach ($configFiles as $file) {
    $targetFile = $windowsTarget . '/config/' . basename($file);
    if (copyFile($file, $targetFile)) {
        echo "✓ config/" . basename($file) . "\n";
    }
}

// 5. templates文件
foreach ($templateFiles as $file) {
    $targetFile = $windowsTarget . '/templates/' . basename($file);
    if (copyFile($file, $targetFile)) {
        echo "✓ templates/" . basename($file) . "\n";
    }
}

// 6. 根目录文件
$winRootFiles = [
    'index.php', 'install.php', 'repair_db.php', 'upgrade_db.php',
    'system_check.php', 'repair_common_issues.php', 'agent_embed_demo.html',
    'install.bat', 'start_server.bat'
];
foreach ($winRootFiles as $file) {
    $srcFile = $source . '/' . $file;
    if (file_exists($srcFile)) {
        $targetFile = $windowsTarget . '/' . $file;
        if (copyFile($srcFile, $targetFile)) {
            echo "✓ " . $file . "\n";
        }
    }
}

// 7. MD文档
foreach ($mdFiles as $file) {
    $targetFile = $windowsTarget . '/' . basename($file);
    if (copyFile($file, $targetFile)) {
        echo "✓ " . basename($file) . "\n";
    }
}

// 8. assets目录
if (is_dir($source . '/assets')) {
    copyDirectory($source . '/assets', $windowsTarget . '/assets', $results);
    echo "✓ assets/ 目录\n";
}

echo "\n=== 同步完成 ===\n";
echo "\n更新日志:\n";
echo date('Y-m-d H:i:s') . " - 同步最新代码\n";

// 写入同步日志
$logEntry = date('Y-m-d H:i:s') . " - 同步最新代码\n";
@file_put_contents($linuxTarget . '/SYNC_LOG.txt', $logEntry, FILE_APPEND);
@file_put_contents($windowsTarget . '/SYNC_LOG.txt', $logEntry, FILE_APPEND);

echo "\n主要更新:\n";
echo "[修复]\n";
echo "- 智能体外部嵌入权限问题\n";
echo "- 视频生成模型加载问题\n";
echo "- 智能体API实例管理优化\n";
echo "- 路由权限检查完善\n";
echo "\n[新增]\n";
echo "- 智能体嵌入SDK (api/agent_sdk.js)\n";
echo "- 智能体API接口 (api/agent_api.php)\n";
echo "- 系统检查工具 (system_check.php)\n";
echo "- 自动修复工具 (repair_common_issues.php)\n";
echo "- 嵌入示例页面 (agent_embed_demo.html)\n";

echo "\n包位置:\n";
echo "Linux:  " . $linuxTarget . "\n";
echo "Windows: " . $windowsTarget . "\n";

echo "</pre>";

echo "<p><a href='gpustack_platform/system_check.php'>运行系统检查</a></p>";
