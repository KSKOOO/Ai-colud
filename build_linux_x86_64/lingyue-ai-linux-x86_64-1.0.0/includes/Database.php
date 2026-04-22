<?php

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $config = require __DIR__ . '/../config/database.php';
        
        try {
            if ($config['type'] === 'sqlite') {

                $dbDir = dirname($config['path']);
                if (!is_dir($dbDir)) {
                    mkdir($dbDir, 0755, true);
                }
                
                $dsn = "sqlite:" . $config['path'];
                $this->pdo = new PDO($dsn, null, null, $config['options']);
                

                $this->pdo->exec("PRAGMA foreign_keys = ON");
                

                $this->initTables();
            } else {

                $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
                $this->pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            }
        } catch (PDOException $e) {
            throw new Exception("数据库连接失败: " . $e->getMessage());
        }
    }

    
    private function initTables() {
        try {

            $this->pdo->exec("PRAGMA foreign_keys = OFF");
            

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(50) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                role VARCHAR(20) DEFAULT 'user',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_login DATETIME NULL,
                is_active INTEGER DEFAULT 1
            )");
            

            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_username ON users(username)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_email ON users(email)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_role ON users(role)");
            

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS workflows (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                model VARCHAR(50),
                nodes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS chat_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title VARCHAR(200),
                messages TEXT,
                model VARCHAR(50),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS online_models (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL,
                model_id VARCHAR(100) NOT NULL,
                api_type VARCHAR(50) DEFAULT 'custom',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS user_usage (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                action_type VARCHAR(50) NOT NULL,
                model VARCHAR(100),
                input_tokens INTEGER DEFAULT 0,
                output_tokens INTEGER DEFAULT 0,
                total_tokens INTEGER DEFAULT 0,
                cost DECIMAL(10,6) DEFAULT 0,
                request_data TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                created_date DATE DEFAULT CURRENT_DATE
            )");
            

            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_user_usage_user_id ON user_usage(user_id)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_user_usage_date ON user_usage(created_date)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_user_usage_user_date ON user_usage(user_id, created_date)");
            

            try {
                $this->pdo->exec("ALTER TABLE users ADD COLUMN balance DECIMAL(10,2) DEFAULT 0.00");
            } catch (PDOException $e) {

            }
            try {
                $this->pdo->exec("ALTER TABLE users ADD COLUMN api_key VARCHAR(64) UNIQUE");
            } catch (PDOException $e) {

            }
            try {
                $this->pdo->exec("ALTER TABLE users ADD COLUMN api_key_created_at DATETIME");
            } catch (PDOException $e) {

            }
            

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS user_recharges (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                payment_method VARCHAR(50) DEFAULT 'manual',
                payment_status VARCHAR(20) DEFAULT 'pending',
                payment_data TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                completed_at DATETIME
            )");
            

            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_recharge_user ON user_recharges(user_id)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_recharge_status ON user_recharges(payment_status)");
            

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS agents (
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
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS agent_conversations (
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
            

            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_agent_user ON agents(user_id)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_agent_status ON agents(status)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_agent_token ON agents(deploy_token)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_conv_agent ON agent_conversations(agent_id)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_conv_session ON agent_conversations(session_id)");
            

            $this->pdo->exec("PRAGMA foreign_keys = ON");
            
        } catch (PDOException $e) {
            error_log("Database initTables error: " . $e->getMessage());

        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPdo() {
        return $this->pdo;
    }

    
    public function exec($sql) {
        return $this->pdo->exec($sql);
    }

    
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    
    public function prepare($sql) {
        return $this->pdo->prepare($sql);
    }

    
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    
    public function execute($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        return $this->pdo->lastInsertId();
    }

    
    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = :{$key}";
        }
        $setStr = implode(', ', $set);
        $sql = "UPDATE {$table} SET {$setStr} WHERE {$where}";
        $params = array_merge($data, $whereParams);
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
}
?>
