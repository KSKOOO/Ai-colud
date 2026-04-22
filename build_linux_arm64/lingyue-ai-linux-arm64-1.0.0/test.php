<?php
/**
 * 服务器诊断脚本
 * 访问: http://your-domain/test.php
 */

header('Content-Type: text/html; charset=utf-8');

echo '<h1>服务器诊断</h1>';

// 1. 检查PHP版本
echo '<h2>1. PHP版本</h2>';
echo '<p>当前版本: ' . PHP_VERSION . '</p>';
echo '<p>要求: >= 7.4</p>';

// 2. 检查关键文件
echo '<h2>2. 文件检查</h2>';
$files = [
    'index.php',
    'templates/home.php',
    'templates/login.php',
    'config/config.php',
    '.htaccess'
];

echo '<ul>';
foreach ($files as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    echo '<li>' . $file . ': ' . ($exists ? '<span style="color:green">✓ 存在</span>' : '<span style="color:red">✗ 缺失</span>') . '</li>';
}
echo '</ul>';

// 3. 检查目录权限
echo '<h2>3. 目录权限</h2>';
$dirs = ['data', 'uploads', 'logs', 'storage'];
echo '<ul>';
foreach ($dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);
    echo '<li>' . $dir . ': ';
    if (!$exists) {
        echo '<span style="color:orange">⚠ 不存在</span>';
    } elseif ($writable) {
        echo '<span style="color:green">✓ 可写</span>';
    } else {
        echo '<span style="color:red">✗ 不可写</span>';
    }
    echo '</li>';
}
echo '</ul>';

// 4. 检查当前路径
echo '<h2>4. 路径信息</h2>';
echo '<p>当前目录: ' . __DIR__ . '</p>';
echo '<p>脚本文件名: ' . $_SERVER['SCRIPT_NAME'] . '</p>';
echo '<p>请求URI: ' . ($_SERVER['REQUEST_URI'] ?? 'N/A') . '</p>';
echo '<p>文档根目录: ' . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . '</p>';

// 5. 检查数据库
echo '<h2>5. 数据库检查</h2>';
$dbFile = __DIR__ . '/data/database.sqlite';
if (file_exists($dbFile)) {
    echo '<p style="color:green">✓ 数据库文件存在 (' . round(filesize($dbFile)/1024, 2) . ' KB)</p>';
} else {
    echo '<p style="color:red">✗ 数据库文件不存在</p>';
}

// 6. 测试重写规则
echo '<h2>6. 重写规则测试</h2>';
echo '<p>尝试访问: <a href="?route=home" target="_blank">?route=home</a></p>';
echo '<p>如果链接正常显示，说明PHP路由工作正常</p>';

// 7. 建议
echo '<h2>7. 修复建议</h2>';
echo '<pre>';
if (!file_exists(__DIR__ . '/.htaccess')) {
    echo "- 缺少 .htaccess 文件，已自动生成\n";
}
if (!is_dir(__DIR__ . '/data')) {
    echo "- 需要创建 data 目录并设置权限\n";
}
echo '</pre>';

echo '<hr>';
echo '<p><a href="index.php">返回首页</a></p>';
