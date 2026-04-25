<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    json_response(false, '请先登录');
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        get_users_list();
        break;
    case 'POST':
        $action = $_POST['action'] ?? '';
        if ($action === 'add') {
            add_user();
        } elseif ($action === 'edit') {
            edit_user();
        } elseif ($action === 'delete') {
            delete_user();
        } else {
            json_response(false, '无效的操作');
        }
        break;
    default:
        json_response(false, '无效的请求方法');
}

function get_users_list() {
    $users = get_users();
    $result = [];
    foreach ($users as $user) {
        $result[] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'phone' => $user['phone'],
            'created_at' => $user['created_at']
        ];
    }
    json_response(true, '获取成功', $result);
}

function add_user() {
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

    json_response(true, '添加成功');
}

function edit_user() {
    $id = intval($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');

    if ($id <= 0) {
        json_response(false, '无效的用户ID');
    }

    $users = get_users();
    $user_index = -1;
    $target_user = null;

    foreach ($users as $index => $user) {
        if ($user['id'] === $id) {
            $user_index = $index;
            $target_user = $user;
            break;
        }
    }

    if ($user_index === -1) {
        json_response(false, '用户不存在');
    }

    $result = validate_username($username);
    if ($result !== true) {
        json_response(false, $result);
    }

    if (!empty($password)) {
        $result = validate_password($password);
        if ($result !== true) {
            json_response(false, $result);
        }
    }

    $result = validate_phone($phone);
    if ($result !== true) {
        json_response(false, $result);
    }

    foreach ($users as $index => $user) {
        if ($user['id'] !== $id) {
            if ($user['username'] === $username) {
                json_response(false, '用户名已存在');
            }
            if ($user['phone'] === $phone) {
                json_response(false, '手机号已被注册');
            }
        }
    }

    $users[$user_index]['username'] = $username;
    $users[$user_index]['phone'] = $phone;

    if (!empty($password)) {
        $users[$user_index]['password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    save_users($users);

    json_response(true, '修改成功');
}

function delete_user() {
    $id = intval($_POST['id'] ?? 0);

    if ($id <= 0) {
        json_response(false, '无效的用户ID');
    }

    $users = get_users();
    $new_users = [];

    foreach ($users as $user) {
        if ($user['id'] !== $id) {
            $new_users[] = $user;
        }
    }

    if (count($new_users) === count($users)) {
        json_response(false, '用户不存在');
    }

    save_users($new_users);

    json_response(true, '删除成功');
}
