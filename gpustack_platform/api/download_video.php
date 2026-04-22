<?php



if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']) {
    http_response_code(403);
    echo json_encode(['error' => '请先登录']);
    exit;
}


$filename = $_GET['file'] ?? '';


if (empty($filename) || !preg_match('/^edited_video_\d+\.mp4$/', $filename)) {
    http_response_code(400);
    echo json_encode(['error' => '无效的文件名']);
    exit;
}


$filePath = __DIR__ . '/../uploads/videos/' . $filename;


if (!file_exists($filePath)) {
    http_response_code(404);
    echo json_encode(['error' => '文件不存在或已过期']);
    exit;
}


$fileSize = filesize($filePath);
$fileExt = pathinfo($filename, PATHINFO_EXTENSION);


header('Content-Type: video/mp4');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-cache, must-revalidate');


readfile($filePath);
exit;
