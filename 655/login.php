<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, '无效的请求方法');
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username)) {
    json_response(false, '用户名不能为空');
}

if (empty($password)) {
    json_response(false, '密码不能为空');
}

$users = get_users();
$found_user = null;

foreach ($users as $user) {
    if ($user['username'] === $username) {
        $found_user = $user;
        break;
    }
}

if (!$found_user) {
    json_response(false, '用户名不存在');
}

if (!password_verify($password, $found_user['password'])) {
    json_response(false, '密码错误');
}

unset($found_user['password']);
$_SESSION['user'] = $found_user;

json_response(true, '登录成功', ['redirect' => 'admin.php']);
