<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$currentUser = getCurrentUser();

$users = getAllUsers();
$contents = getAllContents();
$studentCount = 0;
$adminCount = 0;

foreach ($users as $user) {
    if ($user['role'] === 'admin') {
        $adminCount++;
    } else {
        $studentCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>欢迎页面 - 培训管理系统</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="page-header">
                <h1>欢迎回来，<?php echo htmlspecialchars($currentUser['username']); ?></h1>
                <p>这是培训管理系统的管理后台</p>
            </div>
            
            <div class="welcome-stats">
                <div class="stat-card">
                    <div class="number"><?php echo count($users); ?></div>
                    <div class="label">总用户数</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $studentCount; ?></div>
                    <div class="label">学生数量</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $adminCount; ?></div>
                    <div class="label">管理员数量</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo count($contents); ?></div>
                    <div class="label">培训内容</div>
                </div>
            </div>
            
            <div class="card">
                <h2 style="margin-bottom: 20px;">系统说明</h2>
                <div style="line-height: 1.8; color: #555;">
                    <p><strong>培训管理系统</strong>是一个用于在线培训学习的管理平台，主要功能包括：</p>
                    <ul style="margin: 15px 0 15px 25px;">
                        <li><strong>用户管理</strong>：查看和管理系统用户，支持删除学生账号</li>
                        <li><strong>内容管理</strong>：发布和管理培训内容（文章、视频），设置学习顺序</li>
                        <li><strong>学习进度</strong>：学生必须按顺序学习，前一章未完成无法进入下一章</li>
                        <li><strong>学习打卡</strong>：学生学习完成后可以打卡标记</li>
                        <li><strong>评论互动</strong>：学生可以在每个学习内容下发表评论</li>
                    </ul>
                    <p><strong>默认管理员账号：</strong>admin / admin123</p>
                </div>
            </div>
            
            <div class="card">
                <h2 style="margin-bottom: 20px;">快速操作</h2>
                <div style="display: flex; gap: 15px;">
                    <a href="users.php" class="btn btn-secondary">查看用户列表</a>
                    <a href="contents.php" class="btn btn-success">管理培训内容</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
