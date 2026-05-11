<?php require_once 'config.php'; require_login(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的排班</title>
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
        .content-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .btn-danger-custom { background: #dc3545; border: none; }
        .fc-daygrid-event { border: none; padding: 5px 8px; border-radius: 4px; }
        .fc-event-morning { background: #28a745 !important; }
        .fc-event-evening { background: #6f42c1 !important; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">值班系统</div>
        <a href="welcome.php">欢迎页</a>
        <a href="my_schedule.php" class="active">我的排班</a>
        <a href="punch.php">交接班打卡</a>
        <a href="my_records.php">我的交接记录</a>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <span class="fs-5">我的排班</span>
            <div>
                <span class="me-3"><?php echo $_SESSION['name']; ?> (学生)</span>
                <a href="api/logout.php" class="btn btn-sm btn-danger btn-danger-custom">退出登录</a>
            </div>
        </div>

        <div class="mt-4">
            <div class="content-card">
                <h5 class="mb-3">我的排班日历</h5>
                <div id="calendar"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/zh-cn.min.js"></script>

    <script>
        let calendar;

        async function loadSchedules() {
            const res = await fetch('api/schedules.php');
            const data = await res.json();
            if (data.success) {
                const events = data.data.map(s => ({
                    title: s.shift === 'morning' ? '早班' : '晚班',
                    start: s.date,
                    allDay: true,
                    className: s.shift === 'morning' ? 'fc-event-morning' : 'fc-event-evening'
                }));

                if (calendar) calendar.destroy();
                const el = document.getElementById('calendar');
                calendar = new FullCalendar.Calendar(el, {
                    locale: 'zh-cn',
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,dayGridWeek'
                    },
                    events: events
                });
                calendar.render();
            }
        }

        loadSchedules();
    </script>
</body>
</html>
