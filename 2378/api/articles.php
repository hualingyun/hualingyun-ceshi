<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    json_response(false, '请先登录');
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        get_articles_list();
        break;
    case 'POST':
        $action = $_POST['action'] ?? '';
        if ($action === 'add') {
            add_article();
        } elseif ($action === 'edit') {
            edit_article();
        } elseif ($action === 'delete') {
            delete_article();
        } else {
            json_response(false, '无效的操作');
        }
        break;
    default:
        json_response(false, '无效的请求方法');
}

function get_articles_list() {
    $articles = get_articles();
    $users = get_users();
    $user_map = [];
    foreach ($users as $user) {
        $user_map[$user['id']] = $user['username'];
    }

    $result = [];
    foreach ($articles as $article) {
        $result[] = [
            'id' => $article['id'],
            'title' => $article['title'],
            'content' => $article['content'],
            'author_id' => $article['author_id'],
            'author_name' => $user_map[$article['author_id']] ?? '未知',
            'created_at' => $article['created_at'],
            'updated_at' => $article['updated_at']
        ];
    }
    json_response(true, '获取成功', $result);
}

function add_article() {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (empty($title)) {
        json_response(false, '文章标题不能为空');
    }
    if (strlen($title) > 100) {
        json_response(false, '文章标题不能超过100个字符');
    }
    if (empty($content)) {
        json_response(false, '文章内容不能为空');
    }

    $current_user = app_get_current_user();
    $articles = get_articles();

    $new_article = [
        'id' => empty($articles) ? 1 : max(array_column($articles, 'id')) + 1,
        'title' => $title,
        'content' => $content,
        'author_id' => $current_user['id'],
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $articles[] = $new_article;
    save_articles($articles);

    json_response(true, '添加成功');
}

function edit_article() {
    $id = intval($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($id <= 0) {
        json_response(false, '无效的文章ID');
    }

    if (empty($title)) {
        json_response(false, '文章标题不能为空');
    }
    if (strlen($title) > 100) {
        json_response(false, '文章标题不能超过100个字符');
    }
    if (empty($content)) {
        json_response(false, '文章内容不能为空');
    }

    $articles = get_articles();
    $article_index = -1;

    foreach ($articles as $index => $article) {
        if ($article['id'] === $id) {
            $article_index = $index;
            break;
        }
    }

    if ($article_index === -1) {
        json_response(false, '文章不存在');
    }

    $articles[$article_index]['title'] = $title;
    $articles[$article_index]['content'] = $content;
    $articles[$article_index]['updated_at'] = date('Y-m-d H:i:s');

    save_articles($articles);

    json_response(true, '修改成功');
}

function delete_article() {
    $id = intval($_POST['id'] ?? 0);

    if ($id <= 0) {
        json_response(false, '无效的文章ID');
    }

    $articles = get_articles();
    $new_articles = [];

    foreach ($articles as $article) {
        if ($article['id'] !== $id) {
            $new_articles[] = $article;
        }
    }

    if (count($new_articles) === count($articles)) {
        json_response(false, '文章不存在');
    }

    save_articles($new_articles);

    json_response(true, '删除成功');
}
