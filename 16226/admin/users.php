<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete' && isset($_POST['user_id'])) {
        $result = deleteUser(intval($_POST['user_id']));
        if ($result['success']) {
            $message = $result['message'];
            $messageType = 'success';
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
    } elseif ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'student';
        
        if (empty($username) || empty($password)) {
            $message = '请填写所有必填项';
            $messageType = 'error';
        } elseif (strlen($username) < 3 || strlen($password) < 6) {
            $message = '用户名至少3个字符，密码至少6个字符';
            $messageType = 'error';
        } else {
            $result = registerUser($username, $password, $role);
            if ($result['success']) {
                $message = $result['message'];
                $messageType = 'success';
            } else {
                $message = $result['message'];
                $messageType = 'error';
            }
        }
    }
}

$users = getAllUsers();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 - 培训管理系统</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="page-header">
                <h1>用户管理</h1>
                <p>管理系统中的所有用户</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <h2 style="margin-bottom: 20px;">添加新用户</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <div class="form-row">
                        <div class="form-group">
                            <label>用户名</label>
                            <input type="text" name="username" placeholder="请输入用户名" required>
                        </div>
                        <div class="form-group">
                            <label>密码</label>
                            <input type="password" name="password" placeholder="请输入密码" required>
                        </div>
                        <div class="form-group">
                            <label>角色</label>
                            <select name="role">
                                <option value="student">学生</option>
                                <option value="admin">管理员</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">添加用户</button>
                </form>
            </div>
            
            <div class="card">
                <h2 style="margin-bottom: 20px;">用户列表</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>用户名</th>
                            <th>角色</th>
                            <th>注册时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: #999;">
                                    暂无用户
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-admin' : 'badge-student'; ?>">
                                            <?php echo $user['role'] === 'admin' ? '管理员' : '学生'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                    <td>
                                        <?php if ($user['role'] !== 'admin'): ?>
                                            <form method="POST" action="" style="display: inline;" onsubmit="return confirm('确定要删除该用户吗？');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">删除</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: #999;">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
