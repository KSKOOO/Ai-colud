<?php

header('Content-Type: text/html; charset=utf-8');

echo "<h2>数据库修复工具</h2>";

$dbPath = __DIR__ . '/data/gpustack.db';
$backupPath = __DIR__ . '/data/gpustack_backup_' . date('Ymd_His') . '.db';


if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'backup') {
        if (file_exists($dbPath)) {
            if (copy($dbPath, $backupPath)) {
                echo "<div style='color:green'>✅ 数据库已备份到: {$backupPath}</div>";
            } else {
                echo "<div style='color:red'>❌ 备份失败</div>";
            }
        } else {
            echo "<div style='color:orange'>⚠️ 数据库文件不存在，无需备份</div>";
        }
    }
    
    if ($action === 'repair') {
        try {

            if (file_exists($dbPath)) {
                copy($dbPath, $backupPath);
                echo "<div style='color:green'>✅ 已备份原数据库</div>";
                

                unlink($dbPath);
                echo "<div style='color:green'>✅ 已删除原数据库</div>";
            }
            

            require_once __DIR__ . '/includes/Database.php';
            $db = Database::getInstance();
            
            echo "<div style='color:green'>✅ 数据库重新初始化成功</div>";
            

            require_once __DIR__ . '/includes/UserManager.php';
            $userManager = new UserManager();
            
            $result = $userManager->createUser('admin', 'admin123', 'admin@example.com', UserManager::ROLE_ADMIN);
            if ($result['success']) {
                echo "<div style='color:green'>✅ 默认管理员账号已创建</div>";
                echo "<div style='background:#f0fdf4;padding:10px;margin:10px 0;border-radius:5px'>";
                echo "<strong>默认登录信息:</strong><br>";
                echo "用户名: admin<br>";
                echo "密码: admin123<br>";
                echo "</div>";
            } else {
                echo "<div style='color:orange'>⚠️ 创建管理员账号失败: {$result['message']}</div>";
            }
            
            echo "<hr><a href='?route=home' style='padding:10px 20px;background:#4c51bf;color:white;text-decoration:none;border-radius:5px;'>返回首页</a>";
            exit;
            
        } catch (Exception $e) {
            echo "<div style='color:red'>❌ 修复失败: " . $e->getMessage() . "</div>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
    }
    
    if ($action === 'check') {
        try {
            require_once __DIR__ . '/includes/Database.php';
            $db = Database::getInstance();
            

            $tables = $db->fetchAll("SELECT name FROM sqlite_master WHERE type='table'");
            echo "<h3>数据库表检查</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>表名</th><th>状态</th></tr>";
            foreach ($tables as $table) {
                $name = $table['name'];
                try {
                    $count = $db->fetch("SELECT COUNT(*) as cnt FROM {$name}");
                    echo "<tr><td>{$name}</td><td style='color:green'>正常 ({$count['cnt']} 条记录)</td></tr>";
                } catch (Exception $e) {
                    echo "<tr><td>{$name}</td><td style='color:red'>错误: {$e->getMessage()}</td></tr>";
                }
            }
            echo "</table>";
            
        } catch (Exception $e) {
            echo "<div style='color:red'>❌ 检查失败: " . $e->getMessage() . "</div>";
        }
    }
}


echo "<h3>当前状态</h3>";
if (file_exists($dbPath)) {
    echo "数据库文件: 存在<br>";
    echo "文件大小: " . filesize($dbPath) . " 字节<br>";
    echo "最后修改: " . date('Y-m-d H:i:s', filemtime($dbPath)) . "<br>";
} else {
    echo "数据库文件: <span style='color:red'>不存在</span><br>";
}

echo "<hr>";


echo "<form method='post' style='margin:20px 0'>";
echo "<button type='submit' name='action' value='check' style='padding:10px 20px;margin:5px;cursor:pointer'>检查数据库</button>";
echo "<button type='submit' name='action' value='backup' style='padding:10px 20px;margin:5px;cursor:pointer'>备份数据库</button>";
echo "<button type='submit' name='action' value='repair' style='padding:10px 20px;margin:5px;background:#ef4444;color:white;border:none;border-radius:5px;cursor:pointer' onclick='return confirm(\"确定要重置数据库吗？所有数据将被清除！\")'>重置数据库</button>";
echo "</form>";

echo "<hr>";
echo "<a href='check_db.php'>运行数据库诊断</a> | ";
echo "<a href='?route=home'>返回首页</a>";
