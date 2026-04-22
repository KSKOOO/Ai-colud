<?php

header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => '权限不足']);
    exit;
}

require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();
$action = $_POST['action'] ?? $_GET['action'] ?? '';


static $tablesInitialized = false;
if (!$tablesInitialized) {
    initBillingTables($db);
    $tablesInitialized = true;
}

switch ($action) {
    case 'getStats':
        getBillingStats($db);
        break;
    case 'getRecords':
        getBillingRecords($db);
        break;
    case 'saveBillingConfig':
        saveBillingConfig($db);
        break;
    case 'exportReport':
        exportBillingReport($db);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => '未知操作']);
}


function initBillingTables($db) {

    $db->getPdo()->exec("CREATE TABLE IF NOT EXISTS usage_records (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        tokens_input INTEGER DEFAULT 0,
        tokens_output INTEGER DEFAULT 0,
        tokens_total INTEGER DEFAULT 0,
        cost REAL DEFAULT 0,
        model VARCHAR(100),
        request_type VARCHAR(50),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");


    $db->getPdo()->exec("CREATE INDEX IF NOT EXISTS idx_usage_user_id ON usage_records(user_id)");
    $db->getPdo()->exec("CREATE INDEX IF NOT EXISTS idx_usage_created ON usage_records(created_at)");
}


function getBillingStats($db) {
    try {

        $monthStart = date('Y-m-01 00:00:00');
        $monthEnd = date('Y-m-t 23:59:59');


        $totalStats = $db->fetch(
            "SELECT SUM(tokens_total) as total_tokens, SUM(cost) as total_cost 
             FROM usage_records 
             WHERE created_at BETWEEN :start AND :end",
            ['start' => $monthStart, 'end' => $monthEnd]
        );


        $activeUsers = $db->fetch(
            "SELECT COUNT(DISTINCT user_id) as count 
             FROM usage_records 
             WHERE created_at BETWEEN :start AND :end",
            ['start' => $monthStart, 'end' => $monthEnd]
        );


        $requestCount = $db->fetch(
            "SELECT COUNT(*) as count 
             FROM usage_records 
             WHERE created_at BETWEEN :start AND :end",
            ['start' => $monthStart, 'end' => $monthEnd]
        );

        $totalTokens = intval($totalStats['total_tokens'] ?? 0);
        $totalCost = floatval($totalStats['total_cost'] ?? 0);
        $requestNum = intval($requestCount['count'] ?? 0);

        echo json_encode([
            'status' => 'success',
            'data' => [
                'total_tokens' => $totalTokens,
                'total_cost' => $totalCost,
                'active_users' => intval($activeUsers['count'] ?? 0),
                'avg_cost' => $requestNum > 0 ? round($totalCost / $requestNum, 2) : 0
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'success',
            'data' => [
                'total_tokens' => 0,
                'total_cost' => 0,
                'active_users' => 0,
                'avg_cost' => 0
            ]
        ]);
    }
}


function getBillingRecords($db) {
    try {

        $monthStart = date('Y-m-01 00:00:00');

        $records = $db->fetchAll(
            "SELECT u.username, 
                    SUM(r.tokens_total) as tokens, 
                    SUM(r.cost) as cost,
                    MAX(r.created_at) as date
             FROM usage_records r
             JOIN users u ON r.user_id = u.id
             WHERE r.created_at >= :start
             GROUP BY r.user_id
             ORDER BY cost DESC
             LIMIT 50",
            ['start' => $monthStart]
        );

        echo json_encode([
            'status' => 'success',
            'records' => $records
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'success', 'records' => []]);
    }
}


function saveBillingConfig($db) {
    try {
        $config = require __DIR__ . '/../config/config.php';


        $config['billing'] = [
            'enabled' => $_POST['enabled'] === 'true' || $_POST['enabled'] === true,
            'free_quota' => intval($_POST['free_quota'] ?? 100000),
            'price_per_1k' => floatval($_POST['price_per_1k'] ?? 0.02),
            'cycle' => $_POST['cycle'] ?? 'monthly'
        ];


        $configContent = "<?php\nreturn " . var_export($config, true) . ";\n?>";
        file_put_contents(__DIR__ . '/../config/config.php', $configContent);

        echo json_encode(['status' => 'success', 'message' => '计费设置已保存']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function exportBillingReport($db) {

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=billing_report_' . date('Y-m') . '.csv');


    echo "\xEF\xBB\xBF";


    echo "用户名,Token使用量,费用(元),最后使用日期\n";

    try {
        $monthStart = date('Y-m-01 00:00:00');
        $records = $db->fetchAll(
            "SELECT u.username, 
                    SUM(r.tokens_total) as tokens, 
                    SUM(r.cost) as cost,
                    MAX(r.created_at) as date
             FROM usage_records r
             JOIN users u ON r.user_id = u.id
             WHERE r.created_at >= :start
             GROUP BY r.user_id
             ORDER BY cost DESC",
            ['start' => $monthStart]
        );

        foreach ($records as $record) {
            echo sprintf("%s,%d,%.2f,%s\n",
                $record['username'],
                $record['tokens'],
                $record['cost'],
                date('Y-m-d H:i', strtotime($record['date']))
            );
        }
    } catch (Exception $e) {
        echo "导出失败," . $e->getMessage() . ",,";
    }

    exit;
}
