<?php
header('Content-Type: application/json');

// 工单数据存储文件
$storageFile = 'tickets.json';

// 初始化存储文件
if (!file_exists($storageFile)) {
    file_put_contents($storageFile, json_encode([]));
}

// 获取请求参数
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        getTicketList();
        break;
    case 'add':
        addTicket();
        break;
    case 'update':
        updateTicket();
        break;
    case 'get':
        getTicket();
        break;
    default:
        echo json_encode(['success' => false, 'message' => '无效的操作']);
        break;
}

// 获取工单列表
function getTicketList() {
    global $storageFile;
    
    $tickets = json_decode(file_get_contents($storageFile), true);
    
    // 按创建日期降序排序
    usort($tickets, function($a, $b) {
        return strtotime($b['createDate']) - strtotime($a['createDate']);
    });
    
    echo json_encode(['success' => true, 'tickets' => $tickets]);
}

// 添加工单
function addTicket() {
    global $storageFile;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => '无效的请求数据']);
        return;
    }
    
    $tickets = json_decode(file_get_contents($storageFile), true);
    
    // 生成唯一工单编号
    $ticketId = $data['id'] ?? uniqid('TICKET-');
    
    $newTicket = [
        'id' => $ticketId,
        'subject' => $data['subject'] ?? '',
        'category' => $data['category'] ?? '日常工单',
        'description' => $data['description'] ?? '',
        'plannedStart' => $data['plannedStart'] ?? '',
        'executor' => $data['executor'] ?? '',
        'plannedEnd' => $data['plannedEnd'] ?? '',
        'status' => $data['status'] ?? '待处理',
        'createDate' => $data['createDate'] ?? date('Y-m-d'),
        'creator' => $data['creator'] ?? '管理员'
    ];
    
    $tickets[] = $newTicket;
    
    if (file_put_contents($storageFile, json_encode($tickets, JSON_PRETTY_PRINT))) {
        echo json_encode(['success' => true, 'message' => '工单添加成功']);
    } else {
        echo json_encode(['success' => false, 'message' => '保存工单失败']);
    }
}

// 更新工单
function updateTicket() {
    global $storageFile;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['id'])) {
        echo json_encode(['success' => false, 'message' => '无效的请求数据']);
        return;
    }
    
    $tickets = json_decode(file_get_contents($storageFile), true);
    
    $updated = false;
    foreach ($tickets as &$ticket) {
        if ($ticket['id'] == $data['id']) {
            $ticket['subject'] = $data['subject'] ?? $ticket['subject'];
            $ticket['category'] = $data['category'] ?? $ticket['category'];
            $ticket['description'] = $data['description'] ?? $ticket['description'];
            $ticket['plannedStart'] = $data['plannedStart'] ?? $ticket['plannedStart'];
            $ticket['executor'] = $data['executor'] ?? $ticket['executor'];
            $ticket['plannedEnd'] = $data['plannedEnd'] ?? $ticket['plannedEnd'];
            $ticket['status'] = $data['status'] ?? $ticket['status'];
            $updated = true;
            break;
        }
    }
    
    if ($updated) {
        if (file_put_contents($storageFile, json_encode($tickets, JSON_PRETTY_PRINT))) {
            echo json_encode(['success' => true, 'message' => '工单更新成功']);
        } else {
            echo json_encode(['success' => false, 'message' => '保存工单失败']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => '未找到指定工单']);
    }
}

// 获取单个工单
function getTicket() {
    global $storageFile;
    
    $id = $_GET['id'] ?? '';
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => '缺少工单ID']);
        return;
    }
    
    $tickets = json_decode(file_get_contents($storageFile), true);
    
    $ticket = null;
    foreach ($tickets as $t) {
        if ($t['id'] == $id) {
            $ticket = $t;
            break;
        }
    }
    
    if ($ticket) {
        echo json_encode(['success' => true, 'ticket' => $ticket]);
    } else {
        echo json_encode(['success' => false, 'message' => '未找到指定工单']);
    }
}
?>