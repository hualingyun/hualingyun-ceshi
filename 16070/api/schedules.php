<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = get_input();

if ($method === 'GET') {
    require_auth();
    $year = $_GET['year'] ?? null;
    $month = $_GET['month'] ?? null;
    $userId = $_GET['user_id'] ?? null;

    $schedules = DB::read('schedules');
    $users = DB::read('users');
    $userMap = [];
    foreach ($users as $u) {
        $userMap[$u['id']] = ['id' => $u['id'], 'name' => $u['name'], 'username' => $u['username']];
    }

    $result = [];
    foreach ($schedules as $s) {
        if ($year && $month) {
            $dateParts = explode('-', $s['date']);
            if ($dateParts[0] != $year || $dateParts[1] != $month) continue;
        }
        if ($userId && $s['user_id'] != $userId) continue;

        if (isset($userMap[$s['user_id']])) {
            $s['user'] = $userMap[$s['user_id']];
        }
        $result[] = $s;
    }

    json_response($result);
}

if ($method === 'POST' && $_GET['action'] === 'batch') {
    require_admin();
    $startDate = $input['start_date'] ?? '';
    $endDate = $input['end_date'] ?? '';
    $userIds = $input['user_ids'] ?? [];
    $shiftType = $input['shift_type'] ?? 'day';
    $excludeWeekends = $input['exclude_weekends'] ?? false;

    if (empty($startDate) || empty($endDate) || empty($userIds)) {
        json_response(['error' => '请填写完整信息'], 400);
    }

    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    if ($start > $end) {
        json_response(['error' => '开始日期不能晚于结束日期'], 400);
    }

    $schedules = DB::read('schedules');
    $existingDates = [];
    foreach ($schedules as $s) {
        $existingDates[$s['date']][] = $s['user_id'];
    }

    $userIndex = 0;
    $created = 0;
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end->modify('+1 day'));

    foreach ($period as $date) {
        $dateStr = $date->format('Y-m-d');
        $weekday = $date->format('N');

        if ($excludeWeekends && ($weekday >= 6)) continue;

        $userId = $userIds[$userIndex % count($userIds)];
        $userIndex++;

        $exists = false;
        if (isset($existingDates[$dateStr])) {
            foreach ($existingDates[$dateStr] as $existingUserId) {
                if ($existingUserId == $userId) {
                    $exists = true;
                    break;
                }
            }
        }

        if (!$exists) {
            DB::insert('schedules', [
                'date' => $dateStr,
                'user_id' => $userId,
                'shift_type' => $shiftType,
                'start_time' => $input['start_time'] ?? '08:00',
                'end_time' => $input['end_time'] ?? '18:00'
            ]);
            $created++;
        }
    }

    json_response(['message' => "成功创建 {$created} 条排班记录", 'created' => $created]);
}

if ($method === 'POST') {
    require_admin();
    $date = $input['date'] ?? '';
    $userId = $input['user_id'] ?? 0;
    $shiftType = $input['shift_type'] ?? 'day';
    $startTime = $input['start_time'] ?? '08:00';
    $endTime = $input['end_time'] ?? '18:00';

    if (empty($date) || !$userId) {
        json_response(['error' => '请填写完整信息'], 400);
    }

    $existing = DB::findOneBy('schedules', 'date', $date);
    while ($existing && $existing['user_id'] == $userId) {
        DB::delete('schedules', $existing['id']);
        $existing = DB::findOneBy('schedules', 'date', $date);
    }

    $schedule = DB::insert('schedules', [
        'date' => $date,
        'user_id' => $userId,
        'shift_type' => $shiftType,
        'start_time' => $startTime,
        'end_time' => $endTime
    ]);

    json_response($schedule, 201);
}

if ($method === 'PUT' && $_GET['action'] === 'swap') {
    require_admin();
    $id1 = $input['id1'] ?? 0;
    $id2 = $input['id2'] ?? 0;

    if (!$id1 || !$id2) {
        json_response(['error' => '参数错误'], 400);
    }

    $s1 = DB::find('schedules', $id1);
    $s2 = DB::find('schedules', $id2);

    if (!$s1 || !$s2) {
        json_response(['error' => '排班记录不存在'], 404);
    }

    $userId1 = $s1['user_id'];
    $userId2 = $s2['user_id'];

    DB::update('schedules', $id1, ['user_id' => $userId2]);
    DB::update('schedules', $id2, ['user_id' => $userId1]);

    json_response(['message' => '调班成功']);
}

if ($method === 'PUT') {
    require_admin();
    $id = $_GET['id'] ?? 0;
    if (!$id) {
        json_response(['error' => '参数错误'], 400);
    }

    $updates = [];
    if (isset($input['user_id'])) $updates['user_id'] = $input['user_id'];
    if (isset($input['shift_type'])) $updates['shift_type'] = $input['shift_type'];
    if (isset($input['start_time'])) $updates['start_time'] = $input['start_time'];
    if (isset($input['end_time'])) $updates['end_time'] = $input['end_time'];

    if (empty($updates)) {
        json_response(['error' => '没有需要更新的内容'], 400);
    }

    $schedule = DB::update('schedules', $id, $updates);
    if (!$schedule) {
        json_response(['error' => '排班记录不存在'], 404);
    }

    json_response($schedule);
}

if ($method === 'DELETE') {
    require_admin();
    $id = $_GET['id'] ?? 0;
    if (!$id) {
        json_response(['error' => '参数错误'], 400);
    }

    if (DB::delete('schedules', $id)) {
        json_response(['message' => '删除成功']);
    } else {
        json_response(['error' => '排班记录不存在'], 404);
    }
}

json_response(['error' => '无效请求'], 404);
