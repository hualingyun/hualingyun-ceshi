<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    json_response(false, '请先登录');
}

$current_user = app_get_current_user();
if (!has_menu_permission($current_user, 'categories')) {
    json_response(false, '您没有权限访问此模块');
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        get_categories_list();
        break;
    case 'POST':
        $action = $_POST['action'] ?? '';
        if ($action === 'add') {
            if (!has_button_permission($current_user, 'categories', 'add')) {
                json_response(false, '您没有权限添加分类');
            }
            add_category();
        } elseif ($action === 'edit') {
            if (!has_button_permission($current_user, 'categories', 'edit')) {
                json_response(false, '您没有权限编辑分类');
            }
            edit_category();
        } elseif ($action === 'delete') {
            if (!has_button_permission($current_user, 'categories', 'delete')) {
                json_response(false, '您没有权限删除分类');
            }
            delete_category();
        } else {
            json_response(false, '无效的操作');
        }
        break;
    default:
        json_response(false, '无效的请求方法');
}

function get_categories_list() {
    $categories = get_categories();
    json_response(true, '获取成功', $categories);
}

function add_category() {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $validation = validate_category_name($name);
    if ($validation !== true) {
        json_response(false, $validation);
    }

    $categories = get_categories();

    $new_category = [
        'id' => empty($categories) ? 1 : max(array_column($categories, 'id')) + 1,
        'name' => $name,
        'description' => $description,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $categories[] = $new_category;
    save_categories($categories);

    json_response(true, '添加成功');
}

function edit_category() {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($id <= 0) {
        json_response(false, '无效的分类ID');
    }

    $validation = validate_category_name($name, $id);
    if ($validation !== true) {
        json_response(false, $validation);
    }

    $categories = get_categories();
    $category_index = -1;

    foreach ($categories as $index => $category) {
        if ($category['id'] === $id) {
            $category_index = $index;
            break;
        }
    }

    if ($category_index === -1) {
        json_response(false, '分类不存在');
    }

    $categories[$category_index]['name'] = $name;
    $categories[$category_index]['description'] = $description;
    $categories[$category_index]['updated_at'] = date('Y-m-d H:i:s');

    save_categories($categories);

    json_response(true, '修改成功');
}

function delete_category() {
    $id = intval($_POST['id'] ?? 0);

    if ($id <= 0) {
        json_response(false, '无效的分类ID');
    }

    $articles = get_articles();
    foreach ($articles as $article) {
        if (isset($article['category_id']) && $article['category_id'] === $id) {
            json_response(false, '该分类下还有文章，无法删除');
        }
    }

    $categories = get_categories();
    $new_categories = [];

    foreach ($categories as $category) {
        if ($category['id'] !== $id) {
            $new_categories[] = $category;
        }
    }

    if (count($new_categories) === count($categories)) {
        json_response(false, '分类不存在');
    }

    save_categories($new_categories);

    json_response(true, '删除成功');
}
