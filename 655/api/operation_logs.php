<?php
require_once '../config.php';

if (!is_logged_in()) {
    json_response(false, '请先登录');
}

$current_user = app_get_current_user();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $action = $_GET['action'] ?? '';
        if ($action === 'export') {
            export_logs();
        } else {
            get_logs_list();
        }
        break;
    default:
        json_response(false, '无效的请求方法');
}

function filter_logs() {
    $logs = get_operation_logs();
    
    $operator = trim($_GET['operator'] ?? '');
    $start_time = trim($_GET['start_time'] ?? '');
    $end_time = trim($_GET['end_time'] ?? '');
    $content = trim($_GET['content'] ?? '');
    
    if (!empty($operator)) {
        $logs = array_filter($logs, function($log) use ($operator) {
            return stripos($log['operator_name'], $operator) !== false;
        });
    }
    
    if (!empty($start_time)) {
        $logs = array_filter($logs, function($log) use ($start_time) {
            return strtotime($log['operation_time']) >= strtotime($start_time);
        });
    }
    
    if (!empty($end_time)) {
        $end_datetime = $end_time . ' 23:59:59';
        $logs = array_filter($logs, function($log) use ($end_datetime) {
            return strtotime($log['operation_time']) <= strtotime($end_datetime);
        });
    }
    
    if (!empty($content)) {
        $logs = array_filter($logs, function($log) use ($content) {
            return stripos($log['operation_content'], $content) !== false;
        });
    }
    
    $logs = array_reverse($logs);
    
    return array_values($logs);
}

function get_logs_list() {
    header('Content-Type: application/json');
    
    $logs = filter_logs();
    
    $page = intval($_GET['page'] ?? 1);
    $page_size = intval($_GET['page_size'] ?? 10);
    
    if ($page < 1) $page = 1;
    if ($page_size < 1) $page_size = 10;
    
    $total = count($logs);
    $total_pages = ceil($total / $page_size);
    
    $offset = ($page - 1) * $page_size;
    $paginated_logs = array_slice($logs, $offset, $page_size);
    
    $result = [
        'list' => $paginated_logs,
        'pagination' => [
            'page' => $page,
            'page_size' => $page_size,
            'total' => $total,
            'total_pages' => $total_pages
        ]
    ];
    
    json_response(true, '获取成功', $result);
}

function export_logs() {
    $logs = filter_logs();
    
    $filename = 'operation_logs_' . date('YmdHis') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Cache-Control: max-age=0');
    
    $output = fopen('php://output', 'w');
    
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['ID', '操作人', '操作时间', '操作内容', '操作IP']);
    
    foreach ($logs as $log) {
        fputcsv($output, [
            $log['id'],
            $log['operator_name'],
            $log['operation_time'],
            $log['operation_content'],
            $log['ip']
        ]);
    }
    
    fclose($output);
    exit;
}
