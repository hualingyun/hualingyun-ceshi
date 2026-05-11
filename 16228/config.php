<?php
session_start();

define('DATA_DIR', __DIR__ . '/data');
define('USERS_FILE', DATA_DIR . '/users.json');
define('SCHEDULES_FILE', DATA_DIR . '/schedules.json');
define('RECORDS_FILE', DATA_DIR . '/records.json');

if (!file_exists(DATA_DIR)) {
    mkdir(DATA_DIR, 0777, true);
}

if (!file_exists(USERS_FILE)) {
    $admin = [
        'id' => 1,
        'username' => 'admin',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'role' => 'admin',
        'name' => '管理员',
        'created_at' => date('Y-m-d H:i:s')
    ];
    file_put_contents(USERS_FILE, json_encode([$admin], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

if (!file_exists(SCHEDULES_FILE)) {
    file_put_contents(SCHEDULES_FILE, json_encode([], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

if (!file_exists(RECORDS_FILE)) {
    file_put_contents(RECORDS_FILE, json_encode([], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function get_users() {
    return json_decode(file_get_contents(USERS_FILE), true) ?: [];
}

function save_users($users) {
    file_put_contents(USERS_FILE, json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function get_schedules() {
    return json_decode(file_get_contents(SCHEDULES_FILE), true) ?: [];
}

function save_schedules($schedules) {
    file_put_contents(SCHEDULES_FILE, json_encode($schedules, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function get_records() {
    return json_decode(file_get_contents(RECORDS_FILE), true) ?: [];
}

function save_records($records) {
    file_put_contents(RECORDS_FILE, json_encode($records, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        header('Location: welcome.php');
        exit;
    }
}

function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function get_input() {
    return json_decode(file_get_contents('php://input'), true) ?: [];
}
