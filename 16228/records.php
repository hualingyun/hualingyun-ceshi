<?php require_once 'config.php'; require_admin(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>交接班记录</title>
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
        .content-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .btn-danger-custom { background: #dc3545; border: none; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">值班系统</div>
        <a href="welcome.php">欢迎页</a>
        <a href="users.php">用户管理</a>
        <a href="schedules.php">排班管理</a>
        <a href="records.php" class="active">交接班记录</a>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <span class="fs-5">交接班记录</span>
            <div>
                <span class="me-3"><?php echo $_SESSION['name']; ?> (管理员)</span>
                <a href="api/logout.php" class="btn btn-sm btn-danger btn-danger-custom">退出登录</a>
            </div>
        </div>

        <div class="mt-4">
            <div class="content-card">
                <h5 class="mb-3">交接班记录列表</h5>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" id="search-user" class="form-control" placeholder="搜索值班人员姓名">
                    </div>
                    <div class="col-md-3">
                        <input type="date" id="search-date" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary me-2" onclick="loadRecords(1)">搜索</button>
                        <button class="btn btn-secondary" onclick="clearSearch()">重置</button>
                    </div>
                </div>
                
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>日期</th>
                            <th>班次</th>
                            <th>值班人</th>
                            <th>上班打卡</th>
                            <th>接班人</th>
                            <th>交接时间</th>
                            <th>状态</th>
                        </tr>
                    </thead>
                    <tbody id="record-list"></tbody>
                </table>
                
                <div id="pagination" class="mt-3"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function loadRecords(page = 1) {
            const user = document.getElementById('search-user').value;
            const date = document.getElementById('search-date').value;
            
            let url = `api/records.php?page=${page}&page_size=10`;
            if (user) url += `&user=${encodeURIComponent(user)}`;
            if (date) url += `&date=${date}`;
            
            const res = await fetch(url);
            const data = await res.json();
            if (data.success) {
                if (data.data.length === 0) {
                    document.getElementById('record-list').innerHTML = '<tr><td colspan="8" class="text-center text-muted">暂无数据</td></tr>';
                } else {
                    const html = data.data.map(r => `
                        <tr>
                            <td>${r.id}</td>
                            <td>${r.date}</td>
                            <td><span class="badge ${r.shift === 'morning' ? 'bg-success' : 'bg-secondary'}">${r.shift === 'morning' ? '早班' : '晚班'}</span></td>
                            <td>${r.user_name}</td>
                            <td>${r.punch_time}</td>
                            <td>${r.next_user_name || '-'}</td>
                            <td>${r.next_punch_time || '-'}</td>
                            <td><span class="badge ${r.status === 'completed' ? 'bg-primary' : 'bg-warning'}">${r.status === 'completed' ? '已完成' : '进行中'}</span></td>
                        </tr>
                    `).join('');
                    document.getElementById('record-list').innerHTML = html;
                }
                renderPagination(data.pagination);
            }
        }
        
        function renderPagination(pagination) {
            const { page, total_pages, total } = pagination;
            if (total_pages <= 1) {
                document.getElementById('pagination').innerHTML = `<div class="text-muted text-center">共 ${total} 条记录</div>`;
                return;
            }
            
            let html = `<nav><ul class="pagination justify-content-center">`;
            html += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" onclick="loadRecords(${page - 1})">上一页</a></li>`;
            
            const start = Math.max(1, page - 2);
            const end = Math.min(total_pages, page + 2);
            
            if (start > 1) {
                html += `<li class="page-item"><a class="page-link" onclick="loadRecords(1)">1</a></li>`;
                if (start > 2) html += `<li class="page-item disabled"><a class="page-link">...</a></li>`;
            }
            
            for (let i = start; i <= end; i++) {
                html += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" onclick="loadRecords(${i})">${i}</a></li>`;
            }
            
            if (end < total_pages) {
                if (end < total_pages - 1) html += `<li class="page-item disabled"><a class="page-link">...</a></li>`;
                html += `<li class="page-item"><a class="page-link" onclick="loadRecords(${total_pages})">${total_pages}</a></li>`;
            }
            
            html += `<li class="page-item ${page === total_pages ? 'disabled' : ''}"><a class="page-link" onclick="loadRecords(${page + 1})">下一页</a></li>`;
            html += `</ul><div class="text-center text-muted mt-2">共 ${total} 条记录，第 ${page}/${total_pages} 页</div></nav>`;
            document.getElementById('pagination').innerHTML = html;
        }
        
        function clearSearch() {
            document.getElementById('search-user').value = '';
            document.getElementById('search-date').value = '';
            loadRecords(1);
        }
        
        loadRecords(1);
    </script>
</body>
</html>
