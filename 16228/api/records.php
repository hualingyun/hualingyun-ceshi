<?php
require_once '../config.php';
require_login();

$method = $_SERVER['REQUEST_METHOD'];
$input = get_input();

function get_current_shift() {
    $hour = intval(date('H'));
    return $hour >= 6 && $hour < 18 ? 'morning' : 'evening';
}

if ($method === 'GET') {
    $page = intval($_GET['page'] ?? 1);
    $page_size = intval($_GET['page_size'] ?? 10);
    $search_user = trim($_GET['user'] ?? '');
    $search_date = trim($_GET['date'] ?? '');
    
    $my_id = $_SESSION['user_id'];
    $is_admin = is_admin();
    
    $records = get_records();
    $users = get_users();
    $user_map = [];
    foreach ($users as $u) {
        $user_map[$u['id']] = $u['name'];
    }

    $result = [];
    foreach ($records as $r) {
        if (!$is_admin) {
            if ($r['user_id'] !== $my_id && (!isset($r['next_user_id']) || $r['next_user_id'] !== $my_id)) {
                continue;
            }
        }
        
        $item = [
            'id' => $r['id'],
            'date' => $r['date'],
            'shift' => $r['shift'],
            'user_id' => $r['user_id'],
            'user_name' => $user_map[$r['user_id']] ?? '未知',
            'punch_time' => $r['punch_time'],
            'next_user_name' => isset($r['next_user_id']) ? ($user_map[$r['next_user_id']] ?? '未知') : '',
            'next_punch_time' => $r['next_punch_time'] ?? '',
            'status' => $r['status']
        ];
        
        $match = true;
        if (!empty($search_user)) {
            if (strpos($item['user_name'], $search_user) === false && strpos($item['next_user_name'], $search_user) === false) {
                $match = false;
            }
        }
        if (!empty($search_date)) {
            if ($item['date'] !== $search_date) {
                $match = false;
            }
        }
        
        if ($match) {
            $result[] = $item;
        }
    }
    
    usort($result, function($a, $b) {
        return strcmp($b['punch_time'], $a['punch_time']);
    });
    
    $total = count($result);
    $total_pages = ceil($total / $page_size);
    $start = ($page - 1) * $page_size;
    $paged_data = array_slice($result, $start, $page_size);

    json_response([
        'success' => true, 
        'data' => $paged_data,
        'pagination' => [
            'page' => $page,
            'page_size' => $page_size,
            'total' => $total,
            'total_pages' => $total_pages
        ]
    ]);
}

if ($method === 'POST' && !is_admin()) {
    $action = $input['action'] ?? '';
    $my_id = $_SESSION['user_id'];
    $today = date('Y-m-d');
    $current_shift = get_current_shift();

    $schedules = get_schedules();
    $my_schedule = null;
    foreach ($schedules as $s) {
        if ($s['date'] === $today && $s['user_id'] === $my_id) {
            $my_schedule = $s;
            break;
        }
    }

    if (!$my_schedule) {
        json_response(['success' => false, 'message' => '今天没有您的值班安排']);
    }

    if ($action === 'start') {
        $current_shift = get_current_shift();
        if ($my_schedule['shift'] !== $current_shift) {
            $shift_name = $current_shift === 'morning' ? '早班' : '晚班';
            $my_shift_name = $my_schedule['shift'] === 'morning' ? '早班' : '晚班';
            json_response(['success' => false, 'message' => "当前是{$shift_name}时间，您的值班班次是{$my_shift_name}，请在对应时间打卡"]);
        }

        $records = get_records();
        foreach ($records as $r) {
            if ($r['date'] === $today && $r['user_id'] === $my_id && $r['status'] === 'ongoing') {
                json_response(['success' => false, 'message' => '您已经打卡上班了']);
            }
        }

        $new_id = empty($records) ? 1 : max(array_column($records, 'id')) + 1;
        $records[] = [
            'id' => $new_id,
            'date' => $today,
            'shift' => $my_schedule['shift'],
            'user_id' => $my_id,
            'punch_time' => date('Y-m-d H:i:s'),
            'status' => 'ongoing'
        ];
        save_records($records);
        json_response(['success' => true, 'message' => '上班打卡成功']);
    }

    if ($action === 'handover') {
        $next_user_id = intval($input['next_user_id'] ?? 0);
        if ($next_user_id <= 0) {
            json_response(['success' => false, 'message' => '请选择交接班人员']);
        }

        $found = false;
        foreach ($records as &$r) {
            if ($r['date'] === $today && $r['user_id'] === $my_id && $r['status'] === 'ongoing') {
                $r['next_user_id'] = $next_user_id;
                $r['next_punch_time'] = date('Y-m-d H:i:s');
                $r['status'] = 'completed';
                $found = true;
                break;
            }
        }

        if (!$found) {
            json_response(['success' => false, 'message' => '没有进行中的值班记录']);
        }

        save_records($records);
        json_response(['success' => true, 'message' => '交接班成功']);
    }
}

json_response(['success' => false, 'message' => '请求方法错误'], 405);
