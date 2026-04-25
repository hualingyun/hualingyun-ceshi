<?php
session_start();

define('DATA_PATH', __DIR__ . '/data/');
define('USERS_FILE', DATA_PATH . 'users.json');
define('ARTICLES_FILE', DATA_PATH . 'articles.json');
define('CATEGORIES_FILE', DATA_PATH . 'categories.json');

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
