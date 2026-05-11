<?php require_once 'config.php'; require_admin(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>排班管理</title>
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
        .fc-daygrid-event { cursor: pointer; border: none; padding: 5px 8px; border-radius: 4px; }
        .fc-event-morning { background: #28a745 !important; }
        .fc-event-evening { background: #6f42c1 !important; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">值班系统</div>
        <a href="welcome.php">欢迎页</a>
        <a href="users.php">用户管理</a>
        <a href="schedules.php" class="active">排班管理</a>
        <a href="records.php">交接班记录</a>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <span class="fs-5">排班管理</span>
            <div>
                <span class="me-3"><?php echo $_SESSION['name']; ?> (管理员)</span>
                <a href="api/logout.php" class="btn btn-sm btn-danger btn-danger-custom">退出登录</a>
            </div>
        </div>

        <div class="mt-4">
            <div class="content-card">
                <div class="d-flex justify-content-between mb-3">
                    <h5 class="mb-0">排班日历</h5>
                    <div>
                        <button class="btn btn-success me-2" onclick="openBatchModal()">批量生成</button>
                        <button class="btn btn-primary" onclick="openAddModal()">单独添加</button>
                    </div>
                </div>
                <div id="calendar"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalTitle">添加排班</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit-schedule-id">
                    <div class="mb-3">
                        <label class="form-label">日期</label>
                        <input type="date" id="schedule-date" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">班次</label>
                        <select id="schedule-shift" class="form-select">
                            <option value="morning">早班</option>
                            <option value="evening">晚班</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">值班人员</label>
                        <select id="schedule-user" class="form-select"></select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" onclick="saveSchedule()">保存</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="batchModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">批量生成排班</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">开始日期</label>
                        <input type="date" id="batch-start" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">结束日期</label>
                        <input type="date" id="batch-end" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">值班人员（按顺序轮换）</label>
                        <select id="batch-users" class="form-select" multiple size="8"></select>
                        <small class="text-muted">按住 Ctrl 多选，顺序决定排班顺序</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">班次</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="batch-morning" checked>
                            <label class="form-check-label" for="batch-morning">早班</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="batch-evening" checked>
                            <label class="form-check-label" for="batch-evening">晚班</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-success" onclick="batchGenerate()">生成</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">排班详情</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>日期：</strong><span id="detail-date"></span></p>
                    <p><strong>班次：</strong><span id="detail-shift"></span></p>
                    <p><strong>值班人：</strong><span id="detail-user"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                    <button type="button" class="btn btn-primary" onclick="editFromDetail()">编辑</button>
                    <button type="button" class="btn btn-danger" onclick="deleteFromDetail()">删除</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/zh-cn.min.js"></script>

    <script>
        let calendar;
        let schedules = [];
        let users = [];
        let currentDetailId = null;
        const addModal = new bootstrap.Modal(document.getElementById('addModal'));
        const batchModal = new bootstrap.Modal(document.getElementById('batchModal'));
        const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));

        async function loadData() {
            const [sRes, uRes] = await Promise.all([
                fetch('api/schedules.php'),
                fetch('api/users.php')
            ]);
            const sData = await sRes.json();
            const uData = await uRes.json();
            if (sData.success) schedules = sData.data;
            if (uData.success) users = uData.data;
            renderCalendar();
            renderUserSelects();
        }

        function renderUserSelects() {
            const options = users.map(u => `<option value="${u.id}">${u.name}</option>`).join('');
            document.getElementById('schedule-user').innerHTML = options;
            document.getElementById('batch-users').innerHTML = options;
        }

        function renderCalendar() {
            const events = schedules.map(s => ({
                id: s.id,
                title: (s.shift === 'morning' ? '早班' : '晚班') + ' - ' + s.user_name,
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
                events: events,
                eventClick: function(info) {
                    showDetail(parseInt(info.event.id));
                },
                dateClick: function(info) {
                    openAddModal(info.dateStr);
                }
            });
            calendar.render();
        }

        function showDetail(id) {
            const s = schedules.find(x => x.id === id);
            if (!s) return;
            currentDetailId = id;
            document.getElementById('detail-date').textContent = s.date;
            document.getElementById('detail-shift').textContent = s.shift === 'morning' ? '早班' : '晚班';
            document.getElementById('detail-user').textContent = s.user_name;
            detailModal.show();
        }

        function editFromDetail() {
            const s = schedules.find(x => x.id === currentDetailId);
            if (!s) return;
            detailModal.hide();
            openEditModal(s);
        }

        async function deleteFromDetail() {
            if (!confirm('确定要删除该排班吗？')) return;
            const res = await fetch('api/schedules.php', {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: currentDetailId})
            });
            const data = await res.json();
            if (data.success) {
                detailModal.hide();
                loadData();
            } else {
                alert(data.message);
            }
        }

        function openAddModal(date) {
            document.getElementById('addModalTitle').textContent = '添加排班';
            document.getElementById('edit-schedule-id').value = '';
            document.getElementById('schedule-date').value = date || new Date().toISOString().split('T')[0];
            document.getElementById('schedule-shift').value = 'morning';
            document.getElementById('schedule-user').value = users[0]?.id || '';
            addModal.show();
        }

        function openEditModal(s) {
            document.getElementById('addModalTitle').textContent = '编辑排班';
            document.getElementById('edit-schedule-id').value = s.id;
            document.getElementById('schedule-date').value = s.date;
            document.getElementById('schedule-shift').value = s.shift;
            document.getElementById('schedule-user').value = s.user_id;
            addModal.show();
        }

        async function saveSchedule() {
            const id = document.getElementById('edit-schedule-id').value;
            const method = id ? 'PUT' : 'POST';
            const body = {
                date: document.getElementById('schedule-date').value,
                shift: document.getElementById('schedule-shift').value,
                user_id: parseInt(document.getElementById('schedule-user').value)
            };
            if (id) body.id = parseInt(id);

            const res = await fetch('api/schedules.php', {
                method,
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(body)
            });
            const data = await res.json();
            if (data.success) {
                addModal.hide();
                loadData();
            } else {
                alert(data.message);
            }
        }

        function openBatchModal() {
            const today = new Date();
            document.getElementById('batch-start').value = today.toISOString().split('T')[0];
            document.getElementById('batch-end').value = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
            batchModal.show();
        }

        async function batchGenerate() {
            const start = document.getElementById('batch-start').value;
            const end = document.getElementById('batch-end').value;
            const userSelect = document.getElementById('batch-users');
            const user_ids = Array.from(userSelect.selectedOptions).map(o => parseInt(o.value));
            const shifts = [];
            if (document.getElementById('batch-morning').checked) shifts.push('morning');
            if (document.getElementById('batch-evening').checked) shifts.push('evening');

            if (!start || !end || user_ids.length === 0 || shifts.length === 0) {
                alert('请填写完整信息');
                return;
            }

            const res = await fetch('api/schedules.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({batch: true, start_date: start, end_date: end, user_ids, shifts})
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                batchModal.hide();
                loadData();
            } else {
                alert(data.message);
            }
        }

        loadData();
    </script>
</body>
</html>
