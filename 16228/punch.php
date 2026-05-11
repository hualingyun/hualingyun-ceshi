<?php require_once 'config.php'; require_login(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>交接班打卡</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .punch-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: 40px auto;
        }
        .btn-danger-custom { background: #dc3545; border: none; }
        .punch-btn {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            font-size: 24px;
            border: none;
            transition: all 0.3s;
        }
        .punch-btn.start {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        .punch-btn.handover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .punch-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">值班系统</div>
        <a href="welcome.php">欢迎页</a>
        <a href="my_schedule.php">我的排班</a>
        <a href="punch.php" class="active">交接班打卡</a>
        <a href="my_records.php">我的交接记录</a>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <span class="fs-5">交接班打卡</span>
            <div>
                <span class="me-3"><?php echo $_SESSION['name']; ?> (学生)</span>
                <a href="api/logout.php" class="btn btn-sm btn-danger btn-danger-custom">退出登录</a>
            </div>
        </div>

        <div class="punch-card">
            <h4 id="status-title">上班打卡</h4>
            <div class="mb-4">
                <p class="text-muted">今天：<span id="today"></span></p>
                <p class="text-muted">当前班次：<span id="shift"></span></p>
                <p id="on-duty-text"></p>
            </div>
            
            <div id="start-section">
                <button id="start-btn" class="punch-btn start" onclick="startDuty()">上班打卡</button>
                <p id="shift-not-match" class="text-danger mt-3" style="display: none;"></p>
            </div>
            
            <div id="handover-section" style="display: none;">
                <div class="mb-3">
                    <label class="form-label">选择接班人</label>
                    <select id="next-user" class="form-select">
                    </select>
                </div>
                <button id="handover-btn" class="punch-btn handover" onclick="handover()">交接班</button>
            </div>

            <div id="no-duty-section" style="display: none;">
                <p class="text-danger mb-4">今天没有您的值班安排</p>
                <a href="my_schedule.php" class="btn btn-secondary">查看排班</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let otherUsers = [];
        let hasOngoing = false;

        async function loadStatus() {
            const res = await fetch('api/current_user.php');
            const data = await res.json();
            if (data.success) {
                document.getElementById('today').textContent = data.data.today;
                document.getElementById('shift').textContent = data.data.current_shift === 'morning' ? '早班' : '晚班';
                otherUsers = data.data.other_users;
                
                const recordsRes = await fetch('api/records.php');
                const recordsData = await recordsRes.json();
                hasOngoing = false;
                if (recordsData.success) {
                    hasOngoing = recordsData.data.some(r => r.status === 'ongoing');
                }

                if (!data.data.on_duty) {
                    document.getElementById('on-duty-text').innerHTML = '<span class="badge bg-secondary">无值班</span>';
                    document.getElementById('start-section').style.display = 'none';
                    document.getElementById('handover-section').style.display = 'none';
                    document.getElementById('no-duty-section').style.display = 'block';
                } else if (hasOngoing) {
                    document.getElementById('on-duty-text').innerHTML = '<span class="badge bg-success">值班中</span>';
                    document.getElementById('status-title').textContent = '交接班';
                    document.getElementById('start-section').style.display = 'none';
                    document.getElementById('handover-section').style.display = 'block';
                    const options = otherUsers.map(u => `<option value="${u.id}">${u.name}</option>`).join('');
                    document.getElementById('next-user').innerHTML = options;
                    document.getElementById('no-duty-section').style.display = 'none';
                } else {
                    document.getElementById('on-duty-text').innerHTML = '<span class="badge bg-info">待打卡</span>';
                    document.getElementById('start-section').style.display = 'block';
                    document.getElementById('handover-section').style.display = 'none';
                    document.getElementById('no-duty-section').style.display = 'none';
                    
                    if (!data.data.can_punch) {
                        const myShiftName = data.data.today_shift === 'morning' ? '早班' : '晚班';
                        const currentShiftName = data.data.current_shift === 'morning' ? '早班' : '晚班';
                        document.getElementById('start-btn').disabled = true;
                        document.getElementById('shift-not-match').textContent = `您今天是${myShiftName}，当前是${currentShiftName}时间，请在对应班次时间打卡`;
                        document.getElementById('shift-not-match').style.display = 'block';
                    } else {
                        document.getElementById('start-btn').disabled = false;
                        document.getElementById('shift-not-match').style.display = 'none';
                    }
                }
            }
        }

        async function startDuty() {
            const res = await fetch('api/records.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'start'})
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                loadStatus();
            } else {
                alert(data.message);
            }
        }

        async function handover() {
            const nextUserId = document.getElementById('next-user').value;
            if (!nextUserId) {
                alert('请选择接班人');
                return;
            }
            const res = await fetch('api/records.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'handover', next_user_id: parseInt(nextUserId)})
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                loadStatus();
            } else {
                alert(data.message);
            }
        }

        loadStatus();
    </script>
</body>
</html>
