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

$contents = getSortedContents();
$progress = getUserProgress($currentUser['id']);

$currentContentId = null;
$currentContent = null;
$comments = [];

if (empty($contents)) {
    $message = '暂无学习内容，请等待管理员发布';
    $messageType = 'error';
} else {
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $requestedId = intval($_GET['id']);
        if (canAccessContent($currentUser['id'], $requestedId)) {
            $currentContentId = $requestedId;
        } else {
            $message = '请先完成前面的章节';
            $messageType = 'error';
        }
    }
    
    if (!$currentContentId) {
        $currentContentId = getNextContentId($currentUser['id']);
        if (!$currentContentId && !empty($contents)) {
            $currentContentId = $contents[count($contents) - 1]['id'];
        }
    }
    
    if ($currentContentId) {
        $currentContent = getContentById($currentContentId);
        $comments = getCommentsByContent($currentContentId);
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'complete' && $currentContentId) {
            if (!in_array($currentContentId, $progress)) {
                $result = setContentCompleted($currentUser['id'], $currentContentId);
                if ($result['success']) {
                    header('Location: index.php?id=' . $currentContentId . '&completed=1');
                    exit;
                }
            }
        } elseif ($action === 'comment' && $currentContentId && !empty(trim($_POST['comment'] ?? ''))) {
            $commentText = trim($_POST['comment']);
            $result = addComment($currentUser['id'], $currentUser['username'], $currentContentId, $commentText);
            if ($result['success']) {
                header('Location: index.php?id=' . $currentContentId);
                exit;
            }
        }
    }
}

$isCompleted = in_array($currentContentId, $progress);
$prevId = getPreviousContentId($currentUser['id'], $currentContentId);
$nextId = null;
if ($isCompleted) {
    $nextId = getNextContentId($currentUser['id']);
}

$completedCount = count($progress);
$totalCount = count($contents);
$progressPercent = $totalCount > 0 ? round(($completedCount / $totalCount) * 100, 1) : 0;

$hasCompletedAll = hasCompletedAllCourses($currentUser['id']);
$questions = getExamQuestions();
$latestResult = getLatestExamResult($currentUser['id']);

if (isset($_GET['completed'])) {
    $message = '学习完成！已打卡标记。';
    $messageType = 'success';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>学习中心 - 培训管理系统</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="student-wrapper">
        <div class="content-list">
            <div class="student-header">
                <div class="user-info">
                    <span>👋 <?php echo htmlspecialchars($currentUser['username']); ?></span>
                    <span class="badge badge-student">学生</span>
                </div>
                <a href="../logout.php" class="btn btn-sm logout-btn">退出</a>
            </div>
            
            <div class="content-list-header">
                <h2>📚 课程目录</h2>
                <p>学习进度：<?php echo $completedCount; ?>/<?php echo $totalCount; ?> (<?php echo $progressPercent; ?>%)</p>
                <div style="margin-top: 10px; background: #eee; height: 8px; border-radius: 4px; overflow: hidden;">
                    <div style="width: <?php echo $progressPercent; ?>%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
                </div>
            </div>
            
            <?php if ($totalCount > 0): ?>
                <div style="padding: 15px 25px; border-bottom: 1px solid #eee; background: <?php echo $hasCompletedAll ? '#e8f5e9' : '#fef3c7'; ?>;">
                    <?php if ($hasCompletedAll): ?>
                        <div style="color: #2e7d32; font-weight: 600; margin-bottom: 8px;">🎉 恭喜！您已完成所有课程！</div>
                        <a href="exam.php" class="btn btn-success btn-sm" style="width: 100%; padding: 12px;">📝 参加结业考试</a>
                        <?php if ($latestResult): ?>
                            <div style="margin-top: 10px; text-align: center; font-size: 13px; color: #666;">
                                最近考试：<span style="font-weight: bold; color: <?php echo $latestResult['total_score'] >= $latestResult['total_points'] * 0.6 ? '#28a745' : '#dc3545'; ?>;"><?php echo $latestResult['total_score']; ?>分</span>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="color: #92400e; font-weight: 600; margin-bottom: 8px;">📖 还有 <?php echo $totalCount - $completedCount; ?> 节课程未完成</div>
                        <div style="font-size: 12px; color: #78350f;">完成所有课程后可参加结业考试</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="content-items">
                <?php if (empty($contents)): ?>
                    <div style="padding: 40px 20px; text-align: center; color: #999;">
                        暂无学习内容
                    </div>
                <?php else: ?>
                    <?php 
                    $canAccess = true;
                    foreach ($contents as $index => $content): 
                        $isCurrent = $content['id'] == $currentContentId;
                        $isItemCompleted = in_array($content['id'], $progress);
                        $isLocked = !$canAccess && !$isItemCompleted;
                        if (!$isItemCompleted) $canAccess = false;
                    ?>
                        <a href="<?php echo $isLocked ? '#' : 'index.php?id=' . $content['id']; ?>" 
                           class="content-item <?php echo $isCurrent ? 'active' : ''; ?> <?php echo $isLocked ? 'locked' : ''; ?>">
                            <div class="item-icon" style="
                                <?php if ($isItemCompleted): ?>background: #28a745; color: white;<?php 
                                elseif ($isLocked): ?>background: #ccc; color: #666;<?php 
                                else: ?>background: #667eea; color: white;<?php endif; ?>">
                                <?php if ($isItemCompleted): ?>✓<?php 
                                elseif ($isLocked): ?>🔒<?php 
                                else: ?><?php echo $index + 1; ?><?php endif; ?>
                            </div>
                            <div class="item-info">
                                <div class="item-title"><?php echo htmlspecialchars($content['title']); ?></div>
                                <div class="item-meta">
                                    <span class="badge <?php echo $content['type'] === 'article' ? 'badge-article' : 'badge-video'; ?>">
                                        <?php echo $content['type'] === 'article' ? '文章' : '视频'; ?>
                                    </span>
                                    <?php if ($isItemCompleted): ?>
                                        <span class="badge badge-completed">已完成</span>
                                    <?php elseif ($isLocked): ?>
                                        <span class="badge badge-locked">未解锁</span>
                                    <?php else: ?>
                                        <span class="badge badge-pending">学习中</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="content-detail">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 20px;"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if (!$currentContent): ?>
                <div class="empty-state" style="margin-top: 100px;">
                    <div class="icon">📖</div>
                    <h3>暂无可用内容</h3>
                    <p>请等待管理员发布培训内容</p>
                </div>
            <?php else: ?>
                <div class="content-detail-header">
                    <h1><?php echo htmlspecialchars($currentContent['title']); ?></h1>
                    <div class="content-detail-meta">
                        <span class="badge <?php echo $currentContent['type'] === 'article' ? 'badge-article' : 'badge-video'; ?>">
                            <?php echo $currentContent['type'] === 'article' ? '📖 文章' : '🎬 视频'; ?>
                        </span>
                        <?php if ($isCompleted): ?>
                            <span class="badge badge-completed">✅ 已完成学习</span>
                        <?php endif; ?>
                        <span style="margin-left: 10px;">更新时间：<?php echo htmlspecialchars($currentContent['updated_at']); ?></span>
                    </div>
                </div>
                
                <?php if ($currentContent['type'] === 'video'): ?>
                    <div class="video-container">
                        <?php 
                        $videoUrl = htmlspecialchars($currentContent['content']);
                        if (strpos($videoUrl, 'youtube.com') !== false || strpos($videoUrl, 'youtu.be') !== false) {
                            parse_str(parse_url($videoUrl, PHP_URL_QUERY), $query);
                            $videoId = $query['v'] ?? '';
                            if (!$videoId) {
                                $parts = explode('/', parse_url($videoUrl, PHP_URL_PATH));
                                $videoId = end($parts);
                            }
                            echo '<iframe src="https://www.youtube.com/embed/' . htmlspecialchars($videoId) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                        } else {
                            echo '<video controls><source src="' . $videoUrl . '">您的浏览器不支持视频播放。</video>';
                        }
                        ?>
                    </div>
                <?php else: ?>
                    <div class="content-body">
                        <?php echo nl2br(htmlspecialchars($currentContent['content'])); ?>
                    </div>
                <?php endif; ?>
                
                <div class="action-bar">
                    <div>
                        <?php if ($prevId): ?>
                            <a href="index.php?id=<?php echo $prevId; ?>" class="btn btn-secondary">◀ 上一章</a>
                        <?php endif; ?>
                        <?php if ($nextId): ?>
                            <a href="index.php?id=<?php echo $nextId; ?>" class="btn btn-success">下一章 ▶</a>
                        <?php endif; ?>
                    </div>
                    <?php if (!$isCompleted): ?>
                        <form method="POST" action="" style="margin: 0;">
                            <input type="hidden" name="action" value="complete">
                            <button type="submit" class="btn btn-primary" onclick="return confirm('确定要标记此章节为已完成吗？完成后将解锁下一章。');">
                                ✅ 完成学习并打卡
                            </button>
                        </form>
                    <?php else: ?>
                        <span class="badge badge-completed" style="padding: 10px 20px; font-size: 14px;">
                            ✅ 已完成打卡
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="comments-section">
                    <h3>💬 评论区（<?php echo count($comments); ?>条）</h3>
                    
                    <form method="POST" action="" class="comment-form">
                        <input type="hidden" name="action" value="comment">
                        <div class="form-group">
                            <textarea name="comment" placeholder="发表您的学习心得或问题..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">发表评论</button>
                    </form>
                    
                    <?php if (empty($comments)): ?>
                        <div class="empty-state" style="padding: 30px;">
                            <p style="color: #999;">暂无评论，快来发表第一条评论吧！</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment">
                                <div class="comment-header">
                                    <span class="comment-author"><?php echo htmlspecialchars($comment['username']); ?></span>
                                    <span class="comment-time"><?php echo htmlspecialchars($comment['created_at']); ?></span>
                                </div>
                                <div class="comment-text"><?php echo nl2br(htmlspecialchars($comment['text'])); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
