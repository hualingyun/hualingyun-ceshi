<?php require_once 'config.php'; require_login(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>值班系统 - 欢迎页</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            position: fixed;
            left: 0;
            top: 0;
            padding: 20px 0;
        }
        .sidebar .logo {
            color: white;
            font-size: 22px;
            font-weight: bold;
            text-align: center;
            padding: 0 20px 30px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 20px;
        }
        .sidebar a {
            display: block;
            color: rgba(255,255,255,0.9);
            padding: 12px 25px;
            text-decoration: none;
            transition: all 0.3s;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255,255,255,0.15);
            color: white;
        }
        .main-content { margin-left: 250px; padding: 20px; }
        .top-bar {
            background: white;
            padding: 15px 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .welcome-card {
            background: white;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .welcome-card h1 { color: #667eea; margin-bottom: 20px; }
        .info-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .btn-danger-custom { background: #dc3545; border: none; }
        .fc-daygrid-event { cursor: pointer; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">值班系统</div>
        <a href="welcome.php" class="active">欢迎页</a>
        <?php if (is_admin()): ?>
            <a href="users.php">用户管理</a>
            <a href="schedules.php">排班管理</a>
            <a href="records.php">交接班记录</a>
        <?php else: ?>
            <a href="my_schedule.php">我的排班</a>
            <a href="punch.php">交接班打卡</a>
            <a href="my_records.php">我的交接记录</a>
        <?php endif; ?>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <span class="fs-5">欢迎页</span>
            <div>
                <span class="me-3"><?php echo $_SESSION['name']; ?> (<?php echo $_SESSION['role'] === 'admin' ? '管理员' : '学生'; ?>)</span>
                <a href="api/logout.php" class="btn btn-sm btn-danger btn-danger-custom">退出登录</a>
            </div>
        </div>

        <div class="mt-4">
            <div class="info-box">
                <h5>今日日期：<span id="today"></span></h5>
                <h5>当前班次：<span id="current-shift"></span></h5>
                <h5>是否值班：<span id="on-duty"></span></h5>
            </div>

            <div class="welcome-card">
                <h1>欢迎使用值班系统</h1>
                <p class="text-muted mt-3">请从左侧菜单选择功能</p>
                <div class="mt-4">
                    <?php if (is_admin()): ?>
                        <a href="schedules.php" class="btn btn-primary me-2">排班管理</a>
                        <a href="users.php" class="btn btn-secondary">用户管理</a>
                    <?php else: ?>
                        <a href="punch.php" class="btn btn-primary me-2">去打卡</a>
                        <a href="my_schedule.php" class="btn btn-secondary">查看排班</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function loadUserInfo() {
            const res = await fetch('api/current_user.php');
            const data = await res.json();
            if (data.success) {
                document.getElementById('today').textContent = data.data.today;
                document.getElementById('current-shift').textContent = data.data.current_shift === 'morning' ? '早班 (06:00-18:00)' : '晚班 (18:00-06:00)';
                document.getElementById('on-duty').textContent = data.data.on_duty ? '是' : '否';
                document.getElementById('on-duty').className = data.data.on_duty ? 'badge bg-success' : 'badge bg-secondary';
            }
        }
        loadUserInfo();
    </script>
</body>
</html>
