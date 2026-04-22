<?php

class AgentManager {
    private $db;
    

    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_DISABLED = 'disabled';
    
    public function __construct($database) {
        $this->db = $database;
        $this->initTables();
    }
    
    
    private function initTables() {

        $this->db->exec("CREATE TABLE IF NOT EXISTS agents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            icon VARCHAR(50) DEFAULT 'fa-robot',
            color VARCHAR(20) DEFAULT '#667eea',
            
            -- 角色设定
            role_name VARCHAR(100),
            role_description TEXT,
            personality TEXT,
            
            -- 能力配置
            capabilities TEXT, -- JSON: ['chat', 'file_analysis', 'web_search', 'code_execution']
            
            -- 模型配置
            model_provider VARCHAR(50),
            model_id VARCHAR(100),
            temperature DECIMAL(3,2) DEFAULT 0.7,
            max_tokens INTEGER DEFAULT 2048,
            
            -- 知识库
            knowledge_base_ids TEXT, -- JSON array of kb IDs
            
            -- 工具配置
            tools TEXT, -- JSON: [{name, config}]
            
            -- 开场白
            welcome_message TEXT,
            
            -- 状态
            status VARCHAR(20) DEFAULT 'draft',
            deploy_token VARCHAR(64),
            deploy_url VARCHAR(255),
            
            -- 统计
            usage_count INTEGER DEFAULT 0,
            
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        

        $this->db->exec("CREATE TABLE IF NOT EXISTS agent_conversations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            agent_id INTEGER NOT NULL,
            session_id VARCHAR(64) NOT NULL,
            user_id INTEGER,
            message TEXT NOT NULL,
            role VARCHAR(20) NOT NULL, -- 'user' or 'assistant'
            model VARCHAR(100),
            tokens_used INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE
        )");
        

        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_agent_user ON agents(user_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_agent_status ON agents(status)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_agent_token ON agents(deploy_token)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_conv_agent ON agent_conversations(agent_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_conv_session ON agent_conversations(session_id)");
        
        // 迁移：添加新字段
        try {
            $this->db->exec("ALTER TABLE agents ADD COLUMN category VARCHAR(50) DEFAULT 'general'");
        } catch (Exception $e) { /* 字段已存在 */ }
        
        try {
            $this->db->exec("ALTER TABLE agents ADD COLUMN tags TEXT");
        } catch (Exception $e) { /* 字段已存在 */ }
        
        try {
            $this->db->exec("ALTER TABLE agents ADD COLUMN system_prompt TEXT");
        } catch (Exception $e) { /* 字段已存在 */ }
        
        try {
            $this->db->exec("ALTER TABLE agents ADD COLUMN total_tasks INTEGER DEFAULT 0");
        } catch (Exception $e) { /* 字段已存在 */ }
    }
    
    
    public function createAgent($userId, $data) {
        $sql = "INSERT INTO agents (
            user_id, name, description, icon, color, category, tags,
            role_name, role_description, personality, system_prompt,
            capabilities, model_provider, model_id,
            temperature, max_tokens, knowledge_base_ids,
            tools, welcome_message, status
        ) VALUES (
            :user_id, :name, :description, :icon, :color, :category, :tags,
            :role_name, :role_description, :personality, :system_prompt,
            :capabilities, :model_provider, :model_id,
            :temperature, :max_tokens, :knowledge_base_ids,
            :tools, :welcome_message, :status
        )";
        
        $params = [
            'user_id' => $userId,
            'name' => $data['name'] ?? '未命名智能体',
            'description' => $data['description'] ?? '',
            'icon' => $data['icon'] ?? '🤖',
            'color' => $data['color'] ?? '#667eea',
            'category' => $data['category'] ?? 'general',
            'tags' => json_encode($data['tags'] ?? []),
            'role_name' => $data['role_name'] ?? '',
            'role_description' => $data['role_description'] ?? '',
            'personality' => $data['personality'] ?? '',
            'system_prompt' => $data['system_prompt'] ?? '',
            'capabilities' => json_encode($data['capabilities'] ?? ['chat']),
            'model_provider' => $data['model_provider'] ?? '',
            'model_id' => $data['model_id'] ?? '',
            'temperature' => $data['temperature'] ?? 0.7,
            'max_tokens' => $data['max_tokens'] ?? 2048,
            'knowledge_base_ids' => json_encode($data['knowledge_base_ids'] ?? []),
            'tools' => json_encode($data['tools'] ?? []),
            'welcome_message' => $data['welcome_message'] ?? '你好！我是你的AI助手。',
            'status' => self::STATUS_DRAFT
        ];
        
        $this->db->execute($sql, $params);
        $agentId = $this->db->lastInsertId();
        
        return [
            'success' => true,
            'agent_id' => $agentId,
            'message' => '智能体创建成功'
        ];
    }
    
    
    public function updateAgent($agentId, $userId, $data) {

        $agent = $this->getAgent($agentId);
        if (!$agent || $agent['user_id'] != $userId) {
            return ['success' => false, 'error' => '无权访问该智能体'];
        }
        
        $fields = [];
        $params = ['id' => $agentId];
        
        $allowedFields = [
            'name', 'description', 'icon', 'color', 'category', 'tags',
            'role_name', 'role_description', 'personality', 'system_prompt',
            'capabilities', 'model_provider', 'model_id',
            'temperature', 'max_tokens', 'knowledge_base_ids',
            'tools', 'welcome_message', 'status'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = is_array($data[$field]) ? json_encode($data[$field]) : $data[$field];
            }
        }
        
        if (empty($fields)) {
            return ['success' => false, 'error' => '没有要更新的字段'];
        }
        
        $fields[] = "updated_at = datetime('now')";
        
        $sql = "UPDATE agents SET " . implode(', ', $fields) . " WHERE id = :id";
        $this->db->execute($sql, $params);
        
        return ['success' => true, 'message' => '智能体更新成功'];
    }
    
    
    public function getAgent($agentId) {
        $sql = "SELECT * FROM agents WHERE id = :id";
        $agent = $this->db->fetch($sql, ['id' => $agentId]);
        
        if ($agent) {
            $agent['capabilities'] = json_decode($agent['capabilities'], true) ?: [];
            $agent['knowledge_base_ids'] = json_decode($agent['knowledge_base_ids'], true) ?: [];
            $agent['tools'] = json_decode($agent['tools'], true) ?: [];
            $agent['tags'] = json_decode($agent['tags'], true) ?: [];
        }
        
        return $agent;
    }
    
    
    public function getUserAgents($userId, $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT id, name, description, icon, color, category, tags, status, 
                deploy_token, usage_count, total_tasks, model_provider, model_id, temperature,
                created_at, updated_at
                FROM agents 
                WHERE user_id = :user_id 
                ORDER BY updated_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $agents = $this->db->fetchAll($sql, [
            'user_id' => $userId,
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        $total = $this->db->fetch(
            "SELECT COUNT(*) as count FROM agents WHERE user_id = :user_id",
            ['user_id' => $userId]
        )['count'];
        
        return [
            'agents' => $agents,
            'total' => $total,
            'page' => $page,
            'total_pages' => ceil($total / $limit)
        ];
    }
    
    
    public function deleteAgent($agentId, $userId) {
        $agent = $this->getAgent($agentId);
        if (!$agent || $agent['user_id'] != $userId) {
            return ['success' => false, 'error' => '无权访问该智能体'];
        }
        
        $this->db->execute("DELETE FROM agents WHERE id = :id", ['id' => $agentId]);
        
        return ['success' => true, 'message' => '智能体已删除'];
    }
    
    
    public function deployAgent($agentId, $userId) {
        $agent = $this->getAgent($agentId);
        if (!$agent || $agent['user_id'] != $userId) {
            return ['success' => false, 'error' => '无权访问该智能体'];
        }
        

        if (empty($agent['model_provider']) || empty($agent['model_id'])) {
            return ['success' => false, 'error' => '请先配置AI模型'];
        }
        

        $token = bin2hex(random_bytes(32));
        $deployUrl = 'agent/' . $token;
        
        $this->db->execute(
            "UPDATE agents SET 
                status = :status, 
                deploy_token = :token, 
                deploy_url = :url,
                updated_at = datetime('now')
            WHERE id = :id",
            [
                'id' => $agentId,
                'status' => self::STATUS_ACTIVE,
                'token' => $token,
                'url' => $deployUrl
            ]
        );
        
        return [
            'success' => true,
            'message' => '智能体部署成功',
            'deploy_url' => $deployUrl,
            'deploy_token' => $token
        ];
    }
    
    
    public function undeployAgent($agentId, $userId) {
        $agent = $this->getAgent($agentId);
        if (!$agent || $agent['user_id'] != $userId) {
            return ['success' => false, 'error' => '无权访问该智能体'];
        }
        
        $this->db->execute(
            "UPDATE agents SET status = :status, updated_at = datetime('now') WHERE id = :id",
            ['id' => $agentId, 'status' => self::STATUS_DRAFT]
        );
        
        return ['success' => true, 'message' => '智能体已停用'];
    }
    
    
    public function getAgentByToken($token) {
        $sql = "SELECT * FROM agents WHERE deploy_token = :token AND status = 'active'";
        $agent = $this->db->fetch($sql, ['token' => $token]);
        
        if ($agent) {
            $agent['capabilities'] = json_decode($agent['capabilities'], true) ?: [];
            $agent['knowledge_base_ids'] = json_decode($agent['knowledge_base_ids'], true) ?: [];
            $agent['tools'] = json_decode($agent['tools'], true) ?: [];
        }
        
        return $agent;
    }
    
    
    public function saveConversation($agentId, $sessionId, $userId, $message, $role, $model = null, $tokens = null) {
        // 检查最近5秒内是否有相同的消息（防止重复保存）
        $checkSql = "SELECT id FROM agent_conversations 
                     WHERE agent_id = :agent_id 
                     AND session_id = :session_id 
                     AND role = :role 
                     AND message = :message 
                     AND created_at > datetime('now', '-5 seconds')
                     LIMIT 1";
        
        $existing = $this->db->fetch($checkSql, [
            ':agent_id' => $agentId,
            ':session_id' => $sessionId,
            ':role' => $role,
            ':message' => $message
        ]);
        
        if ($existing) {
            // 重复消息，直接返回
            return $existing['id'];
        }
        
        $sql = "INSERT INTO agent_conversations 
                (agent_id, session_id, user_id, message, role, model, tokens_used) 
                VALUES (:agent_id, :session_id, :user_id, :message, :role, :model, :tokens)";
        
        $this->db->execute($sql, [
            'agent_id' => $agentId,
            'session_id' => $sessionId,
            'user_id' => $userId,
            'message' => $message,
            'role' => $role,
            'model' => $model,
            'tokens' => $tokens
        ]);
        

        if ($role === 'user') {
            $this->db->execute(
                "UPDATE agents SET usage_count = usage_count + 1 WHERE id = :id",
                ['id' => $agentId]
            );
        }
    }
    
    
    public function getConversationHistory($agentId, $sessionId, $limit = 50) {
        $sql = "SELECT * FROM agent_conversations 
                WHERE agent_id = :agent_id AND session_id = :session_id 
                ORDER BY created_at DESC 
                LIMIT :limit";
        
        $results = $this->db->fetchAll($sql, [
            'agent_id' => $agentId,
            'session_id' => $sessionId,
            'limit' => $limit
        ]);
        
        // 去重：相同内容且时间间隔小于1秒的消息视为重复
        $unique = [];
        $lastMsg = null;
        foreach ($results as $msg) {
            if ($lastMsg && 
                $lastMsg['role'] === $msg['role'] && 
                $lastMsg['message'] === $msg['message']) {
                // 检查时间间隔
                $lastTime = strtotime($lastMsg['created_at']);
                $currTime = strtotime($msg['created_at']);
                if (abs($lastTime - $currTime) < 2) {
                    continue; // 跳过重复消息
                }
            }
            $unique[] = $msg;
            $lastMsg = $msg;
        }
        
        return $unique;
    }
    
    
    public function buildSystemPrompt($agent) {
        // 优先使用自定义系统提示词
        if (!empty($agent['system_prompt'])) {
            return $agent['system_prompt'];
        }
        
        $prompt = "";
        
        if (!empty($agent['role_name'])) {
            $prompt .= "你是{$agent['role_name']}。";
        }
        
        if (!empty($agent['role_description'])) {
            $prompt .= $agent['role_description'] . "\n\n";
        }
        
        if (!empty($agent['personality'])) {
            $prompt .= "性格特点：{$agent['personality']}\n\n";
        }
        
        $capabilities = $agent['capabilities'] ?? [];
        if (in_array('file_analysis', $capabilities)) {
            $prompt .= "你可以分析用户上传的文件内容。\n";
        }
        if (in_array('web_search', $capabilities)) {
            $prompt .= "你可以进行网络搜索获取最新信息。\n";
        }
        if (in_array('code_execution', $capabilities)) {
            $prompt .= "你可以执行代码并返回结果。\n";
        }
        
        return $prompt ?: '你是一个 helpful 的AI助手。';
    }
}
