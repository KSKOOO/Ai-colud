<?php
/**
 * FFmpeg 自动下载和安装脚本
 * 运行: php bin/install_ffmpeg.php
 */

$binDir = __DIR__;
$ffmpegDir = $binDir . '/ffmpeg';
$zipFile = $ffmpegDir . '/ffmpeg.zip';

// FFmpeg 下载地址 (gyan.dev 提供 Windows 构建版本)
$downloadUrl = 'https://www.gyan.dev/ffmpeg/builds/ffmpeg-release-essentials.zip';

echo "========================================\n";
echo "  FFmpeg 自动安装脚本\n";
echo "========================================\n\n";

// 创建目录
if (!is_dir($ffmpegDir)) {
    echo "[1/5] 创建 FFmpeg 目录...\n";
    mkdir($ffmpegDir, 0755, true);
} else {
    echo "[1/5] FFmpeg 目录已存在\n";
}

// 检查是否已安装
$ffmpegExe = $ffmpegDir . '/ffmpeg.exe';
if (file_exists($ffmpegExe)) {
    echo "[2/5] FFmpeg 已安装，跳过下载\n";
    $output = [];
    exec('"' . $ffmpegExe . '" -version 2>&1', $output);
    if (!empty($output)) {
        echo "      版本: " . $output[0] . "\n";
    }
} else {
    echo "[2/5] 下载 FFmpeg (约 80MB)...\n";
    
    // 使用 cURL 下载
    $ch = curl_init($downloadUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($resource, $downloadSize, $downloaded) {
        if ($downloadSize > 0) {
            $percent = round(($downloaded / $downloadSize) * 100);
            printf("\r      进度: %d%% (%s / %s)", 
                $percent,
                formatBytes($downloaded),
                formatBytes($downloadSize)
            );
        }
    });
    curl_setopt($ch, CURLOPT_NOPROGRESS, false);
    
    $zipContent = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "\n";
    
    if ($httpCode === 200 && $zipContent) {
        // 保存 ZIP 文件
        file_put_contents($zipFile, $zipContent);
        echo "[3/5] 下载完成，正在解压...\n";
        
        // 解压 ZIP
        $zip = new ZipArchive();
        if ($zip->open($zipFile) === TRUE) {
            // 找到 ffmpeg.exe 在 ZIP 中的路径
            $ffmpegInZip = null;
            $ffprobeInZip = null;
            
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $fileName = $zip->getNameIndex($i);
                if (basename($fileName) === 'ffmpeg.exe') {
                    $ffmpegInZip = $fileName;
                }
                if (basename($fileName) === 'ffprobe.exe') {
                    $ffprobeInZip = $fileName;
                }
            }
            
            if ($ffmpegInZip) {
                // 提取文件到临时位置
                $zip->extractTo($ffmpegDir . '/temp');
                $zip->close();
                
                // 移动文件到正确位置
                $tempDir = glob($ffmpegDir . '/temp/ffmpeg*')[0] ?? null;
                if ($tempDir) {
                    $binPath = $tempDir . '/bin/';
                    if (file_exists($binPath . 'ffmpeg.exe')) {
                        copy($binPath . 'ffmpeg.exe', $ffmpegDir . '/ffmpeg.exe');
                    }
                    if (file_exists($binPath . 'ffprobe.exe')) {
                        copy($binPath . '/ffprobe.exe', $ffmpegDir . '/ffprobe.exe');
                    }
                    // 复制 DLL 文件
                    foreach (glob($binPath . '*.dll') as $dll) {
                        copy($dll, $ffmpegDir . '/' . basename($dll));
                    }
                }
                
                // 清理临时文件
                removeDirectory($ffmpegDir . '/temp');
                @unlink($zipFile);
                
                echo "[4/5] 解压完成\n";
            } else {
                echo "[错误] 无法在 ZIP 中找到 ffmpeg.exe\n";
                exit(1);
            }
        } else {
            echo "[错误] 无法解压文件\n";
            exit(1);
        }
    } else {
        echo "[错误] 下载失败 (HTTP $httpCode)\n";
        echo "      请手动下载: $downloadUrl\n";
        echo "      解压到: $ffmpegDir\n";
        exit(1);
    }
}

// 验证安装
if (file_exists($ffmpegExe)) {
    echo "[5/5] 验证安装...\n";
    $output = [];
    exec('"' . $ffmpegExe . '" -version 2>&1', $output);
    if (!empty($output) && strpos($output[0], 'ffmpeg version') !== false) {
        echo "\n✅ FFmpeg 安装成功!\n";
        echo "   路径: $ffmpegExe\n";
        echo "   " . $output[0] . "\n\n";
        
        // 创建配置文件
        $configFile = __DIR__ . '/../config/ffmpeg.php';
        $configContent = "<?php\n";
        $configContent .= "/**\n";
        $configContent .= " * FFmpeg 配置文件\n";
        $configContent .= " * 由 install_ffmpeg.php 自动生成\n";
        $configContent .= " */\n";
        $configContent .= "return [\n";
        $configContent .= "    'enabled' => true,\n";
        $configContent .= "    'ffmpeg_path' => __DIR__ . '/../bin/ffmpeg/ffmpeg.exe',\n";
        $configContent .= "    'ffprobe_path' => __DIR__ . '/../bin/ffmpeg/ffprobe.exe',\n";
        $configContent .= "];\n";
        
        file_put_contents($configFile, $configContent);
        echo "✅ 配置文件已生成: config/ffmpeg.php\n\n";
        
        echo "使用说明:\n";
        echo "1. PHP 代码会自动检测并使用此 FFmpeg\n";
        echo "2. 视频剪辑功能现在可以进行真实转码\n";
        echo "3. 如需更新 FFmpeg，删除 bin/ffmpeg 目录后重新运行此脚本\n";
    } else {
        echo "\n❌ FFmpeg 验证失败\n";
        exit(1);
    }
} else {
    echo "\n❌ FFmpeg 安装失败\n";
    exit(1);
}

// 辅助函数
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function removeDirectory($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . "/" . $object)) {
                    removeDirectory($dir . "/" . $object);
                } else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        rmdir($dir);
    }
}
