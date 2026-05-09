<?php
session_start();

define('DATA_PATH', __DIR__ . '/data/');
define('USERS_FILE', DATA_PATH . 'users.json');
define('ARTICLES_FILE', DATA_PATH . 'articles.json');
define('CATEGORIES_FILE', DATA_PATH . 'categories.json');
define('ROLES_FILE', DATA_PATH . 'roles.json');
define('PERMISSIONS_FILE', DATA_PATH . 'permissions.json');

if (!is_dir(DATA_PATH)) {
    mkdir(DATA_PATH, 0755, true);
}

if (!file_exists(USERS_FILE)) {
    file_put_contents(USERS_FILE, json_encode([]));
}

if (!file_exists(ARTICLES_FILE)) {
    file_put_contents(ARTICLES_FILE, json_encode([]));
}

if (!file_exists(CATEGORIES_FILE)) {
    file_put_contents(CATEGORIES_FILE, json_encode([]));
}

if (!file_exists(ROLES_FILE)) {
    $default_roles = [
        [
            'id' => 1,
            'name' => '超级管理员',
            'description' => '拥有系统所有权限',
            'data_scope' => 'all',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ];
    file_put_contents(ROLES_FILE, json_encode($default_roles, JSON_PRETTY_PRINT));
}

if (!file_exists(PERMISSIONS_FILE)) {
    $default_permissions = [
        [
            'id' => 1,
            'role_id' => 1,
            'menu_permissions' => ['welcome', 'users', 'articles', 'categories', 'roles', 'permissions'],
            'button_permissions' => [
                'users' => ['add', 'edit', 'delete'],
                'articles' => ['add', 'edit', 'delete'],
                'categories' => ['add', 'edit', 'delete'],
                'roles' => ['add', 'edit', 'delete'],
                'permissions' => ['edit']
            ],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ];
    file_put_contents(PERMISSIONS_FILE, json_encode($default_permissions, JSON_PRETTY_PRINT));
}

if (!function_exists('json_response')) {
    function json_response($success, $message, $data = null) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
}

if (!function_exists('get_users')) {
    function get_users() {
        $content = file_get_contents(USERS_FILE);
        return json_decode($content, true) ?: [];
    }
}

if (!function_exists('save_users')) {
    function save_users($users) {
        file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
    }
}

if (!function_exists('get_articles')) {
    function get_articles() {
        $content = file_get_contents(ARTICLES_FILE);
        return json_decode($content, true) ?: [];
    }
}

if (!function_exists('save_articles')) {
    function save_articles($articles) {
        file_put_contents(ARTICLES_FILE, json_encode($articles, JSON_PRETTY_PRINT));
    }
}

if (!function_exists('get_categories')) {
    function get_categories() {
        $content = file_get_contents(CATEGORIES_FILE);
        return json_decode($content, true) ?: [];
    }
}

if (!function_exists('save_categories')) {
    function save_categories($categories) {
        file_put_contents(CATEGORIES_FILE, json_encode($categories, JSON_PRETTY_PRINT));
    }
}

if (!function_exists('validate_category_name')) {
    function validate_category_name($name, $exclude_id = null) {
        if (empty($name)) {
            return '分类名称不能为空';
        }
        if (strlen($name) > 50) {
            return '分类名称不能超过50个字符';
        }
        $categories = get_categories();
        foreach ($categories as $category) {
            if ($exclude_id !== null && $category['id'] === $exclude_id) {
                continue;
            }
            if ($category['name'] === $name) {
                return '分类名称已存在';
            }
        }
        return true;
    }
}

if (!function_exists('validate_username')) {
    function validate_username($username) {
        if (empty($username)) {
            return '用户名不能为空';
        }
        if (strlen($username) < 3 || strlen($username) > 20) {
            return '用户名长度必须为3-20个字符';
        }
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $username)) {
            return '用户名必须以字母开头，只能包含字母、数字和下划线';
        }
        if (!preg_match('/[a-zA-Z]/', $username) || !preg_match('/[0-9]/', $username) || !preg_match('/_/', $username)) {
            return '用户名必须包含字母、数字和下划线三者组合';
        }
        return true;
    }
}

if (!function_exists('validate_password')) {
    function validate_password($password) {
        if (empty($password)) {
            return '密码不能为空';
        }
        if (strlen($password) < 6 || strlen($password) > 20) {
            return '密码长度必须为6-20个字符';
        }
        if (!preg_match('/^[a-zA-Z]/', $password)) {
            return '密码必须以字母开头';
        }
        if (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return '密码必须包含大小写字母和数字';
        }
        return true;
    }
}

if (!function_exists('validate_phone')) {
    function validate_phone($phone) {
        if (empty($phone)) {
            return '手机号不能为空';
        }
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return '请输入有效的手机号';
        }
        return true;
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user']);
    }
}

function app_get_current_user() {
    return $_SESSION['user'] ?? null;
}

if (!function_exists('require_login')) {
    function require_login() {
        if (!is_logged_in()) {
            header('Location: login.html');
            exit;
        }
    }
}

if (!function_exists('get_roles')) {
    function get_roles() {
        $content = file_get_contents(ROLES_FILE);
        return json_decode($content, true) ?: [];
    }
}

if (!function_exists('save_roles')) {
    function save_roles($roles) {
        file_put_contents(ROLES_FILE, json_encode($roles, JSON_PRETTY_PRINT));
    }
}

if (!function_exists('get_permissions')) {
    function get_permissions() {
        $content = file_get_contents(PERMISSIONS_FILE);
        return json_decode($content, true) ?: [];
    }
}

if (!function_exists('save_permissions')) {
    function save_permissions($permissions) {
        file_put_contents(PERMISSIONS_FILE, json_encode($permissions, JSON_PRETTY_PRINT));
    }
}

if (!function_exists('get_role_by_id')) {
    function get_role_by_id($id) {
        $roles = get_roles();
        foreach ($roles as $role) {
            if ($role['id'] == $id) {
                return $role;
            }
        }
        return null;
    }
}

if (!function_exists('get_permissions_by_role_id')) {
    function get_permissions_by_role_id($role_id) {
        $permissions = get_permissions();
        foreach ($permissions as $permission) {
            if ($permission['role_id'] == $role_id) {
                return $permission;
            }
        }
        return null;
    }
}

if (!function_exists('get_user_role')) {
    function get_user_role($user) {
        if (isset($user['role_id']) && $user['role_id'] > 0) {
            return get_role_by_id($user['role_id']);
        }
        return null;
    }
}

if (!function_exists('get_user_permissions')) {
    function get_user_permissions($user) {
        $role = get_user_role($user);
        if ($role) {
            return get_permissions_by_role_id($role['id']);
        }
        return null;
    }
}

if (!function_exists('has_menu_permission')) {
    function has_menu_permission($user, $menu) {
        $permissions = get_user_permissions($user);
        if (!$permissions) {
            return false;
        }
        return in_array($menu, $permissions['menu_permissions']);
    }
}

if (!function_exists('has_button_permission')) {
    function has_button_permission($user, $module, $action) {
        $permissions = get_user_permissions($user);
        if (!$permissions || !isset($permissions['button_permissions'][$module])) {
            return false;
        }
        return in_array($action, $permissions['button_permissions'][$module]);
    }
}

if (!function_exists('get_system_menus')) {
    function get_system_menus() {
        return [
            [
                'id' => 'welcome',
                'name' => '欢迎页',
                'icon' => '🏠',
                'buttons' => []
            ],
            [
                'id' => 'users',
                'name' => '用户管理',
                'icon' => '👥',
                'buttons' => ['add', 'edit', 'delete']
            ],
            [
                'id' => 'articles',
                'name' => '文章管理',
                'icon' => '📝',
                'buttons' => ['add', 'edit', 'delete']
            ],
            [
                'id' => 'categories',
                'name' => '文章分类管理',
                'icon' => '📂',
                'buttons' => ['add', 'edit', 'delete']
            ],
            [
                'id' => 'roles',
                'name' => '角色管理',
                'icon' => '👤',
                'buttons' => ['add', 'edit', 'delete']
            ],
            [
                'id' => 'permissions',
                'name' => '权限配置',
                'icon' => '🔒',
                'buttons' => ['edit']
            ]
        ];
    }
}

if (!function_exists('get_data_scopes')) {
    function get_data_scopes() {
        return [
            ['value' => 'all', 'name' => '全部数据'],
            ['value' => 'own', 'name' => '仅本人数据']
        ];
    }
}
