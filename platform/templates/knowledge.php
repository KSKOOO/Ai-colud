<?php
/**
 * 知识库管理
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']) {
    header('Location: ?route=login');
    exit;
}

require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();
$userId = $_SESSION['user']['id'];

// 获取用户的知识库列表
$knowledgeBases = [];
try {
    $knowledgeBases = $db->fetchAll(
        "SELECT * FROM knowledge_bases WHERE user_id = :user_id OR is_public = 1 ORDER BY created_at DESC",
        ['user_id' => $userId]
    );
} catch (Exception $e) {
    // 表可能不存在
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>知识库管理 - 巨神兵AIAPI辅助平台</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 24px;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .header p {
            color: #64748b;
            font-size: 14px;
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }

        .kb-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .kb-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.2s;
        }

        .kb-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }

        .kb-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .kb-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }

        .kb-title {
            flex: 1;
        }

        .kb-title h3 {
            font-size: 16px;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .kb-title .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            background: #dbeafe;
            color: #1e40af;
        }

        .kb-desc {
            color: #64748b;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 16px;
            min-height: 40px;
        }

        .kb-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
            font-size: 13px;
            color: #64748b;
        }

        .kb-actions {
            display: flex;
            gap: 8px;
        }

        .kb-actions button {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            background: #f1f5f9;
            color: #475569;
        }

        .kb-actions button:hover {
            background: #e2e8f0;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
        }

        .empty-state i {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 16px;
        }

        .empty-state h3 {
            color: #1e293b;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #64748b;
            margin-bottom: 20px;
        }

        /* 模态框 */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow: hidden;
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 18px;
            color: #1e293b;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            color: #64748b;
            cursor: pointer;
        }

        .modal-body {
            padding: 20px;
            overflow-y: auto;
            max-height: 60vh;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
        }

        .form-input,
        .form-textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-textarea {
            min-height: 80px;
            resize: vertical;
        }

        .form-input:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .modal-footer {
            padding: 16px 20px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .file-upload-area {
            border: 2px dashed #e2e8f0;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .file-upload-area:hover {
            border-color: #667eea;
            background: #f8fafc;
        }

        .file-upload-area i {
            font-size: 32px;
            color: #cbd5e1;
            margin-bottom: 8px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            text-decoration: none;
            margin-bottom: 16px;
        }

        .back-link:hover {
            color: #4c51bf;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="?route=agents" class="back-link">
            <i class="fas fa-arrow-left"></i> 返回智能体
        </a>

        <div class="header">
            <h1><i class="fas fa-database"></i> 知识库管理</h1>
            <p>创建和管理知识库，让AI员工能够访问专业知识</p>
        </div>

        <div class="actions">
            <button class="btn btn-primary" onclick="showCreateModal()">
                <i class="fas fa-plus"></i> 创建知识库
            </button>
        </div>

        <?php if (empty($knowledgeBases)): ?>
        <div class="empty-state">
            <i class="fas fa-database"></i>
            <h3>暂无知识库</h3>
            <p>创建知识库，上传文档让AI员工学习专业知识</p>
            <button class="btn btn-primary" onclick="showCreateModal()">
                <i class="fas fa-plus"></i> 创建知识库
            </button>
        </div>
        <?php else: ?>
        <div class="kb-grid">
            <?php foreach ($knowledgeBases as $kb): ?>
            <div class="kb-card">
                <div class="kb-header">
                    <div class="kb-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="kb-title">
                        <h3><?php echo htmlspecialchars($kb['name']); ?></h3>
                        <span class="badge"><?php echo $kb['status'] === 'active' ? '可用' : '处理中'; ?></span>
                    </div>
                </div>
                <div class="kb-desc">
                    <?php echo htmlspecialchars($kb['description'] ?? '暂无描述'); ?>
                </div>
                <div class="kb-meta">
                    <span><?php echo $kb['document_count'] ?? 0; ?> 个文档</span>
                    <span><?php echo date('Y-m-d', strtotime($kb['created_at'])); ?></span>
                </div>
                <div class="kb-actions" style="margin-top: 12px;">
                    <button onclick="uploadDocument(<?php echo $kb['id']; ?>)">
                        <i class="fas fa-upload"></i> 上传文档
                    </button>
                    <button onclick="viewDocuments(<?php echo $kb['id']; ?>)">
                        <i class="fas fa-file"></i> 查看文档
                    </button>
                    <button onclick="deleteKnowledgeBase(<?php echo $kb['id']; ?>)" style="color: #ef4444;">
                        <i class="fas fa-trash"></i> 删除
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- 创建知识库模态框 -->
    <div class="modal" id="createModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>创建知识库</h3>
                <button class="modal-close" onclick="hideCreateModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">知识库名称 <span style="color: #ef4444;">*</span></label>
                    <input type="text" class="form-input" id="kbName" placeholder="输入知识库名称">
                </div>
                <div class="form-group">
                    <label class="form-label">描述</label>
                    <textarea class="form-textarea" id="kbDescription" placeholder="描述这个知识库的内容和用途"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="hideCreateModal()">取消</button>
                <button class="btn btn-primary" onclick="createKnowledgeBase()">创建</button>
            </div>
        </div>
    </div>

    <!-- 上传文档模态框 -->
    <div class="modal" id="uploadModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>上传文档</h3>
                <button class="modal-close" onclick="hideUploadModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="uploadKbId">
                <div class="form-group">
                    <label class="form-label">选择文件</label>
                    <div class="file-upload-area" onclick="document.getElementById('kbFile').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>点击或拖拽上传文件</p>
                        <p style="font-size: 12px; color: #94a3b8; margin-top: 8px;">
                            支持 PDF、Word、TXT、Markdown 等格式
                        </p>
                    </div>
                    <input type="file" id="kbFile" style="display: none;" 
                           accept=".pdf,.doc,.docx,.txt,.md,.csv,.xls,.xlsx"
                           onchange="handleFileSelect(this)">
                </div>
                <div class="form-group">
                    <label class="form-label">文档说明（可选）</label>
                    <input type="text" class="form-input" id="docDescription" placeholder="描述这个文档的内容">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="hideUploadModal()">取消</button>
                <button class="btn btn-primary" onclick="submitDocument()">上传</button>
            </div>
        </div>
    </div>

    <script>
        // 显示创建模态框
        function showCreateModal() {
            document.getElementById('createModal').classList.add('show');
        }

        // 隐藏创建模态框
        function hideCreateModal() {
            document.getElementById('createModal').classList.remove('show');
            document.getElementById('kbName').value = '';
            document.getElementById('kbDescription').value = '';
        }

        // 创建知识库
        async function createKnowledgeBase() {
            const name = document.getElementById('kbName').value.trim();
            const description = document.getElementById('kbDescription').value.trim();

            if (!name) {
                alert('请输入知识库名称');
                return;
            }

            try {
                const response = await fetch('api/knowledge_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=createKnowledgeBase&name=${encodeURIComponent(name)}&description=${encodeURIComponent(description)}`
                });

                const result = await response.json();
                if (result.success) {
                    alert('知识库创建成功');
                    location.reload();
                } else {
                    alert('创建失败: ' + (result.error || '未知错误'));
                }
            } catch (error) {
                alert('创建出错: ' + error.message);
            }
        }

        // 显示上传模态框
        function uploadDocument(kbId) {
            document.getElementById('uploadKbId').value = kbId;
            document.getElementById('uploadModal').classList.add('show');
        }

        // 隐藏上传模态框
        function hideUploadModal() {
            document.getElementById('uploadModal').classList.remove('show');
            document.getElementById('kbFile').value = '';
            document.getElementById('docDescription').value = '';
        }

        // 处理文件选择
        function handleFileSelect(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                // 可以在这里显示文件名
            }
        }

        // 提交文档
        async function submitDocument() {
            const kbId = document.getElementById('uploadKbId').value;
            const fileInput = document.getElementById('kbFile');
            const description = document.getElementById('docDescription').value;

            if (!fileInput.files || !fileInput.files[0]) {
                alert('请选择文件');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'uploadDocument');
            formData.append('kb_id', kbId);
            formData.append('file', fileInput.files[0]);
            formData.append('description', description);

            try {
                const response = await fetch('api/knowledge_handler.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    alert('文档上传成功');
                    hideUploadModal();
                    location.reload();
                } else {
                    alert('上传失败: ' + (result.error || '未知错误'));
                }
            } catch (error) {
                alert('上传出错: ' + error.message);
            }
        }

        // 查看文档
        function viewDocuments(kbId) {
            window.open(`?route=knowledge_documents&id=${kbId}`, '_blank');
        }

        // 删除知识库
        async function deleteKnowledgeBase(kbId) {
            if (!confirm('确定要删除这个知识库吗？其中的所有文档也会被删除。')) {
                return;
            }

            try {
                const response = await fetch('api/knowledge_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=deleteKnowledgeBase&kb_id=${kbId}`
                });

                const result = await response.json();
                if (result.success) {
                    alert('删除成功');
                    location.reload();
                } else {
                    alert('删除失败: ' + (result.error || '未知错误'));
                }
            } catch (error) {
                alert('删除出错: ' + error.message);
            }
        }

        // 点击模态框外部关闭
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html>
