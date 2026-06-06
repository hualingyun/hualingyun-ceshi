<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = get_input();

if ($method === 'POST' && $_GET['action'] === 'login') {
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';
    $captcha = strtolower(trim($input['captcha'] ?? ''));

    if (empty($username) || empty($password) || empty($captcha)) {
        json_response(['error' => '请填写完整信息'], 400);
    }

    if ($captcha !== ($_SESSION['captcha'] ?? '')) {
        json_response(['error' => '验证码错误'], 400);
    }

    $user = DB::findOneBy('users', 'username', $username);
    if (!$user) {
        json_response(['error' => '用户名或密码错误'], 400);
    }

    if (!verify_password($password, $user['password'])) {
        json_response(['error' => '用户名或密码错误'], 400);
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];

    unset($_SESSION['captcha']);

    json_response([
        'message' => '登录成功',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'name' => $user['name'],
            'role' => $user['role']
        ]
    ]);
}

if ($method === 'POST' && $_GET['action'] === 'logout') {
    session_destroy();
    json_response(['message' => '退出成功']);
}

if ($method === 'GET' && $_GET['action'] === 'check') {
    if (is_logged_in()) {
        json_response([
            'logged_in' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'name' => $_SESSION['name'],
                'role' => $_SESSION['role']
            ]
        ]);
    } else {
        json_response(['logged_in' => false]);
    }
}

if ($method === 'POST' && $_GET['action'] === 'change_password') {
    require_auth();
    $oldPassword = $input['old_password'] ?? '';
    $newPassword = $input['new_password'] ?? '';

    $user = DB::find('users', $_SESSION['user_id']);
    if (!$user || !verify_password($oldPassword, $user['password'])) {
        json_response(['error' => '原密码错误'], 400);
    }

    if (!check_password_strength($newPassword)) {
        json_response(['error' => '密码强度不够，需包含大小写字母和特殊符号，至少8位'], 400);
    }

    DB::update('users', $_SESSION['user_id'], ['password' => hash_password($newPassword)]);
    json_response(['message' => '密码修改成功']);
}

json_response(['error' => '无效请求'], 404);
