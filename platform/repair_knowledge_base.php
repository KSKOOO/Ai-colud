<?php
/**
 * 知识库数据库修复脚本
 * 运行此脚本修复知识库表结构问题
 */

require_once __DIR__ . '/includes/Database.php';

try {
    $db = Database::getInstance();
    
    echo "开始修复知识库数据库表...\n\n";
    
    // 1. 检查并创建知识库主表
    echo "1. 检查 knowledge_bases 表...\n";
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
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "   ✓ knowledge_bases 表已就绪\n\n";
    
    // 2. 检查并创建文档表
    echo "2. 检查 knowledge_documents 表...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS knowledge_documents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        kb_id INTEGER NOT NULL DEFAULT 0,
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
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "   ✓ knowledge_documents 表已就绪\n\n";
    
    // 3. 检查并创建内容块表
    echo "3. 检查 knowledge_chunks 表...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS knowledge_chunks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        doc_id INTEGER NOT NULL,
        kb_id INTEGER NOT NULL,
        content TEXT NOT NULL,
        chunk_index INTEGER,
        embedding BLOB,
        metadata TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "   ✓ knowledge_chunks 表已就绪\n\n";
    
    // 4. 添加缺失的列
    echo "4. 检查并添加缺失的列...\n";
    
    // 获取现有列
    $kbColumns = $db->fetchAll("PRAGMA table_info(knowledge_bases)");
    $docColumns = $db->fetchAll("PRAGMA table_info(knowledge_documents)");
    
    $kbColumnNames = array_column($kbColumns, 'name');
    $docColumnNames = array_column($docColumns, 'name');
    
    // knowledge_bases 需要添加的列
    $kbColumnsToAdd = [
        'status' => "ALTER TABLE knowledge_bases ADD COLUMN status VARCHAR(20) DEFAULT 'active'",
        'document_count' => "ALTER TABLE knowledge_bases ADD COLUMN document_count INTEGER DEFAULT 0",
        'total_size' => "ALTER TABLE knowledge_bases ADD COLUMN total_size INTEGER DEFAULT 0",
        'is_public' => "ALTER TABLE knowledge_bases ADD COLUMN is_public INTEGER DEFAULT 0",
        'embedding_model' => "ALTER TABLE knowledge_bases ADD COLUMN embedding_model VARCHAR(100)",
        'updated_at' => "ALTER TABLE knowledge_bases ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP"
    ];
    
    foreach ($kbColumnsToAdd as $column => $sql) {
        if (!in_array($column, $kbColumnNames)) {
            try {
                $db->exec($sql);
                echo "   ✓ 添加列 knowledge_bases.{$column}\n";
            } catch (Exception $e) {
                echo "   ✗ 添加列 knowledge_bases.{$column} 失败: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // knowledge_documents 需要添加的列
    $docColumnsToAdd = [
        'kb_id' => "ALTER TABLE knowledge_documents ADD COLUMN kb_id INTEGER NOT NULL DEFAULT 0",
        'file_type' => "ALTER TABLE knowledge_documents ADD COLUMN file_type VARCHAR(50)",
        'file_size' => "ALTER TABLE knowledge_documents ADD COLUMN file_size INTEGER",
        'file_path' => "ALTER TABLE knowledge_documents ADD COLUMN file_path VARCHAR(500)",
        'content_text' => "ALTER TABLE knowledge_documents ADD COLUMN content_text TEXT",
        'status' => "ALTER TABLE knowledge_documents ADD COLUMN status VARCHAR(20) DEFAULT 'pending'",
        'chunk_count' => "ALTER TABLE knowledge_documents ADD COLUMN chunk_count INTEGER DEFAULT 0",
        'error_message' => "ALTER TABLE knowledge_documents ADD COLUMN error_message TEXT",
        'updated_at' => "ALTER TABLE knowledge_documents ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP"
    ];
    
    foreach ($docColumnsToAdd as $column => $sql) {
        if (!in_array($column, $docColumnNames)) {
            try {
                $db->exec($sql);
                echo "   ✓ 添加列 knowledge_documents.{$column}\n";
            } catch (Exception $e) {
                echo "   ✗ 添加列 knowledge_documents.{$column} 失败: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n";
    
    // 5. 创建索引
    echo "5. 创建索引...\n";
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_kb_user ON knowledge_bases(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_kb_status ON knowledge_bases(status)",
        "CREATE INDEX IF NOT EXISTS idx_doc_kb ON knowledge_documents(kb_id)",
        "CREATE INDEX IF NOT EXISTS idx_doc_user ON knowledge_documents(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_doc_status ON knowledge_documents(status)",
        "CREATE INDEX IF NOT EXISTS idx_chunk_doc ON knowledge_chunks(doc_id)",
        "CREATE INDEX IF NOT EXISTS idx_chunk_kb ON knowledge_chunks(kb_id)"
    ];
    
    foreach ($indexes as $sql) {
        try {
            $db->exec($sql);
            echo "   ✓ 创建索引\n";
        } catch (Exception $e) {
            echo "   ✗ 创建索引失败: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    
    // 6. 为现有用户创建默认知识库
    echo "6. 检查默认知识库...\n";
    $users = $db->fetchAll("SELECT id FROM users");
    $defaultKbCount = 0;
    
    foreach ($users as $user) {
        $existing = $db->fetch(
            "SELECT id FROM knowledge_bases WHERE user_id = :user_id LIMIT 1",
            ['user_id' => $user['id']]
        );
        
        if (!$existing) {
            $db->execute(
                "INSERT INTO knowledge_bases (user_id, name, description, is_public) 
                 VALUES (:user_id, '默认知识库', '系统自动创建的默认知识库', 0)",
                ['user_id' => $user['id']]
            );
            $defaultKbCount++;
        }
    }
    
    echo "   ✓ 为 {$defaultKbCount} 个用户创建了默认知识库\n\n";
    
    echo "========================================\n";
    echo "数据库修复完成！\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    echo "堆栈跟踪:\n" . $e->getTraceAsString() . "\n";
}
