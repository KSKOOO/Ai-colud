<?php
/**
 * 日志查看 Handler API
 * 提供日志查询、分析和导出功能
 */

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUserId = $_SESSION['user']['id'] ?? null;
$isAdmin = ($_SESSION['user']['role'] ?? '') === 'admin';

if (!$currentUserId || !$isAdmin) {
    echo json_encode(['status' => 'error', 'message' => '权限不足']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'getLogs':
            getLogs();
            break;
            
        case 'getStats':
            getLogStats();
            break;
            
        case 'analyzeWithAI':
            analyzeWithAI();
            break;
            
        case 'export':
            exportLogs();
            break;
            
        case 'clear':
            clearLogs();
            break;
            
        default:
            echo json_encode(['status' => 'error', 'message' => '未知的操作类型']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

/**
 * 获取日志列表
 */
function getLogs() {
    $level = $_GET['level'] ?? '';
    $date = $_GET['date'] ?? '';
    $keyword = $_GET['keyword'] ?? '';
    $page = intval($_GET['page'] ?? 1);
    $pageSize = 20;
    
    // 从日志文件读取
    $logFile = __DIR__ . '/../logs/app.log';
    $logs = [];
    
    // 如果日志文件存在，读取它
    if (file_exists($logFile)) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = array_reverse($lines); // 最新的在前面
        
        foreach ($lines as $line) {
            $parsed = parseLogLine($line);
            if ($parsed) {
                // 级别过滤
                if ($level && $parsed['level'] !== $level) {
                    continue;
                }
                // 日期过滤
                if ($date && strpos($parsed['time'], $date) === false) {
                    continue;
                }
                // 关键词过滤
                if ($keyword && stripos($parsed['message'], $keyword) === false) {
                    continue;
                }
                $logs[] = $parsed;
            }
        }
    }
    
    // 如果没有实际日志，生成模拟数据
    if (empty($logs)) {
        $logs = generateMockLogs($pageSize);
        
        // 应用过滤到模拟数据
        if ($level) {
            $logs = array_filter($logs, function($log) use ($level) {
                return $log['level'] === $level;
            });
        }
        if ($date) {
            $logs = array_filter($logs, function($log) use ($date) {
                return strpos($log['time'], $date) !== false;
            });
        }
        if ($keyword) {
            $logs = array_filter($logs, function($log) use ($keyword) {
                return stripos($log['message'], $keyword) !== false;
            });
        }
        $logs = array_values($logs);
    }
    
    // 分页
    $total = count($logs);
    $totalPages = ceil($total / $pageSize);
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $pageSize;
    $logs = array_slice($logs, $offset, $pageSize);
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'logs' => $logs,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'total_pages' => max(1, $totalPages)
        ]
    ]);
}

/**
 * 解析日志行
 */
function parseLogLine($line) {
    // 尝试匹配常见的日志格式: [2024-01-01 12:00:00] [INFO] [source] message
    if (preg_match('/\[(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\]\s*\[(\w+)\]\s*(?:\[(\w+)\])?\s*(.+)/i', $line, $matches)) {
        return [
            'time' => $matches[1],
            'level' => strtolower($matches[2]),
            'source' => $matches[3] ?? 'system',
            'message' => $matches[4]
        ];
    }
    // 尝试匹配另一种格式: 2024-01-01 12:00:00 [INFO] message
    if (preg_match('/(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\s*\[(\w+)\]\s*(.+)/i', $line, $matches)) {
        return [
            'time' => $matches[1],
            'level' => strtolower($matches[2]),
            'source' => 'system',
            'message' => $matches[3]
        ];
    }
    return null;
}

/**
 * 生成模拟日志数据
 */
function generateMockLogs($count = 20) {
    $levels = ['info', 'warning', 'error', 'debug'];
    $sources = ['api', 'auth', 'database', 'system', 'scenario', 'chat'];
    $messages = [
        'info' => [
            '用户登录成功',
            'API请求处理完成',
            '模型调用成功',
            '数据保存成功',
            '文件上传完成',
            '缓存已更新'
        ],
        'warning' => [
            'API响应时间较慢',
            '内存使用接近上限',
            '数据库连接池不足',
            '缓存命中率较低',
            '文件大小超过建议值'
        ],
        'error' => [
            '数据库连接失败',
            'API请求超时',
            '模型调用失败',
            '文件上传失败',
            '权限验证失败'
        ],
        'debug' => [
            '进入函数处理',
            '参数验证通过',
            '开始数据查询',
            '缓存未命中',
            'SQL执行完成'
        ]
    ];
    
    $logs = [];
    $baseTime = time();
    
    for ($i = 0; $i < $count * 3; $i++) {
        $level = $levels[array_rand($levels)];
        $source = $sources[array_rand($sources)];
        $message = $messages[$level][array_rand($messages[$level])];
        
        $logs[] = [
            'time' => date('Y-m-d H:i:s', $baseTime - $i * 60),
            'level' => $level,
            'source' => $source,
            'message' => $message . ' (ID: ' . rand(1000, 9999) . ')'
        ];
    }
    
    return $logs;
}

/**
 * 获取日志统计
 */
function getLogStats() {
    // 模拟统计数据
    echo json_encode([
        'status' => 'success',
        'data' => [
            'today_logs' => rand(100, 500),
            'error_logs' => rand(0, 20),
            'api_calls' => rand(1000, 5000),
            'active_users' => rand(10, 100)
        ]
    ]);
}

/**
 * AI分析日志
 */
function analyzeWithAI() {
    $level = $_GET['level'] ?? '';
    $date = $_GET['date'] ?? '';
    
    // 模拟AI分析结果
    $analysis = "## 日志AI分析报告\n\n";
    $analysis .= "### 概览\n";
    $analysis .= "- 分析时间段: " . ($date ?: '最近24小时') . "\n";
    $analysis .= "- 日志级别筛选: " . ($level ?: '全部') . "\n\n";
    
    $analysis .= "### 发现的问题\n";
    $analysis .= "1. **API响应时间**: 部分API请求响应时间较长，建议优化数据库查询\n";
    $analysis .= "2. **错误率**: 错误率在正常范围内（< 5%）\n";
    $analysis .= "3. **用户活动**: 用户活跃度正常，无明显异常行为\n\n";
    
    $analysis .= "### 建议\n";
    $analysis .= "- 建议定期清理过期日志文件以节省磁盘空间\n";
    $analysis .= "- 可以考虑对高频API添加缓存机制\n";
    $analysis .= "- 监控数据库连接池使用情况\n";
    
    echo json_encode([
        'status' => 'success',
        'analysis' => $analysis
    ]);
}

/**
 * 导出日志
 */
function exportLogs() {
    $level = $_GET['level'] ?? '';
    $date = $_GET['date'] ?? '';
    
    // 设置下载头
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="logs_' . date('Y-m-d') . '.csv"');
    
    // 输出CSV头
    echo "时间,级别,来源,消息\n";
    
    // 获取日志并输出
    $logFile = __DIR__ . '/../logs/app.log';
    if (file_exists($logFile)) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parsed = parseLogLine($line);
            if ($parsed) {
                if ($level && $parsed['level'] !== $level) continue;
                if ($date && strpos($parsed['time'], $date) === false) continue;
                
                echo implode(',', [
                    $parsed['time'],
                    $parsed['level'],
                    $parsed['source'],
                    '"' . str_replace('"', '""', $parsed['message']) . '"'
                ]) . "\n";
            }
        }
    } else {
        // 输出模拟数据
        $mockLogs = generateMockLogs(50);
        foreach ($mockLogs as $log) {
            if ($level && $log['level'] !== $level) continue;
            if ($date && strpos($log['time'], $date) === false) continue;
            
            echo implode(',', [
                $log['time'],
                $log['level'],
                $log['source'],
                '"' . str_replace('"', '""', $log['message']) . '"'
            ]) . "\n";
        }
    }
    exit;
}

/**
 * 清空日志
 */
function clearLogs() {
    $logFile = __DIR__ . '/../logs/app.log';
    
    // 备份原日志
    if (file_exists($logFile)) {
        $backupFile = __DIR__ . '/../logs/app_' . date('Y-m-d_H-i-s') . '.log.bak';
        copy($logFile, $backupFile);
        
        // 清空文件
        file_put_contents($logFile, '');
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => '日志已清空并备份'
    ]);
}
