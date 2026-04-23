<?php
require_once 'config.php';
require_login();
$current_user = app_get_current_user();
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
        .sidebar .menu-item .icon {
            font-size: 16px;
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
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #dcdfe6;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #409eff;
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
            <div class="menu-item" data-page="categories">
                <span class="icon">📂</span>
                <span>分类管理</span>
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
                    <button class="btn btn-primary" onclick="openAddUserModal()">添加用户</button>
                </div>
                <div class="table-container">
                    <table id="usersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>用户名</th>
                                <th>手机号</th>
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
                    <button class="btn btn-primary" onclick="openAddArticleModal()">添加文章</button>
                </div>
                <div class="table-container">
                    <table id="articlesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>标题</th>
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
                    <button class="btn btn-primary" onclick="openAddCategoryModal()">添加分类</button>
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

    <div id="toast" class="toast"></div>

    <script>
        function showToast(message, type = 'error') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + type + ' show';
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');

                const page = this.dataset.page;
                document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
                
                if (page === 'welcome') {
                    document.getElementById('welcomePage').classList.add('active');
                } else if (page === 'users') {
                    document.getElementById('usersPage').classList.add('active');
                    loadUsers();
                } else if (page === 'articles') {
                    document.getElementById('articlesPage').classList.add('active');
                    loadArticles();
                } else if (page === 'categories') {
                    document.getElementById('categoriesPage').classList.add('active');
                    loadCategories();
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
                tbody.innerHTML = '<tr><td colspan="5" class="empty">暂无数据</td></tr>';
                return;
            }

            tbody.innerHTML = users.map(user => `
                <tr>
                    <td>${user.id}</td>
                    <td>${user.username}</td>
                    <td>${user.phone}</td>
                    <td>${user.created_at}</td>
                    <td>
                        <div class="action-btns">
                            <button class="btn btn-success" onclick="editUser(${user.id})">编辑</button>
                            <button class="btn btn-danger" onclick="deleteUser(${user.id})">删除</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function openAddUserModal() {
            document.getElementById('userModalTitle').textContent = '添加用户';
            document.getElementById('userId').value = '';
            document.getElementById('userName').value = '';
            document.getElementById('userPassword').value = '';
            document.getElementById('confirmPassword').value = '';
            document.getElementById('userPhone').value = '';
            document.getElementById('confirmPasswordGroup').style.display = 'block';
            document.getElementById('userPassword').placeholder = '6-20字符，字母开头，大小写+数字';
            document.getElementById('userModal').classList.add('show');
        }

        function editUser(id) {
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
                            document.getElementById('userModal').classList.add('show');
                        }
                    }
                });
        }

        function closeUserModal() {
            document.getElementById('userModal').classList.remove('show');
        }

        function deleteUser(id) {
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
                tbody.innerHTML = '<tr><td colspan="6" class="empty">暂无数据</td></tr>';
                return;
            }

            tbody.innerHTML = articles.map(article => `
                <tr>
                    <td>${article.id}</td>
                    <td>${article.title}</td>
                    <td>${article.author_name}</td>
                    <td>${article.created_at}</td>
                    <td>${article.updated_at}</td>
                    <td>
                        <div class="action-btns">
                            <button class="btn btn-success" onclick="editArticle(${article.id})">编辑</button>
                            <button class="btn btn-danger" onclick="deleteArticle(${article.id})">删除</button>
                        </div>
                    </td>
                </tr>
            `).join('');
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
            document.getElementById('articleModalTitle').textContent = '添加文章';
            document.getElementById('articleId').value = '';
            document.getElementById('articleTitle').value = '';
            document.getElementById('articleContent').value = '';
            loadCategoryOptions();
            document.getElementById('articleModal').classList.add('show');
        }

        function editArticle(id) {
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

            tbody.innerHTML = categories.map(category => `
                <tr>
                    <td>${category.id}</td>
                    <td>${category.name}</td>
                    <td>${category.description || '-'}</td>
                    <td>${category.created_at}</td>
                    <td>${category.updated_at}</td>
                    <td>
                        <div class="action-btns">
                            <button class="btn btn-success" onclick="editCategory(${category.id})">编辑</button>
                            <button class="btn btn-danger" onclick="deleteCategory(${category.id})">删除</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function openAddCategoryModal() {
            document.getElementById('categoryModalTitle').textContent = '添加分类';
            document.getElementById('categoryId').value = '';
            document.getElementById('categoryName').value = '';
            document.getElementById('categoryDescription').value = '';
            document.getElementById('categoryModal').classList.add('show');
        }

        function editCategory(id) {
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
    </script>
</body>
</html>
