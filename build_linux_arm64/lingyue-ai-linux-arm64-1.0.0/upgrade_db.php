<?php

header('Content-Type: text/html; charset=utf-8');

echo "<h2>数据库升级工具</h2>";

try {
    require_once __DIR__ . '/includes/Database.php';
    $db = Database::getInstance();
    $pdo = $db->getPdo();
    
    echo "<div style='color:green'>✅ 数据库连接成功</div>";
    echo "<hr>";
    
    $errors = [];
    $success = [];
    

    echo "<h3>1. 检查 users 表</h3>";
    try {
        $columns = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
        $existingColumns = array_column($columns, 'name');
        
        $requiredColumns = [
            'last_login' => 'DATETIME NULL',
            'is_active' => 'INTEGER DEFAULT 1'
        ];
        
        foreach ($requiredColumns as $colName => $colType) {
            if (!in_array($colName, $existingColumns)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN {$colName} {$colType}");
                $success[] = "users 表添加列: {$colName}";
                echo "<div style='color:green'>✅ 添加列: {$colName}</div>";
            } else {
                echo "<div>✓ 列已存在: {$colName}</div>";
            }
        }
    } catch (Exception $e) {
        $errors[] = "users 表: " . $e->getMessage();
        echo "<div style='color:red'>❌ 错误: {$e->getMessage()}</div>";
    }
    

    echo "<hr><h3>2. 创建缺失的表</h3>";
    

    try {
        $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='agents'")->fetch();
        if (!$result) {
            $pdo->exec("CREATE TABLE agents (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                icon VARCHAR(50) DEFAULT 'fa-robot',
                color VARCHAR(20) DEFAULT '#667eea',
                role_name VARCHAR(100),
                role_description TEXT,
                personality TEXT,
                capabilities TEXT,
                model_provider VARCHAR(50),
                model_id VARCHAR(100),
                temperature DECIMAL(3,2) DEFAULT 0.7,
                max_tokens INTEGER DEFAULT 2048,
                knowledge_base_ids TEXT,
                tools TEXT,
                welcome_message TEXT,
                status VARCHAR(20) DEFAULT 'draft',
                deploy_token VARCHAR(64),
                deploy_url VARCHAR(255),
                usage_count INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )");
            

            $pdo->exec("CREATE INDEX idx_agent_user ON agents(user_id)");
            $pdo->exec("CREATE INDEX idx_agent_status ON agents(status)");
            $pdo->exec("CREATE INDEX idx_agent_token ON agents(deploy_token)");
            
            $success[] = "创建 agents 表";
            echo "<div style='color:green'>✅ 创建 agents 表</div>";
        } else {
            echo "<div>✓ agents 表已存在</div>";
        }
    } catch (Exception $e) {
        $errors[] = "agents 表: " . $e->getMessage();
        echo "<div style='color:red'>❌ 错误: {$e->getMessage()}</div>";
    }
    

    try {
        $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='agent_conversations'")->fetch();
        if (!$result) {
            $pdo->exec("CREATE TABLE agent_conversations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                agent_id INTEGER NOT NULL,
                session_id VARCHAR(64) NOT NULL,
                user_id INTEGER,
                message TEXT NOT NULL,
                role VARCHAR(20) NOT NULL,
                model VARCHAR(100),
                tokens_used INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE
            )");
            

            $pdo->exec("CREATE INDEX idx_conv_agent ON agent_conversations(agent_id)");
            $pdo->exec("CREATE INDEX idx_conv_session ON agent_conversations(session_id)");
            
            $success[] = "创建 agent_conversations 表";
            echo "<div style='color:green'>✅ 创建 agent_conversations 表</div>";
        } else {
            echo "<div>✓ agent_conversations 表已存在</div>";
        }
    } catch (Exception $e) {
        $errors[] = "agent_conversations 表: " . $e->getMessage();
        echo "<div style='color:red'>❌ 错误: {$e->getMessage()}</div>";
    }
    

    echo "<hr><h3>3. 创建知识库相关表</h3>";
    
    try {

        $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='knowledge_bases'")->fetch();
        if (!$result) {
            $pdo->exec("CREATE TABLE knowledge_bases (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                status VARCHAR(20) DEFAULT 'active',
                document_count INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )");
            
            $pdo->exec("CREATE INDEX idx_kb_user ON knowledge_bases(user_id)");
            $pdo->exec("CREATE INDEX idx_kb_status ON knowledge_bases(status)");
            
            $success[] = "创建 knowledge_bases 表";
            echo "<div style='color:green'>✅ 创建 knowledge_bases 表</div>";
        } else {
            echo "<div>✓ knowledge_bases 表已存在</div>";
        }
    } catch (Exception $e) {
        $errors[] = "knowledge_bases 表: " . $e->getMessage();
        echo "<div style='color:red'>❌ 错误: {$e->getMessage()}</div>";
    }
    

    echo "<hr><h3>4. 数据检查</h3>";
    try {
        $tables = ['users', 'workflows', 'chat_history', 'agents', 'agent_conversations'];
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>表名</th><th>记录数</th></tr>";
        foreach ($tables as $table) {
            try {
                $count = $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
                echo "<tr><td>{$table}</td><td>{$count}</td></tr>";
            } catch (Exception $e) {
                echo "<tr><td>{$table}</td><td style='color:red'>无法访问</td></tr>";
            }
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<div style='color:red'>❌ 数据检查失败: {$e->getMessage()}</div>";
    }
    

    echo "<hr><h3>5. 检查管理员账号</h3>";
    try {
        require_once __DIR__ . '/includes/UserManager.php';
        $userManager = new UserManager();
        

        $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        
        if ($userCount == 0) {
            $result = $userManager->createUser('admin', 'admin123', 'admin@example.com', UserManager::ROLE_ADMIN);
            if ($result['success']) {
                $success[] = "创建默认管理员账号";
                echo "<div style='color:green;background:#f0fdf4;padding:10px;border-radius:5px'>";
                echo "✅ 默认管理员账号已创建<br>";
                echo "用户名: admin<br>";
                echo "密码: admin123<br>";
                echo "</div>";
            } else {
                echo "<div style='color:orange'>⚠️ 创建管理员失败: {$result['message']}</div>";
            }
        } else {
            echo "<div>✓ 已有 {$userCount} 个用户</div>";

            $users = $pdo->query("SELECT id, username, email, role FROM users LIMIT 3")->fetchAll();
            echo "<ul>";
            foreach ($users as $user) {
                echo "<li>{$user['username']} ({$user['role']})</li>";
            }
            echo "</ul>";
        }
    } catch (Exception $e) {
        $errors[] = "创建管理员: " . $e->getMessage();
        echo "<div style='color:red'>❌ 错误: {$e->getMessage()}</div>";
    }
    

    echo "<hr><h3>升级总结</h3>";
    if (count($success) > 0) {
        echo "<div style='color:green'>✅ 成功完成以下操作:</div>";
        echo "<ul>";
        foreach ($success as $s) {
            echo "<li>{$s}</li>";
        }
        echo "</ul>";
    }
    
    if (count($errors) > 0) {
        echo "<div style='color:red'>❌ 以下操作失败:</div>";
        echo "<ul>";
        foreach ($errors as $e) {
            echo "<li>{$e}</li>";
        }
        echo "</ul>";
    }
    
    if (count($success) == 0 && count($errors) == 0) {
        echo "<div style='color:green'>✅ 所有表结构正常，无需升级</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color:red'><h3>严重错误</h3>";
    echo "消息: " . $e->getMessage() . "<br>";
    echo "文件: " . $e->getFile() . "<br>";
    echo "行号: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "<hr>";
echo "<a href='upgrade_db.php' style='padding:10px 20px;background:#4c51bf;color:white;text-decoration:none;border-radius:5px;'>重新运行升级</a> | ";
echo "<a href='check_db.php' style='padding:10px 20px;background:#6b7280;color:white;text-decoration:none;border-radius:5px;'>运行诊断</a> | ";
echo "<a href='?route=home' style='padding:10px 20px;background:#10b981;color:white;text-decoration:none;border-radius:5px;'>返回首页</a>";
