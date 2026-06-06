<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = get_input();

if ($method === 'GET') {
    require_auth();
    $userId = $_GET['user_id'] ?? null;
    $date = $_GET['date'] ?? null;
    $logId = $_GET['id'] ?? null;

    if ($logId) {
        $log = DB::find('logs', $logId);
        if (!$log) {
            json_response(['error' => '日志不存在'], 404);
        }
        json_response($log);
    }

    $logs = DB::read('logs');
    $users = DB::read('users');
    $userMap = [];
    foreach ($users as $u) {
        $userMap[$u['id']] = ['id' => $u['id'], 'name' => $u['name']];
    }

    $result = [];
    foreach ($logs as $log) {
        if ($userId && $log['user_id'] != $userId) continue;
        if ($date && $log['date'] != $date) continue;

        if (isset($userMap[$log['user_id']])) {
            $log['user'] = $userMap[$log['user_id']];
        }
        if (isset($userMap[$log['reliever_id'] ?? 0])) {
            $log['reliever'] = $userMap[$log['reliever_id']];
        }
        $result[] = $log;
    }

    usort($result, function($a, $b) {
        return strcmp($b['created_at'], $a['created_at']);
    });

    json_response($result);
}

if ($method === 'POST' && $_GET['action'] === 'upload_image') {
    require_auth();

    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        json_response(['error' => '文件上传失败'], 400);
    }

    $file = $_FILES['image'];
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        json_response(['error' => '不支持的文件格式，仅支持图片文件'], 400);
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        json_response(['error' => '文件大小不能超过10MB'], 400);
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $filename = date('YmdHis') . '_' . uniqid() . '.' . $ext;
    $filepath = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        json_response(['error' => '文件保存失败'], 500);
    }

    $url = 'api/download.php?file=' . urlencode($filename);
    json_response(['url' => $url, 'filename' => $filename, 'original_name' => $file['name']]);
}

if ($method === 'POST') {
    require_auth();
    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');

    $schedules = DB::read('schedules');
    $hasSchedule = false;
    foreach ($schedules as $s) {
        if ($s['date'] == $today && $s['user_id'] == $userId) {
            $hasSchedule = true;
            break;
        }
    }
    if (!$hasSchedule) {
        json_response(['error' => '您今天没有排班，无法提交交接班日志'], 400);
    }

    $content = trim($input['content'] ?? '');
    $handoverContent = trim($input['handover_content'] ?? '');
    $relieverId = $input['reliever_id'] ?? 0;
    $images = $input['images'] ?? [];

    if (empty($content)) {
        json_response(['error' => '请填写值班日志内容'], 400);
    }

    $log = DB::insert('logs', [
        'user_id' => $userId,
        'date' => $today,
        'content' => $content,
        'handover_content' => $handoverContent,
        'reliever_id' => $relieverId,
        'images' => is_array($images) ? json_encode($images) : '[]'
    ]);

    json_response(['message' => '交接班日志提交成功', 'log' => $log], 201);
}

if ($method === 'PUT') {
    require_auth();
    $id = $_GET['id'] ?? 0;
    if (!$id) {
        json_response(['error' => '参数错误'], 400);
    }

    $log = DB::find('logs', $id);
    if (!$log) {
        json_response(['error' => '日志不存在'], 404);
    }

    if (!is_admin() && $log['user_id'] != $_SESSION['user_id']) {
        json_response(['error' => '无权限修改此日志'], 403);
    }

    $updates = [];
    if (isset($input['content'])) $updates['content'] = trim($input['content']);
    if (isset($input['handover_content'])) $updates['handover_content'] = trim($input['handover_content']);
    if (isset($input['reliever_id'])) $updates['reliever_id'] = $input['reliever_id'];
    if (isset($input['images'])) $updates['images'] = is_array($input['images']) ? json_encode($input['images']) : '[]';

    if (empty($updates)) {
        json_response(['error' => '没有需要更新的内容'], 400);
    }

    $log = DB::update('logs', $id, $updates);
    json_response(['message' => '日志更新成功', 'log' => $log]);
}

if ($method === 'DELETE') {
    require_admin();
    $id = $_GET['id'] ?? 0;
    if (!$id) {
        json_response(['error' => '参数错误'], 400);
    }

    if (DB::delete('logs', $id)) {
        json_response(['message' => '删除成功']);
    } else {
        json_response(['error' => '日志不存在'], 404);
    }
}

json_response(['error' => '无效请求'], 404);
