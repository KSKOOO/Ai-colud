<?php
/**
 * 知识库API处理器
 */
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();
$currentUserId = $_SESSION['user']['id'] ?? null;

if (!$currentUserId) {
    echo json_encode(['success' => false, 'error' => '请先登录']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 检查是否是后台管理页面的请求（使用不同的参数格式）
$isAdminRequest = isset($_POST['name']) && isset($_FILES['file']);

try {
    // 初始化知识库表
    initKnowledgeBaseTables($db);
    
    // 确保用户有一个默认知识库
    ensureDefaultKnowledgeBase($db, $currentUserId);
    
    switch ($action) {
        case 'createKnowledgeBase':
            handleCreateKnowledgeBase($db, $currentUserId);
            break;
            
        case 'getKnowledgeBases':
            handleGetKnowledgeBases($db, $currentUserId);
            break;
            
        case 'getKnowledgeBase':
            handleGetKnowledgeBase($db, $currentUserId);
            break;
            
        case 'updateKnowledgeBase':
            handleUpdateKnowledgeBase($db, $currentUserId);
            break;
            
        case 'deleteKnowledgeBase':
            handleDeleteKnowledgeBase($db, $currentUserId);
            break;
            
        case 'uploadDocument':
            handleUploadDocument($db, $currentUserId);
            break;
            
        case 'getDocuments':
            handleGetDocuments($db, $currentUserId);
            break;
            
        case 'deleteDocument':
            handleDeleteDocument($db, $currentUserId);
            break;
            
        case 'searchKnowledgeBase':
            handleSearchKnowledgeBase($db, $currentUserId);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => '未知的操作类型']);
    }
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    $errorFile = $e->getFile();
    $errorLine = $e->getLine();
    error_log("Knowledge handler error: {$errorMsg} in {$errorFile}:{$errorLine}");
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'error' => $errorMsg,
        'file' => $errorFile,
        'line' => $errorLine
    ]);
}

/**
 * 确保用户有默认知识库
 */
function ensureDefaultKnowledgeBase($db, $userId) {
    // 检查用户是否已有知识库
    $existing = $db->fetch(
        "SELECT id FROM knowledge_bases WHERE user_id = :user_id LIMIT 1",
        ['user_id' => $userId]
    );
    
    if (!$existing) {
        // 创建默认知识库
        $db->execute(
            "INSERT INTO knowledge_bases (user_id, name, description, is_public) 
             VALUES (:user_id, '默认知识库', '系统自动创建的默认知识库', 0)",
            ['user_id' => $userId]
        );
    }
}

/**
 * 获取或创建用户的默认知识库ID
 */
function getDefaultKnowledgeBaseId($db, $userId) {
    // 先查找现有知识库
    $kb = $db->fetch(
        "SELECT id FROM knowledge_bases WHERE user_id = :user_id ORDER BY id ASC LIMIT 1",
        ['user_id' => $userId]
    );
    
    if ($kb) {
        return $kb['id'];
    }
    
    // 创建默认知识库
    $db->execute(
        "INSERT INTO knowledge_bases (user_id, name, description, is_public) 
         VALUES (:user_id, '默认知识库', '系统自动创建的默认知识库', 0)",
        ['user_id' => $userId]
    );
    
    return $db->lastInsertId();
}

/**
 * 检查列是否存在
 */
function columnExists($db, $table, $column) {
    try {
        $result = $db->fetch("PRAGMA table_info({$table})");
        if ($result) {
            foreach ($result as $col) {
                if ($col['name'] === $column) {
                    return true;
                }
            }
        }
    } catch (Exception $e) {
        // 忽略错误
    }
    return false;
}

/**
 * 初始化知识库表
 */
function initKnowledgeBaseTables($db) {
    // 知识库主表
    $db->exec("CREATE TABLE IF NOT EXISTS knowledge_bases (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        status VARCHAR(20) DEFAULT 'active',
        document_count INTEGER DEFAULT 0,
        total_size INTEGER DEFAULT 0,
        is_public INTEGER DEFAULT 0,
        embedding_model VARCHAR(100),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // 知识库文档表
    $db->exec("CREATE TABLE IF NOT EXISTS knowledge_documents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        kb_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_type VARCHAR(50),
        file_size INTEGER,
        file_path VARCHAR(500),
        description TEXT,
        content_text TEXT,
        status VARCHAR(20) DEFAULT 'pending',
        chunk_count INTEGER DEFAULT 0,
        error_message TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (kb_id) REFERENCES knowledge_bases(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // 知识库内容块表（用于向量检索）
    $db->exec("CREATE TABLE IF NOT EXISTS knowledge_chunks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        doc_id INTEGER NOT NULL,
        kb_id INTEGER NOT NULL,
        content TEXT NOT NULL,
        chunk_index INTEGER,
        embedding BLOB,
        metadata TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (doc_id) REFERENCES knowledge_documents(id) ON DELETE CASCADE,
        FOREIGN KEY (kb_id) REFERENCES knowledge_bases(id) ON DELETE CASCADE
    )");
    
    // 创建索引
    $db->exec("CREATE INDEX IF NOT EXISTS idx_kb_user ON knowledge_bases(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_kb_status ON knowledge_bases(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_doc_kb ON knowledge_documents(kb_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_doc_status ON knowledge_documents(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_chunk_doc ON knowledge_chunks(doc_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_chunk_kb ON knowledge_chunks(kb_id)");
    
    // 迁移：添加缺失的列（如果表已存在但缺少某些列）
    $columnsToAdd = [
        'knowledge_bases' => [
            'status' => "ALTER TABLE knowledge_bases ADD COLUMN status VARCHAR(20) DEFAULT 'active'",
            'document_count' => "ALTER TABLE knowledge_bases ADD COLUMN document_count INTEGER DEFAULT 0",
            'total_size' => "ALTER TABLE knowledge_bases ADD COLUMN total_size INTEGER DEFAULT 0",
            'is_public' => "ALTER TABLE knowledge_bases ADD COLUMN is_public INTEGER DEFAULT 0",
            'embedding_model' => "ALTER TABLE knowledge_bases ADD COLUMN embedding_model VARCHAR(100)",
            'updated_at' => "ALTER TABLE knowledge_bases ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP"
        ],
        'knowledge_documents' => [
            'kb_id' => "ALTER TABLE knowledge_documents ADD COLUMN kb_id INTEGER NOT NULL DEFAULT 0",
            'user_id' => "ALTER TABLE knowledge_documents ADD COLUMN user_id INTEGER NOT NULL DEFAULT 0",
            'file_type' => "ALTER TABLE knowledge_documents ADD COLUMN file_type VARCHAR(50)",
            'file_size' => "ALTER TABLE knowledge_documents ADD COLUMN file_size INTEGER",
            'file_path' => "ALTER TABLE knowledge_documents ADD COLUMN file_path VARCHAR(500)",
            'content_text' => "ALTER TABLE knowledge_documents ADD COLUMN content_text TEXT",
            'status' => "ALTER TABLE knowledge_documents ADD COLUMN status VARCHAR(20) DEFAULT 'pending'",
            'chunk_count' => "ALTER TABLE knowledge_documents ADD COLUMN chunk_count INTEGER DEFAULT 0",
            'error_message' => "ALTER TABLE knowledge_documents ADD COLUMN error_message TEXT",
            'updated_at' => "ALTER TABLE knowledge_documents ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP"
        ]
    ];
    
    foreach ($columnsToAdd as $table => $columns) {
        foreach ($columns as $column => $sql) {
            if (!columnExists($db, $table, $column)) {
                try {
                    $db->exec($sql);
                } catch (Exception $e) {
                    // 列可能已存在或添加失败，忽略错误
                    error_log("Add column {$table}.{$column} failed: " . $e->getMessage());
                }
            }
        }
    }
    
}

/**
 * 创建知识库
 */
function handleCreateKnowledgeBase($db, $userId) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => '知识库名称不能为空']);
        return;
    }
    
    $sql = "INSERT INTO knowledge_bases (user_id, name, description) VALUES (:user_id, :name, :description)";
    $db->execute($sql, [
        'user_id' => $userId,
        'name' => $name,
        'description' => $description
    ]);
    
    $kbId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'kb_id' => $kbId,
        'message' => '知识库创建成功'
    ]);
}

/**
 * 获取知识库列表
 */
function handleGetKnowledgeBases($db, $userId) {
    $sql = "SELECT * FROM knowledge_bases 
            WHERE user_id = :user_id OR is_public = 1 
            ORDER BY created_at DESC";
    $kbs = $db->fetchAll($sql, ['user_id' => $userId]);
    
    echo json_encode(['success' => true, 'knowledge_bases' => $kbs]);
}

/**
 * 获取知识库详情
 */
function handleGetKnowledgeBase($db, $userId) {
    $kbId = $_GET['kb_id'] ?? 0;
    
    $sql = "SELECT * FROM knowledge_bases WHERE id = :id AND (user_id = :user_id OR is_public = 1)";
    $kb = $db->fetch($sql, ['id' => $kbId, 'user_id' => $userId]);
    
    if (!$kb) {
        echo json_encode(['success' => false, 'error' => '知识库不存在']);
        return;
    }
    
    echo json_encode(['success' => true, 'knowledge_base' => $kb]);
}

/**
 * 更新知识库
 */
function handleUpdateKnowledgeBase($db, $userId) {
    $kbId = $_POST['kb_id'] ?? 0;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // 检查权限
    $kb = $db->fetch(
        "SELECT * FROM knowledge_bases WHERE id = :id AND user_id = :user_id",
        ['id' => $kbId, 'user_id' => $userId]
    );
    
    if (!$kb) {
        echo json_encode(['success' => false, 'error' => '无权访问该知识库']);
        return;
    }
    
    $sql = "UPDATE knowledge_bases SET 
            name = :name, 
            description = :description,
            updated_at = datetime('now')
            WHERE id = :id AND user_id = :user_id";
    
    $db->execute($sql, [
        'id' => $kbId,
        'user_id' => $userId,
        'name' => $name,
        'description' => $description
    ]);
    
    echo json_encode(['success' => true, 'message' => '知识库更新成功']);
}

/**
 * 删除知识库
 */
function handleDeleteKnowledgeBase($db, $userId) {
    $kbId = $_POST['kb_id'] ?? 0;
    
    // 检查权限
    $kb = $db->fetch(
        "SELECT * FROM knowledge_bases WHERE id = :id AND user_id = :user_id",
        ['id' => $kbId, 'user_id' => $userId]
    );
    
    if (!$kb) {
        echo json_encode(['success' => false, 'error' => '无权访问该知识库']);
        return;
    }
    
    // 删除关联的文档文件
    $docs = $db->fetchAll(
        "SELECT file_path FROM knowledge_documents WHERE kb_id = :kb_id",
        ['kb_id' => $kbId]
    );
    
    foreach ($docs as $doc) {
        if (!empty($doc['file_path']) && file_exists($doc['file_path'])) {
            @unlink($doc['file_path']);
        }
    }
    
    // 删除知识库（级联删除文档和块）
    $db->execute(
        "DELETE FROM knowledge_bases WHERE id = :id AND user_id = :user_id",
        ['id' => $kbId, 'user_id' => $userId]
    );
    
    echo json_encode(['success' => true, 'message' => '知识库删除成功']);
}

/**
 * 上传文档
 */
function handleUploadDocument($db, $userId) {
    $kbId = $_POST['kb_id'] ?? 0;
    $description = trim($_POST['description'] ?? '');
    
    // 如果没有指定kb_id，使用默认知识库
    if (empty($kbId)) {
        $kbId = getDefaultKnowledgeBaseId($db, $userId);
    }
    
    // 检查权限
    $kb = $db->fetch(
        "SELECT * FROM knowledge_bases WHERE id = :id AND user_id = :user_id",
        ['id' => $kbId, 'user_id' => $userId]
    );
    
    if (!$kb) {
        echo json_encode(['success' => false, 'error' => '无权访问该知识库']);
        return;
    }
    
    // 检查文件
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => '文件上传失败']);
        return;
    }
    
    $file = $_FILES['file'];
    $fileName = $file['name'];
    $fileType = $file['type'];
    $fileSize = $file['size'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // 验证文件类型
    $allowedExts = ['pdf', 'doc', 'docx', 'txt', 'md', 'csv', 'xls', 'xlsx', 'ppt', 'pptx'];
    if (!in_array($fileExt, $allowedExts)) {
        echo json_encode(['success' => false, 'error' => '不支持的文件类型']);
        return;
    }
    
    // 创建上传目录
    $uploadDir = __DIR__ . '/../storage/knowledge/' . $kbId . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // 生成唯一文件名
    $uniqueName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
    $filePath = $uploadDir . $uniqueName;
    
    // 移动文件
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        echo json_encode(['success' => false, 'error' => '文件保存失败']);
        return;
    }
    
    // 获取文档名称（优先使用用户指定的name，否则使用文件名）
    $docName = trim($_POST['name'] ?? '') ?: $fileName;
    
    // 保存文档记录
    $sql = "INSERT INTO knowledge_documents 
            (kb_id, user_id, file_name, file_type, file_size, file_path, description, status) 
            VALUES (:kb_id, :user_id, :file_name, :file_type, :file_size, :file_path, :description, 'pending')";
    
    $db->execute($sql, [
        'kb_id' => $kbId,
        'user_id' => $userId,
        'file_name' => $docName,
        'file_type' => $fileExt,
        'file_size' => $fileSize,
        'file_path' => $filePath,
        'description' => $description
    ]);
    
    $docId = $db->lastInsertId();
    
    // 更新知识库文档计数和大小
    $db->execute(
        "UPDATE knowledge_bases SET 
        document_count = document_count + 1,
        total_size = total_size + :size,
        updated_at = datetime('now')
        WHERE id = :id",
        ['id' => $kbId, 'size' => $fileSize]
    );
    
    // 异步处理文档（提取文本）
    processDocumentAsync($docId, $filePath, $fileExt);
    
    // 返回兼容两种格式的响应
    echo json_encode([
        'success' => true,
        'status' => 'success',
        'doc_id' => $docId,
        'message' => '文档上传成功，正在处理中'
    ]);
}

/**
 * 异步处理文档
 */
function processDocumentAsync($docId, $filePath, $fileExt) {
    // 这里可以启动后台进程处理文档
    // 简化版本：直接处理
    
    $content = '';
    
    try {
        switch ($fileExt) {
            case 'txt':
            case 'md':
                $content = file_get_contents($filePath);
                break;
                
            case 'pdf':
                // 需要PDF解析库
                $content = '[PDF内容需要解析]';
                break;
                
            case 'doc':
            case 'docx':
                // 需要Word解析库
                $content = '[Word文档内容需要解析]';
                break;
                
            default:
                $content = '[该类型文件内容暂不支持自动提取]';
        }
        
        // 更新文档记录
        $db = Database::getInstance();
        $db->execute(
            "UPDATE knowledge_documents SET 
            content_text = :content,
            status = 'completed',
            updated_at = datetime('now')
            WHERE id = :id",
            ['id' => $docId, 'content' => $content]
        );
        
    } catch (Exception $e) {
        $db = Database::getInstance();
        $db->execute(
            "UPDATE knowledge_documents SET 
            status = 'error',
            error_message = :error,
            updated_at = datetime('now')
            WHERE id = :id",
            ['id' => $docId, 'error' => $e->getMessage()]
        );
    }
}

/**
 * 获取文档列表
 */
function handleGetDocuments($db, $userId) {
    $kbId = $_GET['kb_id'] ?? 0;
    
    // 如果没有指定kb_id，获取用户的默认知识库
    if (empty($kbId)) {
        $kb = $db->fetch(
            "SELECT id FROM knowledge_bases WHERE user_id = :user_id ORDER BY id ASC LIMIT 1",
            ['user_id' => $userId]
        );
        if ($kb) {
            $kbId = $kb['id'];
        } else {
            // 创建默认知识库
            $db->execute(
                "INSERT INTO knowledge_bases (user_id, name, description, is_public) 
                 VALUES (:user_id, '默认知识库', '系统自动创建的默认知识库', 0)",
                ['user_id' => $userId]
            );
            $kbId = $db->lastInsertId();
        }
    }
    
    // 检查权限
    $kb = $db->fetch(
        "SELECT * FROM knowledge_bases WHERE id = :id AND (user_id = :user_id OR is_public = 1)",
        ['id' => $kbId, 'user_id' => $userId]
    );
    
    if (!$kb) {
        echo json_encode(['success' => false, 'error' => '无权访问该知识库']);
        return;
    }
    
    $docs = $db->fetchAll(
        "SELECT id, file_name as name, file_type, file_size as size, description, status, created_at 
        FROM knowledge_documents 
        WHERE kb_id = :kb_id 
        ORDER BY created_at DESC",
        ['kb_id' => $kbId]
    );
    
    // 为兼容后台管理页面，添加tags字段
    foreach ($docs as &$doc) {
        $doc['tags'] = $doc['file_type'] ?? '';
        // 确保size字段存在
        if (!isset($doc['size'])) {
            $doc['size'] = 0;
        }
    }
    
    // 返回兼容两种格式的响应
    echo json_encode([
        'success' => true,
        'status' => 'success',
        'documents' => $docs
    ]);
}

/**
 * 删除文档
 */
function handleDeleteDocument($db, $userId) {
    $docId = $_POST['doc_id'] ?? 0;
    
    // 获取文档信息并检查权限
    $doc = $db->fetch(
        "SELECT d.*, k.user_id as kb_owner 
        FROM knowledge_documents d 
        JOIN knowledge_bases k ON d.kb_id = k.id 
        WHERE d.id = :id",
        ['id' => $docId]
    );
    
    if (!$doc || $doc['user_id'] != $userId && $doc['kb_owner'] != $userId) {
        echo json_encode(['success' => false, 'error' => '无权删除该文档']);
        return;
    }
    
    // 删除文件
    if (!empty($doc['file_path']) && file_exists($doc['file_path'])) {
        @unlink($doc['file_path']);
    }
    
    // 删除文档记录
    $db->execute("DELETE FROM knowledge_documents WHERE id = :id", ['id' => $docId]);
    
    // 更新知识库统计
    $db->execute(
        "UPDATE knowledge_bases SET 
        document_count = document_count - 1,
        total_size = total_size - :size,
        updated_at = datetime('now')
        WHERE id = :id",
        ['id' => $doc['kb_id'], 'size' => $doc['file_size']]
    );
    
    echo json_encode(['success' => true, 'message' => '文档删除成功']);
}

/**
 * 搜索知识库
 */
function handleSearchKnowledgeBase($db, $userId) {
    $kbId = $_GET['kb_id'] ?? 0;
    $query = trim($_GET['query'] ?? '');
    $limit = intval($_GET['limit'] ?? 5);
    
    if (empty($query)) {
        echo json_encode(['success' => false, 'error' => '搜索关键词不能为空']);
        return;
    }
    
    // 检查权限
    $kb = $db->fetch(
        "SELECT * FROM knowledge_bases WHERE id = :id AND (user_id = :user_id OR is_public = 1)",
        ['id' => $kbId, 'user_id' => $userId]
    );
    
    if (!$kb) {
        echo json_encode(['success' => false, 'error' => '无权访问该知识库']);
        return;
    }
    
    // 简单文本搜索（实际应用中使用向量检索）
    $searchPattern = '%' . $query . '%';
    $results = $db->fetchAll(
        "SELECT d.id, d.file_name, d.description, c.content, c.chunk_index
        FROM knowledge_chunks c
        JOIN knowledge_documents d ON c.doc_id = d.id
        WHERE c.kb_id = :kb_id AND c.content LIKE :pattern
        ORDER BY c.id DESC
        LIMIT :limit",
        ['kb_id' => $kbId, 'pattern' => $searchPattern, 'limit' => $limit]
    );
    
    // 如果没有分块数据，搜索文档内容
    if (empty($results)) {
        $results = $db->fetchAll(
            "SELECT id, file_name, description, content_text as content
            FROM knowledge_documents
            WHERE kb_id = :kb_id AND (content_text LIKE :pattern OR file_name LIKE :pattern)
            ORDER BY id DESC
            LIMIT :limit",
            ['kb_id' => $kbId, 'pattern' => $searchPattern, 'limit' => $limit]
        );
    }
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'results' => $results,
        'total' => count($results)
    ]);
}
