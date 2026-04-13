<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$dataFile = __DIR__ . '/data/tickets.json';

function loadData($file) {
    if (!file_exists($file)) {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($file, json_encode([]));
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

function saveData($file, $data) {
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function validateTicketNo($no) {
    if (strlen($no) < 6 || strlen($no) > 20) {
        return false;
    }
    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9]+$/', $no)) {
        return false;
    }
    if (!preg_match('/[a-z]/', $no)) {
        return false;
    }
    if (!preg_match('/[A-Z]/', $no)) {
        return false;
    }
    if (!preg_match('/[0-9]/', $no)) {
        return false;
    }
    return true;
}

function generateId() {
    return uniqid() . '_' . time();
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' && $action === 'list') {
    $tickets = loadData($dataFile);
    echo json_encode(['success' => true, 'data' => $tickets]);
}
elseif ($method === 'GET' && $action === 'get') {
    $id = isset($_GET['id']) ? $_GET['id'] : '';
    $tickets = loadData($dataFile);
    $ticket = null;
    foreach ($tickets as $t) {
        if ($t['id'] === $id) {
            $ticket = $t;
            break;
        }
    }
    if ($ticket) {
        echo json_encode(['success' => true, 'data' => $ticket]);
    } else {
        echo json_encode(['success' => false, 'message' => '工单不存在']);
    }
}
elseif ($method === 'POST' && $action === 'add') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $ticketNo = trim($input['ticketNo'] ?? '');
    $subject = trim($input['subject'] ?? '');
    $category = trim($input['category'] ?? '');
    $description = trim($input['description'] ?? '');
    $planStartTime = trim($input['planStartTime'] ?? '');
    $executor = trim($input['executor'] ?? '');
    $planEndTime = trim($input['planEndTime'] ?? '');
    $creator = trim($input['creator'] ?? '管理员');
    
    if (!validateTicketNo($ticketNo)) {
        echo json_encode(['success' => false, 'message' => '工单编号格式错误：长度6-20字符，以字母开头，必须同时包含大小写字母和数字']);
        exit;
    }
    
    if (empty($subject)) {
        echo json_encode(['success' => false, 'message' => '工单主题不能为空']);
        exit;
    }
    
    if (!in_array($category, ['日常工单', '事件工单'])) {
        echo json_encode(['success' => false, 'message' => '请选择正确的工单类别']);
        exit;
    }
    
    $tickets = loadData($dataFile);
    foreach ($tickets as $t) {
        if ($t['ticketNo'] === $ticketNo) {
            echo json_encode(['success' => false, 'message' => '工单编号已存在']);
            exit;
        }
    }
    
    $newTicket = [
        'id' => generateId(),
        'ticketNo' => $ticketNo,
        'subject' => $subject,
        'category' => $category,
        'description' => $description,
        'planStartTime' => $planStartTime,
        'executor' => $executor,
        'planEndTime' => $planEndTime,
        'status' => '待处理',
        'creator' => $creator,
        'createTime' => date('Y-m-d H:i:s')
    ];
    
    $tickets[] = $newTicket;
    saveData($dataFile, $tickets);
    
    echo json_encode(['success' => true, 'message' => '添加成功', 'data' => $newTicket]);
}
elseif ($method === 'POST' && $action === 'edit') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = trim($input['id'] ?? '');
    $ticketNo = trim($input['ticketNo'] ?? '');
    $subject = trim($input['subject'] ?? '');
    $category = trim($input['category'] ?? '');
    $description = trim($input['description'] ?? '');
    $planStartTime = trim($input['planStartTime'] ?? '');
    $executor = trim($input['executor'] ?? '');
    $planEndTime = trim($input['planEndTime'] ?? '');
    $status = trim($input['status'] ?? '待处理');
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => '工单ID不能为空']);
        exit;
    }
    
    if (!validateTicketNo($ticketNo)) {
        echo json_encode(['success' => false, 'message' => '工单编号格式错误：长度6-20字符，以字母开头，必须同时包含大小写字母和数字']);
        exit;
    }
    
    if (empty($subject)) {
        echo json_encode(['success' => false, 'message' => '工单主题不能为空']);
        exit;
    }
    
    if (!in_array($category, ['日常工单', '事件工单'])) {
        echo json_encode(['success' => false, 'message' => '请选择正确的工单类别']);
        exit;
    }
    
    $tickets = loadData($dataFile);
    $found = false;
    foreach ($tickets as &$t) {
        if ($t['id'] === $id) {
            foreach ($tickets as $other) {
                if ($other['id'] !== $id && $other['ticketNo'] === $ticketNo) {
                    echo json_encode(['success' => false, 'message' => '工单编号已存在']);
                    exit;
                }
            }
            $t['ticketNo'] = $ticketNo;
            $t['subject'] = $subject;
            $t['category'] = $category;
            $t['description'] = $description;
            $t['planStartTime'] = $planStartTime;
            $t['executor'] = $executor;
            $t['planEndTime'] = $planEndTime;
            $t['status'] = $status;
            $t['updateTime'] = date('Y-m-d H:i:s');
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        echo json_encode(['success' => false, 'message' => '工单不存在']);
        exit;
    }
    
    saveData($dataFile, $tickets);
    echo json_encode(['success' => true, 'message' => '修改成功']);
}
elseif ($method === 'POST' && $action === 'delete') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = trim($input['id'] ?? '');
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => '工单ID不能为空']);
        exit;
    }
    
    $tickets = loadData($dataFile);
    $newTickets = [];
    $found = false;
    foreach ($tickets as $t) {
        if ($t['id'] === $id) {
            $found = true;
        } else {
            $newTickets[] = $t;
        }
    }
    
    if (!$found) {
        echo json_encode(['success' => false, 'message' => '工单不存在']);
        exit;
    }
    
    saveData($dataFile, $newTickets);
    echo json_encode(['success' => true, 'message' => '删除成功']);
}
else {
    echo json_encode(['success' => false, 'message' => '无效的操作']);
}
