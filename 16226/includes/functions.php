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

function hasCompletedAllCourses($userId) {
    $contents = getAllContents();
    if (empty($contents)) {
        return false;
    }
    $progress = getUserProgress($userId);
    $completedCount = 0;
    foreach ($contents as $content) {
        if (in_array($content['id'], $progress)) {
            $completedCount++;
        }
    }
    return $completedCount === count($contents);
}

function getExamQuestions() {
    $examData = readJson(EXAMS_FILE);
    return $examData['questions'] ?? [];
}

function getExamQuestionsForStudent() {
    $questions = getExamQuestions();
    $result = [];
    foreach ($questions as $q) {
        $studentQuestion = [
            'id' => $q['id'],
            'type' => $q['type'],
            'question' => $q['question'],
            'options' => $q['options'] ?? [],
            'points' => $q['points'] ?? 10
        ];
        $result[] = $studentQuestion;
    }
    return $result;
}

function getExamQuestionById($questionId) {
    $questions = getExamQuestions();
    foreach ($questions as $q) {
        if ($q['id'] == $questionId) {
            return $q;
        }
    }
    return null;
}

function addExamQuestion($type, $question, $options, $correctAnswer, $explanation, $points = 10) {
    $examData = readJson(EXAMS_FILE);
    $questions = $examData['questions'] ?? [];
    
    $newQuestion = [
        'id' => getNextId($questions),
        'type' => $type,
        'question' => $question,
        'options' => $options,
        'correct_answer' => $correctAnswer,
        'explanation' => $explanation,
        'points' => intval($points),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $questions[] = $newQuestion;
    $examData['questions'] = $questions;
    $examData['updated_at'] = date('Y-m-d H:i:s');
    writeJson(EXAMS_FILE, $examData);
    
    return ['success' => true, 'message' => '题目添加成功', 'id' => $newQuestion['id']];
}

function updateExamQuestion($questionId, $type, $question, $options, $correctAnswer, $explanation, $points = 10) {
    $examData = readJson(EXAMS_FILE);
    $questions = $examData['questions'] ?? [];
    
    foreach ($questions as &$q) {
        if ($q['id'] == $questionId) {
            $q['type'] = $type;
            $q['question'] = $question;
            $q['options'] = $options;
            $q['correct_answer'] = $correctAnswer;
            $q['explanation'] = $explanation;
            $q['points'] = intval($points);
            $q['updated_at'] = date('Y-m-d H:i:s');
            
            $examData['questions'] = $questions;
            $examData['updated_at'] = date('Y-m-d H:i:s');
            writeJson(EXAMS_FILE, $examData);
            return ['success' => true, 'message' => '题目更新成功'];
        }
    }
    return ['success' => false, 'message' => '题目不存在'];
}

function deleteExamQuestion($questionId) {
    $examData = readJson(EXAMS_FILE);
    $questions = $examData['questions'] ?? [];
    
    foreach ($questions as $index => $q) {
        if ($q['id'] == $questionId) {
            array_splice($questions, $index, 1);
            $examData['questions'] = $questions;
            $examData['updated_at'] = date('Y-m-d H:i:s');
            writeJson(EXAMS_FILE, $examData);
            return ['success' => true, 'message' => '题目删除成功'];
        }
    }
    return ['success' => false, 'message' => '题目不存在'];
}

function compareAnswers($type, $userAnswer, $correctAnswer) {
    if ($type === 'multiple') {
        if (!is_array($userAnswer)) {
            $userAnswer = [$userAnswer];
        }
        if (!is_array($correctAnswer)) {
            $correctAnswer = [$correctAnswer];
        }
        sort($userAnswer);
        sort($correctAnswer);
        return $userAnswer === $correctAnswer;
    }
    return $userAnswer === $correctAnswer;
}

function submitExam($userId, $username, $userAnswers) {
    $questions = getExamQuestions();
    if (empty($questions)) {
        return ['success' => false, 'message' => '暂无考试题目'];
    }
    
    $results = [];
    $totalScore = 0;
    $wrongQuestions = [];
    
    foreach ($questions as $q) {
        $userAnswer = $userAnswers[$q['id']] ?? null;
        $isCorrect = compareAnswers($q['type'], $userAnswer, $q['correct_answer']);
        
        $questionResult = [
            'question_id' => $q['id'],
            'type' => $q['type'],
            'question' => $q['question'],
            'options' => $q['options'],
            'user_answer' => $userAnswer,
            'correct_answer' => $q['correct_answer'],
            'explanation' => $q['explanation'],
            'is_correct' => $isCorrect,
            'points' => $q['points']
        ];
        
        if ($isCorrect) {
            $totalScore += $q['points'];
        } else {
            $wrongQuestions[] = $questionResult;
        }
        $results[] = $questionResult;
    }
    
    $totalPoints = 0;
    foreach ($questions as $q) {
        $totalPoints += $q['points'];
    }
    
    $examResult = [
        'id' => getNextId(getAllExamResults()),
        'user_id' => $userId,
        'username' => $username,
        'total_score' => $totalScore,
        'total_points' => $totalPoints,
        'question_count' => count($questions),
        'correct_count' => count($questions) - count($wrongQuestions),
        'wrong_count' => count($wrongQuestions),
        'results' => $results,
        'wrong_questions' => $wrongQuestions,
        'submitted_at' => date('Y-m-d H:i:s')
    ];
    
    $allResults = getAllExamResults();
    $allResults[] = $examResult;
    writeJson(EXAM_RESULTS_FILE, $allResults);
    
    return [
        'success' => true,
        'result' => $examResult
    ];
}

function getAllExamResults() {
    return readJson(EXAM_RESULTS_FILE);
}

function getUserExamResults($userId) {
    $allResults = getAllExamResults();
    $userResults = [];
    foreach ($allResults as $result) {
        if ($result['user_id'] == $userId) {
            $userResults[] = $result;
        }
    }
    usort($userResults, function($a, $b) {
        return strtotime($b['submitted_at']) - strtotime($a['submitted_at']);
    });
    return $userResults;
}

function getLatestExamResult($userId) {
    $results = getUserExamResults($userId);
    return $results[0] ?? null;
}

function getExamResultById($resultId) {
    $allResults = getAllExamResults();
    foreach ($allResults as $result) {
        if ($result['id'] == $resultId) {
            return $result;
        }
    }
    return null;
}

function formatAnswer($type, $answer, $options) {
    if ($answer === null) {
        return '未作答';
    }
    
    if ($type === 'judge') {
        return $answer === 'true' ? '正确' : '错误';
    }
    
    if ($type === 'multiple') {
        if (!is_array($answer)) {
            $answer = [$answer];
        }
        $formatted = [];
        foreach ($answer as $ans) {
            $optionIndex = array_search($ans, array_keys($options));
            if ($optionIndex !== false) {
                $formatted[] = chr(65 + $optionIndex) . '. ' . ($options[$ans] ?? $ans);
            } else {
                $formatted[] = $ans;
            }
        }
        return implode('; ', $formatted);
    }
    
    $optionIndex = array_search($answer, array_keys($options));
    if ($optionIndex !== false) {
        return chr(65 + $optionIndex) . '. ' . ($options[$answer] ?? $answer);
    }
    return $answer;
}

function getOptionLabel($key) {
    $labels = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
    $keys = array_keys(['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5, 'G' => 6, 'H' => 7]);
    $index = array_search($key, $keys);
    return $index !== false ? $labels[$index] : $key;
}
