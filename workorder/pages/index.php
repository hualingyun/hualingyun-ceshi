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
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .toolbar {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            background-color: #1890ff;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #40a9ff;
        }
        
        .btn-success {
            background-color: #52c41a;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #73d13d;
        }
        
        .btn-danger {
            background-color: #ff4d4f;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #ff7875;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e8e8e8;
        }
        
        th {
            background-color: #fafafa;
            font-weight: 600;
            color: #333;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .status-pending {
            background-color: #fff7e6;
            color: #fa8c16;
            border: 1px solid #ffd591;
        }
        
        .status-processing {
            background-color: #e6f7ff;
            color: #1890ff;
            border: 1px solid #91d5ff;
        }
        
        .status-completed {
            background-color: #f6ffed;
            color: #52c41a;
            border: 1px solid #b7eb8f;
        }
        
        .actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-small {
            padding: 4px 12px;
            font-size: 12px;
        }
        
        .empty-message {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>工单管理系统</h1>
        
        <div class="toolbar">
            <div></div>
            <button class="btn btn-primary" onclick="addWorkOrder()">+ 添加工单</button>
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
            <tbody id="workOrderTable">
                <tr>
                    <td colspan="7" class="loading">加载中...</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <script>
        const API_URL = '../api/workorder.php';
        
        function loadWorkOrders() {
            fetch(API_URL)
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        renderTable(result.data);
                    } else {
                        showError(result.message);
                    }
                })
                .catch(error => {
                    showError('加载数据失败: ' + error.message);
                });
        }
        
        function renderTable(workOrders) {
            const tbody = document.getElementById('workOrderTable');
            
            if (workOrders.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty-message">暂无工单数据</td></tr>';
                return;
            }
            
            tbody.innerHTML = workOrders.map(wo => `
                <tr>
                    <td>${wo.id}</td>
                    <td>${wo.subject}</td>
                    <td>${wo.category}</td>
                    <td><span class="status status-${getStatusClass(wo.status)}">${wo.status}</span></td>
                    <td>${wo.created_at}</td>
                    <td>${wo.creator}</td>
                    <td class="actions">
                        <button class="btn btn-success btn-small" onclick="editWorkOrder('${wo.id}')">编辑</button>
                        <button class="btn btn-danger btn-small" onclick="deleteWorkOrder('${wo.id}')">删除</button>
                    </td>
                </tr>
            `).join('');
        }
        
        function getStatusClass(status) {
            switch(status) {
                case '待处理': return 'pending';
                case '处理中': return 'processing';
                case '已完成': return 'completed';
                default: return 'pending';
            }
        }
        
        function addWorkOrder() {
            window.location.href = 'form.php';
        }
        
        function editWorkOrder(id) {
            window.location.href = 'form.php?id=' + id;
        }
        
        function deleteWorkOrder(id) {
            if (!confirm('确定要删除该工单吗？')) {
                return;
            }
            
            fetch(API_URL, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('删除成功');
                    loadWorkOrders();
                } else {
                    alert('删除失败: ' + result.message);
                }
            })
            .catch(error => {
                alert('删除失败: ' + error.message);
            });
        }
        
        function showError(message) {
            document.getElementById('workOrderTable').innerHTML = 
                `<tr><td colspan="7" class="empty-message">${message}</td></tr>`;
        }
        
        document.addEventListener('DOMContentLoaded', loadWorkOrders);
    </script>
</body>
</html>
