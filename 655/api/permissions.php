<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    json_response(false, '请先登录');
}

$current_user = app_get_current_user();
if (!has_menu_permission($current_user, 'permissions')) {
    json_response(false, '您没有权限访问此模块');
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['role_id'])) {
            get_role_permissions();
        } else {
            get_permissions_list();
        }
        break;
    case 'POST':
        $action = $_POST['action'] ?? '';
        if ($action === 'update') {
            if (!has_button_permission($current_user, 'permissions', 'edit')) {
                json_response(false, '您没有权限修改权限配置');
            }
            update_permissions();
        } else {
            json_response(false, '无效的操作');
        }
        break;
    default:
        json_response(false, '无效的请求方法');
}

function get_permissions_list() {
    $roles = get_roles();
    $permissions = get_permissions();
    $system_menus = get_system_menus();
    
    $result = [];
    foreach ($roles as $role) {
        $permission = null;
        foreach ($permissions as $p) {
            if ($p['role_id'] == $role['id']) {
                $permission = $p;
                break;
            }
        }
        
        $result[] = [
            'role_id' => $role['id'],
            'role_name' => $role['name'],
            'menu_permissions' => $permission['menu_permissions'] ?? [],
            'button_permissions' => $permission['button_permissions'] ?? [],
            'system_menus' => $system_menus
        ];
    }
    
    json_response(true, '获取成功', $result);
}

function get_role_permissions() {
    $role_id = intval($_GET['role_id'] ?? 0);
    if ($role_id <= 0) {
        json_response(false, '无效的角色ID');
    }
    
    $role = get_role_by_id($role_id);
    if (!$role) {
        json_response(false, '角色不存在');
    }
    
    $permission = get_permissions_by_role_id($role_id);
    $system_menus = get_system_menus();
    $data_scopes = get_data_scopes();
    
    $result = [
        'role' => $role,
        'menu_permissions' => $permission['menu_permissions'] ?? [],
        'button_permissions' => $permission['button_permissions'] ?? [],
        'system_menus' => $system_menus,
        'data_scopes' => $data_scopes
    ];
    
    json_response(true, '获取成功', $result);
}

function update_permissions() {
    $role_id = intval($_POST['role_id'] ?? 0);
    
    if ($role_id <= 0) {
        json_response(false, '无效的角色ID');
    }
    
    $role = get_role_by_id($role_id);
    if (!$role) {
        json_response(false, '角色不存在');
    }
    
    $menu_permissions = json_decode($_POST['menu_permissions'] ?? '[]', true);
    if (!is_array($menu_permissions)) {
        $menu_permissions = [];
    }
    
    $button_permissions = json_decode($_POST['button_permissions'] ?? '{}', true);
    if (!is_array($button_permissions)) {
        $button_permissions = [];
    }
    
    $system_menus = get_system_menus();
    $valid_menu_ids = array_column($system_menus, 'id');
    
    foreach ($menu_permissions as $menu_id) {
        if (!in_array($menu_id, $valid_menu_ids)) {
            json_response(false, '无效的菜单权限');
        }
    }
    
    foreach ($button_permissions as $module => $actions) {
        if (!in_array($module, $valid_menu_ids)) {
            json_response(false, '无效的按钮权限模块');
        }
        $menu = null;
        foreach ($system_menus as $m) {
            if ($m['id'] === $module) {
                $menu = $m;
                break;
            }
        }
        if ($menu) {
            foreach ($actions as $action) {
                if (!in_array($action, $menu['buttons'])) {
                    json_response(false, "模块 {$module} 中不存在操作 {$action}");
                }
            }
        }
    }
    
    $permissions = get_permissions();
    $permission_index = -1;
    
    foreach ($permissions as $index => $p) {
        if ($p['role_id'] == $role_id) {
            $permission_index = $index;
            break;
        }
    }
    
    if ($permission_index === -1) {
        $new_permission = [
            'id' => empty($permissions) ? 1 : max(array_column($permissions, 'id')) + 1,
            'role_id' => $role_id,
            'menu_permissions' => $menu_permissions,
            'button_permissions' => $button_permissions,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $permissions[] = $new_permission;
    } else {
        $permissions[$permission_index]['menu_permissions'] = $menu_permissions;
        $permissions[$permission_index]['button_permissions'] = $button_permissions;
        $permissions[$permission_index]['updated_at'] = date('Y-m-d H:i:s');
    }
    
    save_permissions($permissions);
    
    json_response(true, '权限配置更新成功');
}
