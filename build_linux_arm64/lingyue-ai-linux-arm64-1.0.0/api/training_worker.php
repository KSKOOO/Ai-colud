<?php
/**
 * 模型训练工作进程
 * 负责执行实际的训练任务
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 0);

// 日志文件路径
$logFile = __DIR__ . '/../logs/training_' . date('Y-m-d') . '.log';
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}

$db = null;

/**
 * 写入训练日志到文件和数据库
 */
function trainingLog($message, $type = 'info', $taskId = null) {
    global $logFile, $db;
    $timestamp = date('Y-m-d H:i:s');
    $line = "[$timestamp] [$type] $message" . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    
    // 如果有任务ID，同时写入数据库
    if ($taskId && $db) {
        try {
            // 更新任务的 logs 字段
            $task = $db->fetch("SELECT logs FROM training_tasks WHERE id = :id", ['id' => $taskId]);
            if ($task) {
                $logs = json_decode($task['logs'] ?? '[]', true);
                if (!is_array($logs)) $logs = [];
                $logs[] = ['time' => date('H:i:s'), 'type' => $type, 'message' => $message];
                // 限制日志数量
                if (count($logs) > 100) {
                    $logs = array_slice($logs, -100);
                }
                $db->update('training_tasks', ['logs' => json_encode($logs)], 'id = :id', ['id' => $taskId]);
            }
        } catch (Exception $e) {
            // 数据库写入失败不影响文件日志
            file_put_contents($logFile, "[$timestamp] [error] 日志写入数据库失败: " . $e->getMessage() . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }
}

// 检查命令行参数
if ($argc < 2) {
    trainingLog("错误: 缺少 task_id 参数", 'error');
    exit("用法: php training_worker.php <task_id>\n");
}

$taskId = intval($argv[1]);
if (!$taskId) {
    trainingLog("错误: 无效的任务ID", 'error');
    exit("无效的任务ID\n");
}

trainingLog("========================================", 'info', $taskId);
trainingLog("训练工作进程启动 - Task ID: $taskId", 'info', $taskId);
trainingLog("========================================", 'info', $taskId);

// 加载数据库
require_once __DIR__ . '/../includes/Database.php';

try {
    $db = Database::getInstance();
    trainingLog("数据库连接成功", 'success', $taskId);
} catch (Exception $e) {
    trainingLog("数据库连接失败: " . $e->getMessage(), 'error', $taskId);
    exit("数据库连接失败\n");
}

// 更新任务状态为训练中
try {
    $db->update('training_tasks', 
        ['status' => 'training', 'started_at' => date('Y-m-d H:i:s'), 'progress' => 0, 'message' => '开始训练...'],
        'id = :id',
        ['id' => $taskId]
    );
    trainingLog("任务状态已更新为: 训练中", 'info', $taskId);
    
    // 获取任务详情
    $task = $db->fetch("SELECT * FROM training_tasks WHERE id = :id", ['id' => $taskId]);
    if (!$task) {
        trainingLog("错误: 任务不存在", 'error', $taskId);
        exit("任务不存在\n");
    }
    
    $epochs = intval($task['epochs']);
    $targetModel = $task['target_model'];
    $learningRate = $task['learning_rate'];
    $batchSize = $task['batch_size'];
    
    trainingLog("训练配置:", 'info', $taskId);
    trainingLog("  - 目标模型: $targetModel", 'info', $taskId);
    trainingLog("  - 训练轮数: $epochs", 'info', $taskId);
    trainingLog("  - 学习率: $learningRate", 'info', $taskId);
    trainingLog("  - 批次大小: $batchSize", 'info', $taskId);
    
    // 模拟训练过程
    for ($epoch = 1; $epoch <= $epochs; $epoch++) {
        // 检查是否被停止
        $currentStatus = $db->fetch("SELECT status FROM training_tasks WHERE id = :id", ['id' => $taskId])['status'];
        if ($currentStatus === 'stopped') {
            trainingLog("训练被用户停止", 'warning', $taskId);
            exit("训练已停止\n");
        }
        
        trainingLog("开始 Epoch $epoch/$epochs", 'info', $taskId);
        
        // 模拟训练步骤
        $steps = 10;
        for ($step = 1; $step <= $steps; $step++) {
            usleep(300000); // 模拟训练时间 (0.3秒)
            
            $progress = intval((($epoch - 1) * 100 / $epochs) + ($step * 100 / $steps / $epochs));
            $loss = 2.5 - ($epoch * 0.3) - ($step * 0.02) + (rand(-10, 10) / 100);
            
            // 更新进度
            $db->update('training_tasks',
                ['progress' => $progress, 'message' => "Epoch $epoch/$epochs - Step $step/$steps (Loss: " . round($loss, 4) . ")"],
                'id = :id',
                ['id' => $taskId]
            );
            
            if ($step % 5 === 0) {
                trainingLog("  Epoch $epoch - Step $step/$steps, Loss: " . round($loss, 4), 'info', $taskId);
            }
        }
        
        trainingLog("Epoch $epoch/$epochs 完成", 'success', $taskId);
    }
    
    // 训练完成
    trainingLog("========================================", 'success', $taskId);
    trainingLog("训练完成！", 'success', $taskId);
    trainingLog("========================================", 'success', $taskId);
    
    $db->update('training_tasks',
        ['status' => 'completed', 'progress' => 100, 'completed_at' => date('Y-m-d H:i:s'), 'message' => '训练完成'],
        'id = :id',
        ['id' => $taskId]
    );
    
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    trainingLog("训练失败: $errorMsg", 'error', $taskId);
    trainingLog("错误堆栈: " . $e->getTraceAsString(), 'error', $taskId);
    
    if ($db) {
        $db->update('training_tasks',
            ['status' => 'failed', 'message' => $errorMsg],
            'id = :id',
            ['id' => $taskId]
        );
    }
    exit("训练失败: $errorMsg\n");
}
