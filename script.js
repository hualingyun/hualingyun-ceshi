// 模态框元素
const modal = document.getElementById('ticketModal');
const modalTitle = document.getElementById('modalTitle');
const addTicketBtn = document.getElementById('addTicketBtn');
const closeBtn = document.getElementsByClassName('close')[0];
const ticketForm = document.getElementById('ticketForm');

// 表单元素
const ticketId = document.getElementById('ticketId');
const ticketSubject = document.getElementById('ticketSubject');
const ticketCategory = document.getElementById('ticketCategory');
const ticketDescription = document.getElementById('ticketDescription');
const plannedStart = document.getElementById('plannedStart');
const executor = document.getElementById('executor');
const plannedEnd = document.getElementById('plannedEnd');

// 工单表格
const ticketTableBody = document.getElementById('ticketTableBody');

// 打开添加工单模态框
addTicketBtn.onclick = function() {
    modalTitle.textContent = '添加工单';
    ticketForm.reset();
    ticketId.value = '';
    modal.style.display = 'block';
}

// 关闭模态框
closeBtn.onclick = function() {
    modal.style.display = 'none';
}

// 点击模态框外部关闭
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// 提交表单
ticketForm.onsubmit = function(e) {
    e.preventDefault();
    
    const ticketData = {
        id: ticketId.value || Date.now().toString(),
        subject: ticketSubject.value,
        category: ticketCategory.value,
        description: ticketDescription.value,
        plannedStart: plannedStart.value,
        executor: executor.value,
        plannedEnd: plannedEnd.value,
        status: '待处理',
        createDate: new Date().toISOString().slice(0, 10),
        creator: '管理员'
    };

    if (ticketId.value) {
        // 更新工单
        updateTicket(ticketData);
    } else {
        // 添加新工单
        addTicket(ticketData);
    }
    
    modal.style.display = 'none';
};

// 添加新工单
function addTicket(ticketData) {
    fetch('api.php?action=add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(ticketData),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadTickets();
        } else {
            alert('添加工单失败: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('添加工单失败，请重试');
    });
}

// 更新工单
function updateTicket(ticketData) {
    fetch('api.php?action=update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(ticketData),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadTickets();
        } else {
            alert('更新工单失败: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('更新工单失败，请重试');
    });
}

// 编辑工单
function editTicket(id) {
    fetch('api.php?action=get&id=' + id, {
        method: 'GET'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const ticket = data.ticket;
            modalTitle.textContent = '编辑工单';
            ticketId.value = ticket.id;
            ticketSubject.value = ticket.subject;
            ticketCategory.value = ticket.category;
            ticketDescription.value = ticket.description;
            plannedStart.value = ticket.plannedStart;
            executor.value = ticket.executor;
            plannedEnd.value = ticket.plannedEnd;
            modal.style.display = 'block';
        } else {
            alert('获取工单信息失败: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('获取工单信息失败，请重试');
    });
}

// 加载工单列表
function loadTickets() {
    fetch('api.php?action=list', {
        method: 'GET'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderTickets(data.tickets);
        } else {
            alert('加载工单列表失败: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('加载工单列表失败，请重试');
    });
}

// 渲染工单列表
function renderTickets(tickets) {
    ticketTableBody.innerHTML = '';
    
    tickets.forEach(ticket => {
        const row = document.createElement('tr');
        
        row.innerHTML = `
            <td>${ticket.id}</td>
            <td>${ticket.subject}</td>
            <td>${ticket.category}</td>
            <td>${ticket.status}</td>
            <td>${ticket.createDate}</td>
            <td>${ticket.creator}</td>
            <td>
                <button class="edit-btn" onclick="editTicket('${ticket.id}')">编辑</button>
            </td>
        `;
        
        ticketTableBody.appendChild(row);
    });
}

// 页面加载时加载工单列表
window.onload = function() {
    loadTickets();
};