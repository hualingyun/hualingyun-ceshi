let currentUser = null;
let users = [];
let mySchedules = [];
let currentYear, currentMonth;
let uploadedImages = [];

document.addEventListener('DOMContentLoaded', async () => {
    const auth = await checkAuth();
    if (!auth.logged_in || auth.user.role !== 'staff') {
        window.location.href = 'index.html';
        return;
    }
    currentUser = auth.user;
    document.getElementById('userName').textContent = `👋 ${auth.user.name}`;

    initTabs();
    loadUsers();
    loadTodayStatus();
    loadMyLogs();
    renderRelieverSelect();

    const now = new Date();
    currentYear = now.getFullYear();
    currentMonth = now.getMonth();
    loadMySchedules();
});

function initTabs() {
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const tabName = tab.dataset.tab;
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById(`tab-${tabName}`).classList.add('active');

            if (tabName === 'my-logs') loadMyLogs();
        });
    });
}

async function loadUsers() {
    users = await api.users.list();
    renderRelieverSelect();
}

function renderRelieverSelect() {
    const select = document.getElementById('relieverId');
    if (select && users.length > 0) {
        select.innerHTML = '<option value="">请选择（可选）</option>' +
            users.filter(u => u.id !== currentUser.id).map(u => `<option value="${u.id}">${u.name}</option>`).join('');
    }
}

async function loadTodayStatus() {
    try {
        const status = await api.attendance.todayStatus();
        updateTodayUI(status);
    } catch (e) {}
}

function updateTodayUI(status) {
    const todayStatusEl = document.getElementById('todayStatus');
    const checkInEl = document.getElementById('checkInTime');
    const checkOutEl = document.getElementById('checkOutTime');
    const checkInBtn = document.getElementById('checkInBtn');
    const checkOutBtn = document.getElementById('checkOutBtn');
    const scheduleInfo = document.getElementById('todayScheduleInfo');

    if (!status.has_schedule) {
        todayStatusEl.innerHTML = '<span class="status-badge status-info">今日休息</span>';
        checkInBtn.disabled = true;
        checkOutBtn.disabled = true;
        scheduleInfo.textContent = '您今天没有排班，无法打卡。';
    } else {
        todayStatusEl.innerHTML = '<span class="status-badge status-success">今日值班</span>';
        const s = status.schedule;
        scheduleInfo.textContent = `今日排班：${s.start_time} - ${s.end_time} (${s.shift_type === 'day' ? '白班' : s.shift_type === 'night' ? '夜班' : '全天'})`;

        if (status.attendance) {
            if (status.attendance.check_in) {
                checkInEl.textContent = status.attendance.check_in;
                checkInEl.className = 'status-badge status-success';
                checkInBtn.disabled = true;
            }
            if (status.attendance.check_out) {
                checkOutEl.textContent = status.attendance.check_out;
                checkOutEl.className = 'status-badge status-success';
                checkOutBtn.disabled = true;
            } else if (!status.attendance.check_in) {
                checkOutBtn.disabled = true;
            }
        } else {
            checkInBtn.disabled = false;
            checkOutBtn.disabled = true;
        }
    }
}

async function checkIn() {
    try {
        const result = await api.attendance.checkIn();
        showMessage('上班打卡成功');
        loadTodayStatus();
    } catch (e) {}
}

async function checkOut() {
    try {
        const result = await api.attendance.checkOut();
        showMessage('下班打卡成功');
        loadTodayStatus();
    } catch (e) {}
}

async function loadMySchedules() {
    const params = { year: currentYear, month: currentMonth + 1, user_id: currentUser.id };
    mySchedules = await api.schedules.list(params);

    document.getElementById('monthDays').textContent = mySchedules.length;

    renderCalendar();
}

function renderCalendar() {
    const calendar = document.getElementById('calendar');
    const title = document.getElementById('currentMonthTitle');
    title.textContent = `${currentYear}年${currentMonth + 1}月`;

    const firstDay = new Date(currentYear, currentMonth, 1);
    const lastDay = new Date(currentYear, currentMonth + 1, 0);
    const startDay = firstDay.getDay() || 7;
    const daysInMonth = lastDay.getDate();
    const today = new Date();
    const todayStr = today.toISOString().split('T')[0];

    const scheduleMap = {};
    mySchedules.forEach(s => {
        scheduleMap[s.date] = s;
    });

    let html = '';

    for (let i = 1; i < startDay; i++) {
        const prevDate = new Date(currentYear, currentMonth, -startDay + i + 1);
        const dateStr = prevDate.toISOString().split('T')[0];
        html += renderCalendarDay(prevDate.getDate(), dateStr, scheduleMap[dateStr], true);
    }

    for (let i = 1; i <= daysInMonth; i++) {
        const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
        const isToday = dateStr === todayStr;
        html += renderCalendarDay(i, dateStr, scheduleMap[dateStr], false, isToday);
    }

    const remaining = 42 - (startDay - 1 + daysInMonth);
    for (let i = 1; i <= remaining; i++) {
        const nextDate = new Date(currentYear, currentMonth + 1, i);
        const dateStr = nextDate.toISOString().split('T')[0];
        html += renderCalendarDay(nextDate.getDate(), dateStr, scheduleMap[dateStr], true);
    }

    calendar.innerHTML = html;
}

function renderCalendarDay(dayNum, dateStr, schedule, isOtherMonth, isToday = false) {
    const classes = ['calendar-day'];
    if (isOtherMonth) classes.push('other-month');
    if (isToday) classes.push('today');
    if (schedule) classes.push('selected');

    let scheduleHtml = '';
    if (schedule) {
        const shiftType = schedule.shift_type === 'day' ? '白班' : schedule.shift_type === 'night' ? '夜班' : '全天';
        scheduleHtml = `<div class="calendar-schedule">${shiftType} ${schedule.start_time}</div>`;
    }

    return `
        <div class="${classes.join(' ')}" data-date="${dateStr}">
            <div class="calendar-day-number">${dayNum}</div>
            ${scheduleHtml}
        </div>
    `;
}

function prevMonth() {
    currentMonth--;
    if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    }
    loadMySchedules();
}

function nextMonth() {
    currentMonth++;
    if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    }
    loadMySchedules();
}

function goToday() {
    const now = new Date();
    currentYear = now.getFullYear();
    currentMonth = now.getMonth();
    loadMySchedules();
}

async function loadMyLogs() {
    const logs = await api.logs.list({ user_id: currentUser.id });
    const tbody = document.getElementById('logsTableBody');
    tbody.innerHTML = logs.map(log => {
        const reliever = users.find(u => u.id === log.reliever_id);
        const images = JSON.parse(log.images || '[]');
        return `
            <tr>
                <td>${log.id}</td>
                <td>${log.date}</td>
                <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${log.content}</td>
                <td>${reliever ? reliever.name : '-'}</td>
                <td>${images.length > 0 ? `<span class="status-badge status-info">${images.length}张</span>` : '-'}</td>
                <td>${formatDateTime(log.created_at)}</td>
                <td>
                    <button class="btn btn-default btn-sm" onclick="viewLog(${log.id})">查看</button>
                    <button class="btn btn-default btn-sm" onclick="editLog(${log.id})">编辑</button>
                </td>
            </tr>
        `;
    }).join('');
}

async function viewLog(id) {
    const log = await api.logs.list({ id });
    const images = JSON.parse(log.images || '[]');
    const reliever = users.find(u => u.id === log.reliever_id);

    const imagesHtml = images.length > 0 ? `
        <h4 style="margin-top: 15px;">附件图片：</h4>
        <div class="image-preview">
            ${images.map((img, i) => `
                <div>
                    <img src="${img.url}" onclick="window.open('${img.url}&download=1', '_blank')">
                    <div style="text-align: center; font-size: 12px; margin-top: 4px;">
                        <a href="${img.url}&download=1" download="${img.original_name || 'image' + i}">下载</a>
                    </div>
                </div>
            `).join('')}
        </div>
    ` : '';

    document.getElementById('logDetail').innerHTML = `
        <p><strong>日期：</strong>${log.date}</p>
        <p><strong>交接人：</strong>${reliever ? reliever.name : '-'}</p>
        <p><strong>创建时间：</strong>${formatDateTime(log.created_at)}</p>
        <h4 style="margin-top: 15px;">值班内容：</h4>
        <div class="log-content">${log.content}</div>
        ${log.handover_content ? `<h4 style="margin-top: 15px;">交接内容：</h4><div class="log-content">${log.handover_content}</div>` : ''}
        ${imagesHtml}
    `;
    openModal('viewLogModal');
}

async function editLog(id) {
    const log = await api.logs.list({ id });
    const images = JSON.parse(log.images || '[]');

    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.querySelector('[data-tab="submit-log"]').classList.add('active');
    document.getElementById('tab-submit-log').classList.add('active');

    document.getElementById('logContent').value = log.content;
    document.getElementById('handoverContent').value = log.handover_content || '';
    document.getElementById('relieverId').value = log.reliever_id || '';

    uploadedImages = images;
    renderImagePreview();

    showMessage('请修改后重新提交');
}

document.getElementById('imageInput').addEventListener('change', async (e) => {
    const files = Array.from(e.target.files);
    for (const file of files) {
        try {
            const result = await api.uploadImage(file);
            uploadedImages.push(result);
            renderImagePreview();
        } catch (err) {
            console.error('上传失败:', err);
        }
    }
    e.target.value = '';
});

function renderImagePreview() {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = uploadedImages.map((img, index) => `
        <div style="position: relative;">
            <img src="${img.url}" onclick="window.open('${img.url}&download=1', '_blank')">
            <button type="button" class="btn btn-danger btn-sm" style="position: absolute; top: 2px; right: 2px; padding: 2px 6px;" onclick="removeImage(${index})">×</button>
        </div>
    `).join('');
}

function removeImage(index) {
    uploadedImages.splice(index, 1);
    renderImagePreview();
}

document.getElementById('logForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const content = document.getElementById('logContent').value.trim();
    const handoverContent = document.getElementById('handoverContent').value.trim();
    const relieverId = parseInt(document.getElementById('relieverId').value) || 0;

    if (!content) {
        showMessage('请填写值班内容', 'error');
        return;
    }

    try {
        await api.logs.create({
            content,
            handover_content: handoverContent,
            reliever_id: relieverId,
            images: uploadedImages
        });
        showMessage('日志提交成功');
        document.getElementById('logForm').reset();
        uploadedImages = [];
        renderImagePreview();
        loadMyLogs();
    } catch (e) {}
});

function openModal(id) {
    document.getElementById(id).classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });
});
