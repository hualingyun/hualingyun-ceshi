// API 封装
const api = {
    async request(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
            },
        };

        const mergedOptions = { ...defaultOptions, ...options };
        
        // 处理POST/PUT请求的body
        if (mergedOptions.body && typeof mergedOptions.body === 'object') {
            mergedOptions.body = JSON.stringify(mergedOptions.body);
        }

        try {
            const response = await fetch(url, mergedOptions);
            
            // 解析响应
            let data;
            try {
                data = await response.json();
            } catch (e) {
                data = { error: '响应解析失败' };
            }

            if (!response.ok) {
                throw new Error(data.error || `请求失败: ${response.status}`);
            }

            return data;
        } catch (error) {
            if (error instanceof TypeError && error.message.includes('fetch')) {
                throw new Error('网络连接失败，请检查服务器是否启动');
            }
            throw error;
        }
    },

    async get(url) {
        return this.request(url, { method: 'GET' });
    },

    async post(url, data) {
        return this.request(url, { 
            method: 'POST', 
            body: data 
        });
    },

    async put(url, data) {
        return this.request(url, { 
            method: 'PUT', 
            body: data 
        });
    },

    async delete(url) {
        return this.request(url, { method: 'DELETE' });
    }
};

// HTML转义函数
function escapeHtml(text) {
    if (text === null || text === undefined) {
        return '';
    }
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 消息提示
let messageTimeout = null;

function showMessage(text, type = 'success') {
    // 移除已存在的消息
    const existingMessage = document.querySelector('.message');
    if (existingMessage) {
        existingMessage.remove();
    }
    if (messageTimeout) {
        clearTimeout(messageTimeout);
    }

    // 创建消息元素
    const message = document.createElement('div');
    message.className = `message message-${type}`;
    message.textContent = text;
    document.body.appendChild(message);

    // 自动移除
    messageTimeout = setTimeout(() => {
        message.style.animation = 'messageSlideIn 0.3s ease reverse';
        setTimeout(() => {
            message.remove();
        }, 300);
    }, 3000);
}

// 日期格式化
function formatDate(date) {
    if (!(date instanceof Date)) {
        date = new Date(date);
    }
    
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');

    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

// 防抖函数
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// 节流函数
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// 表单验证工具
const validator = {
    required(value, message = '此项为必填项') {
        if (value === null || value === undefined || value === '' || 
            (Array.isArray(value) && value.length === 0)) {
            return message;
        }
        return null;
    },

    minLength(value, min, message = `最少需要${min}个字符`) {
        if (value && value.length < min) {
            return message;
        }
        return null;
    },

    maxLength(value, max, message = `最多允许${max}个字符`) {
        if (value && value.length > max) {
            return message;
        }
        return null;
    },

    range(value, min, max, message = `请输入${min}到${max}之间的数值`) {
        const num = Number(value);
        if (isNaN(num) || num < min || num > max) {
            return message;
        }
        return null;
    }
};

// 导出工具函数（供其他脚本使用）
window.escapeHtml = escapeHtml;
window.showMessage = showMessage;
window.formatDate = formatDate;
window.debounce = debounce;
window.throttle = throttle;
window.validator = validator;
window.api = api;
