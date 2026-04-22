<?php

class PermissionManager {
    private $db;
    

    const MODULE_CHAT = 'chat';
    const MODULE_SCENARIOS = 'scenarios';
    const MODULE_WORKFLOW = 'workflow';
    const MODULE_TRAINING = 'training';
    const MODULE_ADMIN = 'admin';
    const MODULE_USER_CENTER = 'user_center';
    const MODULE_API_KEY = 'api_key';
    const MODULE_AGENTS = 'agents';
    const MODULE_RECHARGE = 'recharge';
    

    const PERMISSION_ACCESS = 'access';
    const PERMISSION_USE = 'use';
    const PERMISSION_TRAIN = 'train';
    
    public function __construct($database) {
        $this->db = $database;
        $this->initTables();
    }
    
    
    private function initTables() {

        $this->db->exec("CREATE TABLE IF NOT EXISTS user_module_permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            module VARCHAR(50) NOT NULL,
            allowed INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(user_id, module)
        )");
        

        $this->db->exec("CREATE TABLE IF NOT EXISTS user_model_permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            provider_id VARCHAR(100) NOT NULL,
            model_id VARCHAR(100) NOT NULL,
            allowed INTEGER DEFAULT 1,
            max_tokens_per_day INTEGER DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(user_id, provider_id, model_id)
        )");
        

        $this->db->exec("CREATE TABLE IF NOT EXISTS user_training_permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            model_name VARCHAR(100) NOT NULL,
            allowed INTEGER DEFAULT 1,
            max_training_jobs INTEGER DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(user_id, model_name)
        )");
        

        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_user_module ON user_module_permissions(user_id, module)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_user_model ON user_model_permissions(user_id, provider_id, model_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_user_training ON user_training_permissions(user_id, model_name)");
    }
    
    
    public function hasModulePermission($userId, $module) {

        if ($this->isAdmin($userId)) {
            return true;
        }
        
        $result = $this->db->fetch(
            "SELECT allowed FROM user_module_permissions 
             WHERE user_id = :user_id AND module = :module",
            ['user_id' => $userId, 'module' => $module]
        );
        

        if (!$result) {
            return true;
        }
        
        return (bool)$result['allowed'];
    }
    
    
    public function hasModelPermission($userId, $providerId, $modelId) {

        if ($this->isAdmin($userId)) {
            return true;
        }
        

        $hasAnyModelPermission = $this->db->fetch(
            "SELECT COUNT(*) as count FROM user_model_permissions WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        

        if (!$hasAnyModelPermission || $hasAnyModelPermission['count'] == 0) {
            return true;
        }
        

        $wildcardResult = $this->db->fetch(
            "SELECT allowed FROM user_model_permissions 
             WHERE user_id = :user_id AND provider_id = :provider_id AND model_id = '*'",
            ['user_id' => $userId, 'provider_id' => $providerId]
        );
        
        if ($wildcardResult) {
            return (bool)$wildcardResult['allowed'];
        }
        

        $result = $this->db->fetch(
            "SELECT allowed FROM user_model_permissions 
             WHERE user_id = :user_id AND provider_id = :provider_id AND model_id = :model_id",
            ['user_id' => $userId, 'provider_id' => $providerId, 'model_id' => $modelId]
        );
        

        if (!$result) {
            return false;
        }
        
        return (bool)$result['allowed'];
    }
    
    
    public function hasTrainingPermission($userId, $modelName = null) {

        if ($this->isAdmin($userId)) {
            return true;
        }
        
        if ($modelName) {
            $result = $this->db->fetch(
                "SELECT allowed FROM user_training_permissions 
                 WHERE user_id = :user_id AND model_name = :model_name",
                ['user_id' => $userId, 'model_name' => $modelName]
            );
            
            if (!$result) {

                $wildcardResult = $this->db->fetch(
                    "SELECT allowed FROM user_training_permissions 
                     WHERE user_id = :user_id AND model_name = '*'",
                    ['user_id' => $userId]
                );
                
                if ($wildcardResult) {
                    return (bool)$wildcardResult['allowed'];
                }
                
                return true;
            }
            
            return (bool)$result['allowed'];
        }
        

        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM user_training_permissions 
             WHERE user_id = :user_id AND allowed = 1",
            ['user_id' => $userId]
        );
        
        return $result['count'] > 0;
    }
    
    
    public function setModulePermission($userId, $module, $allowed) {
        $existing = $this->db->fetch(
            "SELECT id FROM user_module_permissions 
             WHERE user_id = :user_id AND module = :module",
            ['user_id' => $userId, 'module' => $module]
        );
        
        if ($existing) {
            $this->db->execute(
                "UPDATE user_module_permissions 
                 SET allowed = :allowed, updated_at = datetime('now')
                 WHERE id = :id",
                ['allowed' => $allowed ? 1 : 0, 'id' => $existing['id']]
            );
        } else {
            $this->db->execute(
                "INSERT INTO user_module_permissions (user_id, module, allowed) 
                 VALUES (:user_id, :module, :allowed)",
                ['user_id' => $userId, 'module' => $module, 'allowed' => $allowed ? 1 : 0]
            );
        }
        
        return ['success' => true];
    }
    
    
    public function setModelPermission($userId, $providerId, $modelId, $allowed, $maxTokensPerDay = null) {
        $existing = $this->db->fetch(
            "SELECT id FROM user_model_permissions 
             WHERE user_id = :user_id AND provider_id = :provider_id AND model_id = :model_id",
            ['user_id' => $userId, 'provider_id' => $providerId, 'model_id' => $modelId]
        );
        
        if ($existing) {
            $this->db->execute(
                "UPDATE user_model_permissions 
                 SET allowed = :allowed, max_tokens_per_day = :max_tokens, updated_at = datetime('now')
                 WHERE id = :id",
                [
                    'allowed' => $allowed ? 1 : 0,
                    'max_tokens' => $maxTokensPerDay,
                    'id' => $existing['id']
                ]
            );
        } else {
            $this->db->execute(
                "INSERT INTO user_model_permissions (user_id, provider_id, model_id, allowed, max_tokens_per_day) 
                 VALUES (:user_id, :provider_id, :model_id, :allowed, :max_tokens)",
                [
                    'user_id' => $userId,
                    'provider_id' => $providerId,
                    'model_id' => $modelId,
                    'allowed' => $allowed ? 1 : 0,
                    'max_tokens' => $maxTokensPerDay
                ]
            );
        }
        
        return ['success' => true];
    }
    
    
    public function setTrainingPermission($userId, $modelName, $allowed, $maxJobs = null) {
        $existing = $this->db->fetch(
            "SELECT id FROM user_training_permissions 
             WHERE user_id = :user_id AND model_name = :model_name",
            ['user_id' => $userId, 'model_name' => $modelName]
        );
        
        if ($existing) {
            $this->db->execute(
                "UPDATE user_training_permissions 
                 SET allowed = :allowed, max_training_jobs = :max_jobs, updated_at = datetime('now')
                 WHERE id = :id",
                [
                    'allowed' => $allowed ? 1 : 0,
                    'max_jobs' => $maxJobs,
                    'id' => $existing['id']
                ]
            );
        } else {
            $this->db->execute(
                "INSERT INTO user_training_permissions (user_id, model_name, allowed, max_training_jobs) 
                 VALUES (:user_id, :model_name, :allowed, :max_jobs)",
                [
                    'user_id' => $userId,
                    'model_name' => $modelName,
                    'allowed' => $allowed ? 1 : 0,
                    'max_jobs' => $maxJobs
                ]
            );
        }
        
        return ['success' => true];
    }
    
    
    public function getUserPermissions($userId) {

        $modulePermissions = $this->db->fetchAll(
            "SELECT module, allowed FROM user_module_permissions WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        

        $modelPermissions = $this->db->fetchAll(
            "SELECT provider_id, model_id, allowed, max_tokens_per_day 
             FROM user_model_permissions WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        

        $trainingPermissions = $this->db->fetchAll(
            "SELECT model_name, allowed, max_training_jobs 
             FROM user_training_permissions WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        
        return [
            'modules' => $modulePermissions,
            'models' => $modelPermissions,
            'training' => $trainingPermissions
        ];
    }
    
    
    public function getAllModules() {
        return [
            self::MODULE_CHAT => 'AI聊天',
            self::MODULE_SCENARIOS => '场景演示',
            self::MODULE_WORKFLOW => '工作流',
            self::MODULE_TRAINING => '模型训练',
            self::MODULE_AGENTS => '智能体',
            self::MODULE_USER_CENTER => '用户中心',
            self::MODULE_API_KEY => 'API密钥管理',
            self::MODULE_ADMIN => '后台管理'
        ];
    }
    
    
    private function isAdmin($userId) {
        $user = $this->db->fetch(
            "SELECT role FROM users WHERE id = :id",
            ['id' => $userId]
        );
        
        return $user && $user['role'] === 'admin';
    }
    
    
    public function deleteUserPermissions($userId) {
        $this->db->execute(
            "DELETE FROM user_module_permissions WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        
        $this->db->execute(
            "DELETE FROM user_model_permissions WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        
        $this->db->execute(
            "DELETE FROM user_training_permissions WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        
        return ['success' => true];
    }
    
    
    public function batchSetPermissions($userId, $permissions) {
        try {

            if (isset($permissions['modules'])) {
                foreach ($permissions['modules'] as $module => $allowed) {
                    $this->setModulePermission($userId, $module, $allowed);
                }
            }
            

            if (isset($permissions['models'])) {
                foreach ($permissions['models'] as $model) {
                    $this->setModelPermission(
                        $userId,
                        $model['provider_id'],
                        $model['model_id'],
                        $model['allowed'],
                        $model['max_tokens_per_day'] ?? null
                    );
                }
            }
            

            if (isset($permissions['training'])) {
                foreach ($permissions['training'] as $training) {
                    $this->setTrainingPermission(
                        $userId,
                        $training['model_name'],
                        $training['allowed'],
                        $training['max_training_jobs'] ?? null
                    );
                }
            }
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
