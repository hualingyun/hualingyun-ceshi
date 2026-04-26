<?php
define('DATA_DIR', __DIR__ . '/data');
define('BLOGS_FILE', DATA_DIR . '/blogs.json');
define('CATEGORIES_FILE', DATA_DIR . '/categories.json');

if (!file_exists(DATA_DIR)) {
    mkdir(DATA_DIR, 0777, true);
}

if (!file_exists(BLOGS_FILE)) {
    file_put_contents(BLOGS_FILE, json_encode([]));
}

if (!file_exists(CATEGORIES_FILE)) {
    file_put_contents(CATEGORIES_FILE, json_encode([
        ['id' => 1, 'name' => '技术'],
        ['id' => 2, 'name' => '生活'],
        ['id' => 3, 'name' => '随笔']
    ]));
}

function json_response($data, $status = 200) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function get_blogs() {
    $content = file_get_contents(BLOGS_FILE);
    return json_decode($content, true) ?: [];
}

function save_blogs($blogs) {
    file_put_contents(BLOGS_FILE, json_encode($blogs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function get_categories() {
    $content = file_get_contents(CATEGORIES_FILE);
    return json_decode($content, true) ?: [];
}

function save_categories($categories) {
    file_put_contents(CATEGORIES_FILE, json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function generate_id($items) {
    if (empty($items)) {
        return 1;
    }
    $max_id = max(array_column($items, 'id'));
    return $max_id + 1;
}
?>
