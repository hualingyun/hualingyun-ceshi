<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>工单管理系统</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Microsoft YaHei', Arial, sans-serif;
            background-color: #f5f7fa;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .header h1 {
            font-size: 24px;
            color: #333;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        .btn-primary {
            background-color: #409eff;
            color: #fff;
        }
        .btn-primary:hover {
            background-color: #66b1ff;
        }
        .btn-edit {
            background-color: #e6a23c;
            color: #fff;
            margin-right: 5px;
        }
        .btn-edit:hover {
            background-color: #ebb563;
        }
        .btn-delete {
            background-color: #f56c6c;
            color: #fff;
        }
        .btn-delete:hover {
            background-color: #f78989;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ebeef5;
        }
        th {
            background-color: #f5f7fa;
            color: #909399;
            font-weight: 500;
        }
        tr:hover {
            background-color: #f5f7fa;
        }
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        .status-pending {
            background-color: #ecf5ff;
            color: #409eff;
        }
        .status-processing {
            background-color: #fdf6ec;
            color: #e6a23c;
        }
        .status-completed {
            background-color: #f0f9eb;
            color: #67c23a;
        }
        .empty {
            text-align: center;
            padding: 40px;
            color: #909399;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <button class="btn btn-primary" onclick="goToAdd()">新增工单</button>
            <h1>工单管理系统</h1>
        </div>
        <table>
            <thead>
                <tr>
                    <th>工单编号</th>
                    <th>工单主题</th>
                    <th>类别</th>
                    <th>工单状态</th>
                    <th>创建日期</th>
                    <th>创建人</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="tableBody">
            </tbody>
        </table>
    </div>

    <script>
        function loadData() {
            fetch('api.php')
                .then(res => res.json())
                .then(result => {
                    const tbody = document.getElementById('tableBody');
                    if (result.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" class="empty">暂无工单数据</td></tr>';
                        return;
                    }
                    tbody.innerHTML = result.data.map(item => `
                        <tr>
                            <td>${item.orderNo}</td>
                            <td>${item.subject}</td>
                            <td>${item.category}</td>
                            <td><span class="status status-pending">${item.status}</span></td>
                            <td>${item.created_at}</td>
                            <td>${item.creator}</td>
                            <td>
                                <button class="btn btn-edit" onclick="editItem('${item.id}')">编辑</button>
                                <button class="btn btn-delete" onclick="deleteItem('${item.id}')">删除</button>
                            </td>
                        </tr>
                    `).join('');
                });
        }

        function goToAdd() {
            window.location.href = 'form.php';
        }

        function editItem(id) {
            window.location.href = 'form.php?id=' + id;
        }

        function deleteItem(id) {
            if (confirm('确定要删除该工单吗？')) {
                fetch('api.php?id=' + id, {
                    method: 'DELETE'
                }).then(() => {
                    loadData();
                });
            }
        }

        loadData();
    </script>
</body>
</html>