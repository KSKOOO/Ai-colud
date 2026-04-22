<?php


require_once __DIR__ . '/../includes/Database.php';

class UsageTracker {
    private $db;
    private $pricePer1K;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $config = require __DIR__ . '/../config/config.php';
        $this->pricePer1K = $config['billing']['price_per_1k'] ?? 0.02;
    }
    
    
    public function recordUsage($userId, $actionType, $model = '', $inputTokens = 0, $outputTokens = 0, $requestData = null) {
        $totalTokens = $inputTokens + $outputTokens;
        $cost = ($totalTokens / 1000) * $this->pricePer1K;
        
        $data = [
            'user_id' => $userId,
            'action_type' => $actionType,
            'model' => $model,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'total_tokens' => $totalTokens,
            'cost' => $cost,
            'request_data' => $requestData ? json_encode($requestData, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => date('Y-m-d H:i:s'),
            'created_date' => date('Y-m-d')
        ];
        
        return $this->db->insert('user_usage', $data);
    }
    
    
    public function estimateTokens($text) {
        if (empty($text)) return 0;
        

        $charCount = mb_strlen($text);
        $wordCount = str_word_count($text);
        

        $estimatedTokens = ceil($wordCount * 1.3 + ($charCount - $wordCount) * 0.75);
        
        return max(1, $estimatedTokens);
    }
    
    
    public function recordChatUsage($userId, $model, $messages, $response) {

        $inputText = '';
        foreach ($messages as $msg) {
            $content = $msg['content'] ?? '';
            // 处理多模态消息（content是数组）
            if (is_array($content)) {
                foreach ($content as $item) {
                    if (isset($item['type']) && $item['type'] === 'text' && isset($item['text'])) {
                        $inputText .= $item['text'];
                    }
                }
            } else {
                $inputText .= $content;
            }
        }
        $inputTokens = $this->estimateTokens($inputText);
        

        $outputTokens = $this->estimateTokens($response);
        
        return $this->recordUsage($userId, 'chat', $model, $inputTokens, $outputTokens, [
            'message_count' => count($messages)
        ]);
    }
    
    
    public function getUserUsageStats($userId, $startDate = null, $endDate = null) {
        $startDate = $startDate ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $endDate ?? date('Y-m-d');


        $sql = "SELECT
            COALESCE(COUNT(*), 0) as total_requests,
            COALESCE(SUM(input_tokens), 0) as total_input_tokens,
            COALESCE(SUM(output_tokens), 0) as total_output_tokens,
            COALESCE(SUM(total_tokens), 0) as total_tokens,
            COALESCE(SUM(cost), 0) as total_cost,
            COALESCE(COUNT(DISTINCT created_date), 0) as active_days
        FROM user_usage
        WHERE user_id = :user_id
        AND created_date BETWEEN :start_date AND :end_date";

        $summary = $this->db->fetch($sql, [
            ':user_id' => $userId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);

        // 确保数值类型正确
        $summary['total_requests'] = intval($summary['total_requests'] ?? 0);
        $summary['total_input_tokens'] = intval($summary['total_input_tokens'] ?? 0);
        $summary['total_output_tokens'] = intval($summary['total_output_tokens'] ?? 0);
        $summary['total_tokens'] = intval($summary['total_tokens'] ?? 0);
        $summary['total_cost'] = floatval($summary['total_cost'] ?? 0);
        $summary['active_days'] = intval($summary['active_days'] ?? 0);
        

        $sql = "SELECT 
            action_type,
            COUNT(*) as request_count,
            SUM(total_tokens) as tokens,
            SUM(cost) as cost
        FROM user_usage 
        WHERE user_id = :user_id 
        AND created_date BETWEEN :start_date AND :end_date
        GROUP BY action_type";
        
        $byAction = $this->db->fetchAll($sql, [
            ':user_id' => $userId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        

        $sql = "SELECT 
            created_date as date,
            COUNT(*) as request_count,
            SUM(total_tokens) as tokens,
            SUM(cost) as cost
        FROM user_usage 
        WHERE user_id = :user_id 
        AND created_date BETWEEN :start_date AND :end_date
        GROUP BY created_date
        ORDER BY created_date DESC";
        
        $byDate = $this->db->fetchAll($sql, [
            ':user_id' => $userId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        

        $sql = "SELECT 
            model,
            COUNT(*) as request_count,
            SUM(total_tokens) as tokens,
            SUM(cost) as cost
        FROM user_usage 
        WHERE user_id = :user_id 
        AND created_date BETWEEN :start_date AND :end_date
        GROUP BY model
        ORDER BY tokens DESC";
        
        $byModel = $this->db->fetchAll($sql, [
            ':user_id' => $userId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        
        return [
            'summary' => $summary,
            'by_action' => $byAction,
            'by_date' => $byDate,
            'by_model' => $byModel,
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate
            ]
        ];
    }
    
    
    public function getAllUsersUsageStats($startDate = null, $endDate = null) {
        $startDate = $startDate ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $endDate ?? date('Y-m-d');

        $sql = "SELECT
            u.id as user_id,
            u.username,
            u.email,
            COALESCE(COUNT(`usage`.id), 0) as total_requests,
            COALESCE(SUM(`usage`.input_tokens), 0) as total_input_tokens,
            COALESCE(SUM(`usage`.output_tokens), 0) as total_output_tokens,
            COALESCE(SUM(`usage`.total_tokens), 0) as total_tokens,
            COALESCE(SUM(`usage`.cost), 0) as total_cost,
            COALESCE(COUNT(DISTINCT `usage`.created_date), 0) as active_days,
            MAX(`usage`.created_at) as last_usage
        FROM users u
        LEFT JOIN user_usage `usage` ON u.id = `usage`.user_id
            AND `usage`.created_date BETWEEN :start_date AND :end_date
        GROUP BY u.id, u.username, u.email
        ORDER BY total_tokens DESC";

        $results = $this->db->fetchAll($sql, [
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);

        // 确保所有数值字段都是整数或浮点数,而不是字符串
        foreach ($results as &$row) {
            $row['total_requests'] = intval($row['total_requests'] ?? 0);
            $row['total_input_tokens'] = intval($row['total_input_tokens'] ?? 0);
            $row['total_output_tokens'] = intval($row['total_output_tokens'] ?? 0);
            $row['total_tokens'] = intval($row['total_tokens'] ?? 0);
            $row['total_cost'] = floatval($row['total_cost'] ?? 0);
            $row['active_days'] = intval($row['active_days'] ?? 0);
        }

        return $results;
    }
    
    
    public function getSystemUsageStats($startDate = null, $endDate = null) {
        $startDate = $startDate ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $endDate ?? date('Y-m-d');


        $sql = "SELECT
            COALESCE(COUNT(*), 0) as total_requests,
            COALESCE(COUNT(DISTINCT user_id), 0) as active_users,
            COALESCE(SUM(input_tokens), 0) as total_input_tokens,
            COALESCE(SUM(output_tokens), 0) as total_output_tokens,
            COALESCE(SUM(total_tokens), 0) as total_tokens,
            COALESCE(SUM(cost), 0) as total_cost
        FROM user_usage
        WHERE created_date BETWEEN :start_date AND :end_date";

        $summary = $this->db->fetch($sql, [
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);

        // 确保数值类型正确
        $summary['total_requests'] = intval($summary['total_requests'] ?? 0);
        $summary['active_users'] = intval($summary['active_users'] ?? 0);
        $summary['total_input_tokens'] = intval($summary['total_input_tokens'] ?? 0);
        $summary['total_output_tokens'] = intval($summary['total_output_tokens'] ?? 0);
        $summary['total_tokens'] = intval($summary['total_tokens'] ?? 0);
        $summary['total_cost'] = floatval($summary['total_cost'] ?? 0);
        

        $sql = "SELECT 
            created_date as date,
            COUNT(*) as request_count,
            COUNT(DISTINCT user_id) as active_users,
            SUM(total_tokens) as tokens,
            SUM(cost) as cost
        FROM user_usage 
        WHERE created_date BETWEEN :start_date AND :end_date
        GROUP BY created_date
        ORDER BY created_date DESC";
        
        $dailyStats = $this->db->fetchAll($sql, [
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        

        $sql = "SELECT 
            model,
            COUNT(*) as request_count,
            SUM(total_tokens) as tokens,
            SUM(cost) as cost
        FROM user_usage 
        WHERE created_date BETWEEN :start_date AND :end_date
        GROUP BY model
        ORDER BY tokens DESC";
        
        $modelStats = $this->db->fetchAll($sql, [
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        
        return [
            'summary' => $summary,
            'daily_stats' => $dailyStats,
            'model_stats' => $modelStats,
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate
            ]
        ];
    }
    
    
    public function getUserUsageRecords($userId, $page = 1, $pageSize = 50) {
        $offset = ($page - 1) * $pageSize;
        
        $sql = "SELECT * FROM user_usage 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC 
        LIMIT :limit OFFSET :offset";
        
        $records = $this->db->fetchAll($sql, [
            ':user_id' => $userId,
            ':limit' => $pageSize,
            ':offset' => $offset
        ]);
        

        $countSql = "SELECT COUNT(*) as total FROM user_usage WHERE user_id = :user_id";
        $count = $this->db->fetch($countSql, [':user_id' => $userId]);
        
        return [
            'records' => $records,
            'total' => $count['total'],
            'page' => $page,
            'page_size' => $pageSize,
            'total_pages' => ceil($count['total'] / $pageSize)
        ];
    }
}
