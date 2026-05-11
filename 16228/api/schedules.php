<?php
require_once '../config.php';
require_login();

$method = $_SERVER['REQUEST_METHOD'];
$input = get_input();

if ($method === 'GET') {
    $schedules = get_schedules();
    $users = get_users();
    $user_map = [];
    foreach ($users as $u) {
        $user_map[$u['id']] = $u['name'];
    }

    if (is_admin()) {
        $result = [];
        foreach ($schedules as $s) {
            $result[] = [
                'id' => $s['id'],
                'date' => $s['date'],
                'shift' => $s['shift'],
                'user_id' => $s['user_id'],
                'user_name' => $user_map[$s['user_id']] ?? '未知',
                'created_at' => $s['created_at']
            ];
        }
        json_response(['success' => true, 'data' => $result]);
    } else {
        $my_id = $_SESSION['user_id'];
        $result = [];
        foreach ($schedules as $s) {
            if ($s['user_id'] === $my_id) {
                $result[] = [
                    'id' => $s['id'],
                    'date' => $s['date'],
                    'shift' => $s['shift'],
                    'user_name' => $user_map[$s['user_id']] ?? '未知'
                ];
            }
        }
        json_response(['success' => true, 'data' => $result]);
    }
}

require_admin();

if ($method === 'POST' && isset($input['batch'])) {
    $start_date = $input['start_date'] ?? '';
    $end_date = $input['end_date'] ?? '';
    $user_ids = $input['user_ids'] ?? [];
    $shifts = $input['shifts'] ?? ['morning', 'evening'];

    if (empty($start_date) || empty($end_date) || empty($user_ids)) {
        json_response(['success' => false, 'message' => '请填写完整信息']);
    }

    $schedules = get_schedules();
    $new_id = empty($schedules) ? 1 : max(array_column($schedules, 'id')) + 1;

    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $user_idx = 0;
    $count = 0;

    while ($start <= $end) {
        foreach ($shifts as $shift) {
            $existing = false;
            foreach ($schedules as $s) {
                if ($s['date'] === $start->format('Y-m-d') && $s['shift'] === $shift) {
                    $existing = true;
                    break;
                }
            }
            if (!$existing) {
                $schedules[] = [
                    'id' => $new_id++,
                    'date' => $start->format('Y-m-d'),
                    'shift' => $shift,
                    'user_id' => $user_ids[$user_idx % count($user_ids)],
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $count++;
            }
            $user_idx++;
        }
        $start->modify('+1 day');
    }

    save_schedules($schedules);
    json_response(['success' => true, 'message' => "成功生成 {$count} 条排班"]);
}

if ($method === 'POST') {
    $date = $input['date'] ?? '';
    $shift = $input['shift'] ?? '';
    $user_id = intval($input['user_id'] ?? 0);

    if (empty($date) || empty($shift) || $user_id <= 0) {
        json_response(['success' => false, 'message' => '请填写完整信息']);
    }

    $schedules = get_schedules();
    foreach ($schedules as $s) {
        if ($s['date'] === $date && $s['shift'] === $shift) {
            json_response(['success' => false, 'message' => '该日期该班次已存在排班']);
        }
    }

    $new_id = empty($schedules) ? 1 : max(array_column($schedules, 'id')) + 1;
    $schedules[] = [
        'id' => $new_id,
        'date' => $date,
        'shift' => $shift,
        'user_id' => $user_id,
        'created_at' => date('Y-m-d H:i:s')
    ];
    save_schedules($schedules);
    json_response(['success' => true, 'message' => '添加成功']);
}

if ($method === 'PUT') {
    $id = intval($input['id'] ?? 0);
    $date = $input['date'] ?? '';
    $shift = $input['shift'] ?? '';
    $user_id = intval($input['user_id'] ?? 0);

    if ($id <= 0 || empty($date) || empty($shift) || $user_id <= 0) {
        json_response(['success' => false, 'message' => '参数错误']);
    }

    $schedules = get_schedules();
    $found = false;
    foreach ($schedules as &$s) {
        if ($s['id'] === $id) {
            $s['date'] = $date;
            $s['shift'] = $shift;
            $s['user_id'] = $user_id;
            $found = true;
            break;
        }
    }

    if (!$found) {
        json_response(['success' => false, 'message' => '排班不存在']);
    }

    save_schedules($schedules);
    json_response(['success' => true, 'message' => '修改成功']);
}

if ($method === 'DELETE') {
    $id = intval($input['id'] ?? 0);
    if ($id <= 0) {
        json_response(['success' => false, 'message' => '参数错误']);
    }

    $schedules = get_schedules();
    $schedules = array_filter($schedules, function($s) use ($id) {
        return $s['id'] !== $id;
    });
    save_schedules(array_values($schedules));
    json_response(['success' => true, 'message' => '删除成功']);
}

json_response(['success' => false, 'message' => '请求方法错误'], 405);
