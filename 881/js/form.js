let ticketId = null;
let isEditMode = false;

document.addEventListener('DOMContentLoaded', async () => {
    const urlParams = new URLSearchParams(window.location.search);
    ticketId = urlParams.get('id');
    const statusFieldGroup = document.getElementById('statusFieldGroup');
    
    if (ticketId) {
        isEditMode = true;
        document.getElementById('pageTitle').textContent = '编辑工单';
        if (statusFieldGroup) {
            statusFieldGroup.style.display = 'block';
        }
        await loadTicket(ticketId);
    } else {
        isEditMode = false;
        document.getElementById('pageTitle').textContent = '添加工单';
        if (statusFieldGroup) {
            statusFieldGroup.style.display = 'none';
        }
        setDefaultTimes();
    }
    
    initEventListeners();
});

function initEventListeners() {
    const form = document.getElementById('ticketForm');
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        await handleSubmit();
    });
    
    const fields = ['ticket_id', 'subject', 'category', 'description', 
                   'plan_start_time', 'plan_end_time', 'assignee'];
    
    fields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('blur', () => validateField(fieldId));
            field.addEventListener('input', () => clearFieldError(fieldId));
        }
    });
}

function setDefaultTimes() {
    const now = new Date();
    const tomorrow = new Date(now);
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    const startInput = document.getElementById('plan_start_time');
    const endInput = document.getElementById('plan_end_time');
    
    if (startInput) {
        startInput.value = formatDateForInput(now);
    }
    if (endInput) {
        endInput.value = formatDateForInput(tomorrow);
    }
}

async function loadTicket(id) {
    const result = await apiRequest('GET');
    
    if (result.success) {
        const ticket = result.data.find(t => t.id === id);
        
        if (ticket) {
            document.getElementById('ticket_id').value = ticket.ticket_id;
            document.getElementById('subject').value = ticket.subject;
            document.getElementById('category').value = ticket.category;
            document.getElementById('status').value = ticket.status;
            document.getElementById('description').value = ticket.description;
            document.getElementById('plan_start_time').value = formatDateForInput(ticket.plan_start_time);
            document.getElementById('plan_end_time').value = formatDateForInput(ticket.plan_end_time);
            document.getElementById('assignee').value = ticket.assignee;
            document.getElementById('creator').value = ticket.creator;
        } else {
            showToast('工单不存在', 'error');
            setTimeout(() => {
                window.location.href = 'index.html';
            }, 1500);
        }
    } else {
        showToast(result.message || '加载工单失败', 'error');
    }
}

function validateField(fieldId) {
    const field = document.getElementById(fieldId);
    const value = field ? field.value.trim() : '';
    
    if (fieldId === 'ticket_id') {
        if (!value) {
            showFieldError(fieldId, '工单编号不能为空');
            return false;
        }
        if (!validateTicketId(value)) {
            showFieldError(fieldId, '工单编号格式错误：需以字母开头，6-20位，必须同时包含大写字母、小写字母和数字');
            return false;
        }
        clearFieldError(fieldId);
        return true;
    }
    
    const requiredFields = ['subject', 'category', 'description', 
                           'plan_start_time', 'plan_end_time', 'assignee'];
    
    if (requiredFields.includes(fieldId)) {
        if (!value) {
            const labels = {
                subject: '工单主题',
                category: '工单类别',
                description: '问题描述',
                plan_start_time: '计划开始时间',
                plan_end_time: '计划结束时间',
                assignee: '执行人'
            };
            showFieldError(fieldId, `${labels[fieldId]}不能为空`);
            return false;
        }
        clearFieldError(fieldId);
        return true;
    }
    
    return true;
}

function validateAllFields() {
    clearAllErrors();
    
    let isValid = true;
    const requiredFields = ['ticket_id', 'subject', 'category', 'description', 
                           'plan_start_time', 'plan_end_time', 'assignee'];
    
    requiredFields.forEach(fieldId => {
        if (!validateField(fieldId)) {
            isValid = false;
        }
    });
    
    const startTime = document.getElementById('plan_start_time').value;
    const endTime = document.getElementById('plan_end_time').value;
    
    if (startTime && endTime) {
        if (new Date(startTime) > new Date(endTime)) {
            showFieldError('plan_end_time', '计划结束时间不能早于开始时间');
            isValid = false;
        }
    }
    
    return isValid;
}

async function handleSubmit() {
    if (!validateAllFields()) {
        showToast('请检查表单填写是否正确', 'error');
        return;
    }
    
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = '保存中...';
    submitBtn.disabled = true;
    
    const formData = {
        ticket_id: document.getElementById('ticket_id').value.trim(),
        subject: document.getElementById('subject').value.trim(),
        category: document.getElementById('category').value,
        description: document.getElementById('description').value.trim(),
        plan_start_time: document.getElementById('plan_start_time').value,
        plan_end_time: document.getElementById('plan_end_time').value,
        assignee: document.getElementById('assignee').value.trim(),
        creator: document.getElementById('creator').value.trim() || '当前用户'
    };
    
    if (isEditMode) {
        formData.status = document.getElementById('status').value;
    }
    
    let result;
    
    if (isEditMode) {
        result = await apiRequest('PUT', formData, { id: ticketId });
    } else {
        result = await apiRequest('POST', formData);
    }
    
    submitBtn.textContent = originalText;
    submitBtn.disabled = false;
    
    if (result.success) {
        showToast(isEditMode ? '工单更新成功' : '工单创建成功', 'success');
        setTimeout(() => {
            window.location.href = 'index.html';
        }, 1000);
    } else {
        showToast(result.message || '保存失败', 'error');
    }
}
