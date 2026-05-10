<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$search = trim($_GET['search'] ?? '');
$page = intval($_GET['page'] ?? 1);
$perPage = 10;

$allUsers = getAllUsers();
$allResults = getAllExamResults();
$allContents = getAllContents();

$students = [];
foreach ($allUsers as $user) {
    if ($user['role'] === 'student') {
        $progress = getUserProgress($user['id']);
        $completedCount = count($progress);
        $totalContents = count($allContents);
        $progressPercent = $totalContents > 0 ? round(($completedCount / $totalContents) * 100, 1) : 0;
        
        $userResults = getUserExamResults($user['id']);
        $latestResult = $userResults[0] ?? null;
        $bestScore = null;
        $avgScore = null;
        
        if (!empty($userResults)) {
            $scores = [];
            foreach ($userResults as $r) {
                $scores[] = $r['total_score'];
                if ($bestScore === null || $r['total_score'] > $bestScore) {
                    $bestScore = $r['total_score'];
                }
            }
            $avgScore = round(array_sum($scores) / count($scores), 1);
        }
        
        $students[] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'created_at' => $user['created_at'],
            'progress_count' => $completedCount,
            'total_contents' => $totalContents,
            'progress_percent' => $progressPercent,
            'has_completed_all' => $totalContents > 0 && $completedCount === $totalContents,
            'exam_count' => count($userResults),
            'latest_result' => $latestResult,
            'best_score' => $bestScore,
            'avg_score' => $avgScore
        ];
    }
}

if ($search) {
    $filteredStudents = [];
    foreach ($students as $s) {
        if (stripos($s['username'], $search) !== false) {
            $filteredStudents[] = $s;
        }
    }
    $students = $filteredStudents;
}

$totalCount = count($students);
$totalPages = ceil($totalCount / $perPage);
if ($page < 1) $page = 1;
if ($page > $totalPages && $totalPages > 0) $page = $totalPages;
$offset = ($page - 1) * $perPage;
$currentStudents = array_slice($students, $offset, $perPage);

$passedCount = 0;
$totalExamTakers = 0;
foreach ($students as $s) {
    if ($s['latest_result'] !== null) {
        $totalExamTakers++;
        if ($s['latest_result']['total_score'] >= $s['latest_result']['total_points'] * 0.6) {
            $passedCount++;
        }
    }
}
$passRate = $totalExamTakers > 0 ? round(($passedCount / $totalExamTakers) * 100, 1) : 0;

function buildUrl($params = []) {
    $query = array_merge($_GET, $params);
    return '?' . http_build_query($query);
}

$viewResult = null;
if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $userId = intval($_GET['user_id']);
    $userResults = getUserExamResults($userId);
    if (!empty($userResults)) {
        $viewResult = [
            'user_id' => $userId,
            'user' => null,
            'results' => $userResults
        ];
        foreach ($allUsers as $u) {
            if ($u['id'] == $userId) {
                $viewResult['user'] = $u;
                break;
            }
        }
    }
}

$viewResultDetail = null;
if (isset($_GET['result_id']) && is_numeric($_GET['result_id'])) {
    $viewResultDetail = getExamResultById(intval($_GET['result_id']));
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>成绩统计 - 培训管理系统</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }
        .stat-card .icon {
            font-size: 36px;
            margin-bottom: 10px;
        }
        .stat-card .num {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        .stat-card .label {
            color: #666;
            font-size: 14px;
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #eee;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 4px;
        }
        .score-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        .score-high {
            background: #d4edda;
            color: #155724;
        }
        .score-low {
            background: #f8d7da;
            color: #721c24;
        }
        .score-none {
            background: #f8f9fa;
            color: #6c757d;
        }
        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .search-bar input {
            flex: 1;
            max-width: 400px;
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .pagination a,
        .pagination span {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .pagination a {
            background: #f0f2f5;
            color: #333;
        }
        .pagination a:hover {
            background: #667eea;
            color: white;
        }
        .pagination .current {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: bold;
        }
        .pagination .disabled {
            background: #f5f5f5;
            color: #ccc;
            cursor: not-allowed;
        }
        .pagination-info {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-top: 10px;
        }
        .back-btn {
            margin-bottom: 20px;
        }
        .result-detail-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }
        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .result-score {
            font-size: 48px;
            font-weight: bold;
            color: #667eea;
        }
        .result-meta {
            display: flex;
            gap: 30px;
            color: #666;
        }
        .wrong-question {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 0 8px 8px 0;
        }
        .correct-question {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 0 8px 8px 0;
        }
        .answer-compare {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 10px;
        }
        .answer-box {
            padding: 10px;
            border-radius: 6px;
        }
        .answer-user {
            background: #fff;
            border: 1px solid #dc3545;
        }
        .answer-correct {
            background: #fff;
            border: 1px solid #28a745;
        }
        .answer-label {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 5px;
        }
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal-backdrop.active {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            width: 90%;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #999;
        }
        .modal-close:hover {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="page-header">
                <h1>成绩统计</h1>
                <p>查看学生学习进度和考试成绩</p>
            </div>
            
            <?php if ($viewResultDetail): ?>
                <div class="back-btn">
                    <a href="results.php<?php echo $search ? '?search=' . urlencode($search) . '&page=' . $page : ''; ?>" class="btn btn-secondary">← 返回列表</a>
                </div>
                
                <div class="result-detail-card">
                    <div class="result-header">
                        <div>
                            <h2 style="margin-bottom: 5px;"><?php echo htmlspecialchars($viewResultDetail['username']); ?> 的考试成绩</h2>
                            <p style="color: #666;">提交时间：<?php echo htmlspecialchars($viewResultDetail['submitted_at']); ?></p>
                        </div>
                        <div class="result-score"><?php echo $viewResultDetail['total_score']; ?><span style="font-size: 18px;">/<?php echo $viewResultDetail['total_points']; ?></span></div>
                    </div>
                    
                    <div class="result-meta" style="margin-bottom: 25px;">
                        <div>正确题数：<strong style="color: #28a745;"><?php echo $viewResultDetail['correct_count']; ?></strong></div>
                        <div>错误题数：<strong style="color: #dc3545;"><?php echo $viewResultDetail['wrong_count']; ?></strong></div>
                        <div>总题数：<strong><?php echo $viewResultDetail['question_count']; ?></strong></div>
                    </div>
                    
                    <h3 style="margin-bottom: 20px;">答题详情</h3>
                    
                    <?php foreach ($viewResultDetail['results'] as $index => $r): ?>
                        <div class="<?php echo $r['is_correct'] ? 'correct-question' : 'wrong-question'; ?>">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <strong>第 <?php echo $index + 1; ?> 题</strong>
                                <span class="badge <?php echo $r['is_correct'] ? 'badge-completed' : 'badge-locked'; ?>">
                                    <?php echo $r['is_correct'] ? '✓ 正确' : '✗ 错误'; ?>
                                </span>
                            </div>
                            <div style="margin-bottom: 10px; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($r['question'])); ?></div>
                            
                            <?php if ($r['type'] !== 'judge' && !empty($r['options'])): ?>
                                <div style="margin-bottom: 10px;">
                                    <?php foreach ($r['options'] as $key => $value): 
                                        $isUserAnswer = $r['type'] === 'multiple' 
                                            ? (is_array($r['user_answer']) && in_array($key, $r['user_answer']))
                                            : $r['user_answer'] === $key;
                                        $isCorrectAnswer = $r['type'] === 'multiple'
                                            ? (is_array($r['correct_answer']) && in_array($key, $r['correct_answer']))
                                            : $r['correct_answer'] === $key;
                                    ?>
                                        <div style="padding: 5px 0; color: #555;">
                                            <span style="font-weight: bold; margin-right: 8px; <?php echo $isCorrectAnswer ? 'color: #28a745;' : ($isUserAnswer ? 'color: #dc3545;' : ''); ?>">
                                                <?php echo $key; ?>.
                                            </span>
                                            <?php echo htmlspecialchars($value); ?>
                                            <?php if ($isCorrectAnswer): ?>
                                                <span style="color: #28a745; margin-left: 8px;">✓</span>
                                            <?php elseif ($isUserAnswer): ?>
                                                <span style="color: #dc3545; margin-left: 8px;">✗</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!$r['is_correct']): ?>
                                <div class="answer-compare">
                                    <div class="answer-box answer-user">
                                        <div class="answer-label" style="color: #dc3545;">你的答案</div>
                                        <div><?php echo htmlspecialchars(formatAnswer($r['type'], $r['user_answer'], $r['options'] ?? [])); ?></div>
                                    </div>
                                    <div class="answer-box answer-correct">
                                        <div class="answer-label" style="color: #28a745;">正确答案</div>
                                        <div><?php echo htmlspecialchars(formatAnswer($r['type'], $r['correct_answer'], $r['options'] ?? [])); ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($r['explanation'])): ?>
                                <div style="margin-top: 10px; padding: 10px; background: #e3f2fd; border-radius: 6px; color: #1565c0;">
                                    <strong>📖 解析：</strong><?php echo nl2br(htmlspecialchars($r['explanation'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
            <?php elseif ($viewResult): ?>
                <div class="back-btn">
                    <a href="results.php<?php echo $search ? '?search=' . urlencode($search) . '&page=' . $page : ''; ?>" class="btn btn-secondary">← 返回列表</a>
                </div>
                
                <div class="card">
                    <h2 style="margin-bottom: 20px;"><?php echo htmlspecialchars($viewResult['user']['username']); ?> 的考试历史</h2>
                    
                    <?php if (empty($viewResult['results'])): ?>
                        <div class="empty-state">
                            <div class="icon">📝</div>
                            <h3>暂无考试记录</h3>
                            <p>该学生尚未参加考试</p>
                        </div>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>序号</th>
                                    <th>得分</th>
                                    <th>总分</th>
                                    <th>正确率</th>
                                    <th>正确/总题数</th>
                                    <th>提交时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($viewResult['results'] as $index => $r): 
                                    $passPercent = $r['total_points'] > 0 ? round(($r['total_score'] / $r['total_points']) * 100, 1) : 0;
                                ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <span class="score-badge <?php echo $passPercent >= 60 ? 'score-high' : 'score-low'; ?>">
                                                <?php echo $r['total_score']; ?>分
                                            </span>
                                        </td>
                                        <td><?php echo $r['total_points']; ?>分</td>
                                        <td><?php echo $passPercent; ?>%</td>
                                        <td><?php echo $r['correct_count']; ?>/<?php echo $r['question_count']; ?></td>
                                        <td><?php echo htmlspecialchars($r['submitted_at']); ?></td>
                                        <td>
                                            <a href="results.php?result_id=<?php echo $r['id']; ?><?php echo $search ? '&search=' . urlencode($search) . '&page=' . $page : ''; ?>" class="btn btn-primary btn-sm">查看详情</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
            <?php else: ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="icon">👥</div>
                        <div class="num"><?php echo count($students); ?></div>
                        <div class="label">学生总数</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">✅</div>
                        <div class="num"><?php echo $passedCount; ?></div>
                        <div class="label">及格人数</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">📊</div>
                        <div class="num"><?php echo $passRate; ?>%</div>
                        <div class="label">及格率</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">📝</div>
                        <div class="num"><?php echo $totalExamTakers; ?></div>
                        <div class="label">参加考试人数</div>
                    </div>
                </div>
                
                <div class="card">
                    <h2 style="margin-bottom: 20px;">学生成绩列表</h2>
                    
                    <form method="GET" action="" class="search-bar">
                        <input type="text" name="search" placeholder="按用户名搜索..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary" style="width: auto;">🔍 搜索</button>
                        <?php if ($search): ?>
                            <a href="results.php" class="btn btn-secondary" style="width: auto;">清除搜索</a>
                        <?php endif; ?>
                    </form>
                    
                    <?php if (empty($students)): ?>
                        <div class="empty-state">
                            <div class="icon">👥</div>
                            <h3><?php echo $search ? '未找到匹配的学生' : '暂无学生数据'; ?></h3>
                            <p><?php echo $search ? '请尝试其他搜索关键词' : '请等待学生注册'; ?></p>
                        </div>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>学生</th>
                                    <th>学习进度</th>
                                    <th>完成状态</th>
                                    <th>考试次数</th>
                                    <th>最新成绩</th>
                                    <th>最高分</th>
                                    <th>平均分</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($currentStudents as $s): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($s['username']); ?></div>
                                            <div style="font-size: 12px; color: #999;">注册时间：<?php echo htmlspecialchars($s['created_at']); ?></div>
                                        </td>
                                        <td>
                                            <div style="margin-bottom: 3px;"><?php echo $s['progress_count']; ?>/<?php echo $s['total_contents']; ?> 章节</div>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $s['progress_percent']; ?>%;"></div>
                                            </div>
                                            <div style="font-size: 12px; color: #666; margin-top: 3px;"><?php echo $s['progress_percent']; ?>%</div>
                                        </td>
                                        <td>
                                            <?php if ($s['total_contents'] === 0): ?>
                                                <span class="badge badge-pending">暂无课程</span>
                                            <?php elseif ($s['has_completed_all']): ?>
                                                <span class="badge badge-completed">✓ 已完成</span>
                                            <?php else: ?>
                                                <span class="badge badge-pending">学习中</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $s['exam_count']; ?>次</td>
                                        <td>
                                            <?php if ($s['latest_result']): 
                                                $latestPercent = $s['latest_result']['total_points'] > 0 ? round(($s['latest_result']['total_score'] / $s['latest_result']['total_points']) * 100, 1) : 0;
                                            ?>
                                                <span class="score-badge <?php echo $latestPercent >= 60 ? 'score-high' : 'score-low'; ?>">
                                                    <?php echo $s['latest_result']['total_score']; ?>分
                                                </span>
                                            <?php else: ?>
                                                <span class="score-badge score-none">未考试</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($s['best_score'] !== null): 
                                                $bestPercent = $s['latest_result'] && $s['latest_result']['total_points'] > 0 ? round(($s['best_score'] / $s['latest_result']['total_points']) * 100, 1) : 0;
                                            ?>
                                                <span style="font-weight: bold; color: <?php echo $bestPercent >= 60 ? '#28a745' : '#dc3545'; ?>;">
                                                    <?php echo $s['best_score']; ?>分
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #999;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($s['avg_score'] !== null): 
                                                $avgPercent = $s['latest_result'] && $s['latest_result']['total_points'] > 0 ? round(($s['avg_score'] / $s['latest_result']['total_points']) * 100, 1) : 0;
                                            ?>
                                                <span style="color: #666;"><?php echo $s['avg_score']; ?>分</span>
                                            <?php else: ?>
                                                <span style="color: #999;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($s['exam_count'] > 0): ?>
                                                <a href="results.php?user_id=<?php echo $s['id']; ?><?php echo $search ? '&search=' . urlencode($search) . '&page=' . $page : ''; ?>" class="btn btn-primary btn-sm">查看记录</a>
                                            <?php else: ?>
                                                <span style="color: #999; font-size: 13px;">暂无考试</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="<?php echo buildUrl(['page' => $page - 1]); ?>">« 上一页</a>
                                <?php else: ?>
                                    <span class="disabled">« 上一页</span>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <?php if ($i === $page): ?>
                                        <span class="current"><?php echo $i; ?></span>
                                    <?php elseif ($i === 1 || $i === $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                        <a href="<?php echo buildUrl(['page' => $i]); ?>"><?php echo $i; ?></a>
                                    <?php elseif ($i === $page - 3 || $i === $page + 3): ?>
                                        <span>...</span>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="<?php echo buildUrl(['page' => $page + 1]); ?>">下一页 »</a>
                                <?php else: ?>
                                    <span class="disabled">下一页 »</span>
                                <?php endif; ?>
                            </div>
                            <div class="pagination-info">
                                第 <?php echo $page; ?> / <?php echo $totalPages; ?> 页，显示 <?php echo $offset + 1; ?> - <?php echo min($offset + $perPage, $totalCount); ?> 条
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
