<?php

header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']) {
    echo json_encode(['status' => 'error', 'message' => '请先登录']);
    exit;
}

require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();
$currentUser = $_SESSION['user'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';


initRechargeTables($db);

switch ($action) {

    case 'getBalance':
        getUserBalance($db, $currentUser['id']);
        break;


    case 'createOrder':
        createRechargeOrder($db, $currentUser['id']);
        break;


    case 'getRechargeRecords':
        getRechargeRecords($db, $currentUser['id']);
        break;


    case 'simulatePayment':
        simulatePaymentCallback($db, $currentUser['id']);
        break;


    case 'getAllRecharges':
        if ($currentUser['role'] !== 'admin') {
            echo json_encode(['status' => 'error', 'message' => '权限不足']);
            exit;
        }
        getAllRechargeRecords($db);
        break;


    case 'confirmRecharge':
        if ($currentUser['role'] !== 'admin') {
            echo json_encode(['status' => 'error', 'message' => '权限不足']);
            exit;
        }
        confirmRechargeManually($db);
        break;


    case 'saveRechargeConfig':
        if ($currentUser['role'] !== 'admin') {
            echo json_encode(['status' => 'error', 'message' => '权限不足']);
            exit;
        }
        saveRechargeConfig();
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => '未知操作']);
}


function initRechargeTables($db) {

    $db->getPdo()->exec("CREATE TABLE IF NOT EXISTS recharge_orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        order_no VARCHAR(64) UNIQUE NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        gift_amount DECIMAL(10,2) DEFAULT 0.00,
        total_amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) DEFAULT 'alipay',
        payment_status VARCHAR(20) DEFAULT 'pending',
        pay_url TEXT,
        paid_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");


    $db->getPdo()->exec("CREATE INDEX IF NOT EXISTS idx_recharge_order_user ON recharge_orders(user_id)");
    $db->getPdo()->exec("CREATE INDEX IF NOT EXISTS idx_recharge_order_no ON recharge_orders(order_no)");
    $db->getPdo()->exec("CREATE INDEX IF NOT EXISTS idx_recharge_order_status ON recharge_orders(payment_status)");


    try {
        $db->getPdo()->exec("ALTER TABLE users ADD COLUMN balance DECIMAL(10,2) DEFAULT 0.00");
    } catch (PDOException $e) {

    }


    $db->getPdo()->exec("CREATE TABLE IF NOT EXISTS balance_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        type VARCHAR(20) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        balance_before DECIMAL(10,2) NOT NULL,
        balance_after DECIMAL(10,2) NOT NULL,
        remark TEXT,
        order_no VARCHAR(64),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->getPdo()->exec("CREATE INDEX IF NOT EXISTS idx_balance_log_user ON balance_logs(user_id)");
    $db->getPdo()->exec("CREATE INDEX IF NOT EXISTS idx_balance_log_type ON balance_logs(type)");
}


function getUserBalance($db, $userId) {
    try {

        $user = $db->fetch("SELECT balance FROM users WHERE id = :id", ['id' => $userId]);
        $balance = floatval($user['balance'] ?? 0);


        $stats = $db->fetch(
            "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as total_recharge,
                SUM(CASE WHEN payment_status = 'pending' THEN total_amount ELSE 0 END) as pending_amount
            FROM recharge_orders 
            WHERE user_id = :user_id",
            ['user_id' => $userId]
        );

        echo json_encode([
            'status' => 'success',
            'data' => [
                'balance' => $balance,
                'total_orders' => intval($stats['total_orders'] ?? 0),
                'total_recharge' => floatval($stats['total_recharge'] ?? 0),
                'pending_amount' => floatval($stats['pending_amount'] ?? 0)
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function createRechargeOrder($db, $userId) {
    try {
        $amount = floatval($_POST['amount'] ?? 0);
        $paymentMethod = $_POST['payment_method'] ?? 'alipay';


        if ($amount <= 0) {
            echo json_encode(['status' => 'error', 'message' => '充值金额必须大于0']);
            return;
        }


        $validAmounts = [10, 20, 50, 100, 200, 500, 1000];
        if (!in_array($amount, $validAmounts)) {
            echo json_encode(['status' => 'error', 'message' => '无效的充值金额']);
            return;
        }


        $giftAmount = calculateGiftAmount($amount);
        $totalAmount = $amount + $giftAmount;


        $orderNo = generateOrderNo();


        $orderId = $db->insert('recharge_orders', [
            'user_id' => $userId,
            'order_no' => $orderNo,
            'amount' => $amount,
            'gift_amount' => $giftAmount,
            'total_amount' => $totalAmount,
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
            'pay_url' => '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);


        $payUrl = generatePayUrl($orderNo, $amount, $paymentMethod);


        $db->update('recharge_orders', 
            ['pay_url' => $payUrl],
            'id = :id',
            ['id' => $orderId]
        );

        echo json_encode([
            'status' => 'success',
            'data' => [
                'order_id' => $orderId,
                'order_no' => $orderNo,
                'amount' => $amount,
                'gift_amount' => $giftAmount,
                'total_amount' => $totalAmount,
                'pay_url' => $payUrl,
                'payment_method' => $paymentMethod
            ]
        ]);

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function calculateGiftAmount($amount) {

    if ($amount >= 1000) return 200;
    if ($amount >= 500) return 80;
    if ($amount >= 200) return 25;
    if ($amount >= 100) return 10;
    if ($amount >= 50) return 3;
    return 0;
}


function generateOrderNo() {
    return 'R' . date('YmdHis') . rand(1000, 9999);
}


function generatePayUrl($orderNo, $amount, $method) {

    $baseUrl = 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
    return $baseUrl . '/recharge_handler.php?action=callback&order_no=' . $orderNo;
}


function getRechargeRecords($db, $userId) {
    try {
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 10);
        $offset = ($page - 1) * $limit;

        $records = $db->fetchAll(
            "SELECT * FROM recharge_orders 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset",
            ['user_id' => $userId, 'limit' => $limit, 'offset' => $offset]
        );

        $total = $db->fetch(
            "SELECT COUNT(*) as count FROM recharge_orders WHERE user_id = :user_id",
            ['user_id' => $userId]
        );

        echo json_encode([
            'status' => 'success',
            'data' => [
                'records' => $records,
                'total' => intval($total['count'] ?? 0),
                'page' => $page,
                'limit' => $limit
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function simulatePaymentCallback($db, $userId) {
    try {
        $orderNo = $_POST['order_no'] ?? '';

        if (!$orderNo) {
            echo json_encode(['status' => 'error', 'message' => '订单号不能为空']);
            return;
        }


        $order = $db->fetch(
            "SELECT * FROM recharge_orders WHERE order_no = :order_no AND user_id = :user_id",
            ['order_no' => $orderNo, 'user_id' => $userId]
        );

        if (!$order) {
            echo json_encode(['status' => 'error', 'message' => '订单不存在']);
            return;
        }

        if ($order['payment_status'] === 'paid') {
            echo json_encode(['status' => 'error', 'message' => '订单已支付']);
            return;
        }


        $db->update('recharge_orders', [
            'payment_status' => 'paid',
            'paid_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $order['id']]);


        $user = $db->fetch("SELECT balance FROM users WHERE id = :id", ['id' => $userId]);
        $balanceBefore = floatval($user['balance'] ?? 0);
        $balanceAfter = $balanceBefore + floatval($order['total_amount']);

        $db->update('users', [
            'balance' => $balanceAfter
        ], 'id = :id', ['id' => $userId]);


        $db->insert('balance_logs', [
            'user_id' => $userId,
            'type' => 'recharge',
            'amount' => $order['total_amount'],
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'remark' => '充值 ' . $order['amount'] . ' 元，赠送 ' . $order['gift_amount'] . ' 元',
            'order_no' => $orderNo,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => '充值成功',
            'data' => [
                'order_no' => $orderNo,
                'amount' => $order['amount'],
                'gift_amount' => $order['gift_amount'],
                'total_amount' => $order['total_amount'],
                'balance' => $balanceAfter
            ]
        ]);

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function getAllRechargeRecords($db) {
    try {
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        $status = $_GET['status'] ?? '';

        $where = "1=1";
        $params = [];

        if ($status) {
            $where .= " AND r.payment_status = :status";
            $params['status'] = $status;
        }

        $sql = "SELECT r.*, u.username 
                FROM recharge_orders r
                LEFT JOIN users u ON r.user_id = u.id
                WHERE {$where}
                ORDER BY r.created_at DESC 
                LIMIT :limit OFFSET :offset";

        $params['limit'] = $limit;
        $params['offset'] = $offset;

        $records = $db->fetchAll($sql, $params);

        $totalSql = "SELECT COUNT(*) as count FROM recharge_orders r WHERE {$where}";
        $totalParams = array_diff_key($params, ['limit' => 1, 'offset' => 1]);
        $total = $db->fetch($totalSql, $totalParams);

        echo json_encode([
            'status' => 'success',
            'data' => [
                'records' => $records,
                'total' => intval($total['count'] ?? 0),
                'page' => $page,
                'limit' => $limit
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function confirmRechargeManually($db) {
    try {
        $orderId = intval($_POST['order_id'] ?? 0);

        if (!$orderId) {
            echo json_encode(['status' => 'error', 'message' => '订单ID不能为空']);
            return;
        }


        $order = $db->fetch(
            "SELECT * FROM recharge_orders WHERE id = :id",
            ['id' => $orderId]
        );

        if (!$order) {
            echo json_encode(['status' => 'error', 'message' => '订单不存在']);
            return;
        }

        if ($order['payment_status'] === 'paid') {
            echo json_encode(['status' => 'error', 'message' => '订单已支付']);
            return;
        }

        $userId = $order['user_id'];


        $db->update('recharge_orders', [
            'payment_status' => 'paid',
            'paid_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $orderId]);


        $user = $db->fetch("SELECT balance FROM users WHERE id = :id", ['id' => $userId]);
        $balanceBefore = floatval($user['balance'] ?? 0);
        $balanceAfter = $balanceBefore + floatval($order['total_amount']);

        $db->update('users', [
            'balance' => $balanceAfter
        ], 'id = :id', ['id' => $userId]);


        $db->insert('balance_logs', [
            'user_id' => $userId,
            'type' => 'recharge',
            'amount' => $order['total_amount'],
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'remark' => '管理员手动确认充值 ' . $order['amount'] . ' 元，赠送 ' . $order['gift_amount'] . ' 元',
            'order_no' => $order['order_no'],
            'created_at' => date('Y-m-d H:i:s')
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => '充值确认成功'
        ]);

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function saveRechargeConfig() {
    try {
        $config = require __DIR__ . '/../config/config.php';


        $config['recharge'] = [
            'enabled' => $_POST['enabled'] === 'true' || $_POST['enabled'] === true,
            'message' => $_POST['message'] ?? '充值功能开发中，敬请期待'
        ];


        $configContent = "<?php\n// 配置文件 - 由后台管理系统生成\nreturn " . var_export($config, true) . ";\n?>";
        file_put_contents(__DIR__ . '/../config/config.php', $configContent);

        echo json_encode(['status' => 'success', 'message' => '充值设置已保存']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
