<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$message = '';
$messageType = '';
$editQuestion = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $type = $_POST['type'] ?? 'single';
        $question = trim($_POST['question'] ?? '');
        $points = intval($_POST['points'] ?? 10);
        $explanation = trim($_POST['explanation'] ?? '');
        
        $options = [];
        if ($type !== 'judge') {
            $optionKeys = ['A', 'B', 'C', 'D'];
            foreach ($optionKeys as $key) {
                $optValue = trim($_POST['option_' . $key] ?? '');
                if (!empty($optValue)) {
                    $options[$key] = $optValue;
                }
            }
        }
        
        $correctAnswer = null;
        if ($type === 'judge') {
            $correctAnswer = $_POST['correct_judge'] ?? 'true';
        } elseif ($type === 'multiple') {
            $correctAnswer = $_POST['correct_multiple'] ?? [];
        } else {
            $correctAnswer = $_POST['correct_single'] ?? null;
        }
        
        if (empty($question)) {
            $message = '请输入题目内容';
            $messageType = 'error';
        } elseif ($type !== 'judge' && count($options) < 2) {
            $message = '请至少输入两个选项';
            $messageType = 'error';
        } elseif ($type === 'multiple' && (empty($correctAnswer) || count($correctAnswer) < 2)) {
            $message = '多选题请至少选择两个正确答案';
            $messageType = 'error';
        } elseif ($type === 'single' && empty($correctAnswer)) {
            $message = '请选择正确答案';
            $messageType = 'error';
        } else {
            $result = addExamQuestion($type, $question, $options, $correctAnswer, $explanation, $points);
            if ($result['success']) {
                $message = $result['message'];
                $messageType = 'success';
            } else {
                $message = $result['message'];
                $messageType = 'error';
            }
        }
    } elseif ($action === 'update' && isset($_POST['id'])) {
        $questionId = intval($_POST['id']);
        $type = $_POST['type'] ?? 'single';
        $question = trim($_POST['question'] ?? '');
        $points = intval($_POST['points'] ?? 10);
        $explanation = trim($_POST['explanation'] ?? '');
        
        $options = [];
        if ($type !== 'judge') {
            $optionKeys = ['A', 'B', 'C', 'D'];
            foreach ($optionKeys as $key) {
                $optValue = trim($_POST['option_' . $key] ?? '');
                if (!empty($optValue)) {
                    $options[$key] = $optValue;
                }
            }
        }
        
        $correctAnswer = null;
        if ($type === 'judge') {
            $correctAnswer = $_POST['correct_judge'] ?? 'true';
        } elseif ($type === 'multiple') {
            $correctAnswer = $_POST['correct_multiple'] ?? [];
        } else {
            $correctAnswer = $_POST['correct_single'] ?? null;
        }
        
        if (empty($question)) {
            $message = '请输入题目内容';
            $messageType = 'error';
        } elseif ($type !== 'judge' && count($options) < 2) {
            $message = '请至少输入两个选项';
            $messageType = 'error';
        } elseif ($type === 'multiple' && (empty($correctAnswer) || count($correctAnswer) < 2)) {
            $message = '多选题请至少选择两个正确答案';
            $messageType = 'error';
        } elseif ($type === 'single' && empty($correctAnswer)) {
            $message = '请选择正确答案';
            $messageType = 'error';
        } else {
            $result = updateExamQuestion($questionId, $type, $question, $options, $correctAnswer, $explanation, $points);
            if ($result['success']) {
                $message = $result['message'];
                $messageType = 'success';
            } else {
                $message = $result['message'];
                $messageType = 'error';
            }
        }
    } elseif ($action === 'delete' && isset($_POST['id'])) {
        $result = deleteExamQuestion(intval($_POST['id']));
        if ($result['success']) {
            $message = $result['message'];
            $messageType = 'success';
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
    }
}

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editQuestion = getExamQuestionById(intval($_GET['edit']));
}

$questions = getExamQuestions();
$allResults = getAllExamResults();

$totalPoints = 0;
foreach ($questions as $q) {
    $totalPoints += $q['points'];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>考试管理 - 培训管理系统</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .question-type-badge {
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
        .options-group {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .option-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .option-row:last-child {
            margin-bottom: 0;
        }
        .option-label {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #667eea;
            color: white;
            border-radius: 50%;
            font-weight: bold;
            font-size: 14px;
        }
        .option-row input {
            flex: 1;
        }
        .correct-answer-group {
            background: #fff3e0;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .question-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            border-left: 4px solid #667eea;
        }
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .question-content {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .question-options {
            margin-bottom: 15px;
        }
        .question-options li {
            padding: 8px 0;
            color: #555;
        }
        .question-correct {
            background: #d4edda;
            padding: 12px 15px;
            border-radius: 6px;
            color: #155724;
            margin-bottom: 10px;
        }
        .question-explanation {
            background: #e3f2fd;
            padding: 12px 15px;
            border-radius: 6px;
            color: #1565c0;
        }
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-item {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }
        .stat-item .num {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        .stat-item .label {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="page-header">
                <h1>考试管理</h1>
                <p>管理考试题目和查看考试成绩</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <div class="stats-row">
                <div class="stat-item">
                    <div class="num"><?php echo count($questions); ?></div>
                    <div class="label">题目总数</div>
                </div>
                <div class="stat-item">
                    <div class="num"><?php echo $totalPoints; ?></div>
                    <div class="label">试卷总分</div>
                </div>
                <div class="stat-item">
                    <div class="num"><?php echo count($allResults); ?></div>
                    <div class="label">考试次数</div>
                </div>
            </div>
            
            <div class="card">
                <h2 style="margin-bottom: 20px;">
                    <?php echo $editQuestion ? '编辑题目' : '发布新题目'; ?>
                </h2>
                <form method="POST" action="exams.php" id="questionForm">
                    <input type="hidden" name="action" value="<?php echo $editQuestion ? 'update' : 'add'; ?>">
                    <?php if ($editQuestion): ?>
                        <input type="hidden" name="id" value="<?php echo $editQuestion['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>题目类型</label>
                            <select name="type" id="questionType">
                                <option value="single" <?php echo ($editQuestion['type'] ?? '') === 'single' ? 'selected' : ''; ?>>单选题</option>
                                <option value="multiple" <?php echo ($editQuestion['type'] ?? '') === 'multiple' ? 'selected' : ''; ?>>多选题</option>
                                <option value="judge" <?php echo ($editQuestion['type'] ?? '') === 'judge' ? 'selected' : ''; ?>>判断题</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>分值</label>
                            <input type="number" name="points" value="<?php echo htmlspecialchars($editQuestion['points'] ?? '10'); ?>" min="1">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>题目内容</label>
                        <textarea name="question" placeholder="请输入题目内容" required><?php echo htmlspecialchars($editQuestion['question'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="options-group" id="optionsGroup">
                        <h4 style="margin-bottom: 15px; color: #555;">选项设置</h4>
                        <?php 
                        $optionKeys = ['A', 'B', 'C', 'D'];
                        foreach ($optionKeys as $key): 
                            $value = '';
                            if ($editQuestion && isset($editQuestion['options'][$key])) {
                                $value = $editQuestion['options'][$key];
                            }
                        ?>
                            <div class="option-row">
                                <div class="option-label"><?php echo $key; ?></div>
                                <input type="text" name="option_<?php echo $key; ?>" placeholder="请输入选项<?php echo $key; ?>的内容" value="<?php echo htmlspecialchars($value); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="correct-answer-group">
                        <h4 style="margin-bottom: 15px; color: #e65100;">正确答案</h4>
                        
                        <div id="correctSingle">
                            <label style="display: block; margin-bottom: 10px; color: #666;">请选择正确答案：</label>
                            <div style="display: flex; gap: 20px;">
                                <?php foreach ($optionKeys as $key): ?>
                                    <label style="display: flex; align-items: center; gap: 5px;">
                                        <input type="radio" name="correct_single" value="<?php echo $key; ?>" 
                                            <?php echo isset($editQuestion['correct_answer']) && $editQuestion['correct_answer'] === $key ? 'checked' : ''; ?>>
                                        <?php echo $key; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div id="correctMultiple" style="display: none;">
                            <label style="display: block; margin-bottom: 10px; color: #666;">请选择所有正确答案：</label>
                            <div style="display: flex; gap: 20px;">
                                <?php foreach ($optionKeys as $key): ?>
                                    <label style="display: flex; align-items: center; gap: 5px;">
                                        <input type="checkbox" name="correct_multiple[]" value="<?php echo $key; ?>"
                                            <?php 
                                            if (isset($editQuestion['correct_answer']) && is_array($editQuestion['correct_answer'])) {
                                                echo in_array($key, $editQuestion['correct_answer']) ? 'checked' : '';
                                            }
                                            ?>>
                                        <?php echo $key; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div id="correctJudge" style="display: none;">
                            <label style="display: block; margin-bottom: 10px; color: #666;">请选择正确答案：</label>
                            <div style="display: flex; gap: 20px;">
                                <label style="display: flex; align-items: center; gap: 5px;">
                                    <input type="radio" name="correct_judge" value="true" 
                                        <?php echo isset($editQuestion['correct_answer']) && $editQuestion['correct_answer'] === 'true' ? 'checked' : ''; ?>>
                                    正确
                                </label>
                                <label style="display: flex; align-items: center; gap: 5px;">
                                    <input type="radio" name="correct_judge" value="false" 
                                        <?php echo isset($editQuestion['correct_answer']) && $editQuestion['correct_answer'] === 'false' ? 'checked' : ''; ?>>
                                    错误
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>答案解析</label>
                        <textarea name="explanation" placeholder="请输入答案解析（选填）"><?php echo htmlspecialchars($editQuestion['explanation'] ?? ''); ?></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-success">
                            <?php echo $editQuestion ? '更新题目' : '发布题目'; ?>
                        </button>
                        <?php if ($editQuestion): ?>
                            <a href="exams.php" class="btn btn-secondary">取消编辑</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <h2 style="margin-bottom: 20px;">
                    题目列表
                    <span style="font-size: 14px; color: #666; font-weight: normal;">（共 <?php echo count($questions); ?> 题，总分 <?php echo $totalPoints; ?> 分）</span>
                </h2>
                
                <?php if (empty($questions)): ?>
                    <div class="empty-state">
                        <div class="icon">📝</div>
                        <h3>暂无题目</h3>
                        <p>请在上方发布您的第一道考试题目</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($questions as $index => $q): ?>
                        <div class="question-card">
                            <div class="question-header">
                                <div>
                                    <span style="font-weight: bold; font-size: 16px;">第 <?php echo $index + 1; ?> 题</span>
                                    <span class="question-type-badge type-<?php echo $q['type']; ?>" style="margin-left: 10px;">
                                        <?php 
                                        if ($q['type'] === 'single') echo '单选题';
                                        elseif ($q['type'] === 'multiple') echo '多选题';
                                        else echo '判断题';
                                        ?>
                                    </span>
                                    <span style="margin-left: 10px; color: #666; font-size: 14px;"><?php echo $q['points']; ?>分</span>
                                </div>
                                <div>
                                    <a href="exams.php?edit=<?php echo $q['id']; ?>" class="btn btn-warning btn-sm">编辑</a>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('确定要删除该题目吗？');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $q['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">删除</button>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="question-content">
                                <?php echo nl2br(htmlspecialchars($q['question'])); ?>
                            </div>
                            
                            <?php if ($q['type'] !== 'judge' && !empty($q['options'])): ?>
                                <ul class="question-options" style="list-style: none; padding: 0;">
                                    <?php foreach ($q['options'] as $key => $value): ?>
                                        <li>
                                            <span style="font-weight: bold; margin-right: 10px;"><?php echo $key; ?>.</span>
                                            <?php echo htmlspecialchars($value); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            
                            <div class="question-correct">
                                <strong>正确答案：</strong>
                                <?php 
                                if ($q['type'] === 'judge') {
                                    echo $q['correct_answer'] === 'true' ? '正确' : '错误';
                                } elseif ($q['type'] === 'multiple') {
                                    echo is_array($q['correct_answer']) ? implode('、', $q['correct_answer']) : $q['correct_answer'];
                                } else {
                                    echo $q['correct_answer'];
                                }
                                ?>
                            </div>
                            
                            <?php if (!empty($q['explanation'])): ?>
                                <div class="question-explanation">
                                    <strong>解析：</strong><?php echo nl2br(htmlspecialchars($q['explanation'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2 style="margin-bottom: 20px;">
                    考试记录
                    <span style="font-size: 14px; color: #666; font-weight: normal;">（共 <?php echo count($allResults); ?> 条）</span>
                </h2>
                
                <?php if (empty($allResults)): ?>
                    <div class="empty-state">
                        <div class="icon">📊</div>
                        <h3>暂无考试记录</h3>
                        <p>学生完成课程后可参加考试</p>
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>学生</th>
                                <th>得分</th>
                                <th>总分</th>
                                <th>正确/总题数</th>
                                <th>提交时间</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allResults as $result): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($result['username']); ?></td>
                                    <td style="font-weight: bold; color: <?php echo $result['total_score'] >= $result['total_points'] * 0.6 ? '#28a745' : '#dc3545'; ?>;">
                                        <?php echo $result['total_score']; ?>分
                                    </td>
                                    <td><?php echo $result['total_points']; ?>分</td>
                                    <td><?php echo $result['correct_count']; ?>/<?php echo $result['question_count']; ?></td>
                                    <td><?php echo htmlspecialchars($result['submitted_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function updateQuestionType() {
            const type = document.getElementById('questionType').value;
            const optionsGroup = document.getElementById('optionsGroup');
            const correctSingle = document.getElementById('correctSingle');
            const correctMultiple = document.getElementById('correctMultiple');
            const correctJudge = document.getElementById('correctJudge');
            
            if (type === 'judge') {
                optionsGroup.style.display = 'none';
                correctSingle.style.display = 'none';
                correctMultiple.style.display = 'none';
                correctJudge.style.display = 'block';
            } else if (type === 'multiple') {
                optionsGroup.style.display = 'block';
                correctSingle.style.display = 'none';
                correctMultiple.style.display = 'block';
                correctJudge.style.display = 'none';
            } else {
                optionsGroup.style.display = 'block';
                correctSingle.style.display = 'block';
                correctMultiple.style.display = 'none';
                correctJudge.style.display = 'none';
            }
        }
        
        document.getElementById('questionType').addEventListener('change', updateQuestionType);
        updateQuestionType();
    </script>
</body>
</html>
