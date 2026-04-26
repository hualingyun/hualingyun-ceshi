const API_BASE = 'api';
let blogs = [];
let categories = [];
let editingBlogId = null;
let deletingBlogId = null;

const blogModal = document.getElementById('blogModal');
const confirmModal = document.getElementById('confirmModal');
const blogForm = document.getElementById('blogForm');
const blogList = document.getElementById('blogList');
const addBlogBtn = document.getElementById('addBlogBtn');
const cancelBtn = document.getElementById('cancelBtn');
const closeButtons = document.querySelectorAll('.close');
const cancelDelete = document.getElementById('cancelDelete');
const confirmDelete = document.getElementById('confirmDelete');
const modalTitle = document.getElementById('modalTitle');
const categorySelect = document.getElementById('blogCategory');

document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
    loadBlogs();
    setupEventListeners();
});

function setupEventListeners() {
    addBlogBtn.addEventListener('click', openAddModal);
    cancelBtn.addEventListener('click', closeBlogModal);
    closeButtons.forEach(btn => btn.addEventListener('click', closeAllModals));
    cancelDelete.addEventListener('click', closeConfirmModal);
    confirmDelete.addEventListener('click', executeDelete);
    blogForm.addEventListener('submit', handleBlogSubmit);
    
    blogModal.addEventListener('click', function(e) {
        if (e.target === blogModal) closeBlogModal();
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
        renderCategoryOptions();
    } catch (error) {
        showToast('加载分类失败', 'error');
        console.error('Error loading categories:', error);
    }
}

function renderCategoryOptions() {
    categorySelect.innerHTML = '<option value="">请选择类别</option>';
    categories.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat.id;
        option.textContent = cat.name;
        categorySelect.appendChild(option);
    });
}

async function loadBlogs() {
    try {
        const response = await fetch(`${API_BASE}/blogs.php`);
        blogs = await response.json();
        renderBlogList();
    } catch (error) {
        showToast('加载博客列表失败', 'error');
        console.error('Error loading blogs:', error);
        blogList.innerHTML = '<tr><td colspan="5" class="empty">加载失败，请刷新页面重试</td></tr>';
    }
}

function renderBlogList() {
    if (blogs.length === 0) {
        blogList.innerHTML = '<tr><td colspan="5" class="empty">暂无博客，点击"添加博客"创建第一篇</td></tr>';
        return;
    }
    
    blogList.innerHTML = blogs.map(blog => `
        <tr>
            <td>${escapeHtml(blog.title)}</td>
            <td><span class="category-badge">${escapeHtml(blog.category_name)}</span></td>
            <td>${formatDate(blog.created_at)}</td>
            <td>${blog.is_top ? '<span class="top-badge">已置顶</span>' : '否'}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="openEditModal(${blog.id})">编辑</button>
                <button class="btn btn-sm btn-danger" onclick="openDeleteConfirm(${blog.id}, '${escapeHtml(blog.title)}')">删除</button>
            </td>
        </tr>
    `).join('');
}

function openAddModal() {
    editingBlogId = null;
    modalTitle.textContent = '添加博客';
    blogForm.reset();
    document.getElementById('blogId').value = '';
    openBlogModal();
}

function openEditModal(id) {
    const blog = blogs.find(b => b.id === id);
    if (!blog) return;
    
    editingBlogId = id;
    modalTitle.textContent = '编辑博客';
    document.getElementById('blogId').value = blog.id;
    document.getElementById('blogTitle').value = blog.title;
    document.getElementById('blogCategory').value = blog.category_id;
    document.getElementById('blogContent').value = blog.content;
    document.getElementById('blogIsTop').checked = blog.is_top;
    openBlogModal();
}

function openBlogModal() {
    blogModal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeBlogModal() {
    blogModal.classList.remove('active');
    document.body.style.overflow = '';
}

function openDeleteConfirm(id, title) {
    deletingBlogId = id;
    document.getElementById('confirmMessage').textContent = `确定要删除博客"${title}"吗？此操作不可恢复。`;
    confirmModal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeConfirmModal() {
    deletingBlogId = null;
    confirmModal.classList.remove('active');
    document.body.style.overflow = '';
}

function closeAllModals() {
    closeBlogModal();
    closeConfirmModal();
}

async function handleBlogSubmit(e) {
    e.preventDefault();
    
    const formData = {
        title: document.getElementById('blogTitle').value.trim(),
        category_id: parseInt(document.getElementById('blogCategory').value),
        content: document.getElementById('blogContent').value.trim(),
        is_top: document.getElementById('blogIsTop').checked
    };
    
    if (!formData.title) {
        showToast('请输入博客标题', 'error');
        return;
    }
    
    if (!formData.category_id) {
        showToast('请选择博客类别', 'error');
        return;
    }
    
    if (!formData.content) {
        showToast('请输入博客内容', 'error');
        return;
    }
    
    try {
        let response;
        if (editingBlogId) {
            formData.id = editingBlogId;
            response = await fetch(`${API_BASE}/blogs.php`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
        } else {
            response = await fetch(`${API_BASE}/blogs.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
        }
        
        const result = await response.json();
        
        if (response.ok) {
            showToast(editingBlogId ? '博客更新成功' : '博客创建成功', 'success');
            closeBlogModal();
            loadBlogs();
        } else {
            showToast(result.error || '操作失败', 'error');
        }
    } catch (error) {
        showToast('网络错误，请重试', 'error');
        console.error('Error submitting blog:', error);
    }
}

async function executeDelete() {
    if (!deletingBlogId) return;
    
    try {
        const response = await fetch(`${API_BASE}/blogs.php?id=${deletingBlogId}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showToast('博客删除成功', 'success');
            closeConfirmModal();
            loadBlogs();
        } else {
            showToast(result.error || '删除失败', 'error');
        }
    } catch (error) {
        showToast('网络错误，请重试', 'error');
        console.error('Error deleting blog:', error);
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const parts = dateStr.match(/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/);
    if (parts) {
        return `${parts[1]}-${parts[2]}-${parts[3]} ${parts[4]}:${parts[5]}`;
    }
    return dateStr;
}

function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast ${type} show`;
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}
