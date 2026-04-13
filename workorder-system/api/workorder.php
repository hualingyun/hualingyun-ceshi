<?php
// 设置字符编码
ini_set('default_charset', 'UTF-8');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$dataFile = __DIR__ . '/../data/workorders.json';

function readWorkorders() {
    global $dataFile;
    if (!file_exists($dataFile)) {
        return [];
    }
    $content = file_get_contents($dataFile);
    return json_decode($content, true) ?: [];
}

function saveWorkorders($workorders) {
    global $dataFile;
    $dir = dirname($dataFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    // 确保文件使用UTF-8编码
    $json = json_encode($workorders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    // 添加BOM头确保文件编码正确
    file_put_contents($dataFile, "\xEF\xBB\xBF" . $json);
}

function validateWorkorderId($id) {
    // 必须以字母开头
    if (!preg_match('/^[a-zA-Z]/', $id)) {
        return false;
    }
    // 6-20字符，必须同时包含大小写字母和数字
    if (!preg_match('/^[a-zA-Z0-9]{6,20}$/', $id)) {
        return false;
    }
    // 检查是否包含至少一个大写字母
    if (!preg_match('/[A-Z]/', $id)) {
        return false;
    }
    // 检查是否包含至少一个小写字母
    if (!preg_match('/[a-z]/', $id)) {
        return false;
    }
    // 检查是否包含至少一个数字
    if (!preg_match('/[0-9]/', $id)) {
        return false;
    }
    return true;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $workorders = readWorkorders();
        echo json_encode(['success' => true, 'data' => $workorders]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!validateWorkorderId($input['workorder_id'])) {
            echo json_encode(['success' => false, 'message' => '工单编号格式不正确，需要6-20字符，以字母开头，必须同时包含大写字母、小写字母和数字']);
            exit;
        }
        
        $workorders = readWorkorders();
        
        foreach ($workorders as $wo) {
            if ($wo['workorder_id'] === $input['workorder_id']) {
                echo json_encode(['success' => false, 'message' => '工单编号已存在']);
                exit;
            }
        }
        
        $newWorkorder = [
            'id' => uniqid(),
            'workorder_id' => $input['workorder_id'],
            'subject' => $input['subject'],
            'category' => $input['category'],
            'description' => $input['description'] ?? '',
            'planned_start_time' => $input['planned_start_time'],
            'planned_end_time' => $input['planned_end_time'],
            'assignee' => $input['assignee'],
            'status' => '待处理',
            'created_by' => $input['created_by'] ?? '管理员',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $workorders[] = $newWorkorder;
        saveWorkorders($workorders);
        
        echo json_encode(['success' => true, 'message' => '工单创建成功', 'data' => $newWorkorder]);
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'];
        
        $workorders = readWorkorders();
        $found = false;
        
        foreach ($workorders as &$wo) {
            if ($wo['id'] === $id) {
                $wo['subject'] = $input['subject'];
                $wo['category'] = $input['category'];
                $wo['description'] = $input['description'] ?? '';
                $wo['planned_start_time'] = $input['planned_start_time'];
                $wo['planned_end_time'] = $input['planned_end_time'];
                $wo['assignee'] = $input['assignee'];
                $wo['status'] = $input['status'];
                $wo['updated_at'] = date('Y-m-d H:i:s');
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            echo json_encode(['success' => false, 'message' => '工单不存在']);
            exit;
        }
        
        saveWorkorders($workorders);
        echo json_encode(['success' => true, 'message' => '工单更新成功']);
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'];
        
        $workorders = readWorkorders();
        $found = false;
        
        foreach ($workorders as $key => $wo) {
            if ($wo['id'] === $id) {
                unset($workorders[$key]);
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            echo json_encode(['success' => false, 'message' => '工单不存在']);
            exit;
        }
        
        $workorders = array_values($workorders);
        saveWorkorders($workorders);
        
        echo json_encode(['success' => true, 'message' => '工单删除成功']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
        break;
}
?>
