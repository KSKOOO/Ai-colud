<?php
/**
 * OpenClaw AI智能体管理系统
 * "养龙虾" - AI智能体的部署、训练、优化系统
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/Database.php';

session_start();

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'error' => '未登录']);
    exit;
}

$userId = $_SESSION['user']['id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $db = Database::getInstance();
    
    // 初始化数据库表
    initOpenClawTables($db);
    
    switch ($action) {
        // 智能体管理
        case 'getAgents':
            getAgents($db, $userId);
            break;
        case 'createAgent':
            createAgent($db, $userId);
            break;
        case 'updateAgent':
            updateAgent($db, $userId);
            break;
        case 'deleteAgent':
            deleteAgent($db, $userId);
            break;
        case 'getAgentDetails':
            getAgentDetails($db, $userId);
            break;
            
        // 技能管理
        case 'getSkills':
            getSkills($db, $userId);
            break;
        case 'createSkill':
            createSkill($db, $userId);
            break;
        case 'assignSkill':
            assignSkill($db, $userId);
            break;
            
        // 训练记录（喂养）
        case 'getFeedingHistory':
            getFeedingHistory($db, $userId);
            break;
        case 'feedAgent':
            feedAgent($db, $userId);
            break;
            
        // 任务执行
        case 'getAgentTasks':
            getAgentTasks($db, $userId);
            break;
        case 'createTask':
            createTask($db, $userId);
            break;
        case 'getTaskLogs':
            getTaskLogs($db, $userId);
            break;
            
        // 记忆管理
        case 'getAgentMemory':
            getAgentMemory($db, $userId);
            break;
        case 'addMemory':
            addMemory($db, $userId);
            break;
        case 'clearMemory':
            clearMemory($db, $userId);
            break;
            
        // 统计
        case 'getAgentStats':
            getAgentStats($db, $userId);
            break;
        case 'getDashboard':
            getDashboard($db, $userId);
            break;
            
        // 部署管理
        case 'deployAgent':
            deployAgent($db, $userId);
            break;
        case 'undeployAgent':
            undeployAgent($db, $userId);
            break;
        case 'getDeploymentStatus':
            getDeploymentStatus($db, $userId);
            break;
        case 'executeTask':
            executeTask($db, $userId);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => '未知操作']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * 初始化数据库表
 */
function initOpenClawTables($db) {
    // 智能体表（龙虾）
    $db->exec("CREATE TABLE IF NOT EXISTS openclaw_agents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        avatar TEXT DEFAULT '🦞',
        personality TEXT,
        system_prompt TEXT,
        model TEXT DEFAULT 'gpt-3.5-turbo',
        temperature REAL DEFAULT 0.7,
        max_tokens INTEGER DEFAULT 4096,
        status TEXT DEFAULT '孵化中',
        level INTEGER DEFAULT 1,
        experience INTEGER DEFAULT 0,
        intelligence REAL DEFAULT 50.0,
        autonomy REAL DEFAULT 50.0,
        memory_size INTEGER DEFAULT 100,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_active_at DATETIME,
        total_tasks INTEGER DEFAULT 0,
        success_rate REAL DEFAULT 0,
        notes TEXT
    )");
    
    // 技能表
    $db->exec("CREATE TABLE IF NOT EXISTS openclaw_skills (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        name TEXT NOT NULL,
        description TEXT,
        skill_type TEXT DEFAULT 'custom',
        config TEXT,
        is_public INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // 智能体技能关联表
    $db->exec("CREATE TABLE IF NOT EXISTS openclaw_agent_skills (
        agent_id INTEGER NOT NULL,
        skill_id INTEGER NOT NULL,
        proficiency INTEGER DEFAULT 0,
        unlocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (agent_id, skill_id)
    )");
    
    // 喂养记录表（训练数据）
    $db->exec("CREATE TABLE IF NOT EXISTS openclaw_feeding (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        agent_id INTEGER NOT NULL,
        feed_type TEXT,
        feed_data TEXT,
        tokens_used INTEGER DEFAULT 0,
        experience_gained INTEGER DEFAULT 0,
        fed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        notes TEXT
    )");
    
    // 任务执行表
    $db->exec("CREATE TABLE IF NOT EXISTS openclaw_tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        agent_id INTEGER NOT NULL,
        task_type TEXT,
        task_input TEXT,
        task_output TEXT,
        status TEXT DEFAULT 'pending',
        started_at DATETIME,
        completed_at DATETIME,
        tokens_used INTEGER DEFAULT 0,
        success INTEGER DEFAULT 0,
        error_message TEXT
    )");
    
    // 记忆表
    $db->exec("CREATE TABLE IF NOT EXISTS openclaw_memory (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        agent_id INTEGER NOT NULL,
        memory_type TEXT DEFAULT 'conversation',
        content TEXT,
        embedding TEXT,
        importance REAL DEFAULT 0.5,
        access_count INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_accessed DATETIME
    )");
    
    // 部署表
    $db->exec("CREATE TABLE IF NOT EXISTS openclaw_deployments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        agent_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        token TEXT UNIQUE NOT NULL,
        status TEXT DEFAULT 'active',
        usage_count INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_used_at DATETIME,
        expires_at DATETIME
    )");
}

// ========== 智能体管理 ==========

function getAgents($db, $userId) {
    $agents = $db->fetchAll("SELECT * FROM openclaw_agents WHERE user_id = ? ORDER BY created_at DESC", [$userId]);
    
    foreach ($agents as &$agent) {
        // 获取技能数量
        $agent['skill_count'] = $db->fetch("SELECT COUNT(*) as count FROM openclaw_agent_skills WHERE agent_id = ?", [$agent['id']])['count'];
        // 获取喂养次数
        $agent['feed_count'] = $db->fetch("SELECT COUNT(*) as count FROM openclaw_feeding WHERE agent_id = ?", [$agent['id']])['count'];
    }
    
    echo json_encode(['success' => true, 'agents' => $agents]);
}

function createAgent($db, $userId) {
    $name = $_POST['name'] ?? '小虾米';
    $model = $_POST['model'] ?? 'gpt-3.5-turbo';
    $personality = $_POST['personality'] ?? '友好、乐于助人、聪明';
    $systemPrompt = $_POST['system_prompt'] ?? '你是一个AI助手，帮助用户完成各种任务。';
    
    $data = [
        'user_id' => $userId,
        'name' => $name,
        'model' => $model,
        'personality' => $personality,
        'system_prompt' => $systemPrompt,
        'temperature' => floatval($_POST['temperature'] ?? 0.7),
        'max_tokens' => intval($_POST['max_tokens'] ?? 4096),
        'avatar' => $_POST['avatar'] ?? '🦞',
        'status' => '孵化中',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $id = $db->insert('openclaw_agents', $data);
    
    // 为新智能体分配基础技能
    $baseSkills = $db->fetchAll("SELECT id FROM openclaw_skills WHERE is_public = 1 OR user_id = ? LIMIT 3", [$userId]);
    foreach ($baseSkills as $skill) {
        $db->insert('openclaw_agent_skills', [
            'agent_id' => $id,
            'skill_id' => $skill['id'],
            'proficiency' => 50,
            'unlocked_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    echo json_encode(['success' => true, 'id' => $id, 'message' => '龙虾智能体孵化成功！']);
}

function updateAgent($db, $userId) {
    $id = intval($_POST['id'] ?? 0);
    $data = [
        'name' => $_POST['name'] ?? '',
        'personality' => $_POST['personality'] ?? '',
        'system_prompt' => $_POST['system_prompt'] ?? '',
        'model' => $_POST['model'] ?? '',
        'temperature' => floatval($_POST['temperature'] ?? 0.7),
        'max_tokens' => intval($_POST['max_tokens'] ?? 4096),
        'status' => $_POST['status'] ?? '活跃',
        'notes' => $_POST['notes'] ?? ''
    ];
    
    $db->update('openclaw_agents', $data, 'id = ? AND user_id = ?', [$id, $userId]);
    echo json_encode(['success' => true, 'message' => '智能体更新成功']);
}

function deleteAgent($db, $userId) {
    $id = intval($_POST['id'] ?? 0);
    
    // 删除关联数据
    $db->delete('openclaw_agent_skills', 'agent_id = ?', [$id]);
    $db->delete('openclaw_feeding', 'agent_id = ?', [$id]);
    $db->delete('openclaw_tasks', 'agent_id = ?', [$id]);
    $db->delete('openclaw_memory', 'agent_id = ?', [$id]);
    $db->delete('openclaw_agents', 'id = ? AND user_id = ?', [$id, $userId]);
    
    echo json_encode(['success' => true, 'message' => '智能体已释放']);
}

function getAgentDetails($db, $userId) {
    $id = intval($_GET['id'] ?? 0);
    $agent = $db->fetch("SELECT * FROM openclaw_agents WHERE id = ? AND user_id = ?", [$id, $userId]);
    
    if (!$agent) {
        echo json_encode(['success' => false, 'error' => '智能体不存在']);
        return;
    }
    
    // 获取技能列表
    $agent['skills'] = $db->fetchAll("
        SELECT s.*, a_s.proficiency 
        FROM openclaw_skills s 
        JOIN openclaw_agent_skills a_s ON s.id = a_s.skill_id 
        WHERE a_s.agent_id = ?
    ", [$id]);
    
    // 获取最近喂养
    $agent['recent_feeding'] = $db->fetchAll("
        SELECT * FROM openclaw_feeding 
        WHERE agent_id = ? 
        ORDER BY fed_at DESC 
        LIMIT 5
    ", [$id]);
    
    // 获取最近任务
    $agent['recent_tasks'] = $db->fetchAll("
        SELECT * FROM openclaw_tasks 
        WHERE agent_id = ? 
        ORDER BY started_at DESC 
        LIMIT 5
    ", [$id]);
    
    echo json_encode(['success' => true, 'agent' => $agent]);
}

// ========== 技能管理 ==========

function getSkills($db, $userId) {
    $skills = $db->fetchAll("
        SELECT * FROM openclaw_skills 
        WHERE user_id = ? OR is_public = 1 
        ORDER BY is_public DESC, created_at DESC
    ", [$userId]);
    
    echo json_encode(['success' => true, 'skills' => $skills]);
}

function createSkill($db, $userId) {
    $data = [
        'user_id' => $userId,
        'name' => $_POST['name'] ?? '新技能',
        'description' => $_POST['description'] ?? '',
        'skill_type' => $_POST['skill_type'] ?? 'custom',
        'config' => $_POST['config'] ?? '{}',
        'is_public' => intval($_POST['is_public'] ?? 0)
    ];
    
    $id = $db->insert('openclaw_skills', $data);
    echo json_encode(['success' => true, 'id' => $id, 'message' => '技能创建成功']);
}

function assignSkill($db, $userId) {
    $agentId = intval($_POST['agent_id'] ?? 0);
    $skillId = intval($_POST['skill_id'] ?? 0);
    
    // 检查是否已分配
    $exists = $db->fetch("SELECT 1 FROM openclaw_agent_skills WHERE agent_id = ? AND skill_id = ?", [$agentId, $skillId]);
    
    if ($exists) {
        echo json_encode(['success' => false, 'error' => '该技能已分配']);
        return;
    }
    
    $db->insert('openclaw_agent_skills', [
        'agent_id' => $agentId,
        'skill_id' => $skillId,
        'proficiency' => 0,
        'unlocked_at' => date('Y-m-d H:i:s')
    ]);
    
    echo json_encode(['success' => true, 'message' => '技能已分配给智能体']);
}

// ========== 喂养（训练） ==========

function getFeedingHistory($db, $userId) {
    $agentId = $_GET['agent_id'] ?? null;
    $limit = intval($_GET['limit'] ?? 50);
    
    if ($agentId) {
        $records = $db->fetchAll("
            SELECT f.*, a.name as agent_name 
            FROM openclaw_feeding f 
            JOIN openclaw_agents a ON f.agent_id = a.id
            WHERE f.user_id = ? AND f.agent_id = ? 
            ORDER BY f.fed_at DESC 
            LIMIT ?
        ", [$userId, $agentId, $limit]);
    } else {
        $records = $db->fetchAll("
            SELECT f.*, a.name as agent_name 
            FROM openclaw_feeding f 
            JOIN openclaw_agents a ON f.agent_id = a.id
            WHERE f.user_id = ? 
            ORDER BY f.fed_at DESC 
            LIMIT ?
        ", [$userId, $limit]);
    }
    
    echo json_encode(['success' => true, 'records' => $records]);
}

function feedAgent($db, $userId) {
    $agentId = intval($_POST['agent_id'] ?? 0);
    $feedType = $_POST['feed_type'] ?? 'conversation';
    $feedData = $_POST['feed_data'] ?? '';
    $tokensUsed = intval($_POST['tokens_used'] ?? 0);
    
    // 计算经验值（每100 tokens = 1经验）
    $experienceGained = max(1, intval($tokensUsed / 100));
    
    $id = $db->insert('openclaw_feeding', [
        'user_id' => $userId,
        'agent_id' => $agentId,
        'feed_type' => $feedType,
        'feed_data' => $feedData,
        'tokens_used' => $tokensUsed,
        'experience_gained' => $experienceGained,
        'fed_at' => date('Y-m-d H:i:s'),
        'notes' => $_POST['notes'] ?? ''
    ]);
    
    // 更新智能体经验和等级
    $agent = $db->fetch("SELECT experience, level, intelligence FROM openclaw_agents WHERE id = ?", [$agentId]);
    $newExp = ($agent['experience'] ?? 0) + $experienceGained;
    $newLevel = floor($newExp / 100) + 1;
    $newIntelligence = min(100, ($agent['intelligence'] ?? 50) + $experienceGained * 0.1);
    
    $db->update('openclaw_agents', [
        'experience' => $newExp,
        'level' => $newLevel,
        'intelligence' => round($newIntelligence, 1),
        'status' => '活跃',
        'last_active_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$agentId]);
    
    echo json_encode([
        'success' => true, 
        'id' => $id, 
        'experience_gained' => $experienceGained,
        'new_level' => $newLevel,
        'message' => "喂养成功！获得 {$experienceGained} 经验值"
    ]);
}

// ========== 任务执行 ==========

function getAgentTasks($db, $userId) {
    $agentId = $_GET['agent_id'] ?? null;
    $status = $_GET['status'] ?? null;
    
    $sql = "SELECT t.*, a.name as agent_name FROM openclaw_tasks t JOIN openclaw_agents a ON t.agent_id = a.id WHERE t.user_id = ?";
    $params = [$userId];
    
    if ($agentId) {
        $sql .= " AND t.agent_id = ?";
        $params[] = $agentId;
    }
    if ($status) {
        $sql .= " AND t.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY t.started_at DESC LIMIT 100";
    
    $tasks = $db->fetchAll($sql, $params);
    echo json_encode(['success' => true, 'tasks' => $tasks]);
}

function createTask($db, $userId) {
    $data = [
        'user_id' => $userId,
        'agent_id' => intval($_POST['agent_id'] ?? 0),
        'task_type' => $_POST['task_type'] ?? 'chat',
        'task_input' => $_POST['task_input'] ?? '',
        'status' => 'pending',
        'started_at' => date('Y-m-d H:i:s')
    ];
    
    $id = $db->insert('openclaw_tasks', $data);
    
    // 更新智能体任务计数
    $db->exec("UPDATE openclaw_agents SET total_tasks = total_tasks + 1 WHERE id = ?", [$data['agent_id']]);
    
    echo json_encode(['success' => true, 'id' => $id, 'message' => '任务已创建']);
}

function getTaskLogs($db, $userId) {
    $taskId = intval($_GET['task_id'] ?? 0);
    $task = $db->fetch("SELECT * FROM openclaw_tasks WHERE id = ? AND user_id = ?", [$taskId, $userId]);
    
    if (!$task) {
        echo json_encode(['success' => false, 'error' => '任务不存在']);
        return;
    }
    
    echo json_encode(['success' => true, 'task' => $task]);
}

// ========== 记忆管理 ==========

function getAgentMemory($db, $userId) {
    $agentId = intval($_GET['agent_id'] ?? 0);
    $limit = intval($_GET['limit'] ?? 50);
    
    $memories = $db->fetchAll("
        SELECT * FROM openclaw_memory 
        WHERE agent_id = ? 
        ORDER BY importance DESC, created_at DESC 
        LIMIT ?
    ", [$agentId, $limit]);
    
    echo json_encode(['success' => true, 'memories' => $memories]);
}

function addMemory($db, $userId) {
    $data = [
        'agent_id' => intval($_POST['agent_id'] ?? 0),
        'memory_type' => $_POST['memory_type'] ?? 'conversation',
        'content' => $_POST['content'] ?? '',
        'importance' => floatval($_POST['importance'] ?? 0.5),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $id = $db->insert('openclaw_memory', $data);
    
    // 检查记忆数量限制
    $count = $db->fetch("SELECT COUNT(*) as count FROM openclaw_memory WHERE agent_id = ?", [$data['agent_id']])['count'];
    $agent = $db->fetch("SELECT memory_size FROM openclaw_agents WHERE id = ?", [$data['agent_id']]);
    
    if ($count > ($agent['memory_size'] ?? 100)) {
        // 删除最不重要的旧记忆
        $db->exec("
            DELETE FROM openclaw_memory 
            WHERE agent_id = ? AND id NOT IN (
                SELECT id FROM openclaw_memory 
                WHERE agent_id = ? 
                ORDER BY importance DESC, created_at DESC 
                LIMIT ?
            )
        ", [$data['agent_id'], $data['agent_id'], $agent['memory_size']]);
    }
    
    echo json_encode(['success' => true, 'id' => $id, 'message' => '记忆已保存']);
}

function clearMemory($db, $userId) {
    $agentId = intval($_POST['agent_id'] ?? 0);
    $memoryType = $_POST['memory_type'] ?? null;
    
    if ($memoryType) {
        $db->delete('openclaw_memory', 'agent_id = ? AND memory_type = ?', [$agentId, $memoryType]);
    } else {
        $db->delete('openclaw_memory', 'agent_id = ?', [$agentId]);
    }
    
    echo json_encode(['success' => true, 'message' => '记忆已清除']);
}

// ========== 统计 ==========

function getAgentStats($db, $userId) {
    $stats = [
        'total_agents' => $db->fetch("SELECT COUNT(*) as count FROM openclaw_agents WHERE user_id = ?", [$userId])['count'],
        'active_agents' => $db->fetch("SELECT COUNT(*) as count FROM openclaw_agents WHERE user_id = ? AND status = '活跃'", [$userId])['count'],
        'total_feeds' => $db->fetch("SELECT COUNT(*) as count FROM openclaw_feeding WHERE user_id = ?", [$userId])['count'],
        'total_tasks' => $db->fetch("SELECT COUNT(*) as count FROM openclaw_tasks WHERE user_id = ?", [$userId])['count'],
        'success_tasks' => $db->fetch("SELECT COUNT(*) as count FROM openclaw_tasks WHERE user_id = ? AND success = 1", [$userId])['count'],
        'total_tokens' => $db->fetch("SELECT SUM(tokens_used) as total FROM openclaw_feeding WHERE user_id = ?", [$userId])['total'] ?? 0
    ];
    
    $stats['success_rate'] = $stats['total_tasks'] > 0 ? round($stats['success_tasks'] / $stats['total_tasks'] * 100, 1) : 0;
    
    echo json_encode(['success' => true, 'statistics' => $stats]);
}

function getDashboard($db, $userId) {
    $dashboard = [
        'agents' => $db->fetchAll("SELECT id, name, avatar, level, experience, intelligence, status FROM openclaw_agents WHERE user_id = ? ORDER BY last_active_at DESC LIMIT 5", [$userId]),
        'recent_feeding' => $db->fetchAll("
            SELECT f.*, a.name as agent_name, a.avatar 
            FROM openclaw_feeding f 
            JOIN openclaw_agents a ON f.agent_id = a.id
            WHERE f.user_id = ? 
            ORDER BY f.fed_at DESC 
            LIMIT 10
        ", [$userId]),
        'recent_tasks' => $db->fetchAll("
            SELECT t.*, a.name as agent_name, a.avatar 
            FROM openclaw_tasks t 
            JOIN openclaw_agents a ON t.agent_id = a.id
            WHERE t.user_id = ? 
            ORDER BY t.started_at DESC 
            LIMIT 10
        ", [$userId]),
        'skills' => $db->fetchAll("SELECT * FROM openclaw_skills WHERE user_id = ? OR is_public = 1 ORDER BY created_at DESC LIMIT 10", [$userId])
    ];
    
    echo json_encode(['success' => true, 'dashboard' => $dashboard]);
}

// ========== 部署管理 ==========

function deployAgent($db, $userId) {
    $agentId = intval($_POST['agent_id'] ?? 0);
    
    // 检查智能体是否存在
    $agent = $db->fetch("SELECT * FROM openclaw_agents WHERE id = ? AND user_id = ?", [$agentId, $userId]);
    if (!$agent) {
        echo json_encode(['success' => false, 'error' => '智能体不存在']);
        return;
    }
    
    // 检查是否已部署
    $existing = $db->fetch("SELECT * FROM openclaw_deployments WHERE agent_id = ? AND user_id = ?", [$agentId, $userId]);
    
    if ($existing) {
        // 更新为活跃状态
        $db->update('openclaw_deployments', [
            'status' => 'active',
            'expires_at' => null
        ], 'id = ?', [$existing['id']]);
        
        $token = $existing['token'];
    } else {
        // 生成新token
        $token = 'oc_' . bin2hex(random_bytes(16));
        
        $db->insert('openclaw_deployments', [
            'agent_id' => $agentId,
            'user_id' => $userId,
            'token' => $token,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    // 更新智能体状态
    $db->update('openclaw_agents', ['status' => '已部署'], 'id = ?', [$agentId]);
    
    // 构建访问URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host;
    
    echo json_encode([
        'success' => true,
        'message' => '龙虾已部署成功！',
        'token' => $token,
        'urls' => [
            'info' => $baseUrl . '/api/openclaw_api.php?action=info&token=' . $token,
            'chat' => $baseUrl . '/api/openclaw_api.php?action=chat&token=' . $token,
            'history' => $baseUrl . '/api/openclaw_api.php?action=history&token=' . $token
        ],
        'embed_code' => '<iframe src="' . $baseUrl . '/api/openclaw_embed.php?token=' . $token . '" width="400" height="600"></iframe>'
    ]);
}

function undeployAgent($db, $userId) {
    $agentId = intval($_POST['agent_id'] ?? 0);
    
    $db->update('openclaw_deployments', ['status' => 'inactive'], 'agent_id = ? AND user_id = ?', [$agentId, $userId]);
    $db->update('openclaw_agents', ['status' => '活跃'], 'id = ?', [$agentId]);
    
    echo json_encode(['success' => true, 'message' => '部署已取消']);
}

function getDeploymentStatus($db, $userId) {
    $agentId = intval($_GET['agent_id'] ?? 0);
    
    $deployment = $db->fetch("
        SELECT d.*, a.name as agent_name 
        FROM openclaw_deployments d
        JOIN openclaw_agents a ON d.agent_id = a.id
        WHERE d.agent_id = ? AND d.user_id = ?
    ", [$agentId, $userId]);
    
    if (!$deployment) {
        echo json_encode(['success' => true, 'deployed' => false]);
        return;
    }
    
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host;
    
    echo json_encode([
        'success' => true,
        'deployed' => $deployment['status'] === 'active',
        'token' => $deployment['token'],
        'usage_count' => $deployment['usage_count'],
        'created_at' => $deployment['created_at'],
        'last_used_at' => $deployment['last_used_at'],
        'urls' => [
            'info' => $baseUrl . '/api/openclaw_api.php?action=info&token=' . $deployment['token'],
            'chat' => $baseUrl . '/api/openclaw_api.php?action=chat&token=' . $deployment['token']
        ]
    ]);
}

/**
 * 执行任务（内部调用）
 */
function executeTask($db, $userId) {
    $agentId = intval($_POST['agent_id'] ?? 0);
    $taskInput = $_POST['task_input'] ?? '';
    $taskType = $_POST['task_type'] ?? 'chat';
    
    $agent = $db->fetch("SELECT * FROM openclaw_agents WHERE id = ? AND user_id = ?", [$agentId, $userId]);
    if (!$agent) {
        echo json_encode(['success' => false, 'error' => '智能体不存在']);
        return;
    }
    
    // 获取相关记忆
    $memories = $db->fetchAll("
        SELECT content FROM openclaw_memory 
        WHERE agent_id = ? 
        ORDER BY importance DESC, created_at DESC 
        LIMIT 3
    ", [$agentId]);
    
    $memoryContext = '';
    if (!empty($memories)) {
        $memoryContext = "\n\n相关记忆：\n";
        foreach ($memories as $m) {
            $memoryContext .= "- " . $m['content'] . "\n";
        }
    }
    
    // 构建系统提示词
    $systemPrompt = $agent['system_prompt'] ?? '你是一个AI助手。';
    $systemPrompt .= "\n\n你的性格特点：" . ($agent['personality'] ?? '友好、乐于助人');
    
    if ($memoryContext) {
        $systemPrompt .= $memoryContext;
    }
    
    try {
        require_once __DIR__ . '/../lib/AIProviderManager.php';
        $providerManager = new AIProviderManager();
        $caller = $providerManager->createCaller();
        
        $result = $caller->chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $taskInput]
        ], [
            'model' => $agent['model'] ?? 'gpt-3.5-turbo',
            'temperature' => floatval($agent['temperature'] ?? 0.7),
            'max_tokens' => intval($agent['max_tokens'] ?? 2048)
        ]);
        
        if ($result['success']) {
            // 记录任务
            $db->insert('openclaw_tasks', [
                'user_id' => $userId,
                'agent_id' => $agentId,
                'task_type' => $taskType,
                'task_input' => $taskInput,
                'task_output' => $result['content'],
                'status' => 'completed',
                'started_at' => date('Y-m-d H:i:s'),
                'completed_at' => date('Y-m-d H:i:s'),
                'success' => 1,
                'tokens_used' => $result['usage']['total_tokens'] ?? 0
            ]);

            // 记录用量统计
            try {
                require_once __DIR__ . '/../lib/UsageTracker.php';
                $usageTracker = new UsageTracker();
                $inputTokens = $result['usage']['prompt_tokens'] ?? $usageTracker->estimateTokens($taskInput);
                $outputTokens = $result['usage']['completion_tokens'] ?? $usageTracker->estimateTokens($result['content']);
                $usageTracker->recordUsage(
                    $userId,
                    'openclaw_task',
                    $result['model'] ?? ($agent['model'] ?? 'gpt-3.5-turbo'),
                    $inputTokens,
                    $outputTokens,
                    ['agent_id' => $agentId, 'agent_name' => $agent['name'], 'task_type' => $taskType]
                );
            } catch (Exception $e) {
                error_log("Record openclaw usage failed: " . $e->getMessage());
            }

            // 更新智能体状态
            $db->update('openclaw_agents', [
                'total_tasks' => $agent['total_tasks'] + 1,
                'last_active_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$agentId]);

            echo json_encode([
                'success' => true,
                'response' => $result['content'],
                'tokens_used' => $result['usage']['total_tokens'] ?? 0
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'AI调用失败']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
