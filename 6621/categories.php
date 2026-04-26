<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分类管理 - 个人博客管理系统</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>个人博客管理系统</h1>
            <nav>
                <a href="index.php" class="nav-link">博客列表</a>
                <a href="categories.php" class="nav-link active">分类管理</a>
            </nav>
        </header>

        <main>
            <div class="action-bar">
                <button class="btn btn-primary" id="addCategoryBtn">+ 添加分类</button>
            </div>

            <div class="table-container">
                <table id="categoryTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>分类名称</th>
                            <th>博客数量</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="categoryList">
                        <tr>
                            <td colspan="4" class="loading">加载中...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="categoryModalTitle">添加分类</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    <input type="hidden" id="categoryId">
                    <div class="form-group">
                        <label for="categoryName">分类名称 <span class="required">*</span></label>
                        <input type="text" id="categoryName" name="name" required placeholder="请输入分类名称">
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancelCategoryBtn">取消</button>
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
                <p id="confirmMessage">确定要删除这个分类吗？此操作不可恢复。</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelDelete">取消</button>
                <button class="btn btn-danger" id="confirmDelete">删除</button>
            </div>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script src="js/categories.js"></script>
</body>
</html>
