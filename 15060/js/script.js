document.addEventListener('DOMContentLoaded', function() {
    const dashboard = document.getElementById('dashboard');
    const emptyState = document.getElementById('emptyState');
    const widgetItems = document.querySelectorAll('.widget-item');
    const exportBtn = document.getElementById('exportBtn');
    const saveBtn = document.getElementById('saveBtn');
    const editBtn = document.getElementById('editBtn');
    const sidebar = document.querySelector('.sidebar');
    
    let widgets = [];
    let widgetCounter = 0;
    let zIndexCounter = 1;
    let isLocked = false;
    
    const SNAP_DISTANCE = 10;
    
    const chartConfigs = {
        line: {
            title: '折线图',
            option: {
                title: { text: '销售趋势' },
                tooltip: { trigger: 'axis' },
                legend: { data: ['销量', '利润'] },
                xAxis: { type: 'category', data: ['1月', '2月', '3月', '4月', '5月', '6月'] },
                yAxis: { type: 'value' },
                series: [
                    { name: '销量', type: 'line', data: [820, 932, 901, 934, 1290, 1330] },
                    { name: '利润', type: 'line', data: [320, 432, 401, 534, 690, 730] }
                ]
            }
        },
        bar: {
            title: '柱状图',
            option: {
                title: { text: '月度数据' },
                tooltip: { trigger: 'axis' },
                legend: { data: ['A产品', 'B产品'] },
                xAxis: { type: 'category', data: ['1月', '2月', '3月', '4月', '5月', '6月'] },
                yAxis: { type: 'value' },
                series: [
                    { name: 'A产品', type: 'bar', data: [120, 200, 150, 80, 70, 110] },
                    { name: 'B产品', type: 'bar', data: [60, 140, 230, 110, 150, 90] }
                ]
            }
        },
        pie: {
            title: '饼图',
            option: {
                title: { text: '销售占比', left: 'center' },
                tooltip: { trigger: 'item' },
                legend: { orient: 'vertical', left: 'left' },
                series: [
                    {
                        type: 'pie',
                        radius: ['40%', '70%'],
                        data: [
                            { value: 1048, name: '搜索引擎' },
                            { value: 735, name: '直接访问' },
                            { value: 580, name: '邮件营销' },
                            { value: 484, name: '联盟广告' },
                            { value: 300, name: '视频广告' }
                        ]
                    }
                ]
            }
        },
        radar: {
            title: '雷达图',
            option: {
                title: { text: '能力雷达图' },
                tooltip: {},
                legend: { data: ['预算分配', '实际开销'] },
                radar: {
                    indicator: [
                        { name: '销售', max: 6500 },
                        { name: '管理', max: 16000 },
                        { name: '信息技术', max: 30000 },
                        { name: '客服', max: 38000 },
                        { name: '研发', max: 52000 },
                        { name: '市场', max: 25000 }
                    ]
                },
                series: [
                    {
                        type: 'radar',
                        data: [
                            { value: [4200, 3000, 20000, 35000, 50000, 18000], name: '预算分配' },
                            { value: [5000, 14000, 28000, 26000, 42000, 21000], name: '实际开销' }
                        ]
                    }
                ]
            }
        },
        scatter: {
            title: '散点图',
            option: {
                title: { text: '散点图' },
                tooltip: { trigger: 'axis' },
                xAxis: { type: 'value' },
                yAxis: { type: 'value' },
                series: [
                    {
                        type: 'scatter',
                        data: [
                            [10, 20], [20, 30], [30, 40], [40, 50],
                            [50, 60], [60, 70], [70, 80], [80, 90]
                        ],
                        symbolSize: 10
                    }
                ]
            }
        }
    };
    
    function getWidgetRect(widget) {
        return {
            left: widget.element.offsetLeft,
            top: widget.element.offsetTop,
            width: widget.element.offsetWidth,
            height: widget.element.offsetHeight,
            right: widget.element.offsetLeft + widget.element.offsetWidth,
            bottom: widget.element.offsetTop + widget.element.offsetHeight
        };
    }
    
    function checkOverlap(rect1, rect2, padding = 0) {
        return !(rect1.right + padding <= rect2.left || 
                 rect2.right + padding <= rect1.left || 
                 rect1.bottom + padding <= rect2.top || 
                 rect2.bottom + padding <= rect1.top);
    }
    
    function snapToWidget(currentWidget, newLeft, newTop, newWidth, newHeight) {
        const currentRect = {
            left: newLeft,
            top: newTop,
            width: newWidth || currentWidget.element.offsetWidth,
            height: newHeight || currentWidget.element.offsetHeight
        };
        currentRect.right = currentRect.left + currentRect.width;
        currentRect.bottom = currentRect.top + currentRect.height;
        
        let snappedLeft = newLeft;
        let snappedTop = newTop;
        
        const dashboardRect = dashboard.getBoundingClientRect();
        const dashboardPadding = 16;
        
        if (currentRect.left < SNAP_DISTANCE) {
            snappedLeft = dashboardPadding;
        }
        if (currentRect.top < SNAP_DISTANCE) {
            snappedTop = dashboardPadding;
        }
        if (dashboard.clientWidth - currentRect.right < SNAP_DISTANCE + dashboardPadding) {
            snappedLeft = dashboard.clientWidth - currentRect.width - dashboardPadding;
            currentRect.left = snappedLeft;
            currentRect.right = snappedLeft + currentRect.width;
        }
        if (dashboard.clientHeight - currentRect.bottom < SNAP_DISTANCE + dashboardPadding) {
            snappedTop = dashboard.clientHeight - currentRect.height - dashboardPadding;
            currentRect.top = snappedTop;
            currentRect.bottom = snappedTop + currentRect.height;
        }
        
        const otherWidgets = widgets.filter(w => w.id !== currentWidget.id);
        
        for (const other of otherWidgets) {
            const otherRect = getWidgetRect(other);
            
            if (Math.abs(currentRect.right - otherRect.left) < SNAP_DISTANCE) {
                if (currentRect.top < otherRect.bottom && currentRect.bottom > otherRect.top) {
                    snappedLeft = otherRect.left - currentRect.width;
                    currentRect.left = snappedLeft;
                    currentRect.right = snappedLeft + currentRect.width;
                }
            }
            
            if (Math.abs(currentRect.left - otherRect.right) < SNAP_DISTANCE) {
                if (currentRect.top < otherRect.bottom && currentRect.bottom > otherRect.top) {
                    snappedLeft = otherRect.right;
                    currentRect.left = snappedLeft;
                    currentRect.right = snappedLeft + currentRect.width;
                }
            }
            
            if (Math.abs(currentRect.bottom - otherRect.top) < SNAP_DISTANCE) {
                if (currentRect.left < otherRect.right && currentRect.right > otherRect.left) {
                    snappedTop = otherRect.top - currentRect.height;
                    currentRect.top = snappedTop;
                    currentRect.bottom = snappedTop + currentRect.height;
                }
            }
            
            if (Math.abs(currentRect.top - otherRect.bottom) < SNAP_DISTANCE) {
                if (currentRect.left < otherRect.right && currentRect.right > otherRect.left) {
                    snappedTop = otherRect.bottom;
                    currentRect.top = snappedTop;
                    currentRect.bottom = snappedTop + currentRect.height;
                }
            }
        }
        
        if (newWidth || newHeight) {
            for (const other of otherWidgets) {
                const otherRect = getWidgetRect(other);
                const tempRect = {
                    left: snappedLeft,
                    top: snappedTop,
                    right: snappedLeft + (newWidth || currentRect.width),
                    bottom: snappedTop + (newHeight || currentRect.height)
                };
                
                if (checkOverlap(tempRect, otherRect, -5)) {
                    return { left: null, top: null, width: null, height: null };
                }
            }
        }
        
        const finalRect = {
            left: snappedLeft,
            top: snappedTop,
            width: newWidth || currentRect.width,
            height: newHeight || currentRect.height
        };
        finalRect.right = finalRect.left + finalRect.width;
        finalRect.bottom = finalRect.top + finalRect.height;
        
        for (const other of otherWidgets) {
            const otherRect = getWidgetRect(other);
            if (checkOverlap(finalRect, otherRect, -5)) {
                return { left: null, top: null };
            }
        }
        
        return { 
            left: snappedLeft, 
            top: snappedTop,
            width: newWidth,
            height: newHeight
        };
    }
    
    function saveLayout() {
        const layoutData = widgets.map(widget => ({
            type: widget.type,
            left: widget.element.offsetLeft,
            top: widget.element.offsetTop,
            width: widget.element.offsetWidth,
            height: widget.element.offsetHeight
        }));
        
        localStorage.setItem('dashboardLayout', JSON.stringify(layoutData));
        
        isLocked = true;
        dashboard.classList.add('locked');
        sidebar.classList.add('locked');
        saveBtn.style.display = 'none';
        editBtn.style.display = 'block';
        
        alert('布局已保存并锁定！');
    }
    
    function editLayout() {
        isLocked = false;
        dashboard.classList.remove('locked');
        sidebar.classList.remove('locked');
        saveBtn.style.display = 'block';
        editBtn.style.display = 'none';
        
        alert('布局已解锁，可以进行编辑！');
    }
    
    function loadSavedLayout() {
        const savedData = localStorage.getItem('dashboardLayout');
        if (savedData) {
            const layoutData = JSON.parse(savedData);
            if (layoutData.length > 0) {
                layoutData.forEach(item => {
                    createWidget(item.type, item.left + 150, item.top + 100);
                    const lastWidget = widgets[widgets.length - 1];
                    lastWidget.element.style.left = `${item.left}px`;
                    lastWidget.element.style.top = `${item.top}px`;
                    lastWidget.element.style.width = `${item.width}px`;
                    lastWidget.element.style.height = `${item.height}px`;
                    lastWidget.chart.resize();
                });
                
                isLocked = true;
                dashboard.classList.add('locked');
                sidebar.classList.add('locked');
                saveBtn.style.display = 'none';
                editBtn.style.display = 'block';
            }
        }
    }
    
    saveBtn.addEventListener('click', saveLayout);
    editBtn.addEventListener('click', editLayout);
    
    loadSavedLayout();
    
    widgetItems.forEach(item => {
        item.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('widgetType', this.dataset.widgetType);
            e.dataTransfer.effectAllowed = 'copy';
        });
    });
    
    dashboard.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
        dashboard.classList.add('drag-over');
    });
    
    dashboard.addEventListener('dragleave', function(e) {
        dashboard.classList.remove('drag-over');
    });
    
    dashboard.addEventListener('drop', function(e) {
        e.preventDefault();
        dashboard.classList.remove('drag-over');
        
        if (isLocked) return;
        
        const widgetType = e.dataTransfer.getData('widgetType');
        if (widgetType && chartConfigs[widgetType]) {
            const rect = dashboard.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const defaultWidth = 400;
            const defaultHeight = 300;
            const proposedLeft = x - defaultWidth / 2;
            const proposedTop = y - defaultHeight / 2;
            
            const tempWidget = {
                element: {
                    offsetLeft: proposedLeft,
                    offsetTop: proposedTop,
                    offsetWidth: defaultWidth,
                    offsetHeight: defaultHeight
                }
            };
            
            const snapped = snapToWidget(tempWidget, proposedLeft, proposedTop, defaultWidth, defaultHeight);
            
            if (snapped.left !== null && snapped.top !== null) {
                createWidget(widgetType, snapped.left + defaultWidth / 2, snapped.top + defaultHeight / 2);
            } else {
                alert('该位置无法放置模块，请选择其他位置！');
            }
        }
    });
    
    function createWidget(type, x, y, presetWidth, presetHeight) {
        const config = chartConfigs[type];
        const widgetId = `widget-${++widgetCounter}`;
        
        const defaultWidth = presetWidth || 400;
        const defaultHeight = presetHeight || 300;
        
        const widgetEl = document.createElement('div');
        widgetEl.className = 'widget-container';
        widgetEl.id = widgetId;
        widgetEl.style.left = `${x - defaultWidth / 2}px`;
        widgetEl.style.top = `${y - defaultHeight / 2}px`;
        widgetEl.style.width = `${defaultWidth}px`;
        widgetEl.style.height = `${defaultHeight}px`;
        widgetEl.style.zIndex = zIndexCounter++;
        
        widgetEl.innerHTML = `
            <div class="widget-header">
                <span class="widget-title">${config.title}</span>
                <div class="widget-actions">
                    <button class="widget-btn" data-action="delete" title="删除">✕</button>
                </div>
            </div>
            <div class="widget-content" id="chart-${widgetId}"></div>
            <div class="resize-handle" data-action="resize"></div>
        `;
        
        dashboard.appendChild(widgetEl);
        updateEmptyState();
        
        const chartEl = document.getElementById(`chart-${widgetId}`);
        const chart = echarts.init(chartEl);
        chart.setOption(config.option);
        
        const widget = {
            id: widgetId,
            element: widgetEl,
            chart: chart,
            type: type
        };
        widgets.push(widget);
        
        initWidgetEvents(widget);
    }
    
    function initWidgetEvents(widget) {
        const header = widget.element.querySelector('.widget-header');
        const deleteBtn = widget.element.querySelector('[data-action="delete"]');
        const resizeHandle = widget.element.querySelector('[data-action="resize"]');
        
        let isDragging = false;
        let isResizing = false;
        let startX, startY, startLeft, startTop, startWidth, startHeight;
        let lastValidLeft, lastValidTop, lastValidWidth, lastValidHeight;
        
        widget.element.addEventListener('mousedown', function(e) {
            if (!isLocked) {
                widget.element.style.zIndex = zIndexCounter++;
            }
        });
        
        header.addEventListener('mousedown', function(e) {
            if (isLocked || e.target.closest('.widget-btn')) return;
            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            startLeft = widget.element.offsetLeft;
            startTop = widget.element.offsetTop;
            lastValidLeft = startLeft;
            lastValidTop = startTop;
            e.preventDefault();
        });
        
        deleteBtn.addEventListener('click', function() {
            if (!isLocked) {
                deleteWidget(widget.id);
            }
        });
        
        resizeHandle.addEventListener('mousedown', function(e) {
            if (isLocked) return;
            isResizing = true;
            startX = e.clientX;
            startY = e.clientY;
            startWidth = widget.element.offsetWidth;
            startHeight = widget.element.offsetHeight;
            lastValidWidth = startWidth;
            lastValidHeight = startHeight;
            e.preventDefault();
            e.stopPropagation();
        });
        
        document.addEventListener('mousemove', function(e) {
            if (isLocked) return;
            
            if (isDragging) {
                const dx = e.clientX - startX;
                const dy = e.clientY - startY;
                const newLeft = startLeft + dx;
                const newTop = startTop + dy;
                
                const snapped = snapToWidget(widget, newLeft, newTop);
                
                if (snapped.left !== null && snapped.top !== null) {
                    widget.element.style.left = `${snapped.left}px`;
                    widget.element.style.top = `${snapped.top}px`;
                    lastValidLeft = snapped.left;
                    lastValidTop = snapped.top;
                } else {
                    widget.element.style.left = `${lastValidLeft}px`;
                    widget.element.style.top = `${lastValidTop}px`;
                }
            }
            
            if (isResizing) {
                const dx = e.clientX - startX;
                const dy = e.clientY - startY;
                const newWidth = Math.max(300, startWidth + dx);
                const newHeight = Math.max(200, startHeight + dy);
                
                const currentLeft = widget.element.offsetLeft;
                const currentTop = widget.element.offsetTop;
                
                const snapped = snapToWidget(widget, currentLeft, currentTop, newWidth, newHeight);
                
                if (snapped.width !== null && snapped.height !== null) {
                    widget.element.style.width = `${snapped.width || newWidth}px`;
                    widget.element.style.height = `${snapped.height || newHeight}px`;
                    lastValidWidth = snapped.width || newWidth;
                    lastValidHeight = snapped.height || newHeight;
                    widget.chart.resize();
                } else {
                    widget.element.style.width = `${lastValidWidth}px`;
                    widget.element.style.height = `${lastValidHeight}px`;
                }
            }
        });
        
        document.addEventListener('mouseup', function() {
            isDragging = false;
            isResizing = false;
        });
        
        window.addEventListener('resize', function() {
            widget.chart.resize();
        });
    }
    
    function deleteWidget(id) {
        const widgetIndex = widgets.findIndex(w => w.id === id);
        if (widgetIndex !== -1) {
            const widget = widgets[widgetIndex];
            widget.chart.dispose();
            widget.element.remove();
            widgets.splice(widgetIndex, 1);
            updateEmptyState();
        }
    }
    
    function updateEmptyState() {
        emptyState.style.display = widgets.length === 0 ? 'flex' : 'none';
    }
    
    exportBtn.addEventListener('click', function() {
        exportBtn.style.display = 'none';
        
        html2canvas(dashboard, {
            backgroundColor: '#ffffff',
            scale: 2,
            useCORS: true,
            logging: false
        }).then(canvas => {
            const link = document.createElement('a');
            link.download = `dashboard-${Date.now()}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
            
            exportBtn.style.display = 'block';
        }).catch(error => {
            console.error('导出失败:', error);
            exportBtn.style.display = 'block';
            alert('导出失败，请重试');
        });
    });
});
