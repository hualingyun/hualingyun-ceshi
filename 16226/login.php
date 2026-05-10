<?php
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码';
    } else {
        $result = loginUser($username, $password);
        if ($result['success']) {
            if ($result['role'] === 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: student/index.php');
            }
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

if (isset($_GET['registered']) && $_GET['registered'] === '1') {
    $success = '注册成功，请登录';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - 培训管理系统</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-container">
            <div class="auth-header">
                <h1>培训管理系统</h1>
                <p>欢迎回来，请登录您的账号</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" placeholder="请输入用户名" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" placeholder="请输入密码">
                </div>
                
                <button type="submit" class="btn btn-primary">登录</button>
            </form>
            
            <div class="auth-footer">
                <p>还没有账号？<a href="register.php">立即注册</a></p>
                <p style="margin-top: 10px; font-size: 12px; color: #999;">
                    默认管理员账号：admin / admin123
                </p>
            </div>
        </div>
    </div>
</body>
</html>
