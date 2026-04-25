<?php
require_once 'config.php';
require_login();
$current_user = app_get_current_user();

$user_permissions = get_user_permissions($current_user);
$menu_permissions = $user_permissions['menu_permissions'] ?? [];
$button_permissions = $user_permissions['button_permissions'] ?? [];

function has_menu_permission_js($menu, $menu_permissions) {
    return in_array($menu, $menu_permissions) ? 'true' : 'false';
}

function has_button_permission_js($module, $action, $button_permissions) {
    if (!isset($button_permissions[$module])) {
        return 'false';
    }
    return in_array($action, $button_permissions[$module]) ? 'true' : 'false';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台管理系统</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Microsoft YaHei', sans-serif;
            background: #f5f7fa;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header h1 {
            font-size: 20px;
        }
        .header .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .header .user-info span {
            font-size: 14px;
        }
        .header .user-info a {
            color: white;
            text-decoration: none;
            padding: 5px 15px;
            border: 1px solid white;
            border-radius: 3px;
            font-size: 12px;
            transition: background 0.3s;
        }
        .header .user-info a:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .container {
            display: flex;
            min-height: calc(100vh - 57px);
        }
        .sidebar {
            width: 200px;
            background: #304156;
            color: white;
        }
        .sidebar .menu-item {
            padding: 15px 25px;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        .sidebar .menu-item:hover {
            background: #409eff;
        }
        .sidebar .menu-item.active {
            background: #409eff;
        }
        .sidebar .menu-item.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            color: #909399;
        }
        .sidebar .menu-item.disabled:hover {
            background: #304156;
        }
        .sidebar .menu-item .icon {
            font-size: 16px;
        }
        .sidebar .menu-parent {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .sidebar .menu-parent .menu-text {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar .menu-parent .arrow {
            transition: transform 0.3s;
        }
        .sidebar .menu-parent.expanded .arrow {
            transform: rotate(90deg);
        }
        .sidebar .submenu {
            display: none;
            background: #1f2d3d;
        }
        .sidebar .submenu.show {
            display: block;
        }
        .sidebar .submenu .menu-item {
            padding-left: 50px;
            font-size: 13px;
        }
        .main {
            flex: 1;
            padding: 20px;
        }
        .page {
            display: none;
        }
        .page.active {
            display: block;
        }
        .welcome-page {
            text-align: center;
            padding-top: 100px;
        }
        .welcome-page h2 {
            color: #333;
            font-size: 28px;
            margin-bottom: 20px;
        }
        .welcome-page p {
            color: #666;
            font-size: 16px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .page-header h2 {
            color: #333;
            font-size: 18px;
        }
        .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: opacity 0.3s;
        }
        .btn:hover {
            opacity: 0.8;
        }
        .btn-primary {
            background: #409eff;
            color: white;
        }
        .btn-success {
            background: #67c23a;
            color: white;
        }
        .btn-danger {
            background: #f56c6c;
            color: white;
        }
        .btn-default {
            background: #909399;
            color: white;
        }
        .btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
        .table-container {
            background: white;
            border-radius: 4px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #fafafa;
            color: #909399;
            font-weight: normal;
            font-size: 14px;
        }
        td {
            color: #606266;
            font-size: 14px;
        }
        tr:hover {
            background: #f5f7fa;
        }
        .action-btns {
            display: flex;
            gap: 10px;
        }
        .action-btns .btn {
            padding: 5px 10px;
            font-size: 12px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal.show {
            display: flex;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 4px;
            width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-header h3 {
            color: #333;
            font-size: 16px;
        }
        .modal-header .close {
            cursor: pointer;
            font-size: 20px;
            color: #909399;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #606266;
            font-size: 14px;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #dcdfe6;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #409eff;
        }
        .form-group select {
            cursor: pointer;
            background: white;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .form-group .hint {
            font-size: 12px;
            color: #909399;
            margin-top: 5px;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        .toast {
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%);
            padding: 12px 24px;
            border-radius: 4px;
            color: white;
            font-size: 14px;
            z-index: 2000;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .toast.show {
            opacity: 1;
        }
        .toast.success {
            background: #67c23a;
        }
        .toast.error {
            background: #f56c6c;
        }
        .empty {
            text-align: center;
            padding: 40px;
            color: #909399;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>后台管理系统</h1>
        <div class="user-info">
            <span>欢迎，<?php echo htmlspecialchars($current_user['username']); ?></span>
            <a href="logout.php">退出登录</a>
        </div>
    </div>

    <div class="container">
        <div class="sidebar">
            <div class="menu-item active" data-page="welcome">
                <span class="icon">🏠</span>
                <span>欢迎页</span>
            </div>
            <div class="menu-item" data-page="users">
                <span class="icon">👥</span>
                <span>用户管理</span>
            </div>
            <div class="menu-item" data-page="articles">
                <span class="icon">📝</span>
                <span>文章管理</span>
            </div>
            <div class="menu-item menu-parent" data-parent="categories">
                <span class="menu-text">
                    <span class="icon">📂</span>
                    <span>文章分类管理</span>
                </span>
                <span class="arrow">▶</span>
            </div>
            <div class="submenu" id="categoriesSubmenu">
                <div class="menu-item" data-page="categories">
                    <span>分类列表</span>
                </div>
                <div class="menu-item" data-action="addCategory">
                    <span>分类添加</span>
                </div>
            </div>
            <div class="menu-item" data-page="roles">
                <span class="icon">👤</span>
                <span>角色管理</span>
            </div>
            <div class="menu-item" data-page="permissions">
                <span class="icon">🔒</span>
                <span>权限配置</span>
            </div>
        </div>

        <div class="main">
            <div id="welcomePage" class="page active welcome-page">
                <h2>欢迎使用后台管理系统</h2>
                <p>请从左侧菜单选择功能模块</p>
            </div>

            <div id="usersPage" class="page">
                <div class="page-header">
                    <h2>用户管理</h2>
                    <button class="btn btn-primary" id="addUserBtn" onclick="openAddUserModal()">添加用户</button>
                </div>
                <div class="table-container">
                    <table id="usersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>用户名</th>
                                <th>手机号</th>
                                <th>角色</th>
                                <th>注册时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="articlesPage" class="page">
                <div class="page-header">
                    <h2>文章管理</h2>
                    <button class="btn btn-primary" id="addArticleBtn" onclick="openAddArticleModal()">添加文章</button>
                </div>
                <div class="table-container">
                    <table id="articlesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>标题</th>
                                <th>分类</th>
                                <th>作者</th>
                                <th>创建时间</th>
                                <th>更新时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="articlesTableBody">
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="categoriesPage" class="page">
                <div class="page-header">
                    <h2>分类管理</h2>
                    <button class="btn btn-primary" id="addCategoryBtn" onclick="openAddCategoryModal()">添加分类</button>
                </div>
                <div class="table-container">
                    <table id="categoriesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>分类名称</th>
                                <th>描述</th>
                                <th>创建时间</th>
                                <th>更新时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="categoriesTableBody">
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="rolesPage" class="page">
                <div class="page-header">
                    <h2>角色管理</h2>
                    <button class="btn btn-primary" id="addRoleBtn" onclick="openAddRoleModal()">添加角色</button>
                </div>
                <div class="table-container">
                    <table id="rolesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>角色名称</th>
                                <th>描述</th>
                                <th>数据范围</th>
                                <th>用户数量</th>
                                <th>创建时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="rolesTableBody">
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="permissionsPage" class="page">
                <div class="page-header">
                    <h2>权限配置</h2>
                </div>
                <div class="table-container" style="padding: 20px;">
                    <div class="form-group">
                        <label>选择角色</label>
                        <select id="permissionRoleSelect" onchange="loadRolePermissions()">
                            <option value="">请选择角色</option>
                        </select>
                    </div>
                    
                    <div id="permissionConfigContainer" style="display: none;">
                        <h4 style="margin-bottom: 15px; color: #333;">菜单权限</h4>
                        <div id="menuPermissionsContainer" style="margin-bottom: 30px;"></div>
                        
                        <h4 style="margin-bottom: 15px; color: #333;">按钮权限</h4>
                        <div id="buttonPermissionsContainer" style="margin-bottom: 30px;"></div>
                        
                        <button class="btn btn-primary" id="savePermissionsBtn" onclick="savePermissions()">保存配置</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="userModalTitle">添加用户</h3>
                <span class="close" onclick="closeUserModal()">&times;</span>
            </div>
            <form id="userForm">
                <input type="hidden" id="userId" name="id">
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text" id="userName" name="username" placeholder="3-20字符，字母开头，字母数字下划线">
                    <div class="hint">3-20字符，字母开头，只能包含字母、数字和下划线</div>
                </div>
                <div class="form-group">
                    <label>密码</label>
                    <input type="password" id="userPassword" name="password" placeholder="6-20字符，字母开头，大小写+数字">
                    <div class="hint">6-20字符，字母开头，必须包含大小写字母和数字</div>
                </div>
                <div class="form-group" id="confirmPasswordGroup">
                    <label>确认密码</label>
                    <input type="password" id="confirmPassword" name="confirm_password" placeholder="请再次输入密码">
                </div>
                <div class="form-group">
                    <label>手机号</label>
                    <input type="text" id="userPhone" name="phone" placeholder="请输入手机号">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" onclick="closeUserModal()">取消</button>
                    <button type="submit" class="btn btn-primary">确定</button>
                </div>
            </form>
        </div>
    </div>

    <div id="articleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="articleModalTitle">添加文章</h3>
                <span class="close" onclick="closeArticleModal()">&times;</span>
            </div>
            <form id="articleForm">
                <input type="hidden" id="articleId" name="id">
                <div class="form-group">
                    <label>分类</label>
                    <select id="articleCategory" name="category_id">
                        <option value="">请选择分类</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>标题</label>
                    <input type="text" id="articleTitle" name="title" placeholder="请输入文章标题" maxlength="100">
                </div>
                <div class="form-group">
                    <label>内容</label>
                    <textarea id="articleContent" name="content" placeholder="请输入文章内容"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" onclick="closeArticleModal()">取消</button>
                    <button type="submit" class="btn btn-primary">确定</button>
                </div>
            </form>
        </div>
    </div>

    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="categoryModalTitle">添加分类</h3>
                <span class="close" onclick="closeCategoryModal()">&times;</span>
            </div>
            <form id="categoryForm">
                <input type="hidden" id="categoryId" name="id">
                <div class="form-group">
                    <label>分类名称</label>
                    <input type="text" id="categoryName" name="name" placeholder="请输入分类名称" maxlength="50">
                </div>
                <div class="form-group">
                    <label>描述</label>
                    <textarea id="categoryDescription" name="description" placeholder="请输入分类描述"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" onclick="closeCategoryModal()">取消</button>
                    <button type="submit" class="btn btn-primary">确定</button>
                </div>
            </form>
        </div>
    </div>

    <div id="roleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="roleModalTitle">添加角色</h3>
                <span class="close" onclick="closeRoleModal()">&times;</span>
            </div>
            <form id="roleForm">
                <input type="hidden" id="roleId" name="id">
                <div class="form-group">
                    <label>角色名称</label>
                    <input type="text" id="roleName" name="name" placeholder="请输入角色名称" maxlength="50">
                </div>
                <div class="form-group">
                    <label>描述</label>
                    <textarea id="roleDescription" name="description" placeholder="请输入角色描述"></textarea>
                </div>
                <div class="form-group">
                    <label>数据范围</label>
                    <select id="roleDataScope" name="data_scope">
                        <option value="all">全部数据</option>
                        <option value="own">仅本人数据</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" onclick="closeRoleModal()">取消</button>
                    <button type="submit" class="btn btn-primary">确定</button>
                </div>
            </form>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script>
        const currentMenuPermissions = <?php echo json_encode($menu_permissions); ?>;
        const currentButtonPermissions = <?php echo json_encode($button_permissions); ?>;

        function hasMenuPermission(menu) {
            return currentMenuPermissions.includes(menu);
        }

        function hasButtonPermission(module, action) {
            if (!currentButtonPermissions[module]) {
                return false;
            }
            return currentButtonPermissions[module].includes(action);
        }

        function showToast(message, type = 'error') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + type + ' show';
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        function initPermissionState() {
            if (!hasButtonPermission('users', 'add')) {
                const addUserBtn = document.getElementById('addUserBtn');
                if (addUserBtn) {
                    addUserBtn.classList.add('disabled');
                }
            }
            
            if (!hasButtonPermission('articles', 'add')) {
                const addArticleBtn = document.getElementById('addArticleBtn');
                if (addArticleBtn) {
                    addArticleBtn.classList.add('disabled');
                }
            }
            
            if (!hasButtonPermission('categories', 'add')) {
                const addCategoryBtn = document.getElementById('addCategoryBtn');
                if (addCategoryBtn) {
                    addCategoryBtn.classList.add('disabled');
                }
            }
            
            if (!hasButtonPermission('roles', 'add')) {
                const addRoleBtn = document.getElementById('addRoleBtn');
                if (addRoleBtn) {
                    addRoleBtn.classList.add('disabled');
                }
            }
            
            if (!hasButtonPermission('permissions', 'edit')) {
                const savePermissionsBtn = document.getElementById('savePermissionsBtn');
                if (savePermissionsBtn) {
                    savePermissionsBtn.classList.add('disabled');
                }
            }
        }

        initPermissionState();

        document.querySelectorAll('.menu-item').forEach(item => {
            const page = item.dataset.page;
            const action = item.dataset.action;
            
            if (page && page !== 'welcome' && !hasMenuPermission(page)) {
                item.classList.add('disabled');
            }
            
            if (action === 'addCategory' && !hasButtonPermission('categories', 'add')) {
                item.classList.add('disabled');
            }

            item.addEventListener('click', function(e) {
                const parent = this.dataset.parent;
                const page = this.dataset.page;
                const action = this.dataset.action;

                if (this.classList.contains('disabled')) {
                    e.preventDefault();
                    e.stopPropagation();
                    showToast('您没有权限访问此功能', 'error');
                    return;
                }

                if (parent) {
                    this.classList.toggle('expanded');
                    const submenu = document.getElementById(parent + 'Submenu');
                    if (submenu) {
                        submenu.classList.toggle('show');
                    }
                    return;
                }

                document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');

                if (action === 'addCategory') {
                    if (!hasButtonPermission('categories', 'add')) {
                        showToast('您没有权限添加分类', 'error');
                        return;
                    }
                    openAddCategoryModal();
                    return;
                }

                document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
                
                if (page === 'welcome') {
                    document.getElementById('welcomePage').classList.add('active');
                } else if (page === 'users') {
                    if (!hasMenuPermission('users')) {
                        showToast('您没有权限访问用户管理', 'error');
                        return;
                    }
                    document.getElementById('usersPage').classList.add('active');
                    loadUsers();
                } else if (page === 'articles') {
                    if (!hasMenuPermission('articles')) {
                        showToast('您没有权限访问文章管理', 'error');
                        return;
                    }
                    document.getElementById('articlesPage').classList.add('active');
                    loadArticles();
                } else if (page === 'categories') {
                    if (!hasMenuPermission('categories')) {
                        showToast('您没有权限访问分类管理', 'error');
                        return;
                    }
                    document.getElementById('categoriesPage').classList.add('active');
                    loadCategories();
                } else if (page === 'roles') {
                    if (!hasMenuPermission('roles')) {
                        showToast('您没有权限访问角色管理', 'error');
                        return;
                    }
                    document.getElementById('rolesPage').classList.add('active');
                    loadRoles();
                } else if (page === 'permissions') {
                    if (!hasMenuPermission('permissions')) {
                        showToast('您没有权限访问权限配置', 'error');
                        return;
                    }
                    document.getElementById('permissionsPage').classList.add('active');
                    loadPermissionRoles();
                }
            });
        });

        function loadUsers() {
            fetch('api/users.php')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        renderUsersTable(result.data);
                    } else {
                        showToast(result.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('网络错误，请重试', 'error');
                });
        }

        function renderUsersTable(users) {
            const tbody = document.getElementById('usersTableBody');
            if (users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty">暂无数据</td></tr>';
                return;
            }

            const canEdit = hasButtonPermission('users', 'edit');
            const canDelete = hasButtonPermission('users', 'delete');

            tbody.innerHTML = users.map(user => {
                let buttons = '';
                if (canEdit) {
                    buttons += `<button class="btn btn-success" onclick="editUser(${user.id})">编辑</button>`;
                } else {
                    buttons += `<button class="btn btn-success disabled">编辑</button>`;
                }
                if (canDelete) {
                    buttons += `<button class="btn btn-danger" onclick="deleteUser(${user.id})">删除</button>`;
                } else {
                    buttons += `<button class="btn btn-danger disabled">删除</button>`;
                }
                return `
                    <tr>
                        <td>${user.id}</td>
                        <td>${user.username}</td>
                        <td>${user.phone}</td>
                        <td>${user.role_name || '未分配'}</td>
                        <td>${user.created_at}</td>
                        <td>
                            <div class="action-btns">
                                ${buttons}
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function openAddUserModal() {
            if (!hasButtonPermission('users', 'add')) {
                showToast('您没有权限添加用户', 'error');
                return;
            }
            document.getElementById('userModalTitle').textContent = '添加用户';
            document.getElementById('userId').value = '';
            document.getElementById('userName').value = '';
            document.getElementById('userPassword').value = '';
            document.getElementById('confirmPassword').value = '';
            document.getElementById('userPhone').value = '';
            document.getElementById('confirmPasswordGroup').style.display = 'block';
            document.getElementById('userPassword').placeholder = '6-20字符，字母开头，大小写+数字';
            updateUserFormWithRole();
            document.getElementById('userModal').classList.add('show');
        }

        function editUser(id) {
            if (!hasButtonPermission('users', 'edit')) {
                showToast('您没有权限编辑用户', 'error');
                return;
            }
            document.getElementById('userModalTitle').textContent = '编辑用户';
            document.getElementById('userId').value = id;
            document.getElementById('confirmPasswordGroup').style.display = 'none';
            document.getElementById('userPassword').placeholder = '留空则不修改密码';
            
            fetch('api/users.php')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const user = result.data.find(u => u.id === id);
                        if (user) {
                            document.getElementById('userName').value = user.username;
                            document.getElementById('userPassword').value = '';
                            document.getElementById('userPhone').value = user.phone;
                            updateUserFormWithRole();
                            setTimeout(() => {
                                loadUserRoleOptions(user.role_id || null);
                            }, 100);
                            document.getElementById('userModal').classList.add('show');
                        }
                    }
                });
        }

        function closeUserModal() {
            document.getElementById('userModal').classList.remove('show');
        }

        function deleteUser(id) {
            if (!hasButtonPermission('users', 'delete')) {
                showToast('您没有权限删除用户', 'error');
                return;
            }
            if (!confirm('确定要删除该用户吗？')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            fetch('api/users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showToast(result.message, 'success');
                    loadUsers();
                } else {
                    showToast(result.message, 'error');
                }
            })
            .catch(error => {
                showToast('网络错误，请重试', 'error');
            });
        }

        document.getElementById('userForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const userId = document.getElementById('userId').value;

            if (userId) {
                formData.append('action', 'edit');
            } else {
                formData.append('action', 'add');
            }

            fetch('api/users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showToast(result.message, 'success');
                    closeUserModal();
                    loadUsers();
                } else {
                    showToast(result.message, 'error');
                }
            })
            .catch(error => {
                showToast('网络错误，请重试', 'error');
            });
        });

        function loadArticles() {
            fetch('api/articles.php')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        renderArticlesTable(result.data);
                    } else {
                        showToast(result.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('网络错误，请重试', 'error');
                });
        }

        function renderArticlesTable(articles) {
            const tbody = document.getElementById('articlesTableBody');
            if (articles.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty">暂无数据</td></tr>';
                return;
            }

            const canEdit = hasButtonPermission('articles', 'edit');
            const canDelete = hasButtonPermission('articles', 'delete');

            tbody.innerHTML = articles.map(article => {
                let buttons = '';
                if (canEdit) {
                    buttons += `<button class="btn btn-success" onclick="editArticle(${article.id})">编辑</button>`;
                } else {
                    buttons += `<button class="btn btn-success disabled">编辑</button>`;
                }
                if (canDelete) {
                    buttons += `<button class="btn btn-danger" onclick="deleteArticle(${article.id})">删除</button>`;
                } else {
                    buttons += `<button class="btn btn-danger disabled">删除</button>`;
                }
                return `
                    <tr>
                        <td>${article.id}</td>
                        <td>${article.title}</td>
                        <td>${article.category_name}</td>
                        <td>${article.author_name}</td>
                        <td>${article.created_at}</td>
                        <td>${article.updated_at}</td>
                        <td>
                            <div class="action-btns">
                                ${buttons}
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function loadCategoryOptions(selectedId = null) {
            fetch('api/categories.php')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const select = document.getElementById('articleCategory');
                        select.innerHTML = '<option value="">请选择分类</option>';
                        result.data.forEach(category => {
                            const option = document.createElement('option');
                            option.value = category.id;
                            option.textContent = category.name;
                            if (selectedId !== null && category.id === selectedId) {
                                option.selected = true;
                            }
                            select.appendChild(option);
                        });
                    }
                });
        }

        function openAddArticleModal() {
            if (!hasButtonPermission('articles', 'add')) {
                showToast('您没有权限添加文章', 'error');
                return;
            }
            document.getElementById('articleModalTitle').textContent = '添加文章';
            document.getElementById('articleId').value = '';
            document.getElementById('articleTitle').value = '';
            document.getElementById('articleContent').value = '';
            loadCategoryOptions();
            document.getElementById('articleModal').classList.add('show');
        }

        function editArticle(id) {
            if (!hasButtonPermission('articles', 'edit')) {
                showToast('您没有权限编辑文章', 'error');
                return;
            }
            document.getElementById('articleModalTitle').textContent = '编辑文章';
            document.getElementById('articleId').value = id;

            fetch('api/articles.php')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const article = result.data.find(a => a.id === id);
                        if (article) {
                            document.getElementById('articleTitle').value = article.title;
                            document.getElementById('articleContent').value = article.content;
                            loadCategoryOptions(article.category_id || null);
                            document.getElementById('articleModal').classList.add('show');
                        }
                    }
                });
        }

        function closeArticleModal() {
            document.getElementById('articleModal').classList.remove('show');
        }

        function deleteArticle(id) {
            if (!hasButtonPermission('articles', 'delete')) {
                showToast('您没有权限删除文章', 'error');
                return;
            }
            if (!confirm('确定要删除该文章吗？')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            fetch('api/articles.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showToast(result.message, 'success');
                    loadArticles();
                } else {
                    showToast(result.message, 'error');
                }
            })
            .catch(error => {
                showToast('网络错误，请重试', 'error');
            });
        }

        document.getElementById('articleForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const articleId = document.getElementById('articleId').value;

            if (articleId) {
                formData.append('action', 'edit');
            } else {
                formData.append('action', 'add');
            }

            fetch('api/articles.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showToast(result.message, 'success');
                    closeArticleModal();
                    loadArticles();
                } else {
                    showToast(result.message, 'error');
                }
            })
            .catch(error => {
                showToast('网络错误，请重试', 'error');
            });
        });

        document.getElementById('userModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeUserModal();
            }
        });

        document.getElementById('articleModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeArticleModal();
            }
        });

        function loadCategories() {
            fetch('api/categories.php')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        renderCategoriesTable(result.data);
                    } else {
                        showToast(result.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('网络错误，请重试', 'error');
                });
        }

        function renderCategoriesTable(categories) {
            const tbody = document.getElementById('categoriesTableBody');
            if (categories.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty">暂无数据</td></tr>';
                return;
            }

            const canEdit = hasButtonPermission('categories', 'edit');
            const canDelete = hasButtonPermission('categories', 'delete');

            tbody.innerHTML = categories.map(category => {
                let buttons = '';
                if (canEdit) {
                    buttons += `<button class="btn btn-success" onclick="editCategory(${category.id})">编辑</button>`;
                } else {
                    buttons += `<button class="btn btn-success disabled">编辑</button>`;
                }
                if (canDelete) {
                    buttons += `<button class="btn btn-danger" onclick="deleteCategory(${category.id})">删除</button>`;
                } else {
                    buttons += `<button class="btn btn-danger disabled">删除</button>`;
                }
                return `
                    <tr>
                        <td>${category.id}</td>
                        <td>${category.name}</td>
                        <td>${category.description || '-'}</td>
                        <td>${category.created_at}</td>
                        <td>${category.updated_at}</td>
                        <td>
                            <div class="action-btns">
                                ${buttons}
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function openAddCategoryModal() {
            if (!hasButtonPermission('categories', 'add')) {
                showToast('您没有权限添加分类', 'error');
                return;
            }
            document.getElementById('categoryModalTitle').textContent = '添加分类';
            document.getElementById('categoryId').value = '';
            document.getElementById('categoryName').value = '';
            document.getElementById('categoryDescription').value = '';
            document.getElementById('categoryModal').classList.add('show');
        }

        function editCategory(id) {
            if (!hasButtonPermission('categories', 'edit')) {
                showToast('您没有权限编辑分类', 'error');
                return;
            }
            document.getElementById('categoryModalTitle').textContent = '编辑分类';
            document.getElementById('categoryId').value = id;

            fetch('api/categories.php')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const category = result.data.find(c => c.id === id);
                        if (category) {
                            document.getElementById('categoryName').value = category.name;
                            document.getElementById('categoryDescription').value = category.description || '';
                            document.getElementById('categoryModal').classList.add('show');
                        }
                    }
                });
        }

        function closeCategoryModal() {
            document.getElementById('categoryModal').classList.remove('show');
        }

        function deleteCategory(id) {
            if (!hasButtonPermission('categories', 'delete')) {
                showToast('您没有权限删除分类', 'error');
                return;
            }
            if (!confirm('确定要删除该分类吗？')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            fetch('api/categories.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showToast(result.message, 'success');
                    loadCategories();
                } else {
                    showToast(result.message, 'error');
                }
            })
            .catch(error => {
                showToast('网络错误，请重试', 'error');
            });
        }

        document.getElementById('categoryForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const categoryId = document.getElementById('categoryId').value;

            if (categoryId) {
                formData.append('action', 'edit');
            } else {
                formData.append('action', 'add');
            }

            fetch('api/categories.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showToast(result.message, 'success');
                    closeCategoryModal();
                    loadCategories();
                } else {
                    showToast(result.message, 'error');
                }
            })
            .catch(error => {
                showToast('网络错误，请重试', 'error');
            });
        });

        document.getElementById('categoryModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCategoryModal();
            }
        });

        function loadRoles() {
            fetch('api/roles.php')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        renderRolesTable(result.data);
                    } else {
                        showToast(result.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('网络错误，请重试', 'error');
                });
        }

        function renderRolesTable(roles) {
            const tbody = document.getElementById('rolesTableBody');
            if (roles.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty">暂无数据</td></tr>';
                return;
            }

            const dataScopeMap = {
                'all': '全部数据',
                'own': '仅本人数据'
            };

            const canEdit = hasButtonPermission('roles', 'edit');
            const canDelete = hasButtonPermission('roles', 'delete');

            tbody.innerHTML = roles.map(role => {
                let buttons = '';
                if (canEdit) {
                    buttons += `<button class="btn btn-success" onclick="editRole(${role.id})">编辑</button>`;
                } else {
                    buttons += `<button class="btn btn-success disabled">编辑</button>`;
                }
                if (canDelete) {
                    buttons += `<button class="btn btn-danger" onclick="deleteRole(${role.id})">删除</button>`;
                } else {
                    buttons += `<button class="btn btn-danger disabled">删除</button>`;
                }
                return `
                    <tr>
                        <td>${role.id}</td>
                        <td>${role.name}</td>
                        <td>${role.description || '-'}</td>
                        <td>${dataScopeMap[role.data_scope] || role.data_scope}</td>
                        <td>${role.user_count}</td>
                        <td>${role.created_at}</td>
                        <td>
                            <div class="action-btns">
                                ${buttons}
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function openAddRoleModal() {
            if (!hasButtonPermission('roles', 'add')) {
                showToast('您没有权限添加角色', 'error');
                return;
            }
            document.getElementById('roleModalTitle').textContent = '添加角色';
            document.getElementById('roleId').value = '';
            document.getElementById('roleName').value = '';
            document.getElementById('roleDescription').value = '';
            document.getElementById('roleDataScope').value = 'all';
            document.getElementById('roleModal').classList.add('show');
        }

        function editRole(id) {
            if (!hasButtonPermission('roles', 'edit')) {
                showToast('您没有权限编辑角色', 'error');
                return;
            }
            document.getElementById('roleModalTitle').textContent = '编辑角色';
            document.getElementById('roleId').value = id;

            fetch('api/roles.php?id=' + id)
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const role = result.data;
                        document.getElementById('roleName').value = role.name;
                        document.getElementById('roleDescription').value = role.description || '';
                        document.getElementById('roleDataScope').value = role.data_scope || 'all';
                        document.getElementById('roleModal').classList.add('show');
                    } else {
                        showToast(result.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('网络错误，请重试', 'error');
                });
        }

        function closeRoleModal() {
            document.getElementById('roleModal').classList.remove('show');
        }

        function deleteRole(id) {
            if (!hasButtonPermission('roles', 'delete')) {
                showToast('您没有权限删除角色', 'error');
                return;
            }
            if (!confirm('确定要删除该角色吗？')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            fetch('api/roles.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showToast(result.message, 'success');
                    loadRoles();
                } else {
                    showToast(result.message, 'error');
                }
            })
            .catch(error => {
                showToast('网络错误，请重试', 'error');
            });
        }

        document.getElementById('roleForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const roleId = document.getElementById('roleId').value;

            if (roleId) {
                formData.append('action', 'edit');
            } else {
                formData.append('action', 'add');
            }

            fetch('api/roles.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showToast(result.message, 'success');
                    closeRoleModal();
                    loadRoles();
                } else {
                    showToast(result.message, 'error');
                }
            })
            .catch(error => {
                showToast('网络错误，请重试', 'error');
            });
        });

        document.getElementById('roleModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRoleModal();
            }
        });

        let currentSystemMenus = [];

        function loadPermissionRoles() {
            fetch('api/roles.php')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const select = document.getElementById('permissionRoleSelect');
                        select.innerHTML = '<option value="">请选择角色</option>';
                        result.data.forEach(role => {
                            const option = document.createElement('option');
                            option.value = role.id;
                            option.textContent = role.name;
                            select.appendChild(option);
                        });
                        document.getElementById('permissionConfigContainer').style.display = 'none';
                    } else {
                        showToast(result.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('网络错误，请重试', 'error');
                });
        }

        function loadRolePermissions() {
            const roleId = document.getElementById('permissionRoleSelect').value;
            if (!roleId) {
                document.getElementById('permissionConfigContainer').style.display = 'none';
                return;
            }

            fetch('api/permissions.php?role_id=' + roleId)
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        currentSystemMenus = result.data.system_menus;
                        renderMenuPermissions(result.data.menu_permissions, result.data.system_menus);
                        renderButtonPermissions(result.data.button_permissions, result.data.system_menus);
                        document.getElementById('permissionConfigContainer').style.display = 'block';
                    } else {
                        showToast(result.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('网络错误，请重试', 'error');
                });
        }

        function renderMenuPermissions(menuPermissions, systemMenus) {
            const container = document.getElementById('menuPermissionsContainer');
            const menuNameMap = {
                'welcome': '欢迎页',
                'users': '用户管理',
                'articles': '文章管理',
                'categories': '文章分类管理',
                'roles': '角色管理',
                'permissions': '权限配置'
            };

            let html = '<div style="display: flex; flex-wrap: wrap; gap: 20px;">';
            systemMenus.forEach(menu => {
                const isChecked = menuPermissions.includes(menu.id);
                html += `
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" class="menu-permission-checkbox" value="${menu.id}" ${isChecked ? 'checked' : ''} onchange="toggleMenuPermission('${menu.id}')">
                        <span>${menuNameMap[menu.id] || menu.name}</span>
                    </label>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }

        function renderButtonPermissions(buttonPermissions, systemMenus) {
            const container = document.getElementById('buttonPermissionsContainer');
            const moduleNameMap = {
                'welcome': '欢迎页',
                'users': '用户管理',
                'articles': '文章管理',
                'categories': '文章分类管理',
                'roles': '角色管理',
                'permissions': '权限配置'
            };
            const actionNameMap = {
                'add': '新增',
                'edit': '编辑',
                'delete': '删除'
            };

            let html = '';
            systemMenus.forEach(menu => {
                if (menu.buttons && menu.buttons.length > 0) {
                    html += `<div style="margin-bottom: 20px; padding: 15px; background: #f5f7fa; border-radius: 4px;">`;
                    html += `<h5 style="margin-bottom: 10px; color: #333;">${moduleNameMap[menu.id] || menu.name}</h5>`;
                    html += '<div style="display: flex; flex-wrap: wrap; gap: 20px;">';
                    menu.buttons.forEach(action => {
                        const modulePermissions = buttonPermissions[menu.id] || [];
                        const isChecked = modulePermissions.includes(action);
                        html += `
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" class="button-permission-checkbox" data-module="${menu.id}" data-action="${action}" ${isChecked ? 'checked' : ''}>
                                <span>${actionNameMap[action] || action}</span>
                            </label>
                        `;
                    });
                    html += '</div></div>';
                }
            });
            container.innerHTML = html;
        }

        function toggleMenuPermission(menuId) {
            const systemMenus = currentSystemMenus;
            const menu = systemMenus.find(m => m.id === menuId);
            if (!menu || !menu.buttons) return;

            const menuCheckbox = document.querySelector(`.menu-permission-checkbox[value="${menuId}"]`);
            const isChecked = menuCheckbox && menuCheckbox.checked;

            menu.buttons.forEach(action => {
                const buttonCheckbox = document.querySelector(`.button-permission-checkbox[data-module="${menuId}"][data-action="${action}"]`);
                if (buttonCheckbox) {
                    buttonCheckbox.checked = isChecked;
                }
            });
        }

        function savePermissions() {
            if (!hasButtonPermission('permissions', 'edit')) {
                showToast('您没有权限修改权限配置', 'error');
                return;
            }
            const roleId = document.getElementById('permissionRoleSelect').value;
            if (!roleId) {
                showToast('请先选择角色', 'error');
                return;
            }

            const menuPermissions = [];
            document.querySelectorAll('.menu-permission-checkbox:checked').forEach(checkbox => {
                menuPermissions.push(checkbox.value);
            });

            const buttonPermissions = {};
            document.querySelectorAll('.button-permission-checkbox:checked').forEach(checkbox => {
                const module = checkbox.dataset.module;
                const action = checkbox.dataset.action;
                if (!buttonPermissions[module]) {
                    buttonPermissions[module] = [];
                }
                buttonPermissions[module].push(action);
            });

            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('role_id', roleId);
            formData.append('menu_permissions', JSON.stringify(menuPermissions));
            formData.append('button_permissions', JSON.stringify(buttonPermissions));

            fetch('api/permissions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showToast(result.message, 'success');
                } else {
                    showToast(result.message, 'error');
                }
            })
            .catch(error => {
                showToast('网络错误，请重试', 'error');
            });
        }

        function loadUserRoleOptions(selectedId = null) {
            fetch('api/users.php?roles=1')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        let select = document.getElementById('userRole');
                        if (!select) {
                            select = document.createElement('select');
                            select.id = 'userRole';
                            select.name = 'role_id';
                        }
                        select.innerHTML = '<option value="">请选择角色</option>';
                        result.data.forEach(role => {
                            const option = document.createElement('option');
                            option.value = role.id;
                            option.textContent = role.name;
                            if (selectedId !== null && role.id == selectedId) {
                                option.selected = true;
                            }
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    showToast('加载角色列表失败', 'error');
                });
        }

        function updateUserFormWithRole() {
            const userForm = document.getElementById('userForm');
            let roleGroup = document.getElementById('userRoleGroup');
            if (!roleGroup) {
                roleGroup = document.createElement('div');
                roleGroup.id = 'userRoleGroup';
                roleGroup.className = 'form-group';
                roleGroup.innerHTML = `
                    <label>角色</label>
                    <select id="userRole" name="role_id">
                        <option value="">请选择角色</option>
                    </select>
                `;
                const phoneGroup = document.getElementById('confirmPasswordGroup');
                if (phoneGroup && phoneGroup.parentNode) {
                    phoneGroup.parentNode.insertBefore(roleGroup, phoneGroup.nextSibling);
                }
            }
            loadUserRoleOptions();
        }
    </script>
</body>
</html>
