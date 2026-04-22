<?php

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']) {
    echo json_encode(['status' => 'error', 'message' => '请先登录']);
    exit;
}


$config = require __DIR__ . '/../config/config.php';


$configFile = __DIR__ . '/../config/config.php';
$modelsFile = __DIR__ . '/../config/models.php';
$logsFile = __DIR__ . '/../logs/system.log';

if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';


function saveConfig($config) {
    global $configFile;
    $content = "<?php\n// 配置文件 - 由后台管理系统生成\nreturn " . var_export($config, true) . ";\n?>";
    return file_put_contents($configFile, $content) !== false;
}


function loadModels() {
    global $modelsFile;
    if (file_exists($modelsFile)) {
        return require $modelsFile;
    }
    return ['online' => []];
}


function saveModels($models) {
    global $modelsFile;
    // 确保配置目录存在
    if (!is_dir(__DIR__ . '/../config')) {
        mkdir(__DIR__ . '/../config', 0755, true);
    }
    $content = "<?php\n// 模型配置文件\nreturn " . var_export($models, true) . ";\n?>";
    return file_put_contents($modelsFile, $content) !== false;
}

/**
 * 记录日志
 */
function writeLog($message) {
    global $logsFile;
    $time = date('Y-m-d H:i:s');
    $logEntry = "[$time] $message\n";
    file_put_contents($logsFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// 路由处理
switch ($action) {
    // 获取在线模型列表
    case 'getOnlineModels':
        $models = loadModels();
        $modelsFile = __DIR__ . '/../config/models.php';
        $isWritable = is_writable(dirname($modelsFile));
        
        echo json_encode([
            'status' => 'success',
            'models' => $models['online'] ?? [],
            'debug' => [
                'file_exists' => file_exists($modelsFile),
                'is_writable' => $isWritable,
                'file_path' => $modelsFile
            ]
        ]);
        break;

    // 添加在线模型
    case 'addOnlineModel':
        $name = $_POST['name'] ?? '';
        $id = $_POST['id'] ?? '';
        $apiType = $_POST['api_type'] ?? 'custom';

        if (empty($name) || empty($id)) {
            echo json_encode(['status' => 'error', 'message' => '模型名称和ID不能为空']);
            break;
        }

        $models = loadModels();
        
        // 检查是否已存在相同ID的模型
        foreach ($models['online'] as $model) {
            if ($model['id'] === $id) {
                echo json_encode(['status' => 'error', 'message' => '模型ID已存在']);
                break 2;
            }
        }
        
        $models['online'][] = [
            'name' => $name,
            'id' => $id,
            'api_type' => $apiType,
            'created_at' => date('Y-m-d H:i:s')
        ];

        if (saveModels($models)) {
            writeLog("添加在线模型: $name ($id)");
            echo json_encode(['status' => 'success', 'message' => '模型已添加']);
        } else {
            $error = error_get_last();
            writeLog("添加在线模型失败: $name ($id) - " . ($error['message'] ?? '未知错误'));
            echo json_encode(['status' => 'error', 'message' => '保存失败: ' . ($error['message'] ?? '请检查文件权限')]);
        }
        break;

    // 编辑在线模型
    case 'updateOnlineModel':
        $oldId = $_POST['old_id'] ?? '';
        $name = $_POST['name'] ?? '';
        $id = $_POST['id'] ?? '';
        $apiType = $_POST['api_type'] ?? 'custom';

        if (empty($oldId) || empty($name) || empty($id)) {
            echo json_encode(['status' => 'error', 'message' => '参数不能为空']);
            break;
        }

        $models = loadModels();
        $found = false;
        
        foreach ($models['online'] as &$model) {
            if ($model['id'] === $oldId) {
                // 如果ID改变了，检查新ID是否已存在
                if ($id !== $oldId) {
                    foreach ($models['online'] as $existingModel) {
                        if ($existingModel['id'] === $id) {
                            echo json_encode(['status' => 'error', 'message' => '模型ID已存在']);
                            break 2;
                        }
                    }
                }
                
                $model['name'] = $name;
                $model['id'] = $id;
                $model['api_type'] = $apiType;
                $model['updated_at'] = date('Y-m-d H:i:s');
                $found = true;
                break;
            }
        }

        if (!$found) {
            echo json_encode(['status' => 'error', 'message' => '模型不存在']);
            break;
        }

        if (saveModels($models)) {
            writeLog("更新在线模型: $name ($id)");
            echo json_encode(['status' => 'success', 'message' => '模型已更新']);
        } else {
            echo json_encode(['status' => 'error', 'message' => '保存失败']);
        }
        break;

    // 删除在线模型
    case 'deleteOnlineModel':
        $modelId = $_POST['model_id'] ?? '';

        if (empty($modelId)) {
            echo json_encode(['status' => 'error', 'message' => '模型ID不能为空']);
            break;
        }

        $models = loadModels();
        $models['online'] = array_filter($models['online'], function($m) use ($modelId) {
            return $m['id'] !== $modelId;
        });
        $models['online'] = array_values($models['online']); // 重新索引

        if (saveModels($models)) {
            writeLog("删除在线模型: $modelId");
            echo json_encode(['status' => 'success', 'message' => '模型已删除']);
        } else {
            echo json_encode(['status' => 'error', 'message' => '删除失败']);
        }
        break;

    // 保存Ollama配置
    case 'saveOllamaConfig':
        $baseUrl = $_POST['base_url'] ?? 'http://localhost:11434';
        $defaultModel = $_POST['default_model'] ?? 'llama2';
        $temperature = floatval($_POST['temperature'] ?? 0.7);
        $maxTokens = intval($_POST['max_tokens'] ?? 2048);

        $config['ollama_api'] = [
            'base_url' => $baseUrl,
            'default_model' => $defaultModel,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens
        ];

        if (saveConfig($config)) {
            writeLog("更新Ollama配置: $baseUrl, 默认模型: $defaultModel");
            echo json_encode(['status' => 'success', 'message' => '配置已保存']);
        } else {
            echo json_encode(['status' => 'error', 'message' => '保存失败']);
        }
        break;

    // 保存在线API配置
    case 'saveOnlineApiConfig':
        $apiType = $_POST['api_type'] ?? 'openai';
        $apiKey = $_POST['api_key'] ?? '';
        $baseUrl = $_POST['base_url'] ?? '';

        if (!isset($config['online_api'])) {
            $config['online_api'] = [];
        }

        $config['online_api'][$apiType] = [
            'api_key' => $apiKey,
            'base_url' => $baseUrl
        ];

        if (saveConfig($config)) {
            writeLog("更新在线API配置: $apiType");
            echo json_encode(['status' => 'success', 'message' => '配置已保存']);
        } else {
            echo json_encode(['status' => 'error', 'message' => '保存失败']);
        }
        break;

    // 保存GPUStack API配置
    case 'saveGpuStackConfig':
        $enabled = isset($_POST['enabled']) ? ($_POST['enabled'] === 'true' || $_POST['enabled'] === '1') : false;
        $baseUrl = $_POST['base_url'] ?? '';
        $apiKey = $_POST['api_key'] ?? '';
        $defaultModel = $_POST['default_model'] ?? '';
        $temperature = floatval($_POST['temperature'] ?? 0.7);
        $maxTokens = intval($_POST['max_tokens'] ?? 2048);

        $config['gpustack_api'] = [
            'enabled' => $enabled,
            'base_url' => $baseUrl,
            'api_key' => $apiKey,
            'default_model' => $defaultModel,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens
        ];

        if (saveConfig($config)) {
            $status = $enabled ? '启用' : '禁用';
            writeLog("更新GPUStack配置: {$status}, URL: $baseUrl");
            echo json_encode(['status' => 'success', 'message' => 'GPUStack配置已保存']);
        } else {
            echo json_encode(['status' => 'error', 'message' => '保存失败']);
        }
        break;

    // 测试GPUStack连接
    case 'testGpuStackConnection':
        $baseUrl = $config['gpustack_api']['base_url'] ?? '';
        $apiKey = $config['gpustack_api']['api_key'] ?? '';

        if (empty($baseUrl)) {
            echo json_encode(['status' => 'error', 'message' => 'API地址未配置']);
            break;
        }

        // 构建测试请求
        $ch = curl_init($baseUrl . '/models');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            echo json_encode(['status' => 'error', 'message' => '连接错误: ' . $error]);
        } elseif ($httpCode === 200) {
            $result = json_decode($response, true);
            $modelCount = isset($result['data']) ? count($result['data']) : 0;
            echo json_encode([
                'status' => 'success',
                'message' => '连接成功！检测到 ' . $modelCount . ' 个模型'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => '连接失败 (HTTP ' . $httpCode . ')'
            ]);
        }
        break;

    // 保存系统配置
    case 'saveSystemConfig':
        $name = $_POST['name'] ?? '巨神兵AIAPI辅助平台';
        $version = $_POST['version'] ?? '1.0.0';
        $debug = isset($_POST['debug']) ? ($_POST['debug'] === 'true' || $_POST['debug'] === true) : true;

        // 处理LOGO上传
        $logoError = '';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $logo = $_FILES['logo'];
            $allowedTypes = ['image/png', 'image/jpeg', 'image/svg+xml'];
            $maxSize = 2 * 1024 * 1024; // 2MB

            if (!in_array($logo['type'], $allowedTypes)) {
                $logoError = 'LOGO格式不支持，请使用 PNG、JPG 或 SVG 格式';
            } elseif ($logo['size'] > $maxSize) {
                $logoError = 'LOGO文件大小不能超过 2MB';
            } else {
                // 确保目录存在
                $logoDir = __DIR__ . '/../assets/images/';
                if (!is_dir($logoDir)) {
                    mkdir($logoDir, 0755, true);
                }

                // 保存LOGO（统一保存为 logo.png 或保持原格式）
                $ext = pathinfo($logo['name'], PATHINFO_EXTENSION);
                $logoPath = $logoDir . 'logo.' . $ext;

                if (move_uploaded_file($logo['tmp_name'], $logoPath)) {
                    // 如果是SVG以外的格式，同时保存一个PNG版本用于兼容
                    if ($ext !== 'svg') {
                        // 尝试转换为PNG（可选）
                    }
                } else {
                    $logoError = 'LOGO保存失败';
                }
            }
        }

        $config['app'] = [
            'name' => $name,
            'version' => $version,
            'debug' => $debug
        ];

        if (saveConfig($config)) {
            writeLog("更新系统配置: $name v$version");
            if ($logoError) {
                echo json_encode(['status' => 'warning', 'message' => '设置已保存，但' . $logoError]);
            } else {
                echo json_encode(['status' => 'success', 'message' => '设置已保存']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => '保存失败']);
        }
        break;

    // 获取日志
    case 'getLogs':
        if (file_exists($logsFile)) {
            $logs = file_get_contents($logsFile);
            // 只返回最后100行
            $lines = explode("\n", $logs);
            $lines = array_slice($lines, -100);
            $logs = implode("\n", $lines);
            echo json_encode(['status' => 'success', 'logs' => $logs]);
        } else {
            echo json_encode(['status' => 'success', 'logs' => '暂无日志']);
        }
        break;

    // 清理缓存
    case 'clearCache':
        $cacheDir = __DIR__ . '/../cache';
        if (is_dir($cacheDir)) {
            array_map('unlink', glob("$cacheDir/*"));
        }
        writeLog("清理系统缓存");
        echo json_encode(['status' => 'success', 'message' => '缓存已清理']);
        break;

    // 导出配置
    case 'exportConfig':
        $export = [
            'config' => $config,
            'models' => loadModels(),
            'exported_at' => date('Y-m-d H:i:s')
        ];
        echo json_encode([
            'status' => 'success',
            'data' => $export
        ]);
        break;

    // 导入配置
    case 'importConfig':
        $jsonData = $_POST['data'] ?? '';
        $data = json_decode($jsonData, true);

        if (!$data || !isset($data['config'])) {
            echo json_encode(['status' => 'error', 'message' => '无效的配置数据']);
            break;
        }

        if (saveConfig($data['config'])) {
            if (isset($data['models'])) {
                saveModels($data['models']);
            }
            writeLog("导入系统配置");
            echo json_encode(['status' => 'success', 'message' => '配置已导入']);
        } else {
            echo json_encode(['status' => 'error', 'message' => '导入失败']);
        }
        break;

    // ========== 用户用量统计接口 ==========
    
    // 获取系统整体用量统计
    case 'getSystemUsageStats':
        require_once __DIR__ . '/../lib/UsageTracker.php';
        $usageTracker = new UsageTracker();
        
        $startDate = $_GET['start_date'] ?? $_POST['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? $_POST['end_date'] ?? date('Y-m-d');
        
        $stats = $usageTracker->getSystemUsageStats($startDate, $endDate);
        
        echo json_encode([
            'status' => 'success',
            'data' => $stats
        ]);
        break;
    
    // 获取所有用户用量统计
    case 'getAllUsersUsage':
        require_once __DIR__ . '/../lib/UsageTracker.php';
        $usageTracker = new UsageTracker();
        
        $startDate = $_GET['start_date'] ?? $_POST['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? $_POST['end_date'] ?? date('Y-m-d');
        
        $stats = $usageTracker->getAllUsersUsageStats($startDate, $endDate);
        
        echo json_encode([
            'status' => 'success',
            'data' => $stats
        ]);
        break;
    
    // 获取单个用户用量统计
    case 'getUserUsageStats':
        require_once __DIR__ . '/../lib/UsageTracker.php';
        $usageTracker = new UsageTracker();
        
        $userId = $_GET['user_id'] ?? $_POST['user_id'] ?? null;
        $startDate = $_GET['start_date'] ?? $_POST['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? $_POST['end_date'] ?? date('Y-m-d');
        
        if (!$userId) {
            echo json_encode(['status' => 'error', 'message' => '缺少用户ID']);
            break;
        }
        
        $stats = $usageTracker->getUserUsageStats($userId, $startDate, $endDate);
        
        echo json_encode([
            'status' => 'success',
            'data' => $stats
        ]);
        break;
    
    // 获取用户详细使用记录
    case 'getUserUsageRecords':
        require_once __DIR__ . '/../lib/UsageTracker.php';
        $usageTracker = new UsageTracker();
        
        $userId = $_GET['user_id'] ?? $_POST['user_id'] ?? null;
        $page = intval($_GET['page'] ?? $_POST['page'] ?? 1);
        $pageSize = intval($_GET['page_size'] ?? $_POST['page_size'] ?? 50);
        
        if (!$userId) {
            echo json_encode(['status' => 'error', 'message' => '缺少用户ID']);
            break;
        }
        
        $records = $usageTracker->getUserUsageRecords($userId, $page, $pageSize);
        
        echo json_encode([
            'status' => 'success',
            'data' => $records
        ]);
        break;

    default:
        echo json_encode([
            'status' => 'error',
            'message' => '未知操作: ' . $action
        ]);
}
