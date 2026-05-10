<?php
require_once 'config.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    $users = readJson(USERS_FILE);
    foreach ($users as $user) {
        if ($user['id'] == $_SESSION['user_id']) {
            unset($user['password']);
            return $user;
        }
    }
    return null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            jsonResponse(['success' => false, 'message' => '请先登录'], 401);
        }
        header('Location: /login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            jsonResponse(['success' => false, 'message' => '权限不足'], 403);
        }
        header('Location: /login.php');
        exit;
    }
}

function getUserByUsername($username) {
    $users = readJson(USERS_FILE);
    foreach ($users as $user) {
        if ($user['username'] === $username) {
            return $user;
        }
    }
    return null;
}

function registerUser($username, $password, $role = 'student') {
    $users = readJson(USERS_FILE);
    
    if (getUserByUsername($username)) {
        return ['success' => false, 'message' => '用户名已存在'];
    }
    
    $newUser = [
        'id' => getNextId($users),
        'username' => $username,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $users[] = $newUser;
    writeJson(USERS_FILE, $users);
    
    return ['success' => true, 'message' => '注册成功'];
}

function loginUser($username, $password) {
    $user = getUserByUsername($username);
    
    if (!$user) {
        return ['success' => false, 'message' => '用户名不存在'];
    }
    
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => '密码错误'];
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['username'] = $user['username'];
    
    return ['success' => true, 'role' => $user['role']];
}

function logoutUser() {
    session_unset();
    session_destroy();
}

function getAllUsers() {
    $users = readJson(USERS_FILE);
    $result = [];
    foreach ($users as $user) {
        unset($user['password']);
        $result[] = $user;
    }
    return $result;
}

function deleteUser($userId) {
    $users = readJson(USERS_FILE);
    foreach ($users as $index => $user) {
        if ($user['id'] == $userId) {
            if ($user['role'] === 'admin') {
                return ['success' => false, 'message' => '不能删除管理员账号'];
            }
            array_splice($users, $index, 1);
            writeJson(USERS_FILE, $users);
            return ['success' => true, 'message' => '删除成功'];
        }
    }
    return ['success' => false, 'message' => '用户不存在'];
}

function getAllContents() {
    return readJson(CONTENTS_FILE);
}

function getContentById($id) {
    $contents = getAllContents();
    foreach ($contents as $content) {
        if ($content['id'] == $id) {
            return $content;
        }
    }
    return null;
}

function getSortedContents() {
    $contents = getAllContents();
    usort($contents, function($a, $b) {
        return $a['order'] - $b['order'];
    });
    return $contents;
}

function addContent($title, $content, $type = 'article', $order = null) {
    $contents = getAllContents();
    
    if ($order === null) {
        $order = getNextId($contents);
    }
    
    $newContent = [
        'id' => getNextId($contents),
        'title' => $title,
        'content' => $content,
        'type' => $type,
        'order' => intval($order),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $contents[] = $newContent;
    writeJson(CONTENTS_FILE, $contents);
    
    return ['success' => true, 'message' => '添加成功', 'id' => $newContent['id']];
}

function updateContent($id, $title, $content, $type, $order) {
    $contents = getAllContents();
    foreach ($contents as &$item) {
        if ($item['id'] == $id) {
            $item['title'] = $title;
            $item['content'] = $content;
            $item['type'] = $type;
            $item['order'] = intval($order);
            $item['updated_at'] = date('Y-m-d H:i:s');
            writeJson(CONTENTS_FILE, $contents);
            return ['success' => true, 'message' => '更新成功'];
        }
    }
    return ['success' => false, 'message' => '内容不存在'];
}

function deleteContent($id) {
    $contents = getAllContents();
    foreach ($contents as $index => $content) {
        if ($content['id'] == $id) {
            array_splice($contents, $index, 1);
            writeJson(CONTENTS_FILE, $contents);
            return ['success' => true, 'message' => '删除成功'];
        }
    }
    return ['success' => false, 'message' => '内容不存在'];
}

function getUserProgress($userId) {
    $progress = readJson(PROGRESS_FILE);
    if (isset($progress[$userId])) {
        return $progress[$userId];
    }
    return [];
}

function setContentCompleted($userId, $contentId) {
    $progress = readJson(PROGRESS_FILE);
    if (!isset($progress[$userId])) {
        $progress[$userId] = [];
    }
    if (!in_array($contentId, $progress[$userId])) {
        $progress[$userId][] = intval($contentId);
        writeJson(PROGRESS_FILE, $progress);
    }
    return ['success' => true, 'message' => '学习完成'];
}

function canAccessContent($userId, $contentId) {
    $contents = getSortedContents();
    $progress = getUserProgress($userId);
    
    $foundCurrent = false;
    foreach ($contents as $content) {
        if ($content['id'] == $contentId) {
            $foundCurrent = true;
            break;
        }
        if (!in_array($content['id'], $progress)) {
            return false;
        }
    }
    
    return $foundCurrent;
}

function getNextContentId($userId) {
    $contents = getSortedContents();
    $progress = getUserProgress($userId);
    
    foreach ($contents as $content) {
        if (!in_array($content['id'], $progress)) {
            return $content['id'];
        }
    }
    return null;
}

function getPreviousContentId($userId, $currentId) {
    $contents = getSortedContents();
    $previousId = null;
    
    foreach ($contents as $content) {
        if ($content['id'] == $currentId) {
            return $previousId;
        }
        $previousId = $content['id'];
    }
    return null;
}

function getCommentsByContent($contentId) {
    $comments = readJson(COMMENTS_FILE);
    $result = [];
    foreach ($comments as $comment) {
        if ($comment['content_id'] == $contentId) {
            $result[] = $comment;
        }
    }
    usort($result, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    return $result;
}

function addComment($userId, $username, $contentId, $text) {
    $comments = readJson(COMMENTS_FILE);
    
    $newComment = [
        'id' => getNextId($comments),
        'user_id' => $userId,
        'username' => $username,
        'content_id' => intval($contentId),
        'text' => $text,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $comments[] = $newComment;
    writeJson(COMMENTS_FILE, $comments);
    
    return ['success' => true, 'message' => '评论成功', 'comment' => $newComment];
}
