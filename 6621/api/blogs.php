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
    $blogs = get_blogs();
    $categories = get_categories();
    
    $category_map = [];
    foreach ($categories as $cat) {
        $category_map[$cat['id']] = $cat['name'];
    }
    
    foreach ($blogs as &$blog) {
        $blog['category_name'] = $category_map[$blog['category_id']] ?? '未分类';
    }
    
    usort($blogs, function($a, $b) {
        if ($a['is_top'] != $b['is_top']) {
            return $b['is_top'] - $a['is_top'];
        }
        return strcmp($b['created_at'], $a['created_at']);
    });
    
    json_response($blogs);
}

function handle_post($input) {
    if (!isset($input['title']) || !isset($input['category_id']) || !isset($input['content'])) {
        json_response(['error' => 'Missing required fields'], 400);
    }
    
    $blogs = get_blogs();
    $categories = get_categories();
    
    $category_ids = array_column($categories, 'id');
    if (!in_array((int)$input['category_id'], $category_ids)) {
        json_response(['error' => 'Invalid category ID'], 400);
    }
    
    $new_blog = [
        'id' => generate_id($blogs),
        'title' => $input['title'],
        'category_id' => (int)$input['category_id'],
        'content' => $input['content'],
        'is_top' => isset($input['is_top']) ? (bool)$input['is_top'] : false,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $blogs[] = $new_blog;
    save_blogs($blogs);
    
    json_response(['message' => 'Blog created successfully', 'id' => $new_blog['id']], 201);
}

function handle_put($input) {
    if (!isset($input['id'])) {
        json_response(['error' => 'Blog ID is required'], 400);
    }
    
    $blogs = get_blogs();
    $categories = get_categories();
    
    $found = false;
    foreach ($blogs as &$blog) {
        if ($blog['id'] == $input['id']) {
            $found = true;
            
            if (isset($input['title'])) {
                $blog['title'] = $input['title'];
            }
            if (isset($input['category_id'])) {
                $category_ids = array_column($categories, 'id');
                if (!in_array((int)$input['category_id'], $category_ids)) {
                    json_response(['error' => 'Invalid category ID'], 400);
                }
                $blog['category_id'] = (int)$input['category_id'];
            }
            if (isset($input['content'])) {
                $blog['content'] = $input['content'];
            }
            if (isset($input['is_top'])) {
                $blog['is_top'] = (bool)$input['is_top'];
            }
            $blog['updated_at'] = date('Y-m-d H:i:s');
            break;
        }
    }
    
    if (!$found) {
        json_response(['error' => 'Blog not found'], 404);
    }
    
    save_blogs($blogs);
    json_response(['message' => 'Blog updated successfully']);
}

function handle_delete() {
    if (!isset($_GET['id'])) {
        json_response(['error' => 'Blog ID is required'], 400);
    }
    
    $id = (int)$_GET['id'];
    $blogs = get_blogs();
    
    $new_blogs = array_filter($blogs, function($blog) use ($id) {
        return $blog['id'] != $id;
    });
    
    if (count($new_blogs) == count($blogs)) {
        json_response(['error' => 'Blog not found'], 404);
    }
    
    save_blogs(array_values($new_blogs));
    json_response(['message' => 'Blog deleted successfully']);
}
?>
