<?php
define('DATA_DIR', __DIR__ . '/../data');
define('USERS_FILE', DATA_DIR . '/users.json');
define('CONTENTS_FILE', DATA_DIR . '/contents.json');
define('PROGRESS_FILE', DATA_DIR . '/progress.json');
define('COMMENTS_FILE', DATA_DIR . '/comments.json');
define('EXAMS_FILE', DATA_DIR . '/exams.json');
define('EXAM_RESULTS_FILE', DATA_DIR . '/exam_results.json');

function ensureDataFile($file, $defaultData) {
    if (!file_exists($file)) {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($file, json_encode($defaultData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}

function initializeData() {
    ensureDataFile(USERS_FILE, [
        [
            'id' => 1,
            'username' => 'admin',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
    ensureDataFile(CONTENTS_FILE, []);
    ensureDataFile(PROGRESS_FILE, []);
    ensureDataFile(COMMENTS_FILE, []);
    ensureDataFile(EXAMS_FILE, [
        'questions' => [],
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    ensureDataFile(EXAM_RESULTS_FILE, []);
}

initializeData();

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function readJson($file) {
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

function writeJson($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function getNextId($items) {
    if (empty($items)) {
        return 1;
    }
    $maxId = 0;
    foreach ($items as $item) {
        if ($item['id'] > $maxId) {
            $maxId = $item['id'];
        }
    }
    return $maxId + 1;
}

session_start();
