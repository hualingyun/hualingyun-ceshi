<?php
header('Content-Type: application/json; charset=utf-8');

// 数据存储文件路径
$DATA_FILE = '../data/tickets.json';

// 确保数据目录存在
if (!file_exists('../data')) {
    mkdir('../data', 0777, true);
}

// 初始化数据文件
if (!file_exists($DATA_FILE)) {
    file_put_contents($DATA_FILE, json_encode([]));
}

// 获取单个工单
if (isset($_GET['id'])) {
    $ticketId = $_GET['id'];
    $tickets = json_decode(file_get_contents($DATA_FILE), true);
    
    foreach ($tickets as $ticket) {
        if ($ticket['id'] == $ticketId) {
            echo json_encode($ticket);
            exit;
        }
    }
    
    echo json_encode(null);
    exit;
}

// 获取所有工单
$tickets = json_decode(file_get_contents($DATA_FILE), true);

// 转换状态为中文显示
foreach ($tickets as &$ticket) {
    switch ($ticket['status']) {
        case 'open':
            $ticket['statusText'] = '待处理';
            break;
        case 'processing':
            $ticket['statusText'] = '处理中';
            break;
        case 'closed':
            $ticket['statusText'] = '已关闭';
            break;
        default:
            $ticket['statusText'] = '待处理';
    }
}

echo json_encode($tickets);
?>