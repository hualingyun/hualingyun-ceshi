<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
$currentUser = getCurrentUser();

if ($currentUser['role'] !== 'student') {
    header('Location: ../admin/index.php');
    exit;
}

$message = '';
$messageType = '';
$examResult = null;
$showResult = false;
$resultId = null;

if (isset($_GET['result']) && is_numeric($_GET['result'])) {
    $resultId = intval($_GET['result']);
    $examResult = getExamResultById($resultId);
    if ($examResult && $examResult['user_id'] == $currentUser['id']) {
        $showResult = true;
    } else {
        $examResult = null;
    }
}

$hasCompletedAll = hasCompletedAllCourses($currentUser['id']);
$questions = getExamQuestionsForStudent();
$userExamHistory = getUserExamResults($currentUser['id']);

if (!$showResult && $_SERVER['REQUEST_METHOD'] === 'POST' && $hasCompletedAll && !empty($questions)) {
    $userAnswers = [];
    foreach ($questions as $q) {
        if ($q['type'] === 'multiple') {
            $userAnswers[$q['id']] = $_POST['question_' . $q['id']] ?? [];
        } else {
            $userAnswers[$q['id']] = $_POST['question_' . $q['id']] ?? null;
        }
    }
    
    $submitResult = submitExam($currentUser['id'], $currentUser['username'], $userAnswers);
    
    if ($submitResult['success']) {
        $examResult = $submitResult['result'];
        $showResult = true;
    } else {
        $message = $submitResult['message'];
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>在线考试 - 培训管理系统</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .exam-wrapper {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .exam-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 16px;
            margin-bottom: 30px;
        }
        .exam-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .exam-header p {
            opacity: 0.9;
            font-size: 16px;
        }
        .exam-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .info-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }
        .info-card .num {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        .info-card .label {
            color: #666;
            font-size: 14px;
        }
        .question-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .question-number {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
        }
        .question-type {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .type-single {
            background: #e3f2fd;
            color: #1565c0;
        }
        .type-multiple {
            background: #fce4ec;
            color: #c2185b;
        }
        .type-judge {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .question-content {
            font-size: 18px;
            line-height: 1.8;
            margin-bottom: 25px;
            color: #333;
        }
        .options-list {
            list-style: none;
            padding: 0;
        }
        .options-list li {
            padding: 15px 20px;
            margin-bottom: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .options-list li:hover {
            border-color: #667eea;
            background: #f8f9fa;
        }
        .options-list li.selected {
            border-color: #667eea;
            background: #e8f4fd;
        }
        .options-list li input {
            margin-right: 12px;
            transform: scale(1.2);
        }
        .options-list li label {
            cursor: pointer;
            display: flex;
            align-items: flex-start;
            width: 100%;
        }
        .option-label {
            font-weight: bold;
            margin-right: 10px;
            color: #667eea;
            min-width: 25px;
        }
        .judge-options {
            display: flex;
            gap: 20px;
        }
        .judge-option {
            flex: 1;
            padding: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .judge-option:hover {
            border-color: #667eea;
        }
        .judge-option.selected {
            border-color: #667eea;
            background: #e8f4fd;
        }
        .judge-option input {
            display: none;
        }
        .judge-option-text {
            font-size: 18px;
            font-weight: bold;
        }
        .submit-section {
            text-align: center;
            padding: 30px;
        }
        .submit-btn {
            padding: 18px 60px;
            font-size: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: bold;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .result-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 16px;
            text-align: center;
            margin-bottom: 30px;
        }
        .result-score {
            font-size: 72px;
            font-weight: bold;
            margin: 20px 0;
        }
        .result-info {
            display: flex;
            justify-content: center;
            gap: 40px;
            font-size: 16px;
        }
        .result-info-item span {
            font-weight: bold;
        }
        .wrong-questions-header {
            background: #dc3545;
            color: white;
            padding: 15px 25px;
            border-radius: 8px 8px 0 0;
            font-weight: bold;
        }
        .wrong-question-card {
            background: white;
            padding: 25px;
            border: 1px solid #eee;
            border-top: none;
        }
        .wrong-question-card:last-child {
            border-radius: 0 0 8px 8px;
        }
        .answer-comparison {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 15px 0;
        }
        .user-answer {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
        }
        .correct-answer {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
        }
        .answer-label {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 12px;
        }
        .explanation-box {
            background: #e3f2fd;
            color: #1565c0;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        .all-correct {
            background: white;
            padding: 40px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }
        .all-correct-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .locked-message {
            background: white;
            padding: 60px 40px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }
        .locked-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .history-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }
        .student-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            background: white;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }
        .nav-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-info-nav {
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="exam-wrapper">
        <div class="student-nav">
            <div class="nav-left">
                <a href="index.php" class="btn btn-secondary btn-sm">← 返回学习中心</a>
            </div>
            <div class="user-info-nav">
                <span>👋 <?php echo htmlspecialchars($currentUser['username']); ?></span>
                <span class="badge badge-student">学生</span>
                <a href="../logout.php" class="btn btn-sm logout-btn" style="background: #dc3545; color: white;">退出</a>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($showResult && $examResult): ?>
            <div class="result-header">
                <h1>🎓 考试完成</h1>
                <div class="result-score"><?php echo $examResult['total_score']; ?>分</div>
                <div class="result-info">
                    <div class="result-info-item">总分：<span><?php echo $examResult['total_points']; ?>分</span></div>
                    <div class="result-info-item">答对：<span><?php echo $examResult['correct_count']; ?>/<?php echo $examResult['question_count']; ?>题</span></div>
                    <div class="result-info-item">提交时间：<span><?php echo htmlspecialchars($examResult['submitted_at']); ?></span></div>
                </div>
            </div>
            
            <?php if ($examResult['wrong_count'] > 0): ?>
                <div class="wrong-questions-header">
                    ❌ 错题解析（共 <?php echo $examResult['wrong_count']; ?> 题）
                </div>
                <?php foreach ($examResult['wrong_questions'] as $index => $wq): ?>
                    <div class="wrong-question-card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <span style="font-weight: bold; color: #dc3545;">错题 <?php echo $index + 1; ?></span>
                            <span class="question-type type-<?php echo $wq['type']; ?>">
                                <?php 
                                if ($wq['type'] === 'single') echo '单选题';
                                elseif ($wq['type'] === 'multiple') echo '多选题';
                                else echo '判断题';
                                ?>
                            </span>
                        </div>
                        <div class="question-content"><?php echo nl2br(htmlspecialchars($wq['question'])); ?></div>
                        
                        <?php if ($wq['type'] !== 'judge' && !empty($wq['options'])): ?>
                            <ul class="options-list" style="margin-bottom: 15px;">
                                <?php 
                                $optionKeys = array_keys($wq['options']);
                                foreach ($wq['options'] as $key => $value): 
                                    $isUserAnswer = $wq['type'] === 'multiple' 
                                        ? (is_array($wq['user_answer']) && in_array($key, $wq['user_answer']))
                                        : $wq['user_answer'] === $key;
                                    $isCorrectAnswer = $wq['type'] === 'multiple'
                                        ? (is_array($wq['correct_answer']) && in_array($key, $wq['correct_answer']))
                                        : $wq['correct_answer'] === $key;
                                    $style = '';
                                    if ($isCorrectAnswer) {
                                        $style = 'border-color: #28a745; background: #d4edda;';
                                    } elseif ($isUserAnswer && !$isCorrectAnswer) {
                                        $style = 'border-color: #dc3545; background: #f8d7da;';
                                    }
                                ?>
                                    <li style="<?php echo $style; ?>">
                                        <span class="option-label"><?php echo $key; ?>.</span>
                                        <?php echo htmlspecialchars($value); ?>
                                        <?php if ($isCorrectAnswer): ?>
                                            <span style="color: #28a745; margin-left: 10px;">✓ 正确答案</span>
                                        <?php elseif ($isUserAnswer): ?>
                                            <span style="color: #dc3545; margin-left: 10px;">✗ 你的选择</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        
                        <div class="answer-comparison">
                            <div class="user-answer">
                                <div class="answer-label">你的答案</div>
                                <div><?php echo htmlspecialchars(formatAnswer($wq['type'], $wq['user_answer'], $wq['options'] ?? [])); ?></div>
                            </div>
                            <div class="correct-answer">
                                <div class="answer-label">正确答案</div>
                                <div><?php echo htmlspecialchars(formatAnswer($wq['type'], $wq['correct_answer'], $wq['options'] ?? [])); ?></div>
                            </div>
                        </div>
                        
                        <?php if (!empty($wq['explanation'])): ?>
                            <div class="explanation-box">
                                <strong>📖 答案解析：</strong><?php echo nl2br(htmlspecialchars($wq['explanation'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="all-correct">
                    <div class="all-correct-icon">🎉</div>
                    <h2 style="margin-bottom: 15px; color: #28a745;">全部答对！</h2>
                    <p style="color: #666;">恭喜你，本次考试全部正确，你已经完全掌握了所学内容！</p>
                </div>
            <?php endif; ?>
            
            <div class="submit-section">
                <a href="exam.php" class="btn btn-success" style="padding: 15px 40px; font-size: 16px;">重新考试</a>
                <a href="index.php" class="btn btn-secondary" style="padding: 15px 40px; font-size: 16px; margin-left: 15px;">返回学习中心</a>
            </div>
            
        <?php elseif (!$hasCompletedAll): ?>
            <div class="locked-message">
                <div class="locked-icon">🔒</div>
                <h2 style="margin-bottom: 15px; color: #dc3545;">考试未解锁</h2>
                <p style="color: #666; margin-bottom: 20px; line-height: 1.8;">
                    请先完成所有培训课程后再参加考试。<br>
                    您需要按顺序学习并打卡完成每一章内容。
                </p>
                <a href="index.php" class="btn btn-primary" style="padding: 15px 40px;">继续学习</a>
            </div>
            
        <?php elseif (empty($questions)): ?>
            <div class="locked-message">
                <div class="locked-icon">📝</div>
                <h2 style="margin-bottom: 15px; color: #667eea;">暂无考试题目</h2>
                <p style="color: #666; margin-bottom: 20px;">
                    管理员还没有发布考试题目，请稍后再来。
                </p>
                <a href="index.php" class="btn btn-secondary" style="padding: 15px 40px;">返回学习中心</a>
            </div>
            
        <?php else: ?>
            <div class="exam-header">
                <h1>📝 在线考试</h1>
                <p>请认真作答，提交后将无法修改答案</p>
            </div>
            
            <div class="exam-info">
                <div class="info-card">
                    <div class="num"><?php echo count($questions); ?></div>
                    <div class="label">题目数量</div>
                </div>
                <div class="info-card">
                    <div class="num">
                        <?php 
                        $totalPoints = 0;
                        foreach ($questions as $q) {
                            $totalPoints += $q['points'];
                        }
                        echo $totalPoints;
                        ?>
                    </div>
                    <div class="label">试卷总分</div>
                </div>
                <div class="info-card">
                    <div class="num">10</div>
                    <div class="label">每题分值</div>
                </div>
            </div>
            
            <form method="POST" action="" id="examForm" onsubmit="return confirm('确定要提交试卷吗？提交后将无法修改答案。');">
                <?php foreach ($questions as $index => $q): ?>
                    <div class="question-card">
                        <div class="question-header">
                            <span class="question-number">第 <?php echo $index + 1; ?> 题</span>
                            <div>
                                <span class="question-type type-<?php echo $q['type']; ?>">
                                    <?php 
                                    if ($q['type'] === 'single') echo '单选题';
                                    elseif ($q['type'] === 'multiple') echo '多选题';
                                    else echo '判断题';
                                    ?>
                                </span>
                                <span style="margin-left: 10px; color: #666; font-size: 14px;"><?php echo $q['points']; ?>分</span>
                            </div>
                        </div>
                        
                        <div class="question-content"><?php echo nl2br(htmlspecialchars($q['question'])); ?></div>
                        
                        <?php if ($q['type'] === 'judge'): ?>
                            <div class="judge-options">
                                <label class="judge-option" onclick="selectJudge(this, '<?php echo $q['id']; ?>', 'true')">
                                    <input type="radio" name="question_<?php echo $q['id']; ?>" value="true">
                                    <div class="judge-option-text" style="color: #28a745;">✓ 正确</div>
                                </label>
                                <label class="judge-option" onclick="selectJudge(this, '<?php echo $q['id']; ?>', 'false')">
                                    <input type="radio" name="question_<?php echo $q['id']; ?>" value="false">
                                    <div class="judge-option-text" style="color: #dc3545;">✗ 错误</div>
                                </label>
                            </div>
                        <?php else: ?>
                            <ul class="options-list">
                                <?php 
                                $optionKeys = array_keys($q['options']);
                                foreach ($q['options'] as $key => $value): 
                                ?>
                                    <li>
                                        <label>
                                            <?php if ($q['type'] === 'multiple'): ?>
                                                <input type="checkbox" name="question_<?php echo $q['id']; ?>[]" value="<?php echo $key; ?>" onclick="updateOptionStyle(this)">
                                            <?php else: ?>
                                                <input type="radio" name="question_<?php echo $q['id']; ?>" value="<?php echo $key; ?>" onclick="updateSingleOption(this)">
                                            <?php endif; ?>
                                            <span class="option-label"><?php echo $key; ?>.</span>
                                            <?php echo htmlspecialchars($value); ?>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <div class="submit-section">
                    <button type="submit" class="submit-btn">✅ 提交试卷</button>
                </div>
            </form>
        <?php endif; ?>
        
        <?php if (!empty($userExamHistory) && !$showResult): ?>
            <div class="card" style="margin-top: 30px;">
                <h2 style="margin-bottom: 20px;">📊 考试历史记录</h2>
                <div class="history-table">
                    <table class="table" style="margin: 0;">
                        <thead>
                            <tr>
                                <th>序号</th>
                                <th>得分</th>
                                <th>总分</th>
                                <th>正确/总题数</th>
                                <th>提交时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userExamHistory as $index => $history): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td style="font-weight: bold; color: <?php echo $history['total_score'] >= $history['total_points'] * 0.6 ? '#28a745' : '#dc3545'; ?>;">
                                        <?php echo $history['total_score']; ?>分
                                    </td>
                                    <td><?php echo $history['total_points']; ?>分</td>
                                    <td><?php echo $history['correct_count']; ?>/<?php echo $history['question_count']; ?></td>
                                    <td><?php echo htmlspecialchars($history['submitted_at']); ?></td>
                                    <td>
                                        <a href="exam.php?result=<?php echo $history['id']; ?>" class="btn btn-primary btn-sm">查看详情</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function selectJudge(element, questionId, value) {
            const container = element.parentElement;
            const options = container.querySelectorAll('.judge-option');
            options.forEach(opt => opt.classList.remove('selected'));
            element.classList.add('selected');
        }
        
        function updateOptionStyle(checkbox) {
            const li = checkbox.closest('li');
            if (checkbox.checked) {
                li.classList.add('selected');
            } else {
                li.classList.remove('selected');
            }
        }
        
        function updateSingleOption(radio) {
            const ul = radio.closest('ul');
            const lis = ul.querySelectorAll('li');
            lis.forEach(li => li.classList.remove('selected'));
            radio.closest('li').classList.add('selected');
        }
        
        document.querySelectorAll('input[type="radio"], input[type="checkbox"]').forEach(input => {
            input.addEventListener('change', function() {
                if (this.type === 'checkbox') {
                    updateOptionStyle(this);
                }
            });
        });
    </script>
</body>
</html>
