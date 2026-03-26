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

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => '无效的请求数据']);
    exit;
}

// 验证必填字段
$requiredFields = ['title', 'category', 'description', 'startTime', 'executor', 'endTime'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => '请填写完整信息']);
        exit;
    }
}

// 读取现有工单
$tickets = json_decode(file_get_contents($DATA_FILE), true);

// 查找并更新工单
$found = false;
foreach ($tickets as &$ticket) {
    if ($ticket['id'] == $data['id']) {
        $ticket['title'] = $data['title'];
        $ticket['category'] = $data['category'];
        $ticket['description'] = $data['description'];
        $ticket['startTime'] = $data['startTime'];
        $ticket['executor'] = $data['executor'];
        $ticket['endTime'] = $data['endTime'];
        $found = true;
        break;
    }
}

if (!$found) {
    echo json_encode(['success' => false, 'message' => '工单不存在']);
    exit;
}

// 保存到文件
if (file_put_contents($DATA_FILE, json_encode($tickets, JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'message' => '工单更新成功']);
} else {
    echo json_encode(['success' => false, 'message' => '保存失败']);
}
?>