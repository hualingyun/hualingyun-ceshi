<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($_GET['id']) ? '编辑工单' : '新增工单'; ?></title>
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
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .header {
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .header h1 {
            font-size: 24px;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #606266;
        }
        .required {
            color: #f56c6c;
            margin-left: 4px;
        }
        input, select, textarea {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #dcdfe6;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #409eff;
        }
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        .error {
            color: #f56c6c;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }
        .input-error {
            border-color: #f56c6c;
        }
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        .btn {
            padding: 12px 30px;
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
        .btn-default {
            background-color: #fff;
            border: 1px solid #dcdfe6;
            color: #606266;
        }
        .btn-default:hover {
            border-color: #409eff;
            color: #409eff;
        }
        .form-row {
            display: flex;
            gap: 20px;
        }
        .form-row .form-group {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 id="pageTitle"><?php echo isset($_GET['id']) ? '编辑工单' : '新增工单'; ?></h1>
        </div>
        <form id="orderForm">
            <div class="form-group">
                <label>工单编号<span class="required">*</span></label>
                <input type="text" id="orderNo" name="orderNo" placeholder="请输入工单编号（6-20字符，以字母开头，必须包含大小写字母和数字）">
                <div class="error" id="orderNoError"></div>
            </div>
            <div class="form-group">
                <label>工单主题<span class="required">*</span></label>
                <input type="text" id="subject" name="subject" placeholder="请输入工单主题">
                <div class="error" id="subjectError"></div>
            </div>
            <div class="form-group">
                <label>类别<span class="required">*</span></label>
                <select id="category" name="category">
                    <option value="">请选择工单类别</option>
                    <option value="日常工单">日常工单</option>
                    <option value="事件工单">事件工单</option>
                </select>
                <div class="error" id="categoryError"></div>
            </div>
            <div class="form-group">
                <label>问题描述</label>
                <textarea id="description" name="description" placeholder="请输入问题描述"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>计划开始时间</label>
                    <input type="datetime-local" id="startTime" name="startTime">
                </div>
                <div class="form-group">
                    <label>计划结束时间</label>
                    <input type="datetime-local" id="endTime" name="endTime">
                </div>
            </div>
            <div class="form-group">
                <label>执行人<span class="required">*</span></label>
                <input type="text" id="executor" name="executor" placeholder="请输入执行人">
                <div class="error" id="executorError"></div>
            </div>
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">保存</button>
                <button type="button" class="btn btn-default" onclick="goBack()">返回</button>
            </div>
        </form>
    </div>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const editId = urlParams.get('id');
        let isEdit = !!editId;

        function loadEditData() {
            if (!isEdit) return;
            fetch('api.php')
                .then(res => res.json())
                .then(result => {
                    const item = result.data.find(i => i.id === editId);
                    if (item) {
                        document.getElementById('orderNo').value = item.orderNo || '';
                        document.getElementById('subject').value = item.subject || '';
                        document.getElementById('category').value = item.category || '';
                        document.getElementById('description').value = item.description || '';
                        document.getElementById('startTime').value = item.startTime || '';
                        document.getElementById('endTime').value = item.endTime || '';
                        document.getElementById('executor').value = item.executor || '';
                    }
                });
        }

        function validateOrderNo(value) {
            const regex = /^[a-zA-Z][a-zA-Z0-9]{5,19}$/;
            if (!value) {
                return '工单编号不能为空';
            }
            if (!regex.test(value)) {
                return '工单编号必须6-20字符，以字母开头，仅包含大小写字母和数字';
            }
            if (!/[A-Z]/.test(value)) {
                return '工单编号必须包含大写字母';
            }
            if (!/[a-z]/.test(value)) {
                return '工单编号必须包含小写字母';
            }
            if (!/[0-9]/.test(value)) {
                return '工单编号必须包含数字';
            }
            return '';
        }

        function showError(fieldId, message) {
            const errorEl = document.getElementById(fieldId + 'Error');
            const inputEl = document.getElementById(fieldId);
            if (message) {
                errorEl.textContent = message;
                errorEl.style.display = 'block';
                inputEl.classList.add('input-error');
            } else {
                errorEl.textContent = '';
                errorEl.style.display = 'none';
                inputEl.classList.remove('input-error');
            }
        }

        function validateForm() {
            let isValid = true;
            
            const orderNo = document.getElementById('orderNo').value.trim();
            const orderNoError = validateOrderNo(orderNo);
            showError('orderNo', orderNoError);
            if (orderNoError) isValid = false;

            const subject = document.getElementById('subject').value.trim();
            showError('subject', subject ? '' : '工单主题不能为空');
            if (!subject) isValid = false;

            const category = document.getElementById('category').value;
            showError('category', category ? '' : '请选择工单类别');
            if (!category) isValid = false;

            const executor = document.getElementById('executor').value.trim();
            showError('executor', executor ? '' : '执行人不能为空');
            if (!executor) isValid = false;

            return isValid;
        }

        document.getElementById('orderForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!validateForm()) {
                return;
            }

            const formData = {
                orderNo: document.getElementById('orderNo').value.trim(),
                subject: document.getElementById('subject').value.trim(),
                category: document.getElementById('category').value,
                description: document.getElementById('description').value,
                startTime: document.getElementById('startTime').value,
                endTime: document.getElementById('endTime').value,
                executor: document.getElementById('executor').value.trim(),
                creator: '管理员'
            };

            const url = isEdit ? 'api.php?id=' + editId : 'api.php';
            const method = isEdit ? 'PUT' : 'POST';

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            }).then(() => {
                alert('保存成功！');
                goBack();
            });
        });

        function goBack() {
            window.location.href = 'index.php';
        }

        loadEditData();
    </script>
</body>
</html>