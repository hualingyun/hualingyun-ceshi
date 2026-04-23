<?php
session_start();

define('DATA_PATH', __DIR__ . '/data/');
define('USERS_FILE', DATA_PATH . 'users.json');
define('ARTICLES_FILE', DATA_PATH . 'articles.json');

if (!is_dir(DATA_PATH)) {
    mkdir(DATA_PATH, 0755, true);
}

if (!file_exists(USERS_FILE)) {
    file_put_contents(USERS_FILE, json_encode([]));
}

if (!file_exists(ARTICLES_FILE)) {
    file_put_contents(ARTICLES_FILE, json_encode([]));
}

function json_response($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function get_users() {
    $content = file_get_contents(USERS_FILE);
    return json_decode($content, true) ?: [];
}

function save_users($users) {
    file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

function get_articles() {
    $content = file_get_contents(ARTICLES_FILE);
    return json_decode($content, true) ?: [];
}

function save_articles($articles) {
    file_put_contents(ARTICLES_FILE, json_encode($articles, JSON_PRETTY_PRINT));
}

function validate_username($username) {
    if (empty($username)) {
        return '用户名不能为空';
    }
    if (strlen($username) < 3 || strlen($username) > 20) {
        return '用户名长度必须为3-20个字符';
    }
    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $username)) {
        return '用户名必须以字母开头，只能包含字母、数字和下划线';
    }
    return true;
}

function validate_password($password) {
    if (empty($password)) {
        return '密码不能为空';
    }
    if (strlen($password) < 6 || strlen($password) > 20) {
        return '密码长度必须为6-20个字符';
    }
    if (!preg_match('/^[a-zA-Z]/', $password)) {
        return '密码必须以字母开头';
    }
    if (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return '密码必须包含大小写字母和数字';
    }
    return true;
}

function validate_phone($phone) {
    if (empty($phone)) {
        return '手机号不能为空';
    }
    if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
        return '请输入有效的手机号';
    }
    return true;
}

function is_logged_in() {
    return isset($_SESSION['user']);
}

function get_current_user() {
    return $_SESSION['user'] ?? null;
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.html');
        exit;
    }
}
