<?php
require_once 'config.php';

$file = $_GET['file'] ?? '';
if (empty($file)) {
    http_response_code(400);
    echo '参数错误';
    exit;
}

$file = basename($file);
$filepath = UPLOAD_DIR . $file;

if (!file_exists($filepath)) {
    http_response_code(404);
    echo '文件不存在';
    exit;
}

$ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
$mimeTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'bmp' => 'image/bmp',
    'webp' => 'image/webp',
    'pdf' => 'application/pdf',
    'txt' => 'text/plain'
];

$mime = $mimeTypes[$ext] ?? 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filepath));

if (isset($_GET['download']) && $_GET['download'] == 1) {
    header('Content-Disposition: attachment; filename="' . $file . '"');
}

readfile($filepath);
