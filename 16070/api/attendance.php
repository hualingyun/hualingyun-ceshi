<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = get_input();

function getTodaySchedule($userId) {
    $today = date('Y-m-d');
    $schedules = DB::read('schedules');
    foreach ($schedules as $s) {
        if ($s['date'] == $today && $s['user_id'] == $userId) {
            return $s;
        }
    }
    return null;
}

if ($method === 'GET') {
    require_auth();
    $userId = $_GET['user_id'] ?? $_SESSION['user_id'];
    $date = $_GET['date'] ?? null;

    $attendances = DB::read('attendances');
    $users = DB::read('users');
    $userMap = [];
    foreach ($users as $u) {
        $userMap[$u['id']] = ['id' => $u['id'], 'name' => $u['name']];
    }

    $result = [];
    foreach ($attendances as $a) {
        if ($userId && $a['user_id'] != $userId) continue;
        if ($date && $a['date'] != $date) continue;

        if (isset($userMap[$a['user_id']])) {
            $a['user'] = $userMap[$a['user_id']];
        }
        $result[] = $a;
    }

    usort($result, function($a, $b) {
        return strcmp($b['date'] . $b['check_in'], $a['date'] . $a['check_in']);
    });

    json_response($result);
}

if ($method === 'POST' && $_GET['action'] === 'check_in') {
    require_auth();
    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');

    $schedule = getTodaySchedule($userId);
    if (!$schedule) {
        json_response(['error' => '您今天没有排班，无法打卡'], 400);
    }

    $attendances = DB::read('attendances');
    foreach ($attendances as $a) {
        if ($a['date'] == $today && $a['user_id'] == $userId && !empty($a['check_in'])) {
            json_response(['error' => '您今天已经打过上班卡了'], 400);
        }
    }

    $attendance = DB::insert('attendances', [
        'user_id' => $userId,
        'date' => $today,
        'check_in' => date('H:i:s'),
        'check_out' => '',
        'schedule_id' => $schedule['id']
    ]);

    json_response(['message' => '上班打卡成功', 'attendance' => $attendance]);
}

if ($method === 'POST' && $_GET['action'] === 'check_out') {
    require_auth();
    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');

    $attendances = DB::read('attendances');
    $targetId = null;
    foreach ($attendances as $a) {
        if ($a['date'] == $today && $a['user_id'] == $userId) {
            if (empty($a['check_in'])) {
                json_response(['error' => '请先打上班卡'], 400);
            }
            if (!empty($a['check_out'])) {
                json_response(['error' => '您今天已经打过下班卡了'], 400);
            }
            $targetId = $a['id'];
            break;
        }
    }

    if (!$targetId) {
        json_response(['error' => '未找到今天的打卡记录，请先打上班卡'], 400);
    }

    $attendance = DB::update('attendances', $targetId, ['check_out' => date('H:i:s')]);

    json_response(['message' => '下班打卡成功', 'attendance' => $attendance]);
}

if ($method === 'GET' && $_GET['action'] === 'today_status') {
    require_auth();
    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');

    $schedule = getTodaySchedule($userId);
    $hasSchedule = $schedule ? true : false;

    $attendances = DB::read('attendances');
    $todayAttendance = null;
    foreach ($attendances as $a) {
        if ($a['date'] == $today && $a['user_id'] == $userId) {
            $todayAttendance = $a;
            break;
        }
    }

    json_response([
        'has_schedule' => $hasSchedule,
        'schedule' => $schedule,
        'attendance' => $todayAttendance,
        'can_check_in' => $hasSchedule && (!$todayAttendance || empty($todayAttendance['check_in'])),
        'can_check_out' => $todayAttendance && !empty($todayAttendance['check_in']) && empty($todayAttendance['check_out'])
    ]);
}

json_response(['error' => '无效请求'], 404);
