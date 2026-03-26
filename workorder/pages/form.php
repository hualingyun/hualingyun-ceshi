<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>工单表单</title>
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
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        label .required {
            color: #ff4d4f;
            margin-left: 4px;
        }
        
        input[type="text"],
        input[type="datetime-local"],
        select,
        textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d9d9d9;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="datetime-local"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #40a9ff;
            box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-actions {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 10px 30px;
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
        
        .btn-default {
            background-color: #fff;
            color: #333;
            border: 1px solid #d9d9d9;
        }
        
        .btn-default:hover {
            border-color: #40a9ff;
            color: #40a9ff;
        }
        
        .error-message {
            color: #ff4d4f;
            font-size: 12px;
            margin-top: 4px;
            display: none;
        }
        
        .form-group.error input,
        .form-group.error select,
        .form-group.error textarea {
            border-color: #ff4d4f;
        }
        
        .form-group.error .error-message {
            display: block;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #1890ff;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← 返回列表</a>
        <h1 id="pageTitle">添加工单</h1>
        
        <form id="workOrderForm">
            <input type="hidden" id="workOrderId" name="id">
            
            <div class="form-group">
                <label>工单主题<span class="required">*</span></label>
                <input type="text" id="subject" name="subject" placeholder="请输入工单主题">
                <div class="error-message">请输入工单主题</div>
            </div>
            
            <div class="form-group">
                <label>类别<span class="required">*</span></label>
                <select id="category" name="category">
                    <option value="">请选择类别</option>
                    <option value="日常工单">日常工单</option>
                    <option value="事件工单">事件工单</option>
                    <option value="紧急工单">紧急工单</option>
                    <option value="维护工单">维护工单</option>
                </select>
                <div class="error-message">请选择类别</div>
            </div>
            
            <div class="form-group">
                <label>问题描述<span class="required">*</span></label>
                <textarea id="description" name="description" placeholder="请详细描述问题"></textarea>
                <div class="error-message">请输入问题描述</div>
            </div>
            
            <div class="form-group">
                <label>计划开始时间<span class="required">*</span></label>
                <input type="datetime-local" id="start_time" name="start_time">
                <div class="error-message">请选择计划开始时间</div>
            </div>
            
            <div class="form-group">
                <label>执行人<span class="required">*</span></label>
                <input type="text" id="executor" name="executor" placeholder="请输入执行人姓名">
                <div class="error-message">请输入执行人</div>
            </div>
            
            <div class="form-group">
                <label>计划结束时间<span class="required">*</span></label>
                <input type="datetime-local" id="end_time" name="end_time">
                <div class="error-message">请选择计划结束时间</div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-default" onclick="goBack()">取消</button>
                <button type="submit" class="btn btn-primary">保存</button>
            </div>
        </form>
    </div>
    
    <script>
        const API_URL = '../api/workorder.php';
        let isEditMode = false;
        
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const workOrderId = urlParams.get('id');
            
            if (workOrderId) {
                isEditMode = true;
                document.getElementById('pageTitle').textContent = '编辑工单';
                document.getElementById('workOrderId').value = workOrderId;
                loadWorkOrder(workOrderId);
            }
            
            document.getElementById('workOrderForm').addEventListener('submit', handleSubmit);
        });
        
        function loadWorkOrder(id) {
            fetch(API_URL + '?id=' + id)
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        fillForm(result.data);
                    } else {
                        alert('加载工单失败: ' + result.message);
                    }
                })
                .catch(error => {
                    alert('加载工单失败: ' + error.message);
                });
        }
        
        function fillForm(data) {
            document.getElementById('subject').value = data.subject || '';
            document.getElementById('category').value = data.category || '';
            document.getElementById('description').value = data.description || '';
            document.getElementById('start_time').value = formatDateTimeLocal(data.start_time) || '';
            document.getElementById('executor').value = data.executor || '';
            document.getElementById('end_time').value = formatDateTimeLocal(data.end_time) || '';
        }
        
        function formatDateTimeLocal(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return dateStr;
            
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            
            return `${year}-${month}-${day}T${hours}:${minutes}`;
        }
        
        function handleSubmit(e) {
            e.preventDefault();
            
            if (!validateForm()) {
                return;
            }
            
            const formData = {
                subject: document.getElementById('subject').value.trim(),
                category: document.getElementById('category').value,
                description: document.getElementById('description').value.trim(),
                start_time: document.getElementById('start_time').value,
                executor: document.getElementById('executor').value.trim(),
                end_time: document.getElementById('end_time').value,
                creator: '管理员'
            };
            
            if (isEditMode) {
                formData.id = document.getElementById('workOrderId').value;
            }
            
            fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert(isEditMode ? '工单更新成功' : '工单创建成功');
                    window.location.href = 'index.php';
                } else {
                    alert('保存失败: ' + result.message);
                }
            })
            .catch(error => {
                alert('保存失败: ' + error.message);
            });
        }
        
        function validateForm() {
            let isValid = true;
            
            const fields = ['subject', 'category', 'description', 'start_time', 'executor', 'end_time'];
            
            fields.forEach(field => {
                const element = document.getElementById(field);
                const formGroup = element.closest('.form-group');
                
                if (!element.value.trim()) {
                    formGroup.classList.add('error');
                    isValid = false;
                } else {
                    formGroup.classList.remove('error');
                }
            });
            
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            
            if (startTime && endTime && new Date(startTime) >= new Date(endTime)) {
                alert('计划结束时间必须晚于计划开始时间');
                isValid = false;
            }
            
            return isValid;
        }
        
        function goBack() {
            window.location.href = 'index.php';
        }
        
        document.querySelectorAll('input, select, textarea').forEach(element => {
            element.addEventListener('input', function() {
                this.closest('.form-group').classList.remove('error');
            });
        });
    </script>
</body>
</html>
