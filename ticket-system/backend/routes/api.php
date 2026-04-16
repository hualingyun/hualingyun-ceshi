<?php

require_once __DIR__ . '/../app/Models/Ticket.php';
require_once __DIR__ . '/../app/Http/Controllers/Api/TicketController.php';

use App\Http\Controllers\Api\TicketController;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$controller = new TicketController();

if (preg_match('/\/api\/tickets\/?$/', $uri)) {
    if ($method === 'GET') {
        $controller->index();
    } elseif ($method === 'POST') {
        $controller->store();
    }
} elseif (preg_match('/\/api\/tickets\/(\d+)\/?$/', $uri, $matches)) {
    $id = $matches[1];
    
    if ($method === 'GET') {
        $controller->show($id);
    } elseif ($method === 'PUT' || $method === 'POST') {
        $controller->update($id);
    } elseif ($method === 'DELETE') {
        $controller->destroy($id);
    }
} else {
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode(['code' => 404, 'message' => '接口不存在']);
}
