<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人博客管理系统</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>个人博客管理系统</h1>
            <nav>
                <a href="index.php" class="nav-link active">博客列表</a>
                <a href="categories.php" class="nav-link">分类管理</a>
            </nav>
        </header>

        <main>
            <div class="action-bar">
                <button class="btn btn-primary" id="addBlogBtn">+ 添加博客</button>
            </div>

            <div class="table-container">
                <table id="blogTable">
                    <thead>
                        <tr>
                            <th>博客标题</th>
                            <th>类别</th>
                            <th>创建日期</th>
                            <th>是否置顶</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="blogList">
                        <tr>
                            <td colspan="5" class="loading">加载中...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div id="blogModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">添加博客</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="blogForm">
                    <input type="hidden" id="blogId">
                    <div class="form-group">
                        <label for="blogTitle">博客主题 <span class="required">*</span></label>
                        <input type="text" id="blogTitle" name="title" required placeholder="请输入博客标题">
                    </div>
                    <div class="form-group">
                        <label for="blogCategory">博客类别 <span class="required">*</span></label>
                        <select id="blogCategory" name="category_id" required>
                            <option value="">请选择类别</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="blogContent">博客内容 <span class="required">*</span></label>
                        <textarea id="blogContent" name="content" required placeholder="请输入博客内容" rows="8"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="blogIsTop" name="is_top">
                            <span class="checkmark"></span>
                            是否置顶
                        </label>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancelBtn">取消</button>
                        <button type="submit" class="btn btn-primary">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="confirmModal" class="modal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h2>确认删除</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p id="confirmMessage">确定要删除这个博客吗？此操作不可恢复。</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelDelete">取消</button>
                <button class="btn btn-danger" id="confirmDelete">删除</button>
            </div>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script src="js/app.js"></script>
</body>
</html>
