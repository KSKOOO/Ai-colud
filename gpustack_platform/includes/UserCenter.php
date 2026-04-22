<?php
require_once __DIR__ . '/Database.php';


class UserCenter {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    
    public function getUserInfo($userId) {
        try {
            $user = $this->db->fetch(
                "SELECT id, username, email, role, balance, api_key, api_key_created_at, 
                        created_at, last_login, is_active 
                 FROM users WHERE id = :id",
                ['id' => $userId]
            );
            
            if (!$user) {
                return ['success' => false, 'message' => '用户不存在'];
            }
            

            if (!empty($user['api_key'])) {
                $user['api_key_masked'] = substr($user['api_key'], 0, 8) . '****' . substr($user['api_key'], -4);
            } else {
                $user['api_key_masked'] = null;
            }
            
            return ['success' => true, 'data' => $user];
        } catch (Exception $e) {
            return ['success' => false, 'message' => '获取用户信息失败: ' . $e->getMessage()];
        }
    }
    
    
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {

            $user = $this->db->fetch(
                "SELECT password_hash FROM users WHERE id = :id",
                ['id' => $userId]
            );
            
            if (!$user) {
                return ['success' => false, 'message' => '用户不存在'];
            }
            

            if (!password_verify($currentPassword, $user['password_hash'])) {
                return ['success' => false, 'message' => '当前密码错误'];
            }
            

            if (strlen($newPassword) < 6) {
                return ['success' => false, 'message' => '新密码长度至少6位'];
            }
            

            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            

            $this->db->update('users', 
                [
                    'password_hash' => $newHash,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'id = :id',
                ['id' => $userId]
            );
            
            return ['success' => true, 'message' => '密码修改成功'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => '密码修改失败: ' . $e->getMessage()];
        }
    }
    
    
    public function generateApiKey($userId) {
        try {

            $apiKey = bin2hex(random_bytes(32));
            

            $existing = $this->db->fetch(
                "SELECT id FROM users WHERE api_key = :key",
                ['key' => $apiKey]
            );
            
            if ($existing) {

                return $this->generateApiKey($userId);
            }
            

            $this->db->update('users',
                [
                    'api_key' => $apiKey,
                    'api_key_created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'id = :id',
                ['id' => $userId]
            );
            
            return [
                'success' => true, 
                'message' => 'API密钥生成成功',
                'api_key' => $apiKey
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => '生成API密钥失败: ' . $e->getMessage()];
        }
    }
    
    
    public function deleteApiKey($userId) {
        try {
            $this->db->update('users',
                [
                    'api_key' => null,
                    'api_key_created_at' => null,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'id = :id',
                ['id' => $userId]
            );
            
            return ['success' => true, 'message' => 'API密钥已删除'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => '删除API密钥失败: ' . $e->getMessage()];
        }
    }
    
    
    public function getUsageStats($userId, $startDate = null, $endDate = null) {
        try {

            if (!$startDate) {
                $startDate = date('Y-m-d', strtotime('-30 days'));
            }
            if (!$endDate) {
                $endDate = date('Y-m-d');
            }
            

            $totalStats = $this->db->fetch(
                "SELECT 
                    COUNT(*) as total_requests,
                    SUM(input_tokens) as total_input_tokens,
                    SUM(output_tokens) as total_output_tokens,
                    SUM(total_tokens) as total_tokens,
                    SUM(cost) as total_cost
                 FROM user_usage 
                 WHERE user_id = :user_id 
                 AND created_date BETWEEN :start AND :end",
                [
                    'user_id' => $userId,
                    'start' => $startDate,
                    'end' => $endDate
                ]
            );
            

            $actionStats = $this->db->fetchAll(
                "SELECT 
                    action_type,
                    COUNT(*) as count,
                    SUM(total_tokens) as tokens,
                    SUM(cost) as cost
                 FROM user_usage 
                 WHERE user_id = :user_id 
                 AND created_date BETWEEN :start AND :end
                 GROUP BY action_type
                 ORDER BY count DESC",
                [
                    'user_id' => $userId,
                    'start' => $startDate,
                    'end' => $endDate
                ]
            );
            

            $modelStats = $this->db->fetchAll(
                "SELECT 
                    model,
                    COUNT(*) as count,
                    SUM(total_tokens) as tokens,
                    SUM(cost) as cost
                 FROM user_usage 
                 WHERE user_id = :user_id 
                 AND created_date BETWEEN :start AND :end
                 AND model IS NOT NULL
                 GROUP BY model
                 ORDER BY count DESC
                 LIMIT 10",
                [
                    'user_id' => $userId,
                    'start' => $startDate,
                    'end' => $endDate
                ]
            );
            

            $dailyTrend = $this->db->fetchAll(
                "SELECT 
                    created_date as date,
                    COUNT(*) as requests,
                    SUM(total_tokens) as tokens,
                    SUM(cost) as cost
                 FROM user_usage 
                 WHERE user_id = :user_id 
                 AND created_date BETWEEN :start AND :end
                 GROUP BY created_date
                 ORDER BY created_date ASC",
                [
                    'user_id' => $userId,
                    'start' => $startDate,
                    'end' => $endDate
                ]
            );
            

            $recentUsage = $this->db->fetchAll(
                "SELECT 
                    action_type,
                    model,
                    input_tokens,
                    output_tokens,
                    total_tokens,
                    cost,
                    created_at
                 FROM user_usage 
                 WHERE user_id = :user_id 
                 ORDER BY created_at DESC
                 LIMIT 20",
                ['user_id' => $userId]
            );
            
            return [
                'success' => true,
                'data' => [
                    'total' => $totalStats,
                    'by_action' => $actionStats,
                    'by_model' => $modelStats,
                    'daily_trend' => $dailyTrend,
                    'recent' => $recentUsage,
                    'date_range' => [
                        'start' => $startDate,
                        'end' => $endDate
                    ]
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => '获取用量统计失败: ' . $e->getMessage()];
        }
    }
    
    
    public function createRecharge($userId, $amount, $paymentMethod = 'manual') {
        try {

            if (!is_numeric($amount) || $amount <= 0) {
                return ['success' => false, 'message' => '充值金额必须大于0'];
            }
            
            if ($amount > 10000) {
                return ['success' => false, 'message' => '单次充值金额不能超过10000元'];
            }
            

            $rechargeId = $this->db->insert('user_recharges', [
                'user_id' => $userId,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            return [
                'success' => true,
                'message' => '充值订单创建成功',
                'recharge_id' => $rechargeId,
                'amount' => $amount
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => '创建充值订单失败: ' . $e->getMessage()];
        }
    }
    
    
    public function completeRecharge($rechargeId, $userId) {
        try {

            $recharge = $this->db->fetch(
                "SELECT * FROM user_recharges WHERE id = :id AND user_id = :user_id",
                ['id' => $rechargeId, 'user_id' => $userId]
            );
            
            if (!$recharge) {
                return ['success' => false, 'message' => '充值记录不存在'];
            }
            
            if ($recharge['payment_status'] === 'completed') {
                return ['success' => false, 'message' => '该充值订单已完成'];
            }
            

            $this->db->beginTransaction();
            

            $this->db->update('user_recharges',
                [
                    'payment_status' => 'completed',
                    'completed_at' => date('Y-m-d H:i:s')
                ],
                'id = :id',
                ['id' => $rechargeId]
            );
            

            $this->db->exec(
                "UPDATE users SET balance = balance + :amount, updated_at = :updated WHERE id = :id",
                [
                    'amount' => $recharge['amount'],
                    'updated' => date('Y-m-d H:i:s'),
                    'id' => $userId
                ]
            );
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => '充值成功',
                'amount' => $recharge['amount']
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => '充值失败: ' . $e->getMessage()];
        }
    }
    
    
    public function getRechargeHistory($userId, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            $records = $this->db->fetchAll(
                "SELECT 
                    id,
                    amount,
                    payment_method,
                    payment_status,
                    created_at,
                    completed_at
                 FROM user_recharges 
                 WHERE user_id = :user_id 
                 ORDER BY created_at DESC
                 LIMIT :limit OFFSET :offset",
                [
                    'user_id' => $userId,
                    'limit' => $limit,
                    'offset' => $offset
                ]
            );
            
            $total = $this->db->fetch(
                "SELECT COUNT(*) as count FROM user_recharges WHERE user_id = :user_id",
                ['user_id' => $userId]
            )['count'];
            

            $totalAmount = $this->db->fetch(
                "SELECT SUM(amount) as total FROM user_recharges WHERE user_id = :user_id AND payment_status = 'completed'",
                ['user_id' => $userId]
            )['total'] ?? 0;
            
            return [
                'success' => true,
                'data' => [
                    'records' => $records,
                    'total' => $total,
                    'page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_amount' => $totalAmount
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => '获取充值记录失败: ' . $e->getMessage()];
        }
    }
    
    
    public function deductBalance($userId, $amount) {
        try {

            $user = $this->db->fetch(
                "SELECT balance FROM users WHERE id = :id",
                ['id' => $userId]
            );
            
            if (!$user) {
                return ['success' => false, 'message' => '用户不存在'];
            }
            
            if ($user['balance'] < $amount) {
                return ['success' => false, 'message' => '余额不足'];
            }
            

            $this->db->exec(
                "UPDATE users SET balance = balance - :amount, updated_at = :updated WHERE id = :id",
                [
                    'amount' => $amount,
                    'updated' => date('Y-m-d H:i:s'),
                    'id' => $userId
                ]
            );
            
            return ['success' => true, 'message' => '扣费成功'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => '扣费失败: ' . $e->getMessage()];
        }
    }
    
    
    public function recordUsage($userId, $actionType, $model, $inputTokens, $outputTokens, $cost = 0) {
        try {
            $totalTokens = $inputTokens + $outputTokens;
            
            $this->db->insert('user_usage', [
                'user_id' => $userId,
                'action_type' => $actionType,
                'model' => $model,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'total_tokens' => $totalTokens,
                'cost' => $cost,
                'created_at' => date('Y-m-d H:i:s'),
                'created_date' => date('Y-m-d')
            ]);
            
            return ['success' => true];
        } catch (Exception $e) {
            error_log('记录用量失败: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
