<?php
require_once '../config.php';
require_login();

$schedules = get_schedules();
$today = date('Y-m-d');
$current_time = date('H:i:s');
$current_shift = (intval(date('H')) >= 6 && intval(date('H')) < 18) ? 'morning' : 'evening';
$on_duty = false;
$today_shift = null;

foreach ($schedules as $s) {
    if ($s['date'] === $today && $s['user_id'] === $_SESSION['user_id']) {
        $on_duty = true;
        $today_shift = $s['shift'];
        break;
    }
}

$users = get_users();
$others = [];
foreach ($users as $u) {
    if ($u['id'] !== $_SESSION['user_id']) {
        $others[] = ['id' => $u['id'], 'name' => $u['name']];
    }
}

json_response([
    'success' => true,
    'data' => [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'name' => $_SESSION['name'],
        'role' => $_SESSION['role'],
        'today' => $today,
        'current_shift' => $current_shift,
        'today_shift' => $today_shift,
        'on_duty' => $on_duty,
        'can_punch' => $on_duty && ($today_shift === $current_shift),
        'other_users' => $others
    ]
]);
