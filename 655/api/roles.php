<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    json_response(false, '请先登录');
}

$current_user = app_get_current_user();
if (!has_menu_permission($current_user, 'roles')) {
    json_response(false, '您没有权限访问此模块');
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            get_role_detail();
        } else {
            get_roles_list();
        }
        break;
    case 'POST':
        $action = $_POST['action'] ?? '';
        if ($action === 'add') {
            if (!has_button_permission($current_user, 'roles', 'add')) {
                json_response(false, '您没有权限添加角色');
            }
            add_role();
        } elseif ($action === 'edit') {
            if (!has_button_permission($current_user, 'roles', 'edit')) {
                json_response(false, '您没有权限编辑角色');
            }
            edit_role();
        } elseif ($action === 'delete') {
            if (!has_button_permission($current_user, 'roles', 'delete')) {
                json_response(false, '您没有权限删除角色');
            }
            delete_role();
        } else {
            json_response(false, '无效的操作');
        }
        break;
    default:
        json_response(false, '无效的请求方法');
}

function get_roles_list() {
    $roles = get_roles();
    $users = get_users();
    
    $result = [];
    foreach ($roles as $role) {
        $user_count = 0;
        foreach ($users as $user) {
            if (isset($user['role_id']) && $user['role_id'] == $role['id']) {
                $user_count++;
            }
        }
        
        $result[] = [
            'id' => $role['id'],
            'name' => $role['name'],
            'description' => $role['description'] ?? '',
            'data_scope' => $role['data_scope'] ?? 'all',
            'user_count' => $user_count,
            'created_at' => $role['created_at'],
            'updated_at' => $role['updated_at']
        ];
    }
    json_response(true, '获取成功', $result);
}

function get_role_detail() {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        json_response(false, '无效的角色ID');
    }
    
    $role = get_role_by_id($id);
    if (!$role) {
        json_response(false, '角色不存在');
    }
    
    json_response(true, '获取成功', $role);
}

function add_role() {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $data_scope = trim($_POST['data_scope'] ?? 'all');
    
    if (empty($name)) {
        json_response(false, '角色名称不能为空');
    }
    
    if (strlen($name) > 50) {
        json_response(false, '角色名称不能超过50个字符');
    }
    
    $data_scopes = array_column(get_data_scopes(), 'value');
    if (!in_array($data_scope, $data_scopes)) {
        json_response(false, '无效的数据范围');
    }
    
    $roles = get_roles();
    foreach ($roles as $role) {
        if ($role['name'] === $name) {
            json_response(false, '角色名称已存在');
        }
    }
    
    $new_role = [
        'id' => empty($roles) ? 1 : max(array_column($roles, 'id')) + 1,
        'name' => $name,
        'description' => $description,
        'data_scope' => $data_scope,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $roles[] = $new_role;
    save_roles($roles);
    
    $permissions = get_permissions();
    $new_permission = [
        'id' => empty($permissions) ? 1 : max(array_column($permissions, 'id')) + 1,
        'role_id' => $new_role['id'],
        'menu_permissions' => [],
        'button_permissions' => [],
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    $permissions[] = $new_permission;
    save_permissions($permissions);
    
    json_response(true, '添加成功');
}

function edit_role() {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $data_scope = trim($_POST['data_scope'] ?? 'all');
    
    if ($id <= 0) {
        json_response(false, '无效的角色ID');
    }
    
    if (empty($name)) {
        json_response(false, '角色名称不能为空');
    }
    
    if (strlen($name) > 50) {
        json_response(false, '角色名称不能超过50个字符');
    }
    
    $data_scopes = array_column(get_data_scopes(), 'value');
    if (!in_array($data_scope, $data_scopes)) {
        json_response(false, '无效的数据范围');
    }
    
    $roles = get_roles();
    $role_index = -1;
    foreach ($roles as $index => $role) {
        if ($role['id'] === $id) {
            $role_index = $index;
            break;
        }
    }
    
    if ($role_index === -1) {
        json_response(false, '角色不存在');
    }
    
    foreach ($roles as $index => $role) {
        if ($role['id'] !== $id && $role['name'] === $name) {
            json_response(false, '角色名称已存在');
        }
    }
    
    $roles[$role_index]['name'] = $name;
    $roles[$role_index]['description'] = $description;
    $roles[$role_index]['data_scope'] = $data_scope;
    $roles[$role_index]['updated_at'] = date('Y-m-d H:i:s');
    
    save_roles($roles);
    
    json_response(true, '修改成功');
}

function delete_role() {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        json_response(false, '无效的角色ID');
    }
    
    if ($id === 1) {
        json_response(false, '超级管理员角色不能删除');
    }
    
    $users = get_users();
    foreach ($users as $user) {
        if (isset($user['role_id']) && $user['role_id'] == $id) {
            json_response(false, '该角色已被用户使用，无法删除');
        }
    }
    
    $roles = get_roles();
    $new_roles = [];
    $role_exists = false;
    
    foreach ($roles as $role) {
        if ($role['id'] !== $id) {
            $new_roles[] = $role;
        } else {
            $role_exists = true;
        }
    }
    
    if (!$role_exists) {
        json_response(false, '角色不存在');
    }
    
    save_roles($new_roles);
    
    $permissions = get_permissions();
    $new_permissions = [];
    foreach ($permissions as $permission) {
        if ($permission['role_id'] != $id) {
            $new_permissions[] = $permission;
        }
    }
    save_permissions($new_permissions);
    
    json_response(true, '删除成功');
}
