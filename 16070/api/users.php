<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = get_input();

if ($method === 'GET') {
    require_admin();
    $users = DB::read('users');
    $result = [];
    foreach ($users as $user) {
        $result[] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'name' => $user['name'],
            'role' => $user['role'],
            'phone' => $user['phone'] ?? '',
            'email' => $user['email'] ?? '',
            'created_at' => $user['created_at']
        ];
    }
    json_response($result);
}

if ($method === 'POST') {
    require_admin();
    $username = trim($input['username'] ?? '');
    $name = trim($input['name'] ?? '');
    $password = $input['password'] ?? '';
    $role = $input['role'] ?? 'staff';
    $phone = $input['phone'] ?? '';
    $email = $input['email'] ?? '';

    if (empty($username) || empty($name) || empty($password)) {
        json_response(['error' => '请填写完整信息'], 400);
    }

    if (DB::findOneBy('users', 'username', $username)) {
        json_response(['error' => '用户名已存在'], 400);
    }

    if (!check_password_strength($password)) {
        json_response(['error' => '密码强度不够，需包含大小写字母和特殊符号，至少8位'], 400);
    }

    if (!in_array($role, ['admin', 'staff'])) {
        $role = 'staff';
    }

    $user = DB::insert('users', [
        'username' => $username,
        'name' => $name,
        'password' => hash_password($password),
        'role' => $role,
        'phone' => $phone,
        'email' => $email
    ]);

    unset($user['password']);
    json_response($user, 201);
}

if ($method === 'PUT') {
    require_admin();
    $id = $_GET['id'] ?? 0;
    if (!$id) {
        json_response(['error' => '参数错误'], 400);
    }

    $updates = [];
    if (isset($input['name'])) $updates['name'] = trim($input['name']);
    if (isset($input['role'])) $updates['role'] = in_array($input['role'], ['admin', 'staff']) ? $input['role'] : 'staff';
    if (isset($input['phone'])) $updates['phone'] = $input['phone'];
    if (isset($input['email'])) $updates['email'] = $input['email'];
    if (!empty($input['password'])) {
        if (!check_password_strength($input['password'])) {
            json_response(['error' => '密码强度不够，需包含大小写字母和特殊符号，至少8位'], 400);
        }
        $updates['password'] = hash_password($input['password']);
    }

    if (empty($updates)) {
        json_response(['error' => '没有需要更新的内容'], 400);
    }

    $user = DB::update('users', $id, $updates);
    if (!$user) {
        json_response(['error' => '用户不存在'], 404);
    }

    unset($user['password']);
    json_response($user);
}

if ($method === 'DELETE') {
    require_admin();
    $id = $_GET['id'] ?? 0;
    if (!$id || $id == 1) {
        json_response(['error' => '参数错误或不能删除管理员'], 400);
    }

    if (DB::delete('users', $id)) {
        json_response(['message' => '删除成功']);
    } else {
        json_response(['error' => '用户不存在'], 404);
    }
}

json_response(['error' => '无效请求'], 404);
