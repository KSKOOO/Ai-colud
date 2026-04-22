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


initStorageTables($db);

switch ($action) {
    case 'getOverview':
        getStorageOverview($db);
        break;
    case 'getStorageList':
        getStorageList($db);
        break;
    case 'getStorage':
        getStorage($db);
        break;
    case 'addStorage':
        addStorage($db);
        break;
    case 'updateStorage':
        updateStorage($db);
        break;
    case 'deleteStorage':
        deleteStorage($db);
        break;
    case 'setDefaultStorage':
        setDefaultStorage($db);
        break;
    case 'testStorage':
        testStorageConnection();
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => '未知操作']);
}


function initStorageTables($db) {

    $db->getPdo()->exec("CREATE TABLE IF NOT EXISTS storage_configs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(100) NOT NULL,
        type VARCHAR(20) NOT NULL, -- local, s3, oss, cos, minio, custom
        config TEXT NOT NULL, -- JSON配置
        is_default INTEGER DEFAULT 0,
        status VARCHAR(20) DEFAULT 'active',
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");


    $defaultExists = $db->fetch("SELECT id FROM storage_configs WHERE is_default = 1 LIMIT 1");
    if (!$defaultExists) {
        $defaultPath = __DIR__ . '/../storage/models/';
        if (!is_dir($defaultPath)) {
            mkdir($defaultPath, 0755, true);
        }
        
        $db->insert('storage_configs', [
            'name' => '本地存储',
            'type' => 'local',
            'config' => json_encode(['path' => $defaultPath]),
            'is_default' => 1,
            'status' => 'active',
            'notes' => '系统默认本地存储'
        ]);
    }
}


function getStorageOverview($db) {
    try {

        $defaultStorage = $db->fetch(
            "SELECT * FROM storage_configs WHERE is_default = 1 AND status = 'active' LIMIT 1"
        );
        
        $typeNames = [
            'local' => '本地存储',
            's3' => 'Amazon S3',
            'oss' => '阿里云OSS',
            'cos' => '腾讯云COS',
            'minio' => 'MinIO',
            'custom' => '自定义存储',
            'ipsan' => 'IP-SAN'
        ];
        
        $overview = [
            'current_type' => $typeNames[$defaultStorage['type']] ?? '本地存储',
            'status' => '运行正常',
            'used_space' => '0 MB',
            'total_space' => '0 MB',
            'location' => '本地'
        ];
        

        if ($defaultStorage && $defaultStorage['type'] === 'local') {
            $config = json_decode($defaultStorage['config'], true);
            $path = $config['path'] ?? __DIR__ . '/../storage/models/';
            
            if (is_dir($path)) {
                $free = disk_free_space($path);
                $total = disk_total_space($path);
                $used = $total - $free;
                
                $overview['used_space'] = formatBytes($used);
                $overview['total_space'] = formatBytes($total);
                $overview['location'] = $path;
            }
        } else if ($defaultStorage) {
            $overview['location'] = '云端';
            $overview['used_space'] = '-';
            $overview['total_space'] = '-';
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => $overview
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function getStorageList($db) {
    try {
        $storages = $db->fetchAll(
            "SELECT id, name, type, config, is_default, status, notes, created_at 
             FROM storage_configs 
             WHERE status = 'active' 
             ORDER BY is_default DESC, created_at ASC"
        );
        
        echo json_encode([
            'status' => 'success',
            'storages' => $storages
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function getStorage($db) {
    $id = intval($_GET['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => '无效的存储ID']);
        return;
    }
    
    try {
        $storage = $db->fetch(
            "SELECT * FROM storage_configs WHERE id = :id AND status = 'active'",
            ['id' => $id]
        );
        
        if (!$storage) {
            echo json_encode(['status' => 'error', 'message' => '存储不存在']);
            return;
        }
        

        $config = json_decode($storage['config'], true);
        if (!empty($config['secret_key'])) {
            $config['secret_key'] = '********';
        }
        if (!empty($config['access_key'])) {
            $config['access_key'] = substr($config['access_key'], 0, 4) . '********';
        }
        $storage['config'] = json_encode($config);
        
        echo json_encode(['status' => 'success', 'storage' => $storage]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function addStorage($db) {
    $name = $_POST['name'] ?? '';
    $type = $_POST['type'] ?? 'local';
    $isDefault = ($_POST['is_default'] === 'true' || $_POST['is_default'] === true) ? 1 : 0;
    $config = $_POST['config'] ?? '{}';
    $notes = $_POST['notes'] ?? '';
    
    if (empty($name)) {
        echo json_encode(['status' => 'error', 'message' => '请输入存储名称']);
        return;
    }
    
    try {

        $configArray = json_decode($config, true);
        if ($type === 'local') {
            if (empty($configArray['path'])) {
                echo json_encode(['status' => 'error', 'message' => '请输入存储路径']);
                return;
            }

            if (!is_dir($configArray['path'])) {
                mkdir($configArray['path'], 0755, true);
            }
        } else if ($type === 'ipsan') {
            if (empty($configArray['ip']) || empty($configArray['iqn'])) {
                echo json_encode(['status' => 'error', 'message' => '请填写 IP-SAN 的 IP 地址和 IQN']);
                return;
            }

            if (!filter_var($configArray['ip'], FILTER_VALIDATE_IP)) {
                echo json_encode(['status' => 'error', 'message' => 'IP 地址格式不正确']);
                return;
            }

            if (!empty($configArray['mount_path']) && !is_dir($configArray['mount_path'])) {
                mkdir($configArray['mount_path'], 0755, true);
            }
        } else {
            if (empty($configArray['endpoint']) || empty($configArray['bucket'])) {
                echo json_encode(['status' => 'error', 'message' => '请填写完整的云存储配置']);
                return;
            }
        }
        

        if ($isDefault) {
            $db->exec("UPDATE storage_configs SET is_default = 0 WHERE is_default = 1");
        }
        
        $id = $db->insert('storage_configs', [
            'name' => $name,
            'type' => $type,
            'config' => $config,
            'is_default' => $isDefault,
            'notes' => $notes
        ]);
        
        echo json_encode([
            'status' => 'success',
            'message' => '存储已添加',
            'id' => $id
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function updateStorage($db) {
    $id = intval($_POST['id'] ?? 0);
    $name = $_POST['name'] ?? '';
    $type = $_POST['type'] ?? 'local';
    $isDefault = ($_POST['is_default'] === 'true' || $_POST['is_default'] === true) ? 1 : 0;
    $config = $_POST['config'] ?? '{}';
    $notes = $_POST['notes'] ?? '';
    
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => '无效的存储ID']);
        return;
    }
    
    if (empty($name)) {
        echo json_encode(['status' => 'error', 'message' => '请输入存储名称']);
        return;
    }
    
    try {

        $oldStorage = $db->fetch("SELECT config FROM storage_configs WHERE id = :id", ['id' => $id]);
        if ($oldStorage) {
            $oldConfig = json_decode($oldStorage['config'], true);
            $newConfig = json_decode($config, true);
            

            if (isset($newConfig['secret_key']) && strpos($newConfig['secret_key'], '*') !== false) {
                $newConfig['secret_key'] = $oldConfig['secret_key'] ?? '';
            }
            if (isset($newConfig['access_key']) && strpos($newConfig['access_key'], '*') !== false) {
                $newConfig['access_key'] = $oldConfig['access_key'] ?? '';
            }
            $config = json_encode($newConfig);
        }
        

        if ($isDefault) {
            $db->exec("UPDATE storage_configs SET is_default = 0 WHERE is_default = 1 AND id != :id", ['id' => $id]);
        }
        
        $db->update('storage_configs',
            [
                'name' => $name,
                'type' => $type,
                'config' => $config,
                'is_default' => $isDefault,
                'notes' => $notes,
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'id = :id',
            ['id' => $id]
        );
        
        echo json_encode(['status' => 'success', 'message' => '存储已更新']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function deleteStorage($db) {
    $id = intval($_POST['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => '无效的存储ID']);
        return;
    }
    
    try {

        $storage = $db->fetch("SELECT is_default FROM storage_configs WHERE id = :id", ['id' => $id]);
        if ($storage && $storage['is_default']) {
            echo json_encode(['status' => 'error', 'message' => '不能删除默认存储，请先设置其他存储为默认']);
            return;
        }
        

        $db->update('storage_configs',
            ['status' => 'deleted', 'updated_at' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $id]
        );
        
        echo json_encode(['status' => 'success', 'message' => '存储已删除']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function setDefaultStorage($db) {
    $id = intval($_POST['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => '无效的存储ID']);
        return;
    }
    
    try {

        $db->exec("UPDATE storage_configs SET is_default = 0 WHERE is_default = 1");
        

        $db->update('storage_configs',
            ['is_default' => 1, 'updated_at' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $id]
        );
        
        echo json_encode(['status' => 'success', 'message' => '已设为默认存储']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


function testStorageConnection() {
    $type = $_POST['type'] ?? 'local';
    $config = json_decode($_POST['config'] ?? '{}', true);
    
    try {
        if ($type === 'local') {
            $path = $config['path'] ?? '';
            if (empty($path)) {
                echo json_encode(['status' => 'error', 'message' => '请输入存储路径']);
                return;
            }
            
            if (!is_dir($path)) {

                if (!mkdir($path, 0755, true)) {
                    echo json_encode(['status' => 'error', 'message' => '无法创建目录，请检查权限']);
                    return;
                }
            }
            
            if (!is_writable($path)) {
                echo json_encode(['status' => 'error', 'message' => '目录不可写，请检查权限']);
                return;
            }
            

            $testFile = $path . '/.test_' . time();
            if (@file_put_contents($testFile, 'test')) {
                @unlink($testFile);
                $free = disk_free_space($path);
                echo json_encode([
                    'status' => 'success',
                    'message' => '连接成功，可用空间: ' . formatBytes($free)
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => '无法写入测试文件']);
            }
        } else if ($type === 'ipsan') {

            $ip = $config['ip'] ?? '';
            $port = $config['port'] ?? 3260;
            $iqn = $config['iqn'] ?? '';
            
            if (empty($ip) || empty($iqn)) {
                echo json_encode(['status' => 'error', 'message' => '请填写 IP 地址和 IQN']);
                return;
            }
            

            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                echo json_encode(['status' => 'error', 'message' => 'IP 地址格式不正确']);
                return;
            }
            

            $result = testIscsiConnection($ip, $port, $iqn);
            if ($result['success']) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'IP-SAN 连接测试成功。' . $result['message']
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'IP-SAN 连接测试失败: ' . $result['message']
                ]);
            }
        } else {

            if (empty($config['endpoint']) || empty($config['bucket'])) {
                echo json_encode(['status' => 'error', 'message' => '请填写完整的存储配置']);
                return;
            }
            

            echo json_encode([
                'status' => 'success',
                'message' => '连接测试通过（模拟）'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => '测试失败: ' . $e->getMessage()]);
    }
}


function testIscsiConnection($ip, $port, $iqn) {

    $iscsiadmPath = shell_exec('which iscsiadm');
    if (empty($iscsiadmPath)) {
        return ['success' => false, 'message' => '系统未安装 iscsiadm 工具，请先安装 iSCSI initiator'];
    }
    

    $socket = @fsockopen($ip, $port, $errno, $errstr, 5);
    if (!$socket) {
        return ['success' => false, 'message' => "无法连接到 {$ip}:{$port}，请检查网络或防火墙设置"];
    }
    fclose($socket);
    

    $discoveryCmd = "iscsiadm -m discovery -t st -p $ip:$port 2>&1";
    exec($discoveryCmd, $discoveryOutput, $discoveryReturnCode);
    
    if ($discoveryReturnCode !== 0) {
        return ['success' => false, 'message' => 'iSCSI Discovery 失败: ' . implode("\n", $discoveryOutput)];
    }
    

    $found = false;
    foreach ($discoveryOutput as $line) {
        if (strpos($line, $iqn) !== false) {
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $availableTargets = implode(", ", array_filter($discoveryOutput));
        return ['success' => false, 'message' => "未找到指定的 IQN，可用的 Targets: $availableTargets"];
    }
    
    return ['success' => true, 'message' => 'iSCSI Target 发现成功，可以正常连接'];
}


function formatBytes($bytes) {
    if ($bytes === 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $unitIndex = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $unitIndex), 2) . ' ' . $units[$unitIndex];
}
