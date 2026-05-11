<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => '请求方法错误'], 405);
}

$input = get_input();
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
$role = $input['role'] ?? 'student';

if (empty($username) || empty($password)) {
    json_response(['success' => false, 'message' => '用户名和密码不能为空']);
}

$users = get_users();
$user = null;

foreach ($users as $u) {
    if ($u['username'] === $username) {
        $user = $u;
        break;
    }
}

if (!$user) {
    json_response(['success' => false, 'message' => '用户不存在']);
}

if ($user['role'] !== $role) {
    json_response(['success' => false, 'message' => $role === 'admin' ? '该用户不是管理员' : '该用户不是学生']);
}

if (!password_verify($password, $user['password'])) {
    json_response(['success' => false, 'message' => '密码错误']);
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['name'] = $user['name'];
$_SESSION['role'] = $user['role'];

json_response(['success' => true, 'message' => '登录成功', 'role' => $user['role']]);
