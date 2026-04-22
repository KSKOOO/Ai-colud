<?php

header('Content-Type: application/json');

$zipFile = __DIR__ . '/../bin/ffmpeg/ffmpeg.zip';
$extractDir = __DIR__ . '/../bin/ffmpeg/';

if (!file_exists($zipFile)) {
    echo json_encode(['success' => false, 'error' => 'zip文件不存在']);
    exit;
}

if (!class_exists('ZipArchive')) {
    echo json_encode(['success' => false, 'error' => '服务器不支持ZipArchive']);
    exit;
}

$zip = new ZipArchive();
if ($zip->open($zipFile) !== TRUE) {
    echo json_encode(['success' => false, 'error' => '无法打开zip文件']);
    exit;
}


$fileCount = $zip->numFiles;
$files = [];
for ($i = 0; $i < $fileCount; $i++) {
    $files[] = $zip->getNameIndex($i);
}
$zip->close();


$ffmpegPath = null;
foreach ($files as $file) {
    if (basename($file) === 'ffmpeg.exe') {
        $ffmpegPath = $file;
        break;
    }
}

if (!$ffmpegPath) {
    echo json_encode([
        'success' => false, 
        'error' => 'zip文件中未找到ffmpeg.exe',
        'files_sample' => array_slice($files, 0, 20)
    ]);
    exit;
}


$zip = new ZipArchive();
$zip->open($zipFile);


$tempDir = $extractDir . 'extract_' . time() . '/';
mkdir($tempDir, 0755, true);


$zip->extractTo($tempDir);
$zip->close();


$binDir = null;
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getFilename() === 'ffmpeg.exe') {
        $binDir = dirname($file->getPathname());
        break;
    }
}

if (!$binDir) {

    removeDirectory($tempDir);
    echo json_encode(['success' => false, 'error' => '解压后未找到ffmpeg.exe']);
    exit;
}


$copied = 0;
$failed = [];
foreach (glob($binDir . '/*') as $f) {
    if (is_file($f)) {
        $dest = $extractDir . basename($f);
        if (copy($f, $dest)) {
            $copied++;
        } else {
            $failed[] = basename($f);
        }
    }
}


removeDirectory($tempDir);


unlink($zipFile);


if (file_exists($extractDir . 'ffmpeg.exe')) {
    echo json_encode([
        'success' => true,
        'message' => 'FFmpeg安装成功',
        'files_copied' => $copied,
        'files_failed' => $failed
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => '安装失败，ffmpeg.exe未找到',
        'files_copied' => $copied,
        'files_failed' => $failed
    ]);
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
