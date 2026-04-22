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


$allowedExtensions = ['txt', 'doc', 'docx', 'wps', 'et', 'xls', 'xlsx', 'ppt', 'pptx'];



$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'parseDocument':
        parseDocument();
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => '未知操作']);
}


function parseDocument() {
    global $allowedExtensions;

    if (!isset($_FILES['file'])) {
        echo json_encode(['status' => 'error', 'message' => '没有上传文件']);
        return;
    }

    $file = $_FILES['file'];


    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => '文件上传失败: ' . getUploadError($file['error'])]);
        return;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowedExtensions)) {
        echo json_encode(['status' => 'error', 'message' => '不支持的文件格式: ' . $ext]);
        return;
    }
    

    $tempDir = __DIR__ . '/../storage/temp/';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    $tempFile = $tempDir . uniqid() . '_' . $file['name'];
    
    if (!move_uploaded_file($file['tmp_name'], $tempFile)) {
        echo json_encode(['status' => 'error', 'message' => '无法保存上传的文件']);
        return;
    }
    
    try {
        $content = '';
        
        switch ($ext) {
            case 'txt':
                $content = parseTxtFile($tempFile);
                break;
            case 'docx':
                $content = parseDocxFile($tempFile);
                break;
            case 'doc':
            case 'wps':
                $content = parseDocFile($tempFile);
                break;
            case 'xlsx':
            case 'xls':
            case 'et':
                $content = parseExcelFile($tempFile);
                break;
            case 'pptx':
            case 'ppt':
                $content = parsePptFile($tempFile);
                break;
        }
        

        @unlink($tempFile);
        

        $maxLength = 50000;
        $isTruncated = false;
        if (mb_strlen($content) > $maxLength) {
            $content = mb_substr($content, 0, $maxLength) . "\n\n[... 文档内容过长，仅显示前部分 ...]";
            $isTruncated = true;
        }
        
        echo json_encode([
            'status' => 'success',
            'content' => $content,
            'file_name' => $file['name'],
            'file_type' => $ext,
            'truncated' => $isTruncated
        ]);
        
    } catch (Exception $e) {
        @unlink($tempFile);
        echo json_encode(['status' => 'error', 'message' => '解析失败: ' . $e->getMessage()]);
    }
}


function parseTxtFile($filePath) {
    $content = file_get_contents($filePath);
    

    $encoding = mb_detect_encoding($content, ['UTF-8', 'GBK', 'GB2312', 'BIG5', 'ASCII'], true);
    if ($encoding && $encoding !== 'UTF-8') {
        $content = mb_convert_encoding($content, 'UTF-8', $encoding);
    }
    
    return $content;
}


function parseDocxFile($filePath) {
    $content = '';
    

    $zip = new ZipArchive();
    if ($zip->open($filePath) === TRUE) {

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        
        if ($xml) {

            $content = stripXmlTags($xml);

            $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }
    
    return $content ?: '[无法解析 DOCX 文件内容]';
}


function parseDocFile($filePath) {
    $content = '';
    

    $antiwordPath = shell_exec('which antiword');
    if ($antiwordPath) {
        $content = shell_exec('antiword ' . escapeshellarg($filePath) . ' 2>/dev/null');
    }
    

    if (empty($content)) {
        $catdocPath = shell_exec('which catdoc');
        if ($catdocPath) {
            $content = shell_exec('catdoc ' . escapeshellarg($filePath) . ' 2>/dev/null');
        }
    }
    

    if (empty($content)) {
        $raw = file_get_contents($filePath);

        $content = extractTextFromBinary($raw);
    }
    
    return $content ?: '[DOC/WPS 文件解析需要系统支持，当前环境无法直接解析。建议转换为 DOCX 格式后上传。]';
}


function parseExcelFile($filePath) {
    $content = '';
    

    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    if ($ext === 'xlsx') {
        $zip = new ZipArchive();
        if ($zip->open($filePath) === TRUE) {

            $sharedStrings = $zip->getFromName('xl/sharedStrings.xml');
            if ($sharedStrings) {
                $content = stripXmlTags($sharedStrings);
                $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
            $zip->close();
        }
    } else {

        $content = extractTextFromBinary(file_get_contents($filePath));
    }
    
    return $content ?: '[Excel 文件内容已提取，但部分格式信息可能丢失]';
}


function parsePptFile($filePath) {
    $content = '';
    
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    if ($ext === 'pptx') {
        $zip = new ZipArchive();
        if ($zip->open($filePath) === TRUE) {

            $slideNum = 1;
            while (($slideXml = $zip->getFromName("ppt/slides/slide{$slideNum}.xml")) !== false) {
                $slideContent = stripXmlTags($slideXml);
                $slideContent = html_entity_decode($slideContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if (trim($slideContent)) {
                    $content .= "\n\n=== 第 {$slideNum} 页 ===\n" . $slideContent;
                }
                $slideNum++;
            }
            $zip->close();
        }
    } else {

        $content = extractTextFromBinary(file_get_contents($filePath));
    }
    
    return $content ?: '[PPT 文件内容已提取，但部分格式信息可能丢失]';
}


function extractTextFromBinary($data) {

    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $data);

    preg_match_all('/[\x20-\x7E\x{4e00}-\x{9fff}]+/u', $text, $matches);

    $texts = array_filter($matches[0], function($s) { return strlen($s) > 3; });
    return implode("\n", array_slice($texts, 0, 1000));
}


function stripXmlTags($xml) {

    $xml = preg_replace('/<[^>]+xmlns[^>]*>/i', '', $xml);

    $xml = preg_replace('/<\/w:p>/i', "\n", $xml);
    $xml = preg_replace('/<w:br\s*\/?>/i', "\n", $xml);

    $text = strip_tags($xml);

    $text = preg_replace('/\n\s*\n/', "\n\n", $text);
    $text = preg_replace('/[ \t]+/', ' ', $text);
    return trim($text);
}


function getUploadError($code) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => '文件大小超过服务器限制',
        UPLOAD_ERR_FORM_SIZE => '文件大小超过表单限制',
        UPLOAD_ERR_PARTIAL => '文件部分上传失败',
        UPLOAD_ERR_NO_FILE => '没有文件被上传',
        UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
        UPLOAD_ERR_CANT_WRITE => '文件写入失败',
        UPLOAD_ERR_EXTENSION => '上传被扩展阻止'
    ];
    return $errors[$code] ?? '未知错误';
}
