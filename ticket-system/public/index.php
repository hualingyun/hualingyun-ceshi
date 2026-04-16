<?php

// 简单的路由分发器
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// API路由
if (strpos($uri, '/api/') === 0) {
    require_once __DIR__ . '/../routes/api.php';
    exit;
}

// 静态文件服务
$publicFiles = ['/' => 'index.html', '/add' => 'add.html', '/edit' => 'edit.html'];

if (isset($publicFiles[$uri])) {
    $file = __DIR__ . '/../frontend/' . $publicFiles[$uri];
    if (file_exists($file)) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $contentTypes = [
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
        ];
        if (isset($contentTypes[$ext])) {
            header('Content-Type: ' . $contentTypes[$ext]);
        }
        readfile($file);
        exit;
    }
}

// 默认返回首页
$indexFile = __DIR__ . '/../frontend/index.html';
if (file_exists($indexFile)) {
    header('Content-Type: text/html');
    readfile($indexFile);
} else {
    echo 'Welcome to Ticket System';
}
