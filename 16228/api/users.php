<?php
require_once '../config.php';
require_admin();

$method = $_SERVER['REQUEST_METHOD'];
$input = get_input();

if ($method === 'GET') {
    $users = get_users();
    $result = [];
    foreach ($users as $u) {
        $result[] = [
            'id' => $u['id'],
            'username' => $u['username'],
            'name' => $u['name'],
            'role' => $u['role'],
            'created_at' => $u['created_at']
        ];
    }
    json_response(['success' => true, 'data' => $result]);
}

if ($method === 'POST') {
    $username = trim($input['username'] ?? '');
    $name = trim($input['name'] ?? '');
    $password = $input['password'] ?? '';
    $role = $input['role'] ?? 'student';

    if (empty($username) || empty($name) || empty($password)) {
        json_response(['success' => false, 'message' => '请填写完整信息']);
    }

    $users = get_users();
    foreach ($users as $u) {
        if ($u['username'] === $username) {
            json_response(['success' => false, 'message' => '用户名已存在']);
        }
    }

    $new_id = empty($users) ? 1 : max(array_column($users, 'id')) + 1;
    $users[] = [
        'id' => $new_id,
        'username' => $username,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
        'name' => $name,
        'created_at' => date('Y-m-d H:i:s')
    ];
    save_users($users);

    json_response(['success' => true, 'message' => '添加成功']);
}

if ($method === 'PUT') {
    $id = intval($input['id'] ?? 0);
    $name = trim($input['name'] ?? '');
    $role = $input['role'] ?? 'student';
    $password = $input['password'] ?? '';

    if ($id <= 0 || empty($name)) {
        json_response(['success' => false, 'message' => '参数错误']);
    }

    $users = get_users();
    $found = false;
    foreach ($users as &$u) {
        if ($u['id'] === $id) {
            $u['name'] = $name;
            $u['role'] = $role;
            if (!empty($password)) {
                $u['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            $found = true;
            break;
        }
    }

    if (!$found) {
        json_response(['success' => false, 'message' => '用户不存在']);
    }

    save_users($users);
    json_response(['success' => true, 'message' => '修改成功']);
}

if ($method === 'DELETE') {
    $id = intval($input['id'] ?? 0);
    if ($id <= 0) {
        json_response(['success' => false, 'message' => '参数错误']);
    }

    $users = get_users();
    foreach ($users as $u) {
        if ($u['id'] === $id && $u['username'] === 'admin') {
            json_response(['success' => false, 'message' => '不能删除超级管理员']);
        }
    }

    $users = array_filter($users, function($u) use ($id) {
        return $u['id'] !== $id;
    });
    save_users(array_values($users));
    json_response(['success' => true, 'message' => '删除成功']);
}

json_response(['success' => false, 'message' => '请求方法错误'], 405);
