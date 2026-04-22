<?php
require_once __DIR__ . '/Database.php';


class UserManager {
    const ROLE_USER = 'user';
    const ROLE_ADMIN = 'admin';

    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    
    public function createUser($username, $password, $email, $role = self::ROLE_USER) {

        if ($this->getUserByUsername($username)) {
            return ['success' => false, 'message' => '用户名已存在'];
        }


        if ($this->getUserByEmail($email)) {
            return ['success' => false, 'message' => '邮箱已被注册'];
        }


        if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            return ['success' => false, 'message' => '用户名只能包含字母、数字和下划线，长度3-20位'];
        }


        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => '邮箱格式不正确'];
        }


        if (strlen($password) < 6) {
            return ['success' => false, 'message' => '密码长度至少6位'];
        }


        $passwordHash = password_hash($password, PASSWORD_DEFAULT);


        try {
            $userId = $this->db->insert('users', [
                'username' => $username,
                'password_hash' => $passwordHash,
                'email' => $email,
                'role' => $role,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'last_login' => null,
                'is_active' => 1
            ]);

            return [
                'success' => true,
                'message' => '用户创建成功',
                'user_id' => $userId
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => '创建用户失败: ' . $e->getMessage()];
        }
    }

    
    public function login($username, $password) {
        $user = $this->getUserByUsername($username);
        
        if (!$user) {
            return ['success' => false, 'message' => '用户名或密码错误'];
        }

        if (!$user['is_active']) {
            return ['success' => false, 'message' => '账号已被禁用'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => '用户名或密码错误'];
        }


        $this->db->update('users', 
            ['last_login' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $user['id']]
        );


        unset($user['password_hash']);
        
        return [
            'success' => true,
            'message' => '登录成功',
            'user' => $user
        ];
    }

    
    public function getUserByUsername($username) {
        return $this->db->fetch(
            "SELECT * FROM users WHERE username = :username",
            ['username' => $username]
        );
    }

    
    public function getUserByEmail($email) {
        return $this->db->fetch(
            "SELECT * FROM users WHERE email = :email",
            ['email' => $email]
        );
    }

    
    public function getUserById($userId) {
        return $this->db->fetch(
            "SELECT * FROM users WHERE id = :id",
            ['id' => $userId]
        );
    }

    
    public function getAllUsers($page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $users = $this->db->fetchAll(
            "SELECT id, username, email, role, created_at, last_login, is_active 
             FROM users 
             ORDER BY created_at DESC 
             LIMIT :limit OFFSET :offset",
            ['limit' => $limit, 'offset' => $offset]
        );

        $total = $this->db->fetch("SELECT COUNT(*) as count FROM users")['count'];

        return [
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'total_pages' => ceil($total / $limit)
        ];
    }

    
    public function updateUser($userId, $data) {
        $allowedFields = ['email', 'role', 'is_active'];
        $updateData = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return ['success' => false, 'message' => '没有要更新的字段'];
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        try {
            $this->db->update('users', $updateData, 'id = :id', ['id' => $userId]);
            return ['success' => true, 'message' => '用户信息已更新'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => '更新失败: ' . $e->getMessage()];
        }
    }

    
    public function changePassword($userId, $oldPassword, $newPassword) {
        $user = $this->getUserById($userId);
        
        if (!$user) {
            return ['success' => false, 'message' => '用户不存在'];
        }

        if (!password_verify($oldPassword, $user['password_hash'])) {
            return ['success' => false, 'message' => '原密码错误'];
        }

        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => '新密码长度至少6位'];
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

        try {
            $this->db->update('users', 
                ['password_hash' => $newHash, 'updated_at' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $userId]
            );
            return ['success' => true, 'message' => '密码修改成功'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => '修改失败: ' . $e->getMessage()];
        }
    }

    
    public function adminResetPassword($userId, $newPassword) {
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => '密码长度至少6位'];
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

        try {
            $this->db->update('users', 
                ['password_hash' => $newHash, 'updated_at' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $userId]
            );
            return ['success' => true, 'message' => '密码重置成功'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => '重置失败: ' . $e->getMessage()];
        }
    }

    
    public function deleteUser($userId) {
        try {
            $this->db->delete('users', 'id = :id', ['id' => $userId]);
            return ['success' => true, 'message' => '用户已删除'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => '删除失败: ' . $e->getMessage()];
        }
    }

    
    public function isAdmin($userId) {
        $user = $this->getUserById($userId);
        return $user && $user['role'] === self::ROLE_ADMIN;
    }

    
    public function initAdmin() {

        $admin = $this->db->fetch("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
        
        if (!$admin) {

            return $this->createUser('admin', 'admin123', 'admin@example.com', self::ROLE_ADMIN);
        }
        
        return ['success' => true, 'message' => '管理员账号已存在'];
    }
}
?>
