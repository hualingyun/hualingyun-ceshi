<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$dataFile = __DIR__ . '/../data/tickets.json';

if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([]));
}

function getTickets() {
    global $dataFile;
    $json = file_get_contents($dataFile);
    return json_decode($json, true) ?: [];
}

function saveTickets($tickets) {
    global $dataFile;
    file_put_contents($dataFile, json_encode($tickets, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function validateTicketId($ticketId) {
    $pattern = '/^[a-zA-Z][a-zA-Z0-9]{5,19}$/';
    return preg_match($pattern, $ticketId);
}

function validateRequiredFields($data) {
    $required = ['ticket_id', 'subject', 'category', 'description', 'plan_start_time', 'assignee', 'plan_end_time'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            return $field;
        }
    }
    return null;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($method) {
        case 'GET':
            $tickets = getTickets();
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $category = isset($_GET['category']) ? trim($_GET['category']) : '';
            $status = isset($_GET['status']) ? trim($_GET['status']) : '';
            
            $filtered = $tickets;
            
            if ($search !== '') {
                $filtered = array_filter($filtered, function($t) use ($search) {
                    return stripos($t['ticket_id'], $search) !== false ||
                           stripos($t['subject'], $search) !== false ||
                           stripos($t['assignee'], $search) !== false ||
                           stripos($t['creator'], $search) !== false;
                });
            }
            
            if ($category !== '') {
                $filtered = array_filter($filtered, function($t) use ($category) {
                    return $t['category'] === $category;
                });
            }
            
            if ($status !== '') {
                $filtered = array_filter($filtered, function($t) use ($status) {
                    return $t['status'] === $status;
                });
            }
            
            usort($filtered, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            echo json_encode(['success' => true, 'data' => array_values($filtered)]);
            break;
            
        case 'POST':
            $missing = validateRequiredFields($input);
            if ($missing !== null) {
                echo json_encode(['success' => false, 'message' => "缺少必填字段: {$missing}"]);
                http_response_code(400);
                exit();
            }
            
            if (!validateTicketId($input['ticket_id'])) {
                echo json_encode(['success' => false, 'message' => '工单编号格式错误：需以字母开头，6-20位字母数字组合']);
                http_response_code(400);
                exit();
            }
            
            $tickets = getTickets();
            foreach ($tickets as $t) {
                if ($t['ticket_id'] === $input['ticket_id']) {
                    echo json_encode(['success' => false, 'message' => '工单编号已存在']);
                    http_response_code(400);
                    exit();
                }
            }
            
            $ticket = [
                'id' => uniqid(),
                'ticket_id' => $input['ticket_id'],
                'subject' => $input['subject'],
                'category' => $input['category'],
                'description' => $input['description'],
                'plan_start_time' => $input['plan_start_time'],
                'plan_end_time' => $input['plan_end_time'],
                'assignee' => $input['assignee'],
                'status' => '待处理',
                'creator' => $input['creator'] ?? '当前用户',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $tickets[] = $ticket;
            saveTickets($tickets);
            
            echo json_encode(['success' => true, 'message' => '工单创建成功', 'data' => $ticket]);
            break;
            
        case 'PUT':
            $id = isset($_GET['id']) ? $_GET['id'] : null;
            if ($id === null) {
                echo json_encode(['success' => false, 'message' => '缺少工单ID']);
                http_response_code(400);
                exit();
            }
            
            $missing = validateRequiredFields($input);
            if ($missing !== null) {
                echo json_encode(['success' => false, 'message' => "缺少必填字段: {$missing}"]);
                http_response_code(400);
                exit();
            }
            
            if (!validateTicketId($input['ticket_id'])) {
                echo json_encode(['success' => false, 'message' => '工单编号格式错误：需以字母开头，6-20位字母数字组合']);
                http_response_code(400);
                exit();
            }
            
            $tickets = getTickets();
            $found = false;
            $updatedTicket = null;
            
            foreach ($tickets as $index => $t) {
                if ($t['id'] === $id) {
                    if ($t['ticket_id'] !== $input['ticket_id']) {
                        foreach ($tickets as $check) {
                            if ($check['id'] !== $id && $check['ticket_id'] === $input['ticket_id']) {
                                echo json_encode(['success' => false, 'message' => '工单编号已存在']);
                                http_response_code(400);
                                exit();
                            }
                        }
                    }
                    
                    $tickets[$index] = [
                        'id' => $id,
                        'ticket_id' => $input['ticket_id'],
                        'subject' => $input['subject'],
                        'category' => $input['category'],
                        'description' => $input['description'],
                        'plan_start_time' => $input['plan_start_time'],
                        'plan_end_time' => $input['plan_end_time'],
                        'assignee' => $input['assignee'],
                        'status' => $input['status'] ?? $t['status'],
                        'creator' => $t['creator'],
                        'created_at' => $t['created_at'],
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $updatedTicket = $tickets[$index];
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                echo json_encode(['success' => false, 'message' => '工单不存在']);
                http_response_code(404);
                exit();
            }
            
            saveTickets($tickets);
            echo json_encode(['success' => true, 'message' => '工单更新成功', 'data' => $updatedTicket]);
            break;
            
        case 'DELETE':
            $id = isset($_GET['id']) ? $_GET['id'] : null;
            if ($id === null) {
                echo json_encode(['success' => false, 'message' => '缺少工单ID']);
                http_response_code(400);
                exit();
            }
            
            $tickets = getTickets();
            $found = false;
            
            foreach ($tickets as $index => $t) {
                if ($t['id'] === $id) {
                    array_splice($tickets, $index, 1);
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                echo json_encode(['success' => false, 'message' => '工单不存在']);
                http_response_code(404);
                exit();
            }
            
            saveTickets($tickets);
            echo json_encode(['success' => true, 'message' => '工单删除成功']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
            http_response_code(405);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '服务器错误: ' . $e->getMessage()]);
    http_response_code(500);
}
