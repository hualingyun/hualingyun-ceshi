<?php
require_once __DIR__ . '/../config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    json_response(['message' => 'OK']);
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        handle_get();
        break;
    case 'POST':
        handle_post($input);
        break;
    case 'PUT':
        handle_put($input);
        break;
    case 'DELETE':
        handle_delete();
        break;
    default:
        json_response(['error' => 'Method not allowed'], 405);
}

function handle_get() {
    $categories = get_categories();
    $blogs = get_blogs();
    
    $used_categories = array_count_values(array_column($blogs, 'category_id'));
    
    foreach ($categories as &$cat) {
        $cat['blog_count'] = $used_categories[$cat['id']] ?? 0;
    }
    
    json_response($categories);
}

function handle_post($input) {
    if (!isset($input['name']) || trim($input['name']) === '') {
        json_response(['error' => 'Category name is required'], 400);
    }
    
    $categories = get_categories();
    
    foreach ($categories as $cat) {
        if ($cat['name'] === $input['name']) {
            json_response(['error' => 'Category name already exists'], 400);
        }
    }
    
    $new_category = [
        'id' => generate_id($categories),
        'name' => $input['name']
    ];
    
    $categories[] = $new_category;
    save_categories($categories);
    
    json_response(['message' => 'Category created successfully', 'id' => $new_category['id']], 201);
}

function handle_put($input) {
    if (!isset($input['id']) || !isset($input['name']) || trim($input['name']) === '') {
        json_response(['error' => 'Category ID and name are required'], 400);
    }
    
    $categories = get_categories();
    
    foreach ($categories as $cat) {
        if ($cat['id'] != $input['id'] && $cat['name'] === $input['name']) {
            json_response(['error' => 'Category name already exists'], 400);
        }
    }
    
    $found = false;
    foreach ($categories as &$cat) {
        if ($cat['id'] == $input['id']) {
            $found = true;
            $cat['name'] = $input['name'];
            break;
        }
    }
    
    if (!$found) {
        json_response(['error' => 'Category not found'], 404);
    }
    
    save_categories($categories);
    json_response(['message' => 'Category updated successfully']);
}

function handle_delete() {
    if (!isset($_GET['id'])) {
        json_response(['error' => 'Category ID is required'], 400);
    }
    
    $id = (int)$_GET['id'];
    $categories = get_categories();
    $blogs = get_blogs();
    
    foreach ($blogs as $blog) {
        if ($blog['category_id'] == $id) {
            json_response(['error' => 'Cannot delete category: it is used by some blogs'], 400);
        }
    }
    
    $new_categories = array_filter($categories, function($cat) use ($id) {
        return $cat['id'] != $id;
    });
    
    if (count($new_categories) == count($categories)) {
        json_response(['error' => 'Category not found'], 404);
    }
    
    save_categories(array_values($new_categories));
    json_response(['message' => 'Category deleted successfully']);
}
?>
