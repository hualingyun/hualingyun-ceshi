<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>值班系统 - 登录</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .login-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            width: 100%;
            padding: 12px;
            font-size: 16px;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #5a6fd6 0%, #6a4190 100%);
        }
        .tab-btn {
            border: none;
            background: none;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 16px;
            color: #666;
            border-bottom: 2px solid transparent;
        }
        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2 class="login-title">值班系统</h2>
        
        <div class="d-flex justify-content-center mb-4">
            <button class="tab-btn active" onclick="switchTab('login')">登录</button>
            <button class="tab-btn" onclick="switchTab('register')">注册</button>
        </div>
        
        <div id="login-form">
            <form onsubmit="handleLogin(event)">
                <div class="mb-3">
                    <label class="form-label">用户名</label>
                    <input type="text" id="login-username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">密码</label>
                    <input type="password" id="login-password" class="form-control" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">角色</label>
                    <select id="login-role" class="form-select">
                        <option value="student">学生</option>
                        <option value="admin">管理员</option>
                    </select>
                </div>
                <div id="login-error" class="text-danger mb-3" style="display: none;"></div>
                <button type="submit" class="btn btn-primary btn-login">登录</button>
            </form>
            <div class="text-center mt-3">
                <small class="text-muted">默认管理员：admin / admin123</small>
            </div>
        </div>
        
        <div id="register-form" style="display: none;">
            <form onsubmit="handleRegister(event)">
                <div class="mb-3">
                    <label class="form-label">用户名</label>
                    <input type="text" id="reg-username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">姓名</label>
                    <input type="text" id="reg-name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">密码</label>
                    <input type="password" id="reg-password" class="form-control" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">确认密码</label>
                    <input type="password" id="reg-confirm" class="form-control" required>
                </div>
                <div id="register-error" class="text-danger mb-3" style="display: none;"></div>
                <button type="submit" class="btn btn-primary btn-login">注册</button>
            </form>
        </div>
    </div>
    
    <script>
        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            if (tab === 'login') {
                document.getElementById('login-form').style.display = 'block';
                document.getElementById('register-form').style.display = 'none';
                document.querySelectorAll('.tab-btn')[0].classList.add('active');
            } else {
                document.getElementById('login-form').style.display = 'none';
                document.getElementById('register-form').style.display = 'block';
                document.querySelectorAll('.tab-btn')[1].classList.add('active');
            }
        }
        
        async function handleLogin(e) {
            e.preventDefault();
            const username = document.getElementById('login-username').value;
            const password = document.getElementById('login-password').value;
            const role = document.getElementById('login-role').value;
            const errorDiv = document.getElementById('login-error');
            
            try {
                const res = await fetch('api/login.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({username, password, role})
                });
                const data = await res.json();
                
                if (data.success) {
                    window.location.href = 'welcome.php';
                } else {
                    errorDiv.textContent = data.message;
                    errorDiv.style.display = 'block';
                }
            } catch (err) {
                errorDiv.textContent = '网络错误，请重试';
                errorDiv.style.display = 'block';
            }
        }
        
        async function handleRegister(e) {
            e.preventDefault();
            const username = document.getElementById('reg-username').value;
            const name = document.getElementById('reg-name').value;
            const password = document.getElementById('reg-password').value;
            const confirm = document.getElementById('reg-confirm').value;
            const errorDiv = document.getElementById('register-error');
            
            if (password !== confirm) {
                errorDiv.textContent = '两次密码不一致';
                errorDiv.style.display = 'block';
                return;
            }
            
            try {
                const res = await fetch('api/register.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({username, name, password})
                });
                const data = await res.json();
                
                if (data.success) {
                    alert('注册成功，请登录');
                    switchTab('login');
                } else {
                    errorDiv.textContent = data.message;
                    errorDiv.style.display = 'block';
                }
            } catch (err) {
                errorDiv.textContent = '网络错误，请重试';
                errorDiv.style.display = 'block';
            }
        }
    </script>
</body>
</html>
