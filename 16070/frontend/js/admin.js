let currentUser = null;
let users = [];
let schedules = [];
let currentYear, currentMonth;
let selectedDateSchedules = [];
let selectedSchedulesForSwap = [];

document.addEventListener('DOMContentLoaded', async () => {
    const auth = await checkAuth();
    if (!auth.logged_in || auth.user.role !== 'admin') {
        window.location.href = 'index.html';
        return;
    }
    currentUser = auth.user;
    document.getElementById('userName').textContent = `👋 ${auth.user.name}`;

    initTabs();
    loadUsers();
    loadAllData();

    const now = new Date();
    currentYear = now.getFullYear();
    currentMonth = now.getMonth();
    renderCalendar();
});

function initTabs() {
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const tabName = tab.dataset.tab;
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById(`tab-${tabName}`).classList.add('active');

            if (tabName === 'logs') loadLogs();
            if (tabName === 'attendance') loadAttendance();
        });
    });
}

async function loadUsers() {
    users = await api.users.list();
    renderUsersTable();
    renderUserSelection();
    renderUserSelectOptions();
}

function renderUsersTable() {
    const tbody = document.getElementById('usersTableBody');
    tbody.innerHTML = users.map(u => `
        <tr>
            <td>${u.id}</td>
            <td>${u.username}</td>
            <td>${u.name}</td>
            <td><span class="status-badge ${u.role === 'admin' ? 'status-info' : 'status-success'}">${u.role === 'admin' ? '管理员' : '值班人员'}</span></td>
            <td>${u.phone || '-'}</td>
            <td>${u.email || '-'}</td>
            <td>${formatDateTime(u.created_at)}</td>
            <td>
                <button class="btn btn-default btn-sm" onclick="editUser(${u.id})">编辑</button>
                ${u.id !== 1 ? `<button class="btn btn-danger btn-sm" onclick="deleteUser(${u.id})">删除</button>` : ''}
            </td>
        </tr>
    `).join('');
}

function renderUserSelection() {
    const container = document.getElementById('userSelection');
    container.innerHTML = users.filter(u => u.role === 'staff').map(u => `
        <label class="user-checkbox" data-id="${u.id}">
            <input type="checkbox" value="${u.id}"> ${u.name}
        </label>
    `).join('');

    container.querySelectorAll('.user-checkbox').forEach(box => {
        const checkbox = box.querySelector('input');
        box.addEventListener('click', (e) => {
            if (e.target !== checkbox) checkbox.checked = !checkbox.checked;
            box.classList.toggle('selected', checkbox.checked);
        });
    });
}

function renderUserSelectOptions() {
    const editSelect = document.getElementById('editUserId');
    if (editSelect) {
        editSelect.innerHTML = users.map(u => `<option value="${u.id}">${u.name}</option>`).join('');
    }
}

function showAddUserModal() {
    document.getElementById('userModalTitle').textContent = '添加值班人员';
    document.getElementById('editUserId').value = '';
    document.getElementById('username').value = '';
    document.getElementById('name').value = '';
    document.getElementById('password').value = '';
    document.getElementById('passwordField').style.display = 'block';
    document.getElementById('role').value = 'staff';
    document.getElementById('phone').value = '';
    document.getElementById('email').value = '';
    document.getElementById('strengthBar').style.width = '0';
    document.getElementById('strengthText').textContent = '';
    openModal('userModal');
}

function editUser(id) {
    const user = users.find(u => u.id === id);
    if (!user) return;

    document.getElementById('userModalTitle').textContent = '编辑值班人员';
    document.getElementById('editUserId').value = user.id;
    document.getElementById('username').value = user.username;
    document.getElementById('name').value = user.name;
    document.getElementById('password').value = '';
    document.getElementById('passwordField').style.display = 'block';
    document.getElementById('role').value = user.role;
    document.getElementById('phone').value = user.phone || '';
    document.getElementById('email').value = user.email || '';
    document.getElementById('strengthBar').style.width = '0';
    document.getElementById('strengthText').textContent = '（留空则不修改密码）';
    openModal('userModal');
}

async function deleteUser(id) {
    if (!confirm('确定要删除该值班人员吗？')) return;
    await api.users.delete(id);
    showMessage('删除成功');
    loadUsers();
}

document.getElementById('password').addEventListener('input', (e) => {
    const strength = checkPasswordStrength(e.target.value);
    const info = getStrengthInfo(strength);
    document.getElementById('strengthBar').style.width = info.width;
    document.getElementById('strengthBar').style.background = info.color;
    document.getElementById('strengthText').textContent = e.target.value ? `密码强度：${info.text}` : '（留空则不修改密码）';
    document.getElementById('strengthText').style.color = info.color;
});

document.getElementById('userForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('editUserId').value;
    const data = {
        username: document.getElementById('username').value.trim(),
        name: document.getElementById('name').value.trim(),
        role: document.getElementById('role').value,
        phone: document.getElementById('phone').value.trim(),
        email: document.getElementById('email').value.trim()
    };

    const password = document.getElementById('password').value;
    if (password) {
        if (!checkPasswordStrength(password)) {
            showMessage('密码强度不够，需包含大小写字母和特殊符号，至少8位', 'error');
            return;
        }
        data.password = password;
    } else if (!id) {
        showMessage('请设置密码', 'error');
        return;
    }

    try {
        if (id) {
            await api.users.update(id, data);
            showMessage('更新成功');
        } else {
            await api.users.create(data);
            showMessage('添加成功');
        }
        closeModal('userModal');
        loadUsers();
    } catch (e) {}
});

async function loadAllData() {
    const params = { year: currentYear, month: currentMonth + 1 };
    schedules = await api.schedules.list(params);
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
    schedules.forEach(s => {
        if (!scheduleMap[s.date]) scheduleMap[s.date] = [];
        scheduleMap[s.date].push(s);
    });

    let html = '';

    for (let i = 1; i < startDay; i++) {
        const prevDate = new Date(currentYear, currentMonth, -startDay + i + 1);
        const dateStr = prevDate.toISOString().split('T')[0];
        html += renderCalendarDay(prevDate.getDate(), dateStr, scheduleMap[dateStr] || [], true);
    }

    for (let i = 1; i <= daysInMonth; i++) {
        const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
        const isToday = dateStr === todayStr;
        html += renderCalendarDay(i, dateStr, scheduleMap[dateStr] || [], false, isToday);
    }

    const remaining = 42 - (startDay - 1 + daysInMonth);
    for (let i = 1; i <= remaining; i++) {
        const nextDate = new Date(currentYear, currentMonth + 1, i);
        const dateStr = nextDate.toISOString().split('T')[0];
        html += renderCalendarDay(nextDate.getDate(), dateStr, scheduleMap[dateStr] || [], true);
    }

    calendar.innerHTML = html;

    calendar.querySelectorAll('.calendar-day').forEach(day => {
        day.addEventListener('click', () => {
            const date = day.dataset.date;
            const daySchedules = schedules.filter(s => s.date === date);
            if (daySchedules.length > 0) {
                showEditScheduleModal(daySchedules[0]);
            } else {
                showAddScheduleModal(date);
            }
        });
    });
}

function renderCalendarDay(dayNum, dateStr, daySchedules, isOtherMonth, isToday = false) {
    const classes = ['calendar-day'];
    if (isOtherMonth) classes.push('other-month');
    if (isToday) classes.push('today');

    const schedulesHtml = daySchedules.map(s => {
        const user = users.find(u => u.id === s.user_id);
        return `<div class="calendar-schedule" data-id="${s.id}">${user ? user.name : '未知'}</div>`;
    }).join('');

    return `
        <div class="${classes.join(' ')}" data-date="${dateStr}">
            <div class="calendar-day-number">${dayNum}</div>
            ${schedulesHtml}
        </div>
    `;
}

function prevMonth() {
    currentMonth--;
    if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    }
    loadAllData();
}

function nextMonth() {
    currentMonth++;
    if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    }
    loadAllData();
}

function goToday() {
    const now = new Date();
    currentYear = now.getFullYear();
    currentMonth = now.getMonth();
    loadAllData();
}

function showAddScheduleModal(date) {
    document.getElementById('editScheduleId').value = '';
    document.getElementById('editDate').value = date;
    document.getElementById('editUserId').value = users.find(u => u.role === 'staff')?.id || '';
    document.getElementById('editShiftType').value = 'day';
    document.getElementById('editStartTime').value = '08:00';
    document.getElementById('editEndTime').value = '18:00';
    openModal('editScheduleModal');
}

function showEditScheduleModal(schedule) {
    document.getElementById('editScheduleId').value = schedule.id;
    document.getElementById('editDate').value = schedule.date;
    document.getElementById('editUserId').value = schedule.user_id;
    document.getElementById('editShiftType').value = schedule.shift_type;
    document.getElementById('editStartTime').value = schedule.start_time;
    document.getElementById('editEndTime').value = schedule.end_time;
    openModal('editScheduleModal');
}

async function deleteSchedule() {
    const id = document.getElementById('editScheduleId').value;
    if (!id) return;
    if (!confirm('确定要删除该排班记录吗？')) return;
    await api.schedules.delete(id);
    showMessage('删除成功');
    closeModal('editScheduleModal');
    loadAllData();
}

document.getElementById('editScheduleForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('editScheduleId').value;
    const data = {
        date: document.getElementById('editDate').value,
        user_id: parseInt(document.getElementById('editUserId').value),
        shift_type: document.getElementById('editShiftType').value,
        start_time: document.getElementById('editStartTime').value,
        end_time: document.getElementById('editEndTime').value
    };

    try {
        if (id) {
            await api.schedules.update(id, data);
            showMessage('更新成功');
        } else {
            await api.schedules.create(data);
            showMessage('添加成功');
        }
        closeModal('editScheduleModal');
        loadAllData();
    } catch (e) {}
});

function showBatchScheduleModal() {
    const today = new Date();
    document.getElementById('startDate').value = today.toISOString().split('T')[0];
    const nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    document.getElementById('endDate').value = nextMonth.toISOString().split('T')[0];
    document.querySelectorAll('#userSelection input').forEach(cb => cb.checked = false);
    document.querySelectorAll('#userSelection .user-checkbox').forEach(box => box.classList.remove('selected'));
    openModal('batchScheduleModal');
}

document.getElementById('batchScheduleForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const selectedUsers = Array.from(document.querySelectorAll('#userSelection input:checked')).map(cb => parseInt(cb.value));
    if (selectedUsers.length === 0) {
        showMessage('请至少选择一名值班人员', 'error');
        return;
    }

    const data = {
        start_date: document.getElementById('startDate').value,
        end_date: document.getElementById('endDate').value,
        user_ids: selectedUsers,
        shift_type: document.getElementById('shiftType').value,
        start_time: document.getElementById('startTime').value,
        end_time: document.getElementById('endTime').value,
        exclude_weekends: document.getElementById('excludeWeekends').checked
    };

    try {
        const result = await api.schedules.batch(data);
        showMessage(result.message);
        closeModal('batchScheduleModal');
        loadAllData();
    } catch (e) {}
});

function showSwapModal() {
    const select1 = document.getElementById('swapSchedule1');
    const select2 = document.getElementById('swapSchedule2');
    const options = schedules.map(s => {
        const user = users.find(u => u.id === s.user_id);
        return `<option value="${s.id}">${s.date} - ${user ? user.name : '未知'}</option>`;
    }).join('');
    select1.innerHTML = '<option value="">请选择</option>' + options;
    select2.innerHTML = '<option value="">请选择</option>' + options;
    select1.value = '';
    select2.value = '';
    openModal('swapModal');
}

document.getElementById('swapForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id1 = parseInt(document.getElementById('swapSchedule1').value);
    const id2 = parseInt(document.getElementById('swapSchedule2').value);

    if (!id1 || !id2 || id1 === id2) {
        showMessage('请选择两条不同的排班记录', 'error');
        return;
    }

    try {
        await api.schedules.swap({ id1, id2 });
        showMessage('调班成功');
        closeModal('swapModal');
        loadAllData();
    } catch (e) {}
});

async function loadLogs() {
    const logs = await api.logs.list();
    const tbody = document.getElementById('logsTableBody');
    tbody.innerHTML = logs.map(log => {
        const user = users.find(u => u.id === log.user_id);
        const reliever = users.find(u => u.id === log.reliever_id);
        const images = JSON.parse(log.images || '[]');
        return `
            <tr>
                <td>${log.id}</td>
                <td>${log.date}</td>
                <td>${user ? user.name : '-'}</td>
                <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${log.content}</td>
                <td>${reliever ? reliever.name : '-'}</td>
                <td>${images.length > 0 ? `<span class="status-badge status-info">${images.length}张</span>` : '-'}</td>
                <td>${formatDateTime(log.created_at)}</td>
                <td>
                    <button class="btn btn-default btn-sm" onclick="viewLog(${log.id})">查看</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteLog(${log.id})">删除</button>
                </td>
            </tr>
        `;
    }).join('');
}

async function viewLog(id) {
    const log = await api.logs.list({ id });
    const images = JSON.parse(log.images || '[]');
    const user = users.find(u => u.id === log.user_id);
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
        <p><strong>值班人：</strong>${user ? user.name : '-'}</p>
        <p><strong>交接人：</strong>${reliever ? reliever.name : '-'}</p>
        <p><strong>创建时间：</strong>${formatDateTime(log.created_at)}</p>
        <h4 style="margin-top: 15px;">值班内容：</h4>
        <div class="log-content">${log.content}</div>
        ${log.handover_content ? `<h4 style="margin-top: 15px;">交接内容：</h4><div class="log-content">${log.handover_content}</div>` : ''}
        ${imagesHtml}
    `;
    openModal('viewLogModal');
}

async function deleteLog(id) {
    if (!confirm('确定要删除该日志吗？')) return;
    await api.logs.delete(id);
    showMessage('删除成功');
    loadLogs();
}

async function loadAttendance() {
    const records = await api.attendance.list();
    const tbody = document.getElementById('attendanceTableBody');
    tbody.innerHTML = records.map(r => {
        const user = users.find(u => u.id === r.user_id);
        let status = '';
        if (r.check_in && r.check_out) {
            status = '<span class="status-badge status-success">已完成</span>';
        } else if (r.check_in) {
            status = '<span class="status-badge status-warning">值班中</span>';
        } else {
            status = '<span class="status-badge status-danger">未打卡</span>';
        }
        return `
            <tr>
                <td>${r.id}</td>
                <td>${r.date}</td>
                <td>${user ? user.name : '-'}</td>
                <td>${r.check_in || '-'}</td>
                <td>${r.check_out || '-'}</td>
                <td>${status}</td>
            </tr>
        `;
    }).join('');
}

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
