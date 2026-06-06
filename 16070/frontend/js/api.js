const API_BASE = '../api';

const api = {
    async request(url, options = {}) {
        const fullUrl = url.startsWith('http') ? url : `${API_BASE}/${url}`;
        try {
            const response = await fetch(fullUrl, {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                credentials: 'include',
                ...options
            });
            
            let data;
            try {
                data = await response.json();
            } catch (jsonError) {
                const text = await response.text().catch(() => '');
                if (response.status === 401) {
                    window.location.href = 'index.html';
                    return;
                }
                throw new Error('服务器响应异常，请稍后重试');
            }
            
            if (!response.ok) {
                throw new Error(data.error || data.message || '请求失败');
            }
            return data;
        } catch (error) {
            error.message = error.message || '网络错误，请检查网络连接';
            throw error;
        }
    },

    async get(url) {
        return this.request(url, { method: 'GET' });
    },

    async post(url, data) {
        return this.request(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    async put(url, data) {
        return this.request(url, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },

    async delete(url) {
        return this.request(url, { method: 'DELETE' });
    },

    async uploadImage(file) {
        const formData = new FormData();
        formData.append('image', file);
        const response = await fetch(`${API_BASE}/logs.php?action=upload_image`, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.error || '上传失败');
        }
        return data;
    },

    auth: {
        login: (data) => api.post('auth.php?action=login', data),
        logout: () => api.post('auth.php?action=logout', {}),
        check: () => api.get('auth.php?action=check'),
        changePassword: (data) => api.post('auth.php?action=change_password', data)
    },

    users: {
        list: () => api.get('users.php'),
        create: (data) => api.post('users.php', data),
        update: (id, data) => api.put(`users.php?id=${id}`, data),
        delete: (id) => api.delete(`users.php?id=${id}`)
    },

    schedules: {
        list: (params = {}) => {
            const query = new URLSearchParams(params).toString();
            return api.get(`schedules.php${query ? '?' + query : ''}`);
        },
        create: (data) => api.post('schedules.php', data),
        batch: (data) => api.post('schedules.php?action=batch', data),
        swap: (data) => api.put('schedules.php?action=swap', data),
        update: (id, data) => api.put(`schedules.php?id=${id}`, data),
        delete: (id) => api.delete(`schedules.php?id=${id}`)
    },

    attendance: {
        list: (params = {}) => {
            const query = new URLSearchParams(params).toString();
            return api.get(`attendance.php${query ? '?' + query : ''}`);
        },
        checkIn: () => api.post('attendance.php?action=check_in', {}),
        checkOut: () => api.post('attendance.php?action=check_out', {}),
        todayStatus: () => api.get('attendance.php?action=today_status')
    },

    logs: {
        list: (params = {}) => {
            const query = new URLSearchParams(params).toString();
            return api.get(`logs.php${query ? '?' + query : ''}`);
        },
        create: (data) => api.post('logs.php', data),
        update: (id, data) => api.put(`logs.php?id=${id}`, data),
        delete: (id) => api.delete(`logs.php?id=${id}`)
    }
};

function showMessage(message, type = 'success') {
    const alert = document.createElement('div');
    alert.className = 'alert alert-' + type;
    alert.textContent = message;
    alert.style.position = 'fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '9999';
    alert.style.minWidth = '250px';
    alert.style.padding = '12px 16px';
    alert.style.borderRadius = '8px';
    alert.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    if (type === 'success') {
        alert.style.background = '#d4edda';
        alert.style.color = '#155724';
        alert.style.border = '1px solid #c3e6cb';
    } else if (type === 'error') {
        alert.style.background = '#f8d7da';
        alert.style.color = '#721c24';
        alert.style.border = '1px solid #f5c6cb';
    }
    document.body.appendChild(alert);
    setTimeout(function() {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.3s';
        setTimeout(function() {
            if (alert.parentNode) alert.remove();
        }, 300);
    }, 3000);
}

function checkPasswordStrength(password) {
    let strength = 0;
    if (password.length >= 8) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[!@#$%^&*(),.?":{}|<>_\-+=\[\]\/\\~`]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    return strength;
}

function getStrengthInfo(strength) {
    const levels = [
        { color: '#dc3545', text: '非常弱', width: '20%' },
        { color: '#fd7e14', text: '弱', width: '40%' },
        { color: '#ffc107', text: '一般', width: '60%' },
        { color: '#28a745', text: '强', width: '80%' },
        { color: '#007bff', text: '非常强', width: '100%' }
    ];
    return levels[Math.min(strength, 4)];
}

function formatDate(date) {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleDateString('zh-CN');
}

function formatDateTime(date) {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleString('zh-CN');
}

async function checkAuth() {
    try {
        const data = await api.auth.check();
        return data;
    } catch (e) {
        return { logged_in: false };
    }
}

function logout() {
    api.auth.logout().then(() => {
        window.location.href = 'index.html';
    });
}
