const API_BASE_URL = 'http://localhost:8000/api';

const state = {
    workOrders: [],
    currentEditId: null,
    deleteCallback: null
};

const elements = {
    listPage: document.getElementById('list-page'),
    formPage: document.getElementById('form-page'),
    workOrderList: document.getElementById('work-order-list'),
    form: document.getElementById('work-order-form'),
    formTitle: document.getElementById('form-title'),
    addBtn: document.getElementById('add-btn'),
    cancelBtn: document.getElementById('cancel-btn'),
    submitBtn: document.getElementById('submit-btn'),
    modal: document.getElementById('modal'),
    modalMessage: document.getElementById('modal-message'),
    modalConfirm: document.getElementById('modal-confirm'),
    modalCancel: document.getElementById('modal-cancel'),
    workOrderId: document.getElementById('work-order-id'),
    orderNo: document.getElementById('order_no'),
    subject: document.getElementById('subject'),
    category: document.getElementById('category'),
    description: document.getElementById('description'),
    plannedStartTime: document.getElementById('planned_start_time'),
    plannedEndTime: document.getElementById('planned_end_time'),
    executor: document.getElementById('executor')
};

function init() {
    loadWorkOrders();
    bindEvents();
}

function bindEvents() {
    elements.addBtn.addEventListener('click', showAddForm);
    elements.cancelBtn.addEventListener('click', showListPage);
    elements.form.addEventListener('submit', handleFormSubmit);
    elements.modalConfirm.addEventListener('click', handleModalConfirm);
    elements.modalCancel.addEventListener('click', hideModal);
}

async function loadWorkOrders() {
    try {
        const response = await fetch(`${API_BASE_URL}/work-orders`);
        const result = await response.json();
        
        if (result.success) {
            state.workOrders = result.data;
            renderWorkOrderList();
        }
    } catch (error) {
        console.error('加载工单列表失败:', error);
        showToast('加载工单列表失败', 'error');
    }
}

function renderWorkOrderList() {
    if (state.workOrders.length === 0) {
        elements.workOrderList.innerHTML = `
            <tr>
                <td colspan="7">
                    <div class="empty-state">
                        <p>暂无工单数据</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    elements.workOrderList.innerHTML = state.workOrders.map(order => `
        <tr>
            <td>${escapeHtml(order.order_no)}</td>
            <td>${escapeHtml(order.subject)}</td>
            <td>${escapeHtml(order.category)}</td>
            <td>
                <span class="status-badge ${getStatusClass(order.status)}">
                    ${escapeHtml(order.status)}
                </span>
            </td>
            <td>${formatDate(order.created_at)}</td>
            <td>${escapeHtml(order.created_by)}</td>
            <td>
                <div class="action-btns">
                    <button class="btn btn-primary btn-sm" onclick="editWorkOrder(${order.id})">编辑</button>
                    <button class="btn btn-danger btn-sm" onclick="confirmDelete(${order.id})">删除</button>
                </div>
            </td>
        </tr>
    `).join('');
}

function getStatusClass(status) {
    const statusMap = {
        '待处理': 'status-pending',
        '处理中': 'status-processing',
        '已完成': 'status-completed'
    };
    return statusMap[status] || 'status-pending';
}

function showAddForm() {
    state.currentEditId = null;
    elements.formTitle.textContent = '添加工单';
    elements.form.reset();
    clearErrors();
    showFormPage();
}

function editWorkOrder(id) {
    const order = state.workOrders.find(o => o.id === id);
    if (!order) return;
    
    state.currentEditId = id;
    elements.formTitle.textContent = '编辑工单';
    clearErrors();
    
    elements.orderNo.value = order.order_no;
    elements.subject.value = order.subject;
    elements.category.value = order.category;
    elements.description.value = order.description || '';
    elements.plannedStartTime.value = formatDateTimeLocal(order.planned_start_time);
    elements.plannedEndTime.value = formatDateTimeLocal(order.planned_end_time);
    elements.executor.value = order.executor;
    
    showFormPage();
}

function confirmDelete(id) {
    state.deleteCallback = () => deleteWorkOrder(id);
    elements.modalMessage.textContent = '确定要删除这条工单吗？此操作不可撤销。';
    showModal();
}

async function deleteWorkOrder(id) {
    try {
        const response = await fetch(`${API_BASE_URL}/work-orders/${id}`, {
            method: 'DELETE'
        });
        const result = await response.json();
        
        if (result.success) {
            showToast('工单删除成功', 'success');
            loadWorkOrders();
        } else {
            showToast(result.message || '删除失败', 'error');
        }
    } catch (error) {
        console.error('删除工单失败:', error);
        showToast('删除工单失败', 'error');
    }
}

async function handleFormSubmit(e) {
    e.preventDefault();
    
    if (!validateForm()) {
        return;
    }
    
    const formData = {
        order_no: elements.orderNo.value.trim(),
        subject: elements.subject.value.trim(),
        category: elements.category.value,
        description: elements.description.value.trim(),
        planned_start_time: elements.plannedStartTime.value,
        planned_end_time: elements.plannedEndTime.value,
        executor: elements.executor.value.trim()
    };
    
    try {
        let response;
        if (state.currentEditId) {
            response = await fetch(`${API_BASE_URL}/work-orders/${state.currentEditId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });
        } else {
            response = await fetch(`${API_BASE_URL}/work-orders`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });
        }
        
        const result = await response.json();
        
        if (result.success) {
            showToast(state.currentEditId ? '工单更新成功' : '工单创建成功', 'success');
            showListPage();
            loadWorkOrders();
        } else {
            if (result.errors) {
                showErrors(result.errors);
            } else {
                showToast(result.message || '操作失败', 'error');
            }
        }
    } catch (error) {
        console.error('保存工单失败:', error);
        showToast('保存工单失败', 'error');
    }
}

function validateForm() {
    clearErrors();
    let isValid = true;
    
    const orderNo = elements.orderNo.value.trim();
    if (!orderNo) {
        showError('order_no', '工单编号不能为空');
        isValid = false;
    } else if (!/^[a-zA-Z][a-zA-Z0-9]{5,19}$/.test(orderNo)) {
        showError('order_no', '工单编号必须以字母开头，长度6-20字符');
        isValid = false;
    } else if (!/[A-Z]/.test(orderNo) || !/[a-z]/.test(orderNo) || !/[0-9]/.test(orderNo)) {
        showError('order_no', '工单编号必须同时包含大写字母、小写字母和数字');
        isValid = false;
    }
    
    if (!elements.subject.value.trim()) {
        showError('subject', '工单主题不能为空');
        isValid = false;
    }
    
    if (!elements.category.value) {
        showError('category', '请选择工单类别');
        isValid = false;
    }
    
    if (!elements.plannedStartTime.value) {
        showError('planned_start_time', '计划开始时间不能为空');
        isValid = false;
    }
    
    if (!elements.plannedEndTime.value) {
        showError('planned_end_time', '计划结束时间不能为空');
        isValid = false;
    }
    
    if (!elements.executor.value.trim()) {
        showError('executor', '执行人不能为空');
        isValid = false;
    }
    
    return isValid;
}

function showError(field, message) {
    const errorElement = document.getElementById(`${field}_error`);
    if (errorElement) {
        errorElement.textContent = message;
    }
}

function showErrors(errors) {
    Object.keys(errors).forEach(field => {
        showError(field, errors[field]);
    });
}

function clearErrors() {
    document.querySelectorAll('.error-msg').forEach(el => {
        el.textContent = '';
    });
}

function showListPage() {
    elements.listPage.classList.add('active');
    elements.formPage.classList.remove('active');
}

function showFormPage() {
    elements.listPage.classList.remove('active');
    elements.formPage.classList.add('active');
}

function showModal() {
    elements.modal.classList.add('active');
}

function hideModal() {
    elements.modal.classList.remove('active');
    state.deleteCallback = null;
}

function handleModalConfirm() {
    if (state.deleteCallback) {
        state.deleteCallback();
    }
    hideModal();
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 24px;
        border-radius: 6px;
        color: white;
        font-size: 14px;
        z-index: 2000;
        animation: slideIn 0.3s ease;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('zh-CN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

function formatDateTimeLocal(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

document.addEventListener('DOMContentLoaded', init);
