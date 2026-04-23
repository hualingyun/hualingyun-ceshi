let currentPage = 1;
const pageSize = 10;
let allTickets = [];
let filteredTickets = [];
let deleteTicketId = null;

document.addEventListener('DOMContentLoaded', async () => {
    initEventListeners();
    await loadTickets();
});

function initEventListeners() {
    document.getElementById('addTicketBtn').addEventListener('click', () => {
        window.location.href = 'form.html';
    });
    
    document.getElementById('searchBtn').addEventListener('click', searchTickets);
    document.getElementById('resetBtn').addEventListener('click', resetFilters);
    
    document.getElementById('searchInput').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            searchTickets();
        }
    });
    
    document.getElementById('cancelDelete').addEventListener('click', () => {
        hideModal('deleteModal');
        deleteTicketId = null;
    });
    
    document.getElementById('confirmDelete').addEventListener('click', async () => {
        if (deleteTicketId) {
            await deleteTicket(deleteTicketId);
            hideModal('deleteModal');
            deleteTicketId = null;
        }
    });
    
    document.getElementById('deleteModal').addEventListener('click', (e) => {
        if (e.target.id === 'deleteModal') {
            hideModal('deleteModal');
            deleteTicketId = null;
        }
    });
}

async function loadTickets() {
    const result = await apiRequest('GET');
    
    if (result.success) {
        allTickets = result.data;
        filteredTickets = [...allTickets];
        renderTable();
    } else {
        showToast(result.message || '加载工单失败', 'error');
        renderEmptyTable();
    }
}

function searchTickets() {
    const search = document.getElementById('searchInput').value.trim();
    const category = document.getElementById('categoryFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    filteredTickets = allTickets.filter(ticket => {
        let matchSearch = true;
        let matchCategory = true;
        let matchStatus = true;
        
        if (search) {
            const searchLower = search.toLowerCase();
            matchSearch = ticket.ticket_id.toLowerCase().includes(searchLower) ||
                          ticket.subject.toLowerCase().includes(searchLower) ||
                          ticket.assignee.toLowerCase().includes(searchLower) ||
                          ticket.creator.toLowerCase().includes(searchLower);
        }
        
        if (category) {
            matchCategory = ticket.category === category;
        }
        
        if (status) {
            matchStatus = ticket.status === status;
        }
        
        return matchSearch && matchCategory && matchStatus;
    });
    
    currentPage = 1;
    renderTable();
    
    if (filteredTickets.length === 0) {
        showToast('没有找到匹配的工单', 'warning');
    }
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('statusFilter').value = '';
    
    filteredTickets = [...allTickets];
    currentPage = 1;
    renderTable();
}

function renderTable() {
    const tbody = document.getElementById('ticketTableBody');
    const pagination = document.getElementById('pagination');
    
    if (filteredTickets.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="empty-data">
                    <div style="text-align: center; padding: 40px;">
                        <p style="color: #999; margin-bottom: 10px;">暂无工单数据</p>
                        <p style="color: #bbb; font-size: 12px;">点击左上角"添加工单"按钮创建新工单</p>
                    </div>
                </td>
            </tr>
        `;
        pagination.style.display = 'none';
        return;
    }
    
    const totalPages = Math.ceil(filteredTickets.length / pageSize);
    const startIndex = (currentPage - 1) * pageSize;
    const endIndex = startIndex + pageSize;
    const pageTickets = filteredTickets.slice(startIndex, endIndex);
    
    let html = '';
    pageTickets.forEach(ticket => {
        html += `
            <tr>
                <td><strong>${escapeHtml(ticket.ticket_id)}</strong></td>
                <td>${escapeHtml(ticket.subject)}</td>
                <td><span class="category-badge category-${ticket.category}">${escapeHtml(ticket.category)}</span></td>
                <td><span class="status-badge status-${ticket.status}">${escapeHtml(ticket.status)}</span></td>
                <td>${formatDate(ticket.created_at)}</td>
                <td>${escapeHtml(ticket.creator)}</td>
                <td class="action-buttons">
                    <button class="btn btn-warning btn-sm" onclick="editTicket('${ticket.id}')">编辑</button>
                    <button class="btn btn-danger btn-sm" onclick="confirmDeleteTicket('${ticket.id}')">删除</button>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
    
    if (totalPages > 1) {
        pagination.style.display = 'flex';
        renderPagination(totalPages);
    } else {
        pagination.style.display = 'none';
    }
}

function renderEmptyTable() {
    const tbody = document.getElementById('ticketTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="empty-data">
                <div style="text-align: center; padding: 40px;">
                    <p style="color: #999; margin-bottom: 10px;">无法加载数据</p>
                    <p style="color: #bbb; font-size: 12px;">请确保PHP服务器已启动（php -S localhost:8000）</p>
                </div>
            </td>
        </tr>
    `;
    document.getElementById('pagination').style.display = 'none';
}

function renderPagination(totalPages) {
    const pagination = document.getElementById('pagination');
    let html = `<span class="pagination-info">共 ${filteredTickets.length} 条，第 ${currentPage}/${totalPages} 页</span>`;
    
    html += `<button onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>上一页</button>`;
    
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    if (startPage > 1) {
        html += `<button onclick="goToPage(1)" class="${currentPage === 1 ? 'active' : ''}">1</button>`;
        if (startPage > 2) {
            html += `<span>...</span>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        html += `<button onclick="goToPage(${i})" class="${currentPage === i ? 'active' : ''}">${i}</button>`;
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            html += `<span>...</span>`;
        }
        html += `<button onclick="goToPage(${totalPages})" class="${currentPage === totalPages ? 'active' : ''}">${totalPages}</button>`;
    }
    
    html += `<button onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>下一页</button>`;
    
    pagination.innerHTML = html;
}

function goToPage(page) {
    const totalPages = Math.ceil(filteredTickets.length / pageSize);
    if (page >= 1 && page <= totalPages) {
        currentPage = page;
        renderTable();
    }
}

function editTicket(id) {
    window.location.href = `form.html?id=${id}`;
}

function confirmDeleteTicket(id) {
    deleteTicketId = id;
    showModal('deleteModal');
}

async function deleteTicket(id) {
    const result = await apiRequest('DELETE', null, { id: id });
    
    if (result.success) {
        showToast('工单删除成功', 'success');
        allTickets = allTickets.filter(t => t.id !== id);
        filteredTickets = filteredTickets.filter(t => t.id !== id);
        
        const totalPages = Math.ceil(filteredTickets.length / pageSize);
        if (currentPage > totalPages && totalPages > 0) {
            currentPage = totalPages;
        }
        
        renderTable();
    } else {
        showToast(result.message || '删除失败', 'error');
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
