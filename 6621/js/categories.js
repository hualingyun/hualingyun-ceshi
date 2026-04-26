const API_BASE = 'api';
let categories = [];
let editingCategoryId = null;
let deletingCategoryId = null;

const categoryModal = document.getElementById('categoryModal');
const confirmModal = document.getElementById('confirmModal');
const categoryForm = document.getElementById('categoryForm');
const categoryList = document.getElementById('categoryList');
const addCategoryBtn = document.getElementById('addCategoryBtn');
const cancelCategoryBtn = document.getElementById('cancelCategoryBtn');
const closeButtons = document.querySelectorAll('.close');
const cancelDelete = document.getElementById('cancelDelete');
const confirmDelete = document.getElementById('confirmDelete');
const categoryModalTitle = document.getElementById('categoryModalTitle');

document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
    setupEventListeners();
});

function setupEventListeners() {
    addCategoryBtn.addEventListener('click', openAddModal);
    cancelCategoryBtn.addEventListener('click', closeCategoryModal);
    closeButtons.forEach(btn => btn.addEventListener('click', closeAllModals));
    cancelDelete.addEventListener('click', closeConfirmModal);
    confirmDelete.addEventListener('click', executeDelete);
    categoryForm.addEventListener('submit', handleCategorySubmit);
    
    categoryModal.addEventListener('click', function(e) {
        if (e.target === categoryModal) closeCategoryModal();
    });
    confirmModal.addEventListener('click', function(e) {
        if (e.target === confirmModal) closeConfirmModal();
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeAllModals();
    });
}

async function loadCategories() {
    try {
        const response = await fetch(`${API_BASE}/categories.php`);
        categories = await response.json();
        renderCategoryList();
    } catch (error) {
        showToast('加载分类列表失败', 'error');
        console.error('Error loading categories:', error);
        categoryList.innerHTML = '<tr><td colspan="4" class="empty">加载失败，请刷新页面重试</td></tr>';
    }
}

function renderCategoryList() {
    if (categories.length === 0) {
        categoryList.innerHTML = '<tr><td colspan="4" class="empty">暂无分类，点击"添加分类"创建第一个分类</td></tr>';
        return;
    }
    
    categoryList.innerHTML = categories.map(cat => `
        <tr>
            <td>${cat.id}</td>
            <td><span class="category-badge">${escapeHtml(cat.name)}</span></td>
            <td>${cat.blog_count} 篇</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="openEditModal(${cat.id})">编辑</button>
                <button class="btn btn-sm btn-danger" onclick="openDeleteConfirm(${cat.id}, '${escapeHtml(cat.name)}', ${cat.blog_count})">删除</button>
            </td>
        </tr>
    `).join('');
}

function openAddModal() {
    editingCategoryId = null;
    categoryModalTitle.textContent = '添加分类';
    categoryForm.reset();
    document.getElementById('categoryId').value = '';
    openCategoryModal();
}

function openEditModal(id) {
    const category = categories.find(c => c.id === id);
    if (!category) return;
    
    editingCategoryId = id;
    categoryModalTitle.textContent = '编辑分类';
    document.getElementById('categoryId').value = category.id;
    document.getElementById('categoryName').value = category.name;
    openCategoryModal();
}

function openCategoryModal() {
    categoryModal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeCategoryModal() {
    categoryModal.classList.remove('active');
    document.body.style.overflow = '';
}

function openDeleteConfirm(id, name, blogCount) {
    if (blogCount > 0) {
        showToast(`无法删除分类"${name}"：该分类下有 ${blogCount} 篇博客`, 'error');
        return;
    }
    
    deletingCategoryId = id;
    document.getElementById('confirmMessage').textContent = `确定要删除分类"${name}"吗？此操作不可恢复。`;
    confirmModal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeConfirmModal() {
    deletingCategoryId = null;
    confirmModal.classList.remove('active');
    document.body.style.overflow = '';
}

function closeAllModals() {
    closeCategoryModal();
    closeConfirmModal();
}

async function handleCategorySubmit(e) {
    e.preventDefault();
    
    const formData = {
        name: document.getElementById('categoryName').value.trim()
    };
    
    if (!formData.name) {
        showToast('请输入分类名称', 'error');
        return;
    }
    
    try {
        let response;
        if (editingCategoryId) {
            formData.id = editingCategoryId;
            response = await fetch(`${API_BASE}/categories.php`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
        } else {
            response = await fetch(`${API_BASE}/categories.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
        }
        
        const result = await response.json();
        
        if (response.ok) {
            showToast(editingCategoryId ? '分类更新成功' : '分类创建成功', 'success');
            closeCategoryModal();
            loadCategories();
        } else {
            showToast(result.error || '操作失败', 'error');
        }
    } catch (error) {
        showToast('网络错误，请重试', 'error');
        console.error('Error submitting category:', error);
    }
}

async function executeDelete() {
    if (!deletingCategoryId) return;
    
    try {
        const response = await fetch(`${API_BASE}/categories.php?id=${deletingCategoryId}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showToast('分类删除成功', 'success');
            closeConfirmModal();
            loadCategories();
        } else {
            showToast(result.error || '删除失败', 'error');
        }
    } catch (error) {
        showToast('网络错误，请重试', 'error');
        console.error('Error deleting category:', error);
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast ${type} show`;
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}
