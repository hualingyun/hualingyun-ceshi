<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$dataFile = __DIR__ . '/data/workorders.json';

function loadData() {
    global $dataFile;
    if (!file_exists($dataFile)) {
        file_put_contents($dataFile, json_encode([]));
    }
    $data = json_decode(file_get_contents($dataFile), true);
    return $data ?: [];
}

function saveData($data) {
    global $dataFile;
    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        $data = loadData();
        echo json_encode(['success' => true, 'data' => $data]);
        break;
        
    case 'POST':
        $data = loadData();
        $input['id'] = uniqid();
        $input['created_at'] = date('Y-m-d H:i:s');
        $input['status'] = '待处理';
        $data[] = $input;
        saveData($data);
        echo json_encode(['success' => true, 'message' => '工单添加成功']);
        break;
        
    case 'PUT':
        $data = loadData();
        $id = $_GET['id'] ?? '';
        foreach ($data as &$item) {
            if ($item['id'] === $id) {
                $item = array_merge($item, $input);
                break;
            }
        }
        saveData($data);
        echo json_encode(['success' => true, 'message' => '工单更新成功']);
        break;
        
    case 'DELETE':
        $data = loadData();
        $id = $_GET['id'] ?? '';
        $data = array_filter($data, function($item) use ($id) {
            return $item['id'] !== $id;
        });
        saveData(array_values($data));
        echo json_encode(['success' => true, 'message' => '工单删除成功']);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
}
?>