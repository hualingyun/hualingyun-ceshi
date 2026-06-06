<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

define('DATA_DIR', __DIR__ . '/../data/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('DEFAULT_ADMIN_USER', 'Admin');
define('DEFAULT_ADMIN_PASS', 'Admin.123');

session_start();

function json_response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function get_input() {
    return json_decode(file_get_contents('php://input'), true) ?: [];
}

function check_password_strength($password) {
    if (strlen($password) < 8) return false;
    if (!preg_match('/[A-Z]/', $password)) return false;
    if (!preg_match('/[a-z]/', $password)) return false;
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>_\-+=\\[\\]\/\\\\~`]/', $password)) return false;
    return true;
}

function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_auth() {
    if (!is_logged_in()) {
        json_response(['error' => '未登录'], 401);
    }
}

function require_admin() {
    require_auth();
    if (!is_admin()) {
        json_response(['error' => '无权限'], 403);
    }
}
