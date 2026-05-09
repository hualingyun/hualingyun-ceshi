<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, '无效的请求方法');
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$phone = trim($_POST['phone'] ?? '');

$result = validate_username($username);
if ($result !== true) {
    json_response(false, $result);
}

$result = validate_password($password);
if ($result !== true) {
    json_response(false, $result);
}

if ($password !== $confirm_password) {
    json_response(false, '两次输入的密码不一致');
}

$result = validate_phone($phone);
if ($result !== true) {
    json_response(false, $result);
}

$users = get_users();
foreach ($users as $user) {
    if ($user['username'] === $username) {
        json_response(false, '用户名已存在');
    }
    if ($user['phone'] === $phone) {
        json_response(false, '手机号已被注册');
    }
}

$new_user = [
    'id' => empty($users) ? 1 : max(array_column($users, 'id')) + 1,
    'username' => $username,
    'password' => password_hash($password, PASSWORD_DEFAULT),
    'phone' => $phone,
    'created_at' => date('Y-m-d H:i:s')
];

$users[] = $new_user;
save_users($users);

json_response(true, '注册成功，请登录', ['redirect' => 'login.html']);
