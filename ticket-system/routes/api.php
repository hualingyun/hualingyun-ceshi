<?php

require_once __DIR__ . '/../app/Models/Ticket.php';
require_once __DIR__ . '/../app/Http/Controllers/TicketController.php';

use App\Http\Controllers\TicketController;

// 设置UTF-8编码
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace('/api', '', $uri);

$controller = new TicketController();

// 路由匹配
if ($uri === '/tickets' && $method === 'GET') {
    $controller->index();
} elseif (preg_match('/^\/tickets\/(\d+)$/', $uri, $matches) && $method === 'GET') {
    $controller->show($matches[1]);
} elseif ($uri === '/tickets' && $method === 'POST') {
    $controller->store();
} elseif (preg_match('/^\/tickets\/(\d+)$/', $uri, $matches) && $method === 'PUT') {
    $controller->update($matches[1]);
} elseif (preg_match('/^\/tickets\/(\d+)$/', $uri, $matches) && $method === 'DELETE') {
    $controller->destroy($matches[1]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => '接口不存在']);
}
