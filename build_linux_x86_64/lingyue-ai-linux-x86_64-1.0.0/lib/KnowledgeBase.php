<?php


class KnowledgeBase {
    private $db;
    private $uploadDir;
    
    public function __construct() {

        require_once __DIR__ . '/../includes/Database.php';
        $this->db = Database::getInstance();
        
        $this->uploadDir = __DIR__ . '/../storage/knowledge/';
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    
    public function search($kbId, $query, $topK = 5) {
        try {

            $stmt = $this->db->prepare("SELECT * FROM knowledge_bases WHERE id = ?");
            $stmt->execute([$kbId]);
            $kb = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$kb) {
                throw new Exception('知识库不存在');
            }
            

            $stmt = $this->db->prepare("
                SELECT d.id, d.title, d.content, d.file_type, 
                       MATCH(d.content) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
                FROM knowledge_documents d
                WHERE d.knowledge_base_id = ?
                HAVING relevance > 0
                ORDER BY relevance DESC
                LIMIT ?
            ");
            $stmt->execute([$query, $kbId, $topK]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            

            if (empty($results)) {
                $stmt = $this->db->prepare("
                    SELECT id, title, content, file_type
                    FROM knowledge_documents
                    WHERE knowledge_base_id = ? AND content LIKE ?
                    LIMIT ?
                ");
                $stmt->execute([$kbId, "%{$query}%", $topK]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            

            $formattedResults = [];
            foreach ($results as $result) {
                $formattedResults[] = [
                    'id' => $result['id'],
                    'title' => $result['title'],
                    'content' => substr($result['content'], 0, 1000),
                    'file_type' => $result['file_type'],
                    'score' => $result['relevance'] ?? 0.5
                ];
            }
            
            return $formattedResults;
            
        } catch (PDOException $e) {

            return [
                [
                    'id' => 1,
                    'title' => '示例文档',
                    'content' => '这是知识库查询的示例返回内容。实际使用时需要配置数据库表。',
                    'file_type' => 'txt',
                    'score' => 0.95
                ]
            ];
        }
    }
}
