<?php
/**
 * 简化版知识库API - 用于测试
 */
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // 关闭错误显示，避免输出HTML

// 捕获所有输出
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']['id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => '请先登录']);
    exit;
}

require_once __DIR__ . '/../includes/Database.php';

try {
    $db = Database::getInstance();
    if (!$db) {
        throw new Exception('数据库连接失败');
    }
    $userId = $_SESSION['user']['id'];
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    // 确保表存在
    $db->exec("CREATE TABLE IF NOT EXISTS knowledge_bases (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS knowledge_documents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        kb_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_type VARCHAR(50),
        file_size INTEGER,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // 迁移：添加缺失的列
    try {
        // 检查并添加 knowledge_documents 表的列
        $result = $db->fetchAll("PRAGMA table_info(knowledge_documents)");
        $columns = [];
        if ($result) {
            foreach ($result as $col) {
                $columns[] = $col['name'];
            }
        }

        // 如果表使用 'name' 列而不是 'file_name'，需要迁移
        if (in_array('name', $columns) && !in_array('file_name', $columns)) {
            // 重命名列（SQLite不支持直接重命名，需要重建表）
            $db->exec("BEGIN TRANSACTION");
            $db->exec("CREATE TABLE knowledge_documents_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kb_id INTEGER NOT NULL DEFAULT 0,
                user_id INTEGER NOT NULL DEFAULT 0,
                file_name VARCHAR(255) NOT NULL DEFAULT '',
                file_type VARCHAR(50),
                file_size INTEGER,
                file_path VARCHAR(500),
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            $db->exec("INSERT INTO knowledge_documents_new (id, kb_id, user_id, file_name, file_type, file_size, description, created_at)
                SELECT id, kb_id, user_id, name, file_type, file_size, description, created_at FROM knowledge_documents");
            $db->exec("DROP TABLE knowledge_documents");
            $db->exec("ALTER TABLE knowledge_documents_new RENAME TO knowledge_documents");
            $db->exec("COMMIT");
            // 重新获取列信息
            $columns = ['id', 'kb_id', 'user_id', 'file_name', 'file_type', 'file_size', 'file_path', 'description', 'created_at'];
        }

        if (!in_array('user_id', $columns)) {
            $db->exec("ALTER TABLE knowledge_documents ADD COLUMN user_id INTEGER NOT NULL DEFAULT 0");
        }
        if (!in_array('kb_id', $columns)) {
            $db->exec("ALTER TABLE knowledge_documents ADD COLUMN kb_id INTEGER NOT NULL DEFAULT 0");
        }
        if (!in_array('file_path', $columns)) {
            $db->exec("ALTER TABLE knowledge_documents ADD COLUMN file_path VARCHAR(500)");
        }
        if (!in_array('file_type', $columns)) {
            $db->exec("ALTER TABLE knowledge_documents ADD COLUMN file_type VARCHAR(50)");
        }
        if (!in_array('file_size', $columns)) {
            $db->exec("ALTER TABLE knowledge_documents ADD COLUMN file_size INTEGER");
        }
    } catch (Exception $e) {
        // 忽略迁移错误
    }
    
    switch ($action) {
        case 'getDocuments':
            // 获取或创建默认知识库
            $kb = $db->fetch(
                "SELECT id FROM knowledge_bases WHERE user_id = :user_id ORDER BY id ASC LIMIT 1",
                ['user_id' => $userId]
            );
            
            if (!$kb) {
                $db->execute(
                    "INSERT INTO knowledge_bases (user_id, name, description) 
                     VALUES (:user_id, '默认知识库', '自动创建')",
                    ['user_id' => $userId]
                );
                $kbId = $db->lastInsertId();
            } else {
                $kbId = $kb['id'];
            }
            
            // 获取文档
            $docs = $db->fetchAll(
                "SELECT id, file_name as name, file_type, file_size as size, description, created_at 
                FROM knowledge_documents 
                WHERE kb_id = :kb_id",
                ['kb_id' => $kbId]
            );
            
            // 添加tags字段
            foreach ($docs as &$doc) {
                $doc['tags'] = $doc['file_type'] ?? '';
            }
            
            ob_end_clean();
            echo json_encode(['success' => true, 'status' => 'success', 'documents' => $docs]);
            break;
            
        case 'uploadDocument':
            if (!isset($_FILES['file'])) {
                ob_end_clean();
                echo json_encode(['success' => false, 'error' => '没有文件']);
                exit;
            }

            $file = $_FILES['file'];

            // 检查文件上传错误
            if ($file['error'] !== UPLOAD_ERR_OK) {
                ob_end_clean();
                echo json_encode(['success' => false, 'error' => '文件上传失败，错误码: ' . $file['error']]);
                exit;
            }

            $fileName = $_POST['name'] ?? $file['name'];
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            // 获取默认知识库
            $kb = $db->fetch(
                "SELECT id FROM knowledge_bases WHERE user_id = :user_id ORDER BY id ASC LIMIT 1",
                ['user_id' => $userId]
            );

            if (!$kb) {
                $db->execute(
                    "INSERT INTO knowledge_bases (user_id, name, description)
                     VALUES (:user_id, '默认知识库', '自动创建')",
                    ['user_id' => $userId]
                );
                $kbId = $db->lastInsertId();
            } else {
                $kbId = $kb['id'];
            }

            // 创建上传目录
            $uploadDir = __DIR__ . '/../uploads/knowledge/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // 生成唯一文件名
            $uniqueName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
            $filePath = $uploadDir . $uniqueName;

            // 移动上传的文件
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'error' => '文件保存失败']);
                exit;
            }

            // 保存到数据库
            $db->execute(
                "INSERT INTO knowledge_documents (kb_id, user_id, file_name, file_type, file_size, description, file_path)
                 VALUES (:kb_id, :user_id, :file_name, :file_type, :file_size, :description, :file_path)",
                [
                    'kb_id' => $kbId,
                    'user_id' => $userId,
                    'file_name' => $fileName,
                    'file_type' => $fileExt,
                    'file_size' => $file['size'],
                    'description' => $_POST['description'] ?? '',
                    'file_path' => 'uploads/knowledge/' . $uniqueName
                ]
            );

            ob_end_clean();
            echo json_encode(['success' => true, 'status' => 'success', 'message' => '上传成功']);
            break;
            
        case 'deleteDocument':
            $docId = $_POST['doc_id'] ?? 0;
            $db->execute(
                "DELETE FROM knowledge_documents WHERE id = :id AND user_id = :user_id",
                ['id' => $docId, 'user_id' => $userId]
            );
            ob_end_clean();
            echo json_encode(['success' => true, 'status' => 'success', 'message' => '删除成功']);
            break;
            
        default:
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => '未知操作: ' . $action]);
    }

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
