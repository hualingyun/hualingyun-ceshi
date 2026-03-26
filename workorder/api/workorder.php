<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/DataStore.php';

$dataStore = new DataStore('file');
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $workOrder = $dataStore->getWorkOrderById($_GET['id']);
            if ($workOrder) {
                echo json_encode(['success' => true, 'data' => $workOrder]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => '工单不存在']);
            }
        } else {
            $workOrders = $dataStore->getAllWorkOrders();
            echo json_encode(['success' => true, 'data' => $workOrders]);
        }
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '无效的数据']);
            exit;
        }
        
        $requiredFields = ['subject', 'category', 'description', 'start_time', 'executor', 'end_time'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "字段 {$field} 不能为空"]);
                exit;
            }
        }
        
        $workOrder = [
            'subject' => $input['subject'],
            'category' => $input['category'],
            'description' => $input['description'],
            'start_time' => $input['start_time'],
            'executor' => $input['executor'],
            'end_time' => $input['end_time'],
            'status' => $input['status'] ?? '待处理',
            'creator' => $input['creator'] ?? '管理员'
        ];
        
        if (isset($input['id'])) {
            $workOrder['id'] = $input['id'];
            $existing = $dataStore->getWorkOrderById($input['id']);
            if ($existing) {
                $workOrder['created_at'] = $existing['created_at'];
            }
        }
        
        if ($dataStore->saveWorkOrder($workOrder)) {
            echo json_encode(['success' => true, 'message' => '保存成功', 'data' => $workOrder]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '保存失败']);
        }
        break;
        
    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '无效的请求']);
            exit;
        }
        
        $existing = $dataStore->getWorkOrderById($input['id']);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => '工单不存在']);
            exit;
        }
        
        $workOrder = array_merge($existing, $input);
        
        if ($dataStore->saveWorkOrder($workOrder)) {
            echo json_encode(['success' => true, 'message' => '更新成功', 'data' => $workOrder]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '更新失败']);
        }
        break;
        
    case 'DELETE':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? $_GET['id'] ?? null;
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '缺少工单ID']);
            exit;
        }
        
        if ($dataStore->deleteWorkOrder($id)) {
            echo json_encode(['success' => true, 'message' => '删除成功']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '删除失败']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
        break;
}
