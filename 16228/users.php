<?php require_once 'config.php'; require_admin(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理</title>
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
        <a href="users.php" class="active">用户管理</a>
        <a href="schedules.php">排班管理</a>
        <a href="records.php">交接班记录</a>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <span class="fs-5">用户管理</span>
            <div>
                <span class="me-3"><?php echo $_SESSION['name']; ?> (管理员)</span>
                <a href="api/logout.php" class="btn btn-sm btn-danger btn-danger-custom">退出登录</a>
            </div>
        </div>

        <div class="mt-4">
            <div class="content-card">
                <div class="d-flex justify-content-between mb-3">
                    <h5 class="mb-0">用户列表</h5>
                    <button class="btn btn-primary" onclick="openModal()">添加用户</button>
                </div>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>用户名</th>
                            <th>姓名</th>
                            <th>角色</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="user-list"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-title">添加用户</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit-id">
                    <div class="mb-3">
                        <label class="form-label">用户名</label>
                        <input type="text" id="username" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">姓名</label>
                        <input type="text" id="name" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">密码 <small id="pwd-hint" class="text-muted">(留空则不修改)</small></label>
                        <input type="password" id="password" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">角色</label>
                        <select id="role" class="form-select">
                            <option value="student">学生</option>
                            <option value="admin">管理员</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" onclick="saveUser()">保存</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modal = new bootstrap.Modal(document.getElementById('userModal'));

        async function loadUsers() {
            const res = await fetch('api/users.php');
            const data = await res.json();
            if (data.success) {
                const html = data.data.map(u => `
                    <tr>
                        <td>${u.id}</td>
                        <td>${u.username}</td>
                        <td>${u.name}</td>
                        <td><span class="badge ${u.role === 'admin' ? 'bg-primary' : 'bg-success'}">${u.role === 'admin' ? '管理员' : '学生'}</span></td>
                        <td>${u.created_at}</td>
                        <td>
                            <button class="btn btn-sm btn-info me-1" onclick='editUser(${JSON.stringify(u)})'>编辑</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteUser(${u.id}, '${u.username}')">删除</button>
                        </td>
                    </tr>
                `).join('');
                document.getElementById('user-list').innerHTML = html;
            }
        }

        function openModal() {
            document.getElementById('modal-title').textContent = '添加用户';
            document.getElementById('edit-id').value = '';
            document.getElementById('username').value = '';
            document.getElementById('username').disabled = false;
            document.getElementById('name').value = '';
            document.getElementById('password').value = '';
            document.getElementById('pwd-hint').style.display = 'none';
            document.getElementById('role').value = 'student';
            modal.show();
        }

        function editUser(user) {
            document.getElementById('modal-title').textContent = '编辑用户';
            document.getElementById('edit-id').value = user.id;
            document.getElementById('username').value = user.username;
            document.getElementById('username').disabled = true;
            document.getElementById('name').value = user.name;
            document.getElementById('password').value = '';
            document.getElementById('pwd-hint').style.display = 'inline';
            document.getElementById('role').value = user.role;
            modal.show();
        }

        async function saveUser() {
            const id = document.getElementById('edit-id').value;
            const method = id ? 'PUT' : 'POST';
            const body = {
                name: document.getElementById('name').value,
                role: document.getElementById('role').value,
                password: document.getElementById('password').value
            };
            if (!id) {
                body.username = document.getElementById('username').value;
            } else {
                body.id = parseInt(id);
            }

            const res = await fetch('api/users.php', {
                method,
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(body)
            });
            const data = await res.json();
            if (data.success) {
                modal.hide();
                loadUsers();
            } else {
                alert(data.message);
            }
        }

        async function deleteUser(id, username) {
            if (username === 'admin') {
                alert('不能删除超级管理员');
                return;
            }
            if (confirm('确定要删除该用户吗？')) {
                const res = await fetch('api/users.php', {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id})
                });
                const data = await res.json();
                if (data.success) {
                    loadUsers();
                } else {
                    alert(data.message);
                }
            }
        }

        loadUsers();
    </script>
</body>
</html>
