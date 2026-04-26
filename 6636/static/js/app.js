let currentPage = 1;
let currentSearch = '';
let currentUser = null;
let currentBookId = null;

const API_BASE = '/api';

document.addEventListener('DOMContentLoaded', function() {
    checkAuth();
    loadRecommendedBooks();
    loadBooks();
});

function checkAuth() {
    const token = localStorage.getItem('token');
    if (token) {
        fetch(`${API_BASE}/user`, {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.id) {
                currentUser = data;
                updateUserArea();
            }
        })
        .catch(() => {
            localStorage.removeItem('token');
        });
    }
}

function updateUserArea() {
    const userArea = document.getElementById('userArea');
    if (currentUser) {
        userArea.innerHTML = `
            <div class="user-info">
                <div class="user-avatar">${currentUser.username.charAt(0).toUpperCase()}</div>
                <span class="user-name">${currentUser.username}</span>
                <button class="btn-logout" onclick="logout()">退出</button>
            </div>
        `;
    } else {
        userArea.innerHTML = `
            <button class="btn-login" onclick="showLoginModal()">登录</button>
            <button class="btn-register" onclick="showRegisterModal()">注册</button>
        `;
    }
}

function showLoginModal() {
    document.getElementById('registerModal').classList.remove('active');
    document.getElementById('loginModal').classList.add('active');
}

function showRegisterModal() {
    document.getElementById('loginModal').classList.remove('active');
    document.getElementById('registerModal').classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function handleLogin(event) {
    event.preventDefault();
    const username = document.getElementById('loginUsername').value;
    const password = document.getElementById('loginPassword').value;

    fetch(`${API_BASE}/login`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ username, password })
    })
    .then(response => response.json())
    .then(data => {
        if (data.token) {
            localStorage.setItem('token', data.token);
            currentUser = data.user;
            closeModal('loginModal');
            updateUserArea();
            showToast('登录成功！', 'success');
            if (currentBookId) {
                loadBookDetail(currentBookId);
            }
        } else {
            showToast(data.error || '登录失败', 'error');
        }
    })
    .catch(() => {
        showToast('登录失败，请稍后重试', 'error');
    });
}

function handleRegister(event) {
    event.preventDefault();
    const username = document.getElementById('registerUsername').value;
    const email = document.getElementById('registerEmail').value;
    const password = document.getElementById('registerPassword').value;

    fetch(`${API_BASE}/register`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ username, email, password })
    })
    .then(response => response.json())
    .then(data => {
        if (data.token) {
            localStorage.setItem('token', data.token);
            currentUser = data.user;
            closeModal('registerModal');
            updateUserArea();
            showToast('注册成功！', 'success');
            if (currentBookId) {
                loadBookDetail(currentBookId);
            }
        } else {
            showToast(data.error || '注册失败', 'error');
        }
    })
    .catch(() => {
        showToast('注册失败，请稍后重试', 'error');
    });
}

function logout() {
    localStorage.removeItem('token');
    currentUser = null;
    updateUserArea();
    showToast('已退出登录', 'success');
    if (currentBookId) {
        loadBookDetail(currentBookId);
    }
}

function loadRecommendedBooks() {
    const container = document.getElementById('recommendedBooks');
    container.innerHTML = '<div class="loading">加载中...</div>';

    fetch(`${API_BASE}/books/recommended/details`)
    .then(response => response.json())
    .then(data => {
        const recommendedSection = document.getElementById('recommendedSection');
        if (data.recommendations && data.recommendations.length > 0) {
            recommendedSection.style.display = 'block';
            renderRecommendedBooks(data.recommendations);
        } else {
            recommendedSection.style.display = 'none';
        }
    })
    .catch(() => {
        fetch(`${API_BASE}/books/recommended`)
        .then(response => response.json())
        .then(data => {
            const recommendedSection = document.getElementById('recommendedSection');
            if (data.books && data.books.length > 0) {
                recommendedSection.style.display = 'block';
                renderBooks(data.books, 'recommendedBooks', true);
            } else {
                recommendedSection.style.display = 'none';
            }
        })
        .catch(() => {
            document.getElementById('recommendedSection').style.display = 'none';
        });
    });
}

function renderRecommendedBooks(recommendations) {
    const container = document.getElementById('recommendedBooks');
    
    if (!recommendations || recommendations.length === 0) {
        container.innerHTML = '<div class="loading">暂无推荐</div>';
        return;
    }

    const seen = {};
    const uniqueRecs = [];
    for (const rec of recommendations) {
        if (!seen[rec.book_id]) {
            seen[rec.book_id] = true;
            uniqueRecs.push(rec);
        }
        if (uniqueRecs.length >= 8) break;
    }

    container.innerHTML = uniqueRecs.map(rec => `
        <div class="book-card" onclick="showBookDetail(${rec.book.id})">
            <div class="book-card-header">
                <img src="${rec.book.cover || '/static/images/no-cover.png'}" alt="${rec.book.title}" class="book-cover" onerror="this.src='https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=book%20cover%20placeholder&image_size=square'">
            </div>
            <div class="book-info">
                <h3 class="book-title">${escapeHtml(rec.book.title)}</h3>
                <p class="book-author">${escapeHtml(rec.book.author)}</p>
                <div class="book-footer">
                    <span class="book-price">¥${rec.book.price.toFixed(2)}</span>
                    <span class="book-rating">
                        <span class="star">★</span>
                        ${rec.book.rating.toFixed(1)}
                    </span>
                </div>
                <div class="recommended-by">
                    <span class="recommender">👤 ${escapeHtml(rec.username || '匿名用户')} 推荐</span>
                    ${rec.reason ? `<p class="recommend-reason">"${escapeHtml(rec.reason)}"</p>` : ''}
                </div>
            </div>
        </div>
    `).join('');
}

function loadBooks(page = 1, search = '') {
    currentPage = page;
    currentSearch = search;

    const container = document.getElementById('booksList');
    container.innerHTML = '<div class="loading">加载中...</div>';

    let url = `${API_BASE}/books?page=${page}`;
    if (search) {
        url += `&search=${encodeURIComponent(search)}`;
        document.getElementById('booksSectionTitle').textContent = `🔍 搜索结果: "${search}"`;
    } else {
        document.getElementById('booksSectionTitle').textContent = '📖 全部图书';
    }

    fetch(url)
    .then(response => response.json())
    .then(data => {
        renderBooks(data.books, 'booksList');
        renderPagination(data.currentPage, data.totalPages, data.total);
    })
    .catch(() => {
        container.innerHTML = '<div class="loading">加载失败，请稍后重试</div>';
    });
}

function renderBooks(books, containerId, isRecommended = false) {
    const container = document.getElementById(containerId);
    
    if (!books || books.length === 0) {
        container.innerHTML = '<div class="loading">暂无图书</div>';
        return;
    }

    container.innerHTML = books.map(book => `
        <div class="book-card" onclick="showBookDetail(${book.id})">
            <img src="${book.cover || '/static/images/no-cover.png'}" alt="${book.title}" class="book-cover" onerror="this.src='https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=book%20cover%20placeholder&image_size=square'">
            <div class="book-info">
                <h3 class="book-title">${escapeHtml(book.title)}</h3>
                <p class="book-author">${escapeHtml(book.author)}</p>
                <div class="book-footer">
                    <span class="book-price">¥${book.price.toFixed(2)}</span>
                    <span class="book-rating">
                        <span class="star">★</span>
                        ${book.rating.toFixed(1)}
                    </span>
                </div>
            </div>
        </div>
    `).join('');
}

function renderPagination(currentPage, totalPages, total) {
    const paginationHtml = generatePaginationHtml(currentPage, totalPages, total);
    document.getElementById('pagination').innerHTML = paginationHtml;
    document.getElementById('paginationBottom').innerHTML = paginationHtml;
}

function generatePaginationHtml(currentPage, totalPages, total) {
    if (totalPages <= 1) {
        return `<span>共 ${total} 本图书</span>`;
    }

    let html = `<span>共 ${total} 本</span>`;
    
    html += `<button onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>上一页</button>`;

    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    if (startPage > 1) {
        html += `<button onclick="goToPage(1)" ${1 === currentPage ? 'class="active"' : ''}>1</button>`;
        if (startPage > 2) {
            html += `<span>...</span>`;
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        html += `<button onclick="goToPage(${i})" ${i === currentPage ? 'class="active"' : ''}>${i}</button>`;
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            html += `<span>...</span>`;
        }
        html += `<button onclick="goToPage(${totalPages})" ${totalPages === currentPage ? 'class="active"' : ''}>${totalPages}</button>`;
    }

    html += `<button onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>下一页</button>`;

    return html;
}

function goToPage(page) {
    loadBooks(page, currentSearch);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function handleSearchKeyPress(event) {
    if (event.key === 'Enter') {
        searchBooks();
    }
}

function searchBooks() {
    const searchValue = document.getElementById('searchInput').value.trim();
    loadBooks(1, searchValue);
}

function showHome() {
    document.getElementById('searchInput').value = '';
    loadBooks(1, '');
    loadRecommendedBooks();
    closeModal('bookDetailModal');
}

function showBookDetail(bookId) {
    currentBookId = bookId;
    document.getElementById('bookDetailModal').classList.add('active');
    loadBookDetail(bookId);
}

function loadBookDetail(bookId) {
    const container = document.getElementById('bookDetailContent');
    container.innerHTML = '<div class="loading">加载中...</div>';

    const token = localStorage.getItem('token');
    const headers = token ? { 'Authorization': `Bearer ${token}` } : {};

    Promise.all([
        fetch(`${API_BASE}/books/${bookId}`, { headers }).then(r => r.json()),
        fetch(`${API_BASE}/books/${bookId}/recommendations`).then(r => r.json())
    ])
    .then(([bookData, recData]) => {
        const book = bookData.book;
        const isLiked = bookData.isLiked || false;
        const hasRecommended = bookData.hasRecommended || false;
        const recommendations = recData.recommendations || [];

        container.innerHTML = `
            <div class="book-detail-container">
                <img src="${book.cover || '/static/images/no-cover.png'}" alt="${book.title}" class="book-detail-cover" onerror="this.src='https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=book%20cover%20placeholder&image_size=square'">
                <div class="book-detail-info">
                    <h2 class="book-detail-title">${escapeHtml(book.title)}</h2>
                    <p class="book-detail-author">作者：${escapeHtml(book.author)}</p>
                    <div class="book-detail-meta">
                        <span class="book-detail-price">¥${book.price.toFixed(2)}</span>
                        <span class="book-detail-rating">
                            <span class="star">★</span>
                            ${book.rating.toFixed(1)}
                            <span class="book-detail-rating-count">(${book.like_count}人喜欢)</span>
                        </span>
                    </div>
                    <p class="book-detail-description">${escapeHtml(book.description)}</p>
                    <div class="book-actions">
                        <button class="book-action-btn ${isLiked ? 'liked' : ''}" onclick="toggleLike(${book.id})">
                            <span class="like-icon">${isLiked ? '❤️' : '🤍'}</span>
                            ${isLiked ? '已点赞' : '点赞'}
                            <span id="likeCount">(${book.like_count})</span>
                        </button>
                        <button class="book-action-btn ${hasRecommended ? 'recommended' : ''}" onclick="showRecommendModal(${book.id}, ${hasRecommended})">
                            <span>📢</span>
                            ${hasRecommended ? '已推荐' : '推荐'}
                        </button>
                    </div>
                </div>
            </div>
            ${recommendations.length > 0 ? `
                <div class="recommendations-section">
                    <h3 class="comments-title">📢 推荐记录</h3>
                    <div class="recommendations-list">
                        ${recommendations.map(rec => `
                            <div class="recommendation-item">
                                <div class="recommendation-header">
                                    <div class="comment-user">
                                        <div class="comment-user-avatar">${(rec.username || '匿').charAt(0).toUpperCase()}</div>
                                        <span class="comment-user-name">${escapeHtml(rec.username || '匿名用户')}</span>
                                    </div>
                                    <span class="comment-time">${formatDate(rec.created_at)}</span>
                                </div>
                                ${rec.reason ? `<p class="recommendation-reason">"${escapeHtml(rec.reason)}"</p>` : ''}
                            </div>
                        `).join('')}
                    </div>
                </div>
            ` : ''}
            <div class="comments-section">
                <h3 class="comments-title">💬 评论 (${book.comment_count})</h3>
                ${currentUser ? `
                    <form class="comment-form" onsubmit="submitComment(event, ${book.id})">
                        <textarea id="commentText" placeholder="写下你的评论..." required></textarea>
                        <button type="submit">发表评论</button>
                    </form>
                ` : `
                    <div class="comment-login-hint">
                        请先<a href="#" onclick="showLoginModal(); return false;">登录</a>后发表评论
                    </div>
                `}
                <div id="commentsList" class="comments-list">
                    <div class="loading">加载评论中...</div>
                </div>
            </div>
        `;

        loadComments(bookId);
    })
    .catch(() => {
        container.innerHTML = '<div class="loading">加载失败，请稍后重试</div>';
    });
}

function loadComments(bookId) {
    const commentsList = document.getElementById('commentsList');
    
    fetch(`${API_BASE}/books/${bookId}/comments`)
    .then(response => response.json())
    .then(data => {
        const comments = data.comments || [];
        
        if (comments.length === 0) {
            commentsList.innerHTML = '<div class="no-comments">暂无评论，快来抢沙发吧！</div>';
            return;
        }

        commentsList.innerHTML = comments.map(comment => `
            <div class="comment-item">
                <div class="comment-header">
                    <div class="comment-user">
                        <div class="comment-user-avatar">${comment.user.username.charAt(0).toUpperCase()}</div>
                        <span class="comment-user-name">${escapeHtml(comment.user.username)}</span>
                    </div>
                    <span class="comment-time">${formatDate(comment.created_at)}</span>
                </div>
                <p class="comment-content">${escapeHtml(comment.content)}</p>
            </div>
        `).join('');
    })
    .catch(() => {
        commentsList.innerHTML = '<div class="loading">评论加载失败</div>';
    });
}

function submitComment(event, bookId) {
    event.preventDefault();
    
    const token = localStorage.getItem('token');
    if (!token) {
        showLoginModal();
        return;
    }

    const content = document.getElementById('commentText').value.trim();
    if (!content) {
        showToast('请输入评论内容', 'error');
        return;
    }

    fetch(`${API_BASE}/books/${bookId}/comments`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({ content })
    })
    .then(response => response.json())
    .then(data => {
        if (data.message) {
            showToast('评论发表成功！', 'success');
            document.getElementById('commentText').value = '';
            loadComments(bookId);
        } else {
            showToast(data.error || '评论失败', 'error');
        }
    })
    .catch(() => {
        showToast('评论失败，请稍后重试', 'error');
    });
}

function toggleLike(bookId) {
    const token = localStorage.getItem('token');
    if (!token) {
        showLoginModal();
        return;
    }

    fetch(`${API_BASE}/books/${bookId}/like`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.isLiked !== undefined) {
            showToast(data.message, 'success');
            loadBookDetail(bookId);
        } else {
            showToast(data.error || '操作失败', 'error');
        }
    })
    .catch(() => {
        showToast('操作失败，请稍后重试', 'error');
    });
}

function showRecommendModal(bookId, hasRecommended) {
    const token = localStorage.getItem('token');
    if (!token) {
        showLoginModal();
        return;
    }

    if (hasRecommended) {
        showToast('您已经推荐过这本书了', 'error');
        return;
    }

    const modalContent = `
        <span class="close" onclick="closeModal('recommendModal')">&times;</span>
        <h2>推荐这本书</h2>
        <div class="recommend-modal-content">
            <form onsubmit="submitRecommendation(event, ${bookId})">
                <div class="form-group">
                    <label for="recommendReason">推荐理由（选填）</label>
                    <textarea id="recommendReason" placeholder="写下你推荐这本书的理由..."></textarea>
                </div>
                <button type="submit" class="btn-primary">确认推荐</button>
            </form>
        </div>
    `;

    let recommendModal = document.getElementById('recommendModal');
    if (!recommendModal) {
        recommendModal = document.createElement('div');
        recommendModal.id = 'recommendModal';
        recommendModal.className = 'modal';
        document.body.appendChild(recommendModal);
    }

    recommendModal.innerHTML = `<div class="modal-content">${modalContent}</div>`;
    recommendModal.classList.add('active');
}

function submitRecommendation(event, bookId) {
    event.preventDefault();
    
    const token = localStorage.getItem('token');
    const reason = document.getElementById('recommendReason').value.trim();

    fetch(`${API_BASE}/books/${bookId}/recommend`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({ reason })
    })
    .then(response => response.json())
    .then(data => {
        if (data.message) {
            closeModal('recommendModal');
            showToast('推荐成功！', 'success');
            loadBookDetail(bookId);
            loadRecommendedBooks();
        } else {
            showToast(data.error || '推荐失败', 'error');
        }
    })
    .catch(() => {
        showToast('推荐失败，请稍后重试', 'error');
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    
    if (diff < 60000) return '刚刚';
    if (diff < 3600000) return `${Math.floor(diff / 60000)}分钟前`;
    if (diff < 86400000) return `${Math.floor(diff / 3600000)}小时前`;
    if (diff < 604800000) return `${Math.floor(diff / 86400000)}天前`;
    
    return date.toLocaleDateString('zh-CN');
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast ${type} show`;
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.classList.remove('active');
            if (modal.id === 'bookDetailModal') {
                currentBookId = null;
            }
        }
    });
}
