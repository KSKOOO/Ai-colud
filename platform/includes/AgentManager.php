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
        
        // 创建任务表
        $this->db->exec("CREATE TABLE IF NOT EXISTS agent_tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            agent_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            status VARCHAR(20) DEFAULT 'pending', -- pending, in_progress, completed, failed, cancelled
            priority VARCHAR(20) DEFAULT 'medium', -- low, medium, high, urgent
            task_type VARCHAR(50) DEFAULT 'general', -- general, analysis, research, coding, writing
            input_data TEXT, -- JSON: 任务输入数据
            output_data TEXT, -- JSON: 任务输出结果
            progress INTEGER DEFAULT 0, -- 0-100
            started_at DATETIME,
            completed_at DATETIME,
            estimated_duration INTEGER, -- 预计耗时（分钟）
            actual_duration INTEGER, -- 实际耗时（分钟）
            parent_task_id INTEGER, -- 父任务ID，支持任务分解
            dependencies TEXT, -- JSON: 依赖的任务ID列表
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE,
            FOREIGN KEY (parent_task_id) REFERENCES agent_tasks(id) ON DELETE CASCADE
        )");
        
        // 创建任务日志表
        $this->db->exec("CREATE TABLE IF NOT EXISTS agent_task_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            task_id INTEGER NOT NULL,
            log_type VARCHAR(50) DEFAULT 'info', -- info, warning, error, progress, milestone
            message TEXT NOT NULL,
            details TEXT, -- JSON: 详细数据
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES agent_tasks(id) ON DELETE CASCADE
        )");
        
        // 创建AI员工绩效表
        $this->db->exec("CREATE TABLE IF NOT EXISTS agent_performance (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            agent_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            rating INTEGER, -- 1-5星评分
            feedback TEXT, -- 文字反馈
            task_quality INTEGER, -- 任务质量评分 1-5
            response_speed INTEGER, -- 响应速度评分 1-5
            helpfulness INTEGER, -- 有用性评分 1-5
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE
        )");
        
        // 创建索引
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_tasks_agent ON agent_tasks(agent_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_tasks_status ON agent_tasks(status)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_tasks_user ON agent_tasks(user_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_task_logs_task ON agent_task_logs(task_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_performance_agent ON agent_performance(agent_id)");
        
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
        
        try {
            $this->db->exec("ALTER TABLE agents ADD COLUMN success_rate INTEGER DEFAULT 0");
        } catch (Exception $e) { /* 字段已存在 */ }
        
        try {
            $this->db->exec("ALTER TABLE agents ADD COLUMN avg_rating DECIMAL(3,2) DEFAULT 0");
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
            $agent['capabilities'] = !empty($agent['capabilities']) ? json_decode($agent['capabilities'], true) : [];
            $agent['knowledge_base_ids'] = !empty($agent['knowledge_base_ids']) ? json_decode($agent['knowledge_base_ids'], true) : [];
            $agent['tools'] = !empty($agent['tools']) ? json_decode($agent['tools'], true) : [];
            $agent['tags'] = !empty($agent['tags']) ? json_decode($agent['tags'], true) : [];
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
            $agent['capabilities'] = !empty($agent['capabilities']) ? json_decode($agent['capabilities'], true) : [];
            $agent['knowledge_base_ids'] = !empty($agent['knowledge_base_ids']) ? json_decode($agent['knowledge_base_ids'], true) : [];
            $agent['tools'] = !empty($agent['tools']) ? json_decode($agent['tools'], true) : [];
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
        
        // 构建AI员工系统提示词
        $prompt = "【AI员工角色定义】\n";
        $prompt .= "你是一名专业的AI员工，不是普通的AI助手。你需要以员工的身份认真对待每一项工作任务。\n\n";
        
        if (!empty($agent['role_name'])) {
            $prompt .= "【职位】{$agent['role_name']}\n";
        }
        
        if (!empty($agent['role_description'])) {
            $prompt .= "【岗位职责】\n{$agent['role_description']}\n\n";
        }
        
        if (!empty($agent['personality'])) {
            $prompt .= "【工作风格】{$agent['personality']}\n\n";
        }
        
        // 能力说明
        $capabilities = $agent['capabilities'] ?? [];
        if (!empty($capabilities)) {
            $prompt .= "【工作能力】\n";
            if (in_array('chat', $capabilities)) {
                $prompt .= "- 专业对话：可以进行深入的专业交流和咨询\n";
            }
            if (in_array('file_analysis', $capabilities)) {
                $prompt .= "- 文档分析：可以阅读、理解和分析各类文档、报告、数据\n";
            }
            if (in_array('web_search', $capabilities)) {
                $prompt .= "- 信息检索：可以搜索网络获取最新信息和行业动态\n";
            }
            if (in_array('code_execution', $capabilities)) {
                $prompt .= "- 代码执行：可以编写、执行和调试代码\n";
            }
            if (in_array('knowledge_query', $capabilities)) {
                $prompt .= "- 知识库查询：可以访问和使用关联的知识库获取专业信息\n";
            }
            if (in_array('image_generation', $capabilities)) {
                $prompt .= "- 图像生成：可以根据描述生成AI图像\n";
            }
            if (in_array('task_planning', $capabilities)) {
                $prompt .= "- 任务规划：可以将复杂任务分解为可执行的步骤\n";
            }
            if (in_array('data_analysis', $capabilities)) {
                $prompt .= "- 数据分析：可以处理数据、生成报表和可视化\n";
            }
            if (in_array('translation', $capabilities)) {
                $prompt .= "- 多语言翻译：可以进行多种语言之间的翻译\n";
            }
            if (in_array('summarization', $capabilities)) {
                $prompt .= "- 摘要生成：可以生成长文本的精炼摘要\n";
            }
            $prompt .= "\n";
        }
        
        // AI员工工作准则
        $prompt .= "【工作准则】\n";
        $prompt .= "1. 主动性：主动理解用户需求，提供超出期望的解决方案\n";
        $prompt .= "2. 专业性：以专业标准完成工作，确保输出质量\n";
        $prompt .= "3. 可追溯：记录工作过程和思考逻辑，便于复盘\n";
        $prompt .= "4. 持续学习：从每次交互中学习，不断优化工作方式\n";
        $prompt .= "5. 协作精神：与团队成员（包括人类和其他AI）良好协作\n\n";
        
        $prompt .= "【输出要求】\n";
        $prompt .= "- 结构化输出：使用清晰的标题、列表和段落组织内容\n";
        $prompt .= "- 可执行性：提供具体、可操作的步骤和建议\n";
        $prompt .= "- 完整性：确保回答全面，不遗漏重要信息\n";
        $prompt .= "- 准确性：不确定的信息要明确标注，不编造内容\n\n";
        
        // 添加知识库信息
        $knowledgeBases = $agent['knowledge_base_ids'] ?? [];
        if (!empty($knowledgeBases)) {
            $prompt .= "【已连接知识库】\n";
            $prompt .= "你可以访问以下知识库获取信息：\n";
            foreach ($knowledgeBases as $kbId) {
                $kbInfo = $this->getKnowledgeBaseInfo($kbId);
                if ($kbInfo) {
                    $prompt .= "- {$kbInfo['name']}: {$kbInfo['description']}\n";
                }
            }
            $prompt .= "\n";
        }
        
        return $prompt ?: '你是一名专业的AI员工，请认真对待每一项工作任务。';
    }
    
    
    /**
     * 获取知识库信息
     */
    private function getKnowledgeBaseInfo($kbId) {
        try {
            $sql = "SELECT id, name, description FROM knowledge_bases WHERE id = :id AND status = 'active'";
            return $this->db->fetch($sql, ['id' => $kbId]);
        } catch (Exception $e) {
            return null;
        }
    }
    
    
    // ==================== 任务管理功能 ====================
    
    /**
     * 创建任务
     */
    public function createTask($agentId, $userId, $data) {
        $sql = "INSERT INTO agent_tasks (
            agent_id, user_id, title, description, task_type, 
            priority, input_data, estimated_duration, parent_task_id, dependencies
        ) VALUES (
            :agent_id, :user_id, :title, :description, :task_type,
            :priority, :input_data, :estimated_duration, :parent_task_id, :dependencies
        )";
        
        $params = [
            'agent_id' => $agentId,
            'user_id' => $userId,
            'title' => $data['title'] ?? '未命名任务',
            'description' => $data['description'] ?? '',
            'task_type' => $data['task_type'] ?? 'general',
            'priority' => $data['priority'] ?? 'medium',
            'input_data' => json_encode($data['input_data'] ?? [], JSON_UNESCAPED_UNICODE),
            'estimated_duration' => $data['estimated_duration'] ?? null,
            'parent_task_id' => $data['parent_task_id'] ?? null,
            'dependencies' => json_encode($data['dependencies'] ?? [], JSON_UNESCAPED_UNICODE)
        ];
        
        $this->db->execute($sql, $params);
        $taskId = $this->db->lastInsertId();
        
        // 记录任务创建日志
        $this->addTaskLog($taskId, 'info', '任务已创建', ['creator' => $userId]);
        
        // 更新智能体任务计数
        $this->db->execute(
            "UPDATE agents SET total_tasks = total_tasks + 1 WHERE id = :id",
            ['id' => $agentId]
        );
        
        return [
            'success' => true,
            'task_id' => $taskId,
            'message' => '任务创建成功'
        ];
    }
    
    
    /**
     * 获取任务详情
     */
    public function getTask($taskId) {
        $sql = "SELECT * FROM agent_tasks WHERE id = :id";
        $task = $this->db->fetch($sql, ['id' => $taskId]);
        
        if ($task) {
            $task['input_data'] = !empty($task['input_data']) ? json_decode($task['input_data'], true) : [];
            $task['output_data'] = !empty($task['output_data']) ? json_decode($task['output_data'], true) : [];
            $task['dependencies'] = !empty($task['dependencies']) ? json_decode($task['dependencies'], true) : [];
        }
        
        return $task;
    }
    
    
    /**
     * 获取智能体的任务列表
     */
    public function getAgentTasks($agentId, $status = null, $limit = 50) {
        $sql = "SELECT * FROM agent_tasks WHERE agent_id = :agent_id";
        $params = ['agent_id' => $agentId];
        
        if ($status) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        }
        
        $sql .= " ORDER BY 
            CASE priority 
                WHEN 'urgent' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'medium' THEN 3 
                ELSE 4 
            END,
            created_at DESC 
            LIMIT :limit";
        $params['limit'] = $limit;
        
        $tasks = $this->db->fetchAll($sql, $params);
        
        foreach ($tasks as &$task) {
            $task['input_data'] = !empty($task['input_data']) ? json_decode($task['input_data'], true) : [];
            $task['output_data'] = !empty($task['output_data']) ? json_decode($task['output_data'], true) : [];
        }
        
        return $tasks;
    }
    
    
    /**
     * 开始执行任务
     */
    public function startTask($taskId, $agentId) {
        $this->db->execute(
            "UPDATE agent_tasks SET 
                status = 'in_progress', 
                started_at = datetime('now'),
                updated_at = datetime('now')
            WHERE id = :id AND agent_id = :agent_id",
            ['id' => $taskId, 'agent_id' => $agentId]
        );
        
        $this->addTaskLog($taskId, 'milestone', '任务开始执行');
        
        return ['success' => true, 'message' => '任务已开始'];
    }
    
    
    /**
     * 更新任务进度
     */
    public function updateTaskProgress($taskId, $progress, $message = '') {
        $progress = max(0, min(100, intval($progress)));
        
        $this->db->execute(
            "UPDATE agent_tasks SET 
                progress = :progress,
                updated_at = datetime('now')
            WHERE id = :id",
            ['id' => $taskId, 'progress' => $progress]
        );
        
        if ($message) {
            $this->addTaskLog($taskId, 'progress', $message, ['progress' => $progress]);
        }
        
        return ['success' => true, 'progress' => $progress];
    }
    
    
    /**
     * 完成任务
     */
    public function completeTask($taskId, $outputData = null) {
        $task = $this->getTask($taskId);
        if (!$task) {
            return ['success' => false, 'error' => '任务不存在'];
        }
        
        // 计算实际耗时
        $startedAt = strtotime($task['started_at'] ?? 'now');
        $actualDuration = ceil((time() - $startedAt) / 60); // 分钟
        
        $this->db->execute(
            "UPDATE agent_tasks SET 
                status = 'completed',
                progress = 100,
                output_data = :output_data,
                completed_at = datetime('now'),
                actual_duration = :actual_duration,
                updated_at = datetime('now')
            WHERE id = :id",
            [
                'id' => $taskId,
                'output_data' => json_encode($outputData ?? [], JSON_UNESCAPED_UNICODE),
                'actual_duration' => $actualDuration
            ]
        );
        
        $this->addTaskLog($taskId, 'milestone', '任务已完成', ['duration' => $actualDuration]);
        
        // 更新智能体成功率统计
        $this->updateAgentSuccessRate($task['agent_id']);
        
        return ['success' => true, 'message' => '任务完成'];
    }
    
    
    /**
     * 任务失败
     */
    public function failTask($taskId, $errorMessage) {
        $this->db->execute(
            "UPDATE agent_tasks SET 
                status = 'failed',
                updated_at = datetime('now')
            WHERE id = :id",
            ['id' => $taskId]
        );
        
        $this->addTaskLog($taskId, 'error', $errorMessage);
        
        // 更新智能体成功率统计
        $task = $this->getTask($taskId);
        if ($task) {
            $this->updateAgentSuccessRate($task['agent_id']);
        }
        
        return ['success' => true, 'message' => '任务状态已更新为失败'];
    }
    
    
    /**
     * 添加任务日志
     */
    public function addTaskLog($taskId, $logType, $message, $details = null) {
        $sql = "INSERT INTO agent_task_logs (task_id, log_type, message, details) 
                VALUES (:task_id, :log_type, :message, :details)";
        
        $this->db->execute($sql, [
            'task_id' => $taskId,
            'log_type' => $logType,
            'message' => $message,
            'details' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null
        ]);
    }
    
    
    /**
     * 获取任务日志
     */
    public function getTaskLogs($taskId) {
        $sql = "SELECT * FROM agent_task_logs 
                WHERE task_id = :task_id 
                ORDER BY created_at ASC";
        
        $logs = $this->db->fetchAll($sql, ['task_id' => $taskId]);
        
        foreach ($logs as &$log) {
            $log['details'] = !empty($log['details']) ? json_decode($log['details'], true) : [];
        }
        
        return $logs;
    }
    
    
    /**
     * 更新智能体成功率
     */
    private function updateAgentSuccessRate($agentId) {
        $stats = $this->db->fetch(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
            FROM agent_tasks 
            WHERE agent_id = :agent_id",
            ['agent_id' => $agentId]
        );
        
        if ($stats && $stats['total'] > 0) {
            $successRate = round(($stats['completed'] / $stats['total']) * 100);
            $this->db->execute(
                "UPDATE agents SET success_rate = :rate WHERE id = :id",
                ['id' => $agentId, 'rate' => $successRate]
            );
        }
    }
    
    
    // ==================== 绩效评估功能 ====================
    
    /**
     * 添加绩效评估
     */
    public function addPerformanceReview($agentId, $userId, $data) {
        $sql = "INSERT INTO agent_performance (
            agent_id, user_id, rating, feedback, 
            task_quality, response_speed, helpfulness
        ) VALUES (
            :agent_id, :user_id, :rating, :feedback,
            :task_quality, :response_speed, :helpfulness
        )";
        
        $params = [
            'agent_id' => $agentId,
            'user_id' => $userId,
            'rating' => $data['rating'] ?? null,
            'feedback' => $data['feedback'] ?? '',
            'task_quality' => $data['task_quality'] ?? null,
            'response_speed' => $data['response_speed'] ?? null,
            'helpfulness' => $data['helpfulness'] ?? null
        ];
        
        $this->db->execute($sql, $params);
        
        // 更新智能体平均评分
        $this->updateAgentRating($agentId);
        
        return ['success' => true, 'message' => '评价已提交'];
    }
    
    
    /**
     * 获取智能体绩效统计
     */
    public function getAgentPerformanceStats($agentId) {
        $stats = $this->db->fetch(
            "SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as avg_rating,
                AVG(task_quality) as avg_quality,
                AVG(response_speed) as avg_speed,
                AVG(helpfulness) as avg_helpfulness
            FROM agent_performance 
            WHERE agent_id = :agent_id",
            ['agent_id' => $agentId]
        );
        
        // 获取最近5条评价
        $recentReviews = $this->db->fetchAll(
            "SELECT * FROM agent_performance 
            WHERE agent_id = :agent_id 
            ORDER BY created_at DESC 
            LIMIT 5",
            ['agent_id' => $agentId]
        );
        
        return [
            'stats' => $stats,
            'recent_reviews' => $recentReviews
        ];
    }
    
    
    /**
     * 更新智能体平均评分
     */
    private function updateAgentRating($agentId) {
        $result = $this->db->fetch(
            "SELECT AVG(rating) as avg_rating FROM agent_performance WHERE agent_id = :agent_id",
            ['agent_id' => $agentId]
        );
        
        if ($result && $result['avg_rating'] !== null) {
            $this->db->execute(
                "UPDATE agents SET avg_rating = :rating WHERE id = :id",
                ['id' => $agentId, 'rating' => round($result['avg_rating'], 2)]
            );
        }
    }
}
