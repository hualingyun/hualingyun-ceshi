const API_BASE = 'api/tickets.php';

async function apiRequest(method, data = null, params = {}) {
    const url = new URL(API_BASE, window.location.href);
    
    Object.keys(params).forEach(key => {
        if (params[key] !== null && params[key] !== undefined && params[key] !== '') {
            url.searchParams.append(key, params[key]);
        }
    });
    
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        }
    };
    
    if (data && (method === 'POST' || method === 'PUT')) {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url.toString(), options);
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('API请求错误:', error);
        return { success: false, message: '网络请求失败，请检查服务器是否启动' };
    }
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    if (!toast) return;
    
    toast.textContent = message;
    toast.className = `toast ${type}`;
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
    }
}

function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleString('zh-CN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatDateForInput(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

function validateTicketId(ticketId) {
    const pattern = /^[a-zA-Z][a-zA-Z0-9]{5,19}$/;
    return pattern.test(ticketId);
}

function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const formGroup = field ? field.closest('.form-group') : null;
    
    if (field) {
        field.classList.add('error');
    }
    
    if (formGroup) {
        let errorEl = formGroup.querySelector('.error-text');
        if (!errorEl) {
            errorEl = document.createElement('div');
            errorEl.className = 'error-text';
            formGroup.appendChild(errorEl);
        }
        errorEl.textContent = message;
    }
}

function clearFieldError(fieldId) {
    const field = document.getElementById(fieldId);
    const formGroup = field ? field.closest('.form-group') : null;
    
    if (field) {
        field.classList.remove('error');
    }
    
    if (formGroup) {
        const errorEl = formGroup.querySelector('.error-text');
        if (errorEl) {
            errorEl.remove();
        }
    }
}

function clearAllErrors() {
    document.querySelectorAll('.error').forEach(el => {
        el.classList.remove('error');
    });
    document.querySelectorAll('.error-text').forEach(el => {
        el.remove();
    });
}
