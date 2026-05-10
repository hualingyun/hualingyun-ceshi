<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="sidebar-header">
        <h2>培训管理系统</h2>
        <div class="user-info">
            <?php echo htmlspecialchars($currentUser['username']); ?>
            <span class="badge badge-admin">管理员</span>
        </div>
    </div>
    <nav class="sidebar-menu">
        <a href="index.php" class="menu-item <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
            🏠 欢迎页面
        </a>
        <a href="users.php" class="menu-item <?php echo $currentPage === 'users.php' ? 'active' : ''; ?>">
            👥 用户管理
        </a>
        <a href="contents.php" class="menu-item <?php echo $currentPage === 'contents.php' ? 'active' : ''; ?>">
            📚 内容管理
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="../logout.php" class="btn">🚪 退出登录</a>
    </div>
</div>
