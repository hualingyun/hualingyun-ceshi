<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => '请求方法错误'], 405);
}

$input = get_input();
$username = trim($input['username'] ?? '');
$name = trim($input['name'] ?? '');
$password = $input['password'] ?? '';

if (empty($username) || empty($name) || empty($password)) {
    json_response(['success' => false, 'message' => '请填写完整信息']);
}

if (strlen($username) < 3 || strlen($username) > 20) {
    json_response(['success' => false, 'message' => '用户名长度应为3-20个字符']);
}

if (strlen($password) < 6) {
    json_response(['success' => false, 'message' => '密码长度至少6位']);
}

$users = get_users();

foreach ($users as $u) {
    if ($u['username'] === $username) {
        json_response(['success' => false, 'message' => '用户名已存在']);
    }
}

$new_id = empty($users) ? 1 : max(array_column($users, 'id')) + 1;

$new_user = [
    'id' => $new_id,
    'username' => $username,
    'password' => password_hash($password, PASSWORD_DEFAULT),
    'role' => 'student',
    'name' => $name,
    'created_at' => date('Y-m-d H:i:s')
];

$users[] = $new_user;
save_users($users);

json_response(['success' => true, 'message' => '注册成功']);
