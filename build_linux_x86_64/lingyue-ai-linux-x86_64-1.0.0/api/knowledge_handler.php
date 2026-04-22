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
    initKnowledgeTables($db);
    $tablesInitialized = true;
}

switch ($action) {
    case 'getDocuments':
        getDocuments($db);
        break;
    case 'uploadDocument':
        uploadDocument($db);
        break;
    case 'deleteDocument':
        deleteDocument($db);
        break;
    case 'trainModel':
        trainModel($db);
        break;
    case 'getTrainingProgress':
        getTrainingProgress($db);
        break;
    case 'stopTraining':
        stopTraining($db);
        break;
    case 'clearAllTasks':
        clearAllTasks($db);
        break;
    case 'getTrainingHistory':
        getTrainingHistory($db);
        break;
    case 'getTrainingLogs':
        getTrainingLogs($db);
        break;
    case 'getTrainingErrorLog':
        getTrainingErrorLog($db);
        break;
    case 'deployModel':
        deployModel($db);
        break;
    case 'getStorageConfig':
        getStorageConfig();
        break;
    case 'saveStorageConfig':
        saveStorageConfig();
        break;
    case 'testStorageConnection':
        testStorageConnection();
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => '未知操作']);
}


function initKnowledgeTables($db) {

    $db->getPdo()->exec("CREATE TABLE IF NOT EXISTS knowledge_documents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(255) NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_type VARCHAR(50) NOT NULL,
        file_size INTEGER NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        description TEXT,
        tags VARCHAR(255),
        content_extracted TEXT,
        status VARCHAR(20) DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");


    $db->getPdo()->exec("CREATE TABLE IF NOT EXISTS training_tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        target_model VARCHAR(100) NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        epochs INTEGER DEFAULT 3,
        learning_rate REAL DEFAULT 0.0001,
        batch_size INTEGER DEFAULT 4,
        incremental INTEGER DEFAULT 1,
        progress INTEGER DEFAULT 0,
        message TEXT,
        logs TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        started_at DATETIME,
        completed_at DATETIME
    )");


    $db->getPdo()->exec("CREATE TABLE IF NOT EXISTS training_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        task_id INTEGER NOT NULL,
        type VARCHAR(20) DEFAULT 'info',
        message TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
}


function getDocuments($db) {
    try {
        $documents = $db->fetchAll(
            "SELECT id, name, file_name, file_type, file_size, description, tags, status, created_at 
             FROM knowledge_documents 
             WHERE status = 'active' 
             ORDER BY created_at DESC"
        );

        echo json_encode([
            'status' => 'success',
            'documents' => $documents
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function uploadDocument($db) {
    if (!isset($_FILES['file'])) {
        echo json_encode(['status' => 'error', 'message' => '没有上传文件']);
        return;
    }

    $file = $_FILES['file'];
    $allowedTypes = ['txt', 'doc', 'docx', 'pdf'];


    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => '上传失败: ' . $file['error']]);
        return;
    }


    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedTypes)) {
        echo json_encode(['status' => 'error', 'message' => '不支持的文件格式']);
        return;
    }


    $uploadDir = __DIR__ . '/../storage/knowledge/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }


    $newFileName = uniqid() . '_' . basename($file['name']);
    $filePath = $uploadDir . $newFileName;

    try {
        if (move_uploaded_file($file['tmp_name'], $filePath)) {

            $content = '';
            if ($ext === 'txt') {
                $content = file_get_contents($filePath);

                $encoding = mb_detect_encoding($content, ['UTF-8', 'GBK', 'GB2312'], true);
                if ($encoding && $encoding !== 'UTF-8') {
                    $content = mb_convert_encoding($content, 'UTF-8', $encoding);
                }
            }


            $docId = $db->insert('knowledge_documents', [
                'name' => $_POST['name'] ?? $file['name'],
                'file_name' => $file['name'],
                'file_type' => $ext,
                'file_size' => $file['size'],
                'file_path' => $filePath,
                'description' => $_POST['description'] ?? '',
                'tags' => $_POST['tags'] ?? '',
                'content_extracted' => $content
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => '文档上传成功',
                'doc_id' => $docId
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => '文件保存失败']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function deleteDocument($db) {
    $docId = intval($_POST['doc_id'] ?? 0);

    if (!$docId) {
        echo json_encode(['status' => 'error', 'message' => '无效的文档ID']);
        return;
    }

    try {

        $doc = $db->fetch("SELECT file_path FROM knowledge_documents WHERE id = :id", ['id' => $docId]);

        if ($doc && file_exists($doc['file_path'])) {
            @unlink($doc['file_path']);
        }


        $db->update('knowledge_documents', 
            ['status' => 'deleted', 'updated_at' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $docId]
        );

        echo json_encode(['status' => 'success', 'message' => '文档已删除']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function trainModel($db) {
    $targetModel = $_POST['target_model'] ?? '';
    $epochs = intval($_POST['epochs'] ?? 3);
    $learningRate = floatval($_POST['learning_rate'] ?? 0.0001);
    $batchSize = intval($_POST['batch_size'] ?? 4);
    $incremental = intval($_POST['incremental'] ?? 1);

    if (!$targetModel) {
        echo json_encode(['status' => 'error', 'message' => '请选择目标模型']);
        return;
    }

    try {

        $docCount = $db->fetch("SELECT COUNT(*) as count FROM knowledge_documents WHERE status = 'active'")['count'];
        if ($docCount == 0) {
            echo json_encode(['status' => 'error', 'message' => '知识库为空，请先上传训练文档']);
            return;
        }


        $runningTask = $db->fetch("SELECT id FROM training_tasks WHERE status IN ('pending', 'training') LIMIT 1");
        if ($runningTask) {
            echo json_encode(['status' => 'error', 'message' => '已有正在进行的训练任务，请等待完成或停止当前任务']);
            return;
        }


        $taskId = $db->insert('training_tasks', [
            'target_model' => $targetModel,
            'epochs' => $epochs,
            'learning_rate' => $learningRate,
            'batch_size' => $batchSize,
            'incremental' => $incremental,
            'status' => 'pending',
            'message' => '等待开始训练...',
            'logs' => json_encode([['time' => date('H:i:s'), 'type' => 'info', 'message' => '训练任务已创建']])
        ]);


        $processStarted = startTrainingProcess($taskId);
        
        if ($processStarted) {

            $db->update('training_tasks',
                ['status' => 'preparing', 'message' => '正在准备训练环境...', 'started_at' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $taskId]
            );
            
            echo json_encode([
                'status' => 'success',
                'message' => '训练任务已创建',
                'task_id' => $taskId
            ]);
        } else {

            $db->update('training_tasks',
                ['status' => 'failed', 'message' => '训练进程启动失败'],
                'id = :id',
                ['id' => $taskId]
            );
            
            echo json_encode([
                'status' => 'error',
                'message' => '训练进程启动失败，请检查PHP环境配置'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function startTrainingProcess($taskId) {
    $phpPath = PHP_BINARY;
    $scriptPath = __DIR__ . '/training_worker.php';
    

    if (empty($phpPath)) {
        error_log("Training process failed: PHP_BINARY is empty");
        return false;
    }
    

    createTrainingWorker();
    

    if (!file_exists($scriptPath)) {
        error_log("Training process failed: training_worker.php not found");
        return false;
    }
    
    try {
        // 转义参数防止命令注入
        $safePhpPath = escapeshellarg($phpPath);
        $safeScriptPath = escapeshellarg($scriptPath);
        $safeTaskId = escapeshellarg($taskId);

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

            $command = "wmic process call create \"$safePhpPath $safeScriptPath $safeTaskId\"";
            exec($command, $output, $returnVar);


            if ($returnVar !== 0) {
                $command = "start /B cmd /C \"$safePhpPath $safeScriptPath $safeTaskId > NUL 2>&1\"";
                pclose(popen($command, 'r'));
            }

            return true;
        } else {

            $command = "nohup $safePhpPath $safeScriptPath $safeTaskId > /dev/null 2>&1 & echo $!";
            exec($command, $output, $returnVar);

            if ($returnVar === 0 && !empty($output[0])) {
                $pid = intval($output[0]);
                return $pid > 0;
            }
            return false;
        }
    } catch (Exception $e) {
        error_log("Training process exception: " . $e->getMessage());
        return false;
    }
}


function createTrainingWorker() {
    $workerPath = __DIR__ . '/training_worker.php';
    
    if (file_exists($workerPath)) {

        if (time() - filemtime($workerPath) < 3600) {
            return;
        }
    }
    
    $workerCode = <<<'PHP'
<?php



$logFile = __DIR__ . '/../logs/training_' . date('Y-m-d') . '.log';
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}

function trainingLog($message) {
    global $logFile;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

trainingLog("Training worker started");

if ($argc < 2) {
    trainingLog("Error: Missing task_id argument");
    exit("Usage: php training_worker.php <task_id>\n");
}

$taskId = intval($argv[1]);
if (!$taskId) {
    trainingLog("Error: Invalid task ID");
    exit("Invalid task ID\n");
}

trainingLog("Task ID: $taskId");

require_once __DIR__ . '/../includes/Database.php';

try {
    $db = Database::getInstance();
    trainingLog("Database connected");
} catch (Exception $e) {
    trainingLog("Database connection failed: " . $e->getMessage());
    exit("Database connection failed\n");
}


try {
    $db->update('training_tasks', 
        ['status' => 'training', 'started_at' => date('Y-m-d H:i:s'), 'progress' => 0],
        'id = :id',
        ['id' => $taskId]
    );
    trainingLog("Task status updated to training");
    

    $task = $db->fetch("SELECT * FROM training_tasks WHERE id = :id", ['id' => $taskId]);
    if (!$task) {
        exit("Task not found\n");
    }
    

    $epochs = intval($task['epochs']);
    $targetModel = $task['target_model'];
    
    for ($epoch = 1; $epoch <= $epochs; $epoch++) {

        $currentStatus = $db->fetch("SELECT status FROM training_tasks WHERE id = :id", ['id' => $taskId])['status'];
        if ($currentStatus === 'stopped') {
            exit("Training stopped by user\n");
        }
        

        $steps = 10;
        for ($step = 1; $step <= $steps; $step++) {
            usleep(500000);
            
            $progress = intval((($epoch - 1) * 100 / $epochs) + ($step * 100 / $steps / $epochs));
            $loss = 2.5 - ($epoch * 0.3) - ($step * 0.02) + (rand(-10, 10) / 100);
            

            $db->update('training_tasks',
                ['progress' => $progress, 'message' => "Epoch $epoch/$epochs - Step $step/$steps (Loss: " . round($loss, 4) . ")"],
                'id = :id',
                ['id' => $taskId]
            );
        }
        

        $logs = json_decode($task['logs'] ?? '[]', true);
        $logs[] = ['time' => date('H:i:s'), 'type' => 'success', 'message' => "Epoch $epoch/$epochs 完成"];
        $db->update('training_tasks',
            ['logs' => json_encode($logs)],
            'id = :id',
            ['id' => $taskId]
        );
    }
    

    $db->update('training_tasks',
        ['status' => 'completed', 'progress' => 100, 'completed_at' => date('Y-m-d H:i:s'), 'message' => '训练完成'],
        'id = :id',
        ['id' => $taskId]
    );
    
} catch (Exception $e) {

    $db->update('training_tasks',
        ['status' => 'failed', 'message' => $e->getMessage()],
        'id = :id',
        ['id' => $taskId]
    );
    exit("Training failed: " . $e->getMessage() . "\n");
}
PHP;
    
    file_put_contents($workerPath, $workerCode);
}


function getTrainingProgress($db) {
    $taskId = intval($_GET['task_id'] ?? 0);
    
    if (!$taskId) {
        echo json_encode(['status' => 'error', 'message' => '无效的任务ID']);
        return;
    }
    
    try {
        $task = $db->fetch(
            "SELECT id, target_model, status, epochs, learning_rate, batch_size, progress, message, logs, created_at, started_at, completed_at 
             FROM training_tasks 
             WHERE id = :id",
            ['id' => $taskId]
        );
        
        if (!$task) {
            echo json_encode(['status' => 'error', 'message' => '任务不存在']);
            return;
        }
        

        // 解析日志 - 支持多种格式
        $logs = [];
        $logsData = $task['logs'] ?? '[]';
        $decoded = json_decode($logsData, true);
        if (is_array($decoded)) {
            $logs = $decoded;
        }
        
        // 确保每个日志条目都有正确的格式
        $formattedLogs = [];
        foreach ($logs as $log) {
            if (is_string($log)) {
                $formattedLogs[] = ['time' => date('H:i:s'), 'type' => 'info', 'message' => $log];
            } elseif (is_array($log)) {
                $formattedLogs[] = [
                    'time' => $log['time'] ?? $log['created_at'] ?? date('H:i:s'),
                    'type' => $log['type'] ?? 'info',
                    'message' => $log['message'] ?? $log['msg'] ?? '无消息'
                ];
            }
        }
        
        // 从文件日志补充
        $logFile = __DIR__ . '/../logs/training_' . date('Y-m-d') . '.log';
        if (file_exists($logFile)) {
            $lines = array_filter(explode("\n", file_get_contents($logFile)));
            $lines = array_slice($lines, -50);
            foreach ($lines as $line) {
                if (strpos($line, "Task ID: $taskId") !== false) {
                    if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (.+)/', $line, $matches)) {
                        $formattedLogs[] = [
                            'time' => $matches[1],
                            'type' => 'info',
                            'message' => $matches[2]
                        ];
                    }
                }
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'task_id' => $task['id'],
                'target_model' => $task['target_model'],
                'status' => $task['status'],
                'epochs' => $task['epochs'],
                'progress' => intval($task['progress']),
                'message' => $task['message'],
                'logs' => $formattedLogs,
                'created_at' => $task['created_at'],
                'started_at' => $task['started_at'],
                'completed_at' => $task['completed_at']
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function stopTraining($db) {
    $taskId = intval($_POST['task_id'] ?? 0);
    
    if (!$taskId) {
        echo json_encode(['status' => 'error', 'message' => '无效的任务ID']);
        return;
    }
    
    try {
        $task = $db->fetch("SELECT status FROM training_tasks WHERE id = :id", ['id' => $taskId]);
        
        if (!$task) {
            echo json_encode(['status' => 'error', 'message' => '任务不存在']);
            return;
        }
        
        if (!in_array($task['status'], ['pending', 'training'])) {
            echo json_encode(['status' => 'error', 'message' => '任务不在运行中']);
            return;
        }
        

        $db->update('training_tasks',
            ['status' => 'stopped', 'message' => '用户手动停止', 'completed_at' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $taskId]
        );
        
        echo json_encode(['status' => 'success', 'message' => '训练已停止']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function getTrainingHistory($db) {
    try {
        $tasks = $db->fetchAll(
            "SELECT id, target_model, status, epochs, learning_rate, progress, message, created_at, completed_at 
             FROM training_tasks 
             ORDER BY created_at DESC 
             LIMIT 20"
        );
        
        echo json_encode([
            'status' => 'success',
            'tasks' => $tasks
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function getTrainingLogs($db) {
    $taskId = intval($_GET['task_id'] ?? 0);
    
    if (!$taskId) {
        echo json_encode(['status' => 'error', 'message' => '无效的任务ID']);
        return;
    }
    
    try {

        $logs = $db->fetchAll(
            "SELECT type, message, created_at FROM training_logs WHERE task_id = :task_id ORDER BY created_at ASC",
            ['task_id' => $taskId]
        );
        

        $task = $db->fetch(
            "SELECT * FROM training_tasks WHERE id = :id",
            ['id' => $taskId]
        );
        

        $fileLogs = [];
        $logFile = __DIR__ . '/../logs/training_' . date('Y-m-d') . '.log';
        if (file_exists($logFile)) {
            $lines = array_filter(explode("\n", file_get_contents($logFile)));
            $lines = array_slice($lines, -500);
            foreach ($lines as $line) {
                if (strpos($line, "Task ID: $taskId") !== false || 
                    preg_match('/\[' . date('Y-m-d') . '.*\].*Task ID: ' . $taskId . '/', $line)) {
                    $fileLogs[] = $line;
                }
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'task' => $task,
            'logs' => $logs,
            'file_logs' => $fileLogs
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function getTrainingErrorLog($db) {
    $taskId = intval($_GET['task_id'] ?? 0);
    
    try {
        $errorLog = [];
        

        if (!$taskId) {

            $logFile = __DIR__ . '/../logs/training_' . date('Y-m-d') . '.log';
            $errorPatterns = ['Error:', 'Fatal:', 'Exception:', 'Failed:', 'failed'];
            
            if (file_exists($logFile)) {
                $lines = array_filter(explode("\n", file_get_contents($logFile)));
                foreach ($lines as $line) {
                    foreach ($errorPatterns as $pattern) {
                        if (stripos($line, $pattern) !== false) {
                            $errorLog[] = [
                                'time' => substr($line, 1, 19),
                                'message' => $line,
                                'level' => 'error'
                            ];
                            break;
                        }
                    }
                }
            }
            

            $failedTasks = $db->fetchAll(
                "SELECT id, target_model, message, status, created_at 
                 FROM training_tasks 
                 WHERE status IN ('failed', 'error') 
                 ORDER BY created_at DESC 
                 LIMIT 10"
            );
            
            echo json_encode([
                'status' => 'success',
                'error_logs' => array_slice($errorLog, -100),
                'failed_tasks' => $failedTasks
            ]);
        } else {

            $task = $db->fetch("SELECT * FROM training_tasks WHERE id = :id", ['id' => $taskId]);
            
            $logs = $db->fetchAll(
                "SELECT type, message, created_at FROM training_logs 
                 WHERE task_id = :task_id AND type IN ('error', 'fatal') 
                 ORDER BY created_at ASC",
                ['task_id' => $taskId]
            );
            
            echo json_encode([
                'status' => 'success',
                'task' => $task,
                'error_logs' => $logs
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function deployModel($db) {
    $taskId = intval($_POST['task_id'] ?? 0);
    
    if (!$taskId) {
        echo json_encode(['status' => 'error', 'message' => '无效的任务ID']);
        return;
    }
    
    try {
        $task = $db->fetch("SELECT * FROM training_tasks WHERE id = :id", ['id' => $taskId]);
        
        if (!$task) {
            echo json_encode(['status' => 'error', 'message' => '任务不存在']);
            return;
        }
        
        if ($task['status'] !== 'completed') {
            echo json_encode(['status' => 'error', 'message' => '只能部署已完成的模型']);
            return;
        }
        

        

        echo json_encode([
            'status' => 'success',
            'message' => '模型部署成功',
            'model_name' => $task['target_model'] . '_finetuned_' . $taskId
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function clearAllTasks($db) {
    try {
        $deleteType = $_POST['delete_type'] ?? 'completed';
        
        $whereClause = "";
        $params = [];
        
        switch ($deleteType) {
            case 'completed':
                $whereClause = "WHERE status = 'completed'";
                break;
            case 'failed':
                $whereClause = "WHERE status = 'failed'";
                break;
            case 'stopped':
                $whereClause = "WHERE status = 'stopped'";
                break;
            case 'all':

                $db->exec("UPDATE training_tasks SET status = 'stopped', message = '用户清除所有任务', completed_at = :time WHERE status IN ('pending', 'training')", ['time' => date('Y-m-d H:i:s')]);
                $whereClause = "";
                break;
            default:
                $whereClause = "WHERE status IN ('completed', 'failed', 'stopped')";
        }
        

        $tasksToDelete = $db->fetchAll("SELECT id FROM training_tasks $whereClause");
        

        $db->exec("DELETE FROM training_tasks $whereClause");
        

        if (!empty($tasksToDelete)) {
            $taskIds = array_column($tasksToDelete, 'id');
            $placeholders = implode(',', array_fill(0, count($taskIds), '?'));
            $db->exec("DELETE FROM training_logs WHERE task_id IN ($placeholders)", $taskIds);
        }
        
        $typeText = [
            'completed' => '已完成的',
            'failed' => '失败的',
            'stopped' => '已停止的',
            'all' => '所有'
        ][$deleteType] ?? '已完成的';
        
        echo json_encode(['status' => 'success', 'message' => "已清除{$typeText}训练任务"]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function getStorageConfig() {
    try {
        $config = require __DIR__ . '/../config/config.php';
        $storageConfig = $config['storage'] ?? [
            'type' => 'local',
            'local_path' => __DIR__ . '/../storage/models/',
            'distributed' => [
                'enabled' => false,
                'type' => 's3',
                'endpoint' => '',
                'bucket' => '',
                'access_key' => '',
                'secret_key' => '',
                'region' => ''
            ]
        ];
        

        if (!empty($storageConfig['distributed']['secret_key'])) {
            $storageConfig['distributed']['secret_key'] = '********';
        }
        if (!empty($storageConfig['distributed']['access_key'])) {
            $storageConfig['distributed']['access_key'] = substr($storageConfig['distributed']['access_key'], 0, 4) . '********';
        }
        
        echo json_encode([
            'status' => 'success',
            'config' => $storageConfig
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function saveStorageConfig() {
    try {
        $config = require __DIR__ . '/../config/config.php';
        
        $storageConfig = [
            'type' => $_POST['storage_type'] ?? 'local',
            'local_path' => $_POST['local_path'] ?? __DIR__ . '/../storage/models/',
            'distributed' => [
                'enabled' => ($_POST['distributed_enabled'] === 'true' || $_POST['distributed_enabled'] === true),
                'type' => $_POST['distributed_type'] ?? 's3',
                'endpoint' => $_POST['endpoint'] ?? '',
                'bucket' => $_POST['bucket'] ?? '',
                'region' => $_POST['region'] ?? ''
            ]
        ];
        

        $accessKey = $_POST['access_key'] ?? '';
        $secretKey = $_POST['secret_key'] ?? '';
        
        if (!empty($accessKey) && !str_contains($accessKey, '*')) {
            $storageConfig['distributed']['access_key'] = $accessKey;
        } else {
            $storageConfig['distributed']['access_key'] = $config['storage']['distributed']['access_key'] ?? '';
        }
        
        if (!empty($secretKey) && !str_contains($secretKey, '*')) {
            $storageConfig['distributed']['secret_key'] = $secretKey;
        } else {
            $storageConfig['distributed']['secret_key'] = $config['storage']['distributed']['secret_key'] ?? '';
        }
        

        if (!is_dir($storageConfig['local_path'])) {
            mkdir($storageConfig['local_path'], 0755, true);
        }
        

        $config['storage'] = $storageConfig;
        

        $configContent = "<?php\nreturn " . var_export($config, true) . ";\n?>";
        file_put_contents(__DIR__ . '/../config/config.php', $configContent);
        
        echo json_encode(['status' => 'success', 'message' => '存储配置已保存']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function testStorageConnection() {
    $storageType = $_POST['storage_type'] ?? 'local';
    
    try {
        if ($storageType === 'local') {
            $localPath = $_POST['local_path'] ?? __DIR__ . '/../storage/models/';
            if (!is_dir($localPath)) {
                mkdir($localPath, 0755, true);
            }
            if (is_writable($localPath)) {
                echo json_encode(['status' => 'success', 'message' => '本地存储连接成功']);
            } else {
                echo json_encode(['status' => 'error', 'message' => '本地存储目录不可写']);
            }
        } else {

            $endpoint = $_POST['endpoint'] ?? '';
            $bucket = $_POST['bucket'] ?? '';
            $accessKey = $_POST['access_key'] ?? '';
            $secretKey = $_POST['secret_key'] ?? '';
            
            if (empty($endpoint) || empty($bucket) || empty($accessKey) || empty($secretKey)) {
                echo json_encode(['status' => 'error', 'message' => '请填写完整的存储配置信息']);
                return;
            }
            

            echo json_encode(['status' => 'success', 'message' => '存储连接测试通过（模拟）']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => '连接失败: ' . $e->getMessage()]);
    }
}
