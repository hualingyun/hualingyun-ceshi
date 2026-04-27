class DashboardManager {
    constructor() {
        this.charts = new Map();
        this.chartIdCounter = 0;
        this.currentEditingChart = null;
        this.theme = 'dark';
        this.isResizing = false;
        this.resizeElement = null;
        this.resizeStartX = 0;
        this.resizeStartY = 0;
        this.resizeStartWidth = 0;
        this.resizeStartHeight = 0;
        this.draggedChartCard = null;
        
        this.themeColors = {
            dark: {
                text: '#e0e0e0',
                grid: 'rgba(255, 255, 255, 0.1)',
                background: 'rgba(59, 130, 246, 0.2)',
                border: 'rgba(59, 130, 246, 0.5)'
            },
            light: {
                text: '#1e293b',
                grid: 'rgba(0, 0, 0, 0.1)',
                background: 'rgba(59, 130, 246, 0.1)',
                border: 'rgba(59, 130, 246, 0.3)'
            }
        };
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.createInitialCharts();
    }
    
    bindEvents() {
        const themeToggle = document.getElementById('themeToggle');
        const addChartBtn = document.getElementById('addChartBtn');
        const exportPngBtn = document.getElementById('exportPngBtn');
        const exportCsvBtn = document.getElementById('exportCsvBtn');
        const closePanelBtn = document.getElementById('closePanelBtn');
        const applyConfigBtn = document.getElementById('applyConfigBtn');
        const deleteChartBtn = document.getElementById('deleteChartBtn');
        
        if (themeToggle) {
            themeToggle.addEventListener('change', () => this.toggleTheme());
        }
        
        if (addChartBtn) {
            addChartBtn.addEventListener('click', () => this.addChart());
        }
        
        if (exportPngBtn) {
            exportPngBtn.addEventListener('click', () => this.exportAsPng());
        }
        
        if (exportCsvBtn) {
            exportCsvBtn.addEventListener('click', () => this.exportAsCsv());
        }
        
        if (closePanelBtn) {
            closePanelBtn.addEventListener('click', () => this.closeConfigPanel());
        }
        
        if (applyConfigBtn) {
            applyConfigBtn.addEventListener('click', () => this.applyChartConfig());
        }
        
        if (deleteChartBtn) {
            deleteChartBtn.addEventListener('click', () => this.deleteCurrentChart());
        }
        
        document.addEventListener('mouseup', (e) => this.handleMouseUp(e));
        document.addEventListener('mousemove', (e) => this.handleMouseMove(e));
        
        const container = document.getElementById('dashboardContainer');
        if (container) {
            container.addEventListener('dragover', (e) => this.handleDragOver(e));
            container.addEventListener('drop', (e) => this.handleDrop(e));
            container.addEventListener('dragleave', (e) => this.handleDragLeave(e));
        }
    }
    
    createInitialCharts() {
        this.addChart('line', '系统负载监控');
        this.addChart('bar', '用户访问统计');
        this.addChart('pie', '流量来源分布');
        this.addChart('radar', '服务性能指标');
    }
    
    addChart(type = 'line', title = null) {
        const chartId = `chart-${this.chartIdCounter++}`;
        const chartTypes = ['line', 'bar', 'pie', 'radar'];
        const actualType = chartTypes.includes(type) ? type : 'line';
        
        const chartTitles = {
            line: '系统负载监控',
            bar: '用户访问统计',
            pie: '流量来源分布',
            radar: '服务性能指标'
        };
        
        const actualTitle = title || chartTitles[actualType] || '新图表';
        
        const chartCard = document.createElement('div');
        chartCard.className = 'chart-card';
        chartCard.dataset.chartId = chartId;
        chartCard.dataset.chartType = actualType;
        chartCard.draggable = true;
        
        chartCard.innerHTML = `
            <div class="chart-header">
                <h3 class="chart-title">${actualTitle}</h3>
                <div class="chart-actions">
                    <button class="chart-action-btn" title="配置" data-action="config">
                        ⚙️
                    </button>
                    <button class="chart-action-btn" title="拖拽" data-action="drag">
                        📌
                    </button>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="${chartId}"></canvas>
            </div>
            <div class="resize-handle"></div>
        `;
        
        const container = document.getElementById('dashboardContainer');
        if (container.querySelector('.empty-state')) {
            container.innerHTML = '';
        }
        container.appendChild(chartCard);
        
        this.setupChartInteractions(chartCard);
        this.initializeChart(chartId, actualType, actualTitle);
        
        return chartId;
    }
    
    setupChartInteractions(chartCard) {
        const configBtn = chartCard.querySelector('[data-action="config"]');
        const dragBtn = chartCard.querySelector('[data-action="drag"]');
        const resizeHandle = chartCard.querySelector('.resize-handle');
        
        if (configBtn) {
            configBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.openConfigPanel(chartCard);
            });
        }
        
        chartCard.addEventListener('dragstart', (e) => {
            this.draggedChartCard = chartCard;
            chartCard.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', chartCard.dataset.chartId);
        });
        
        chartCard.addEventListener('dragend', (e) => {
            chartCard.classList.remove('dragging');
            this.draggedChartCard = null;
            const container = document.getElementById('dashboardContainer');
            const placeholders = container.querySelectorAll('.drag-placeholder');
            placeholders.forEach(p => p.remove());
        });
        
        chartCard.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            
            if (chartCard === this.draggedChartCard) return;
            
            this.insertDragPlaceholder(chartCard, e);
        });
        
        chartCard.addEventListener('dragleave', (e) => {
            if (!e.relatedTarget || !e.relatedTarget.closest('.chart-card')) {
                const placeholders = document.querySelectorAll('.drag-placeholder');
                placeholders.forEach(p => p.remove());
            }
        });
        
        if (resizeHandle) {
            resizeHandle.addEventListener('mousedown', (e) => {
                e.stopPropagation();
                this.startResize(e, chartCard);
            });
        }
    }
    
    initializeChart(chartId, type, title) {
        const canvas = document.getElementById(chartId);
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const themeColors = this.themeColors[this.theme];
        
        const chartConfig = this.generateChartConfig(type, title, themeColors);
        const chart = new Chart(ctx, chartConfig);
        
        this.charts.set(chartId, {
            chart: chart,
            type: type,
            title: title,
            interval: 2000,
            color: '#3b82f6',
            timer: null
        });
        
        this.startDataUpdate(chartId);
    }
    
    generateChartConfig(type, title, themeColors) {
        const labels = this.generateLabels();
        const baseColor = '#3b82f6';
        
        const configs = {
            line: {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: title,
                        data: this.generateRandomData(labels.length),
                        borderColor: baseColor,
                        backgroundColor: themeColors.background,
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: baseColor,
                        pointBorderColor: '#fff',
                        pointHoverRadius: 6,
                        pointRadius: 3
                    }]
                },
                options: this.getDefaultOptions(title, themeColors)
            },
            bar: {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: title,
                        data: this.generateRandomData(labels.length),
                        backgroundColor: this.generateColorArray(labels.length, baseColor),
                        borderColor: this.generateColorArray(labels.length, baseColor),
                        borderWidth: 1
                    }]
                },
                options: this.getDefaultOptions(title, themeColors)
            },
            pie: {
                type: 'pie',
                data: {
                    labels: ['社交媒体', '直接访问', '搜索引擎', '外部链接', '其他'],
                    datasets: [{
                        label: title,
                        data: this.generateRandomData(5),
                        backgroundColor: [
                            '#ef4444', '#f97316', '#eab308', '#22c55e', '#3b82f6'
                        ],
                        borderColor: themeColors.text,
                        borderWidth: 2
                    }]
                },
                options: this.getDefaultOptions(title, themeColors)
            },
            radar: {
                type: 'radar',
                data: {
                    labels: ['响应时间', 'CPU使用率', '内存使用率', '网络带宽', '错误率', '可用性'],
                    datasets: [{
                        label: '当前性能',
                        data: this.generateRandomData(6, 20, 90),
                        borderColor: baseColor,
                        backgroundColor: themeColors.background,
                        borderWidth: 2,
                        pointBackgroundColor: baseColor,
                        pointBorderColor: '#fff'
                    }, {
                        label: '目标值',
                        data: [85, 85, 85, 85, 95, 95],
                        borderColor: '#22c55e',
                        backgroundColor: 'transparent',
                        borderWidth: 1,
                        borderDash: [5, 5],
                        pointBackgroundColor: '#22c55e'
                    }]
                },
                options: {
                    ...this.getDefaultOptions(title, themeColors),
                    scales: {
                        r: {
                            angleLines: {
                                color: themeColors.grid
                            },
                            grid: {
                                color: themeColors.grid
                            },
                            pointLabels: {
                                color: themeColors.text
                            },
                            ticks: {
                                color: themeColors.text,
                                backdropColor: 'transparent'
                            }
                        }
                    }
                }
            }
        };
        
        return configs[type] || configs.line;
    }
    
    getDefaultOptions(title, themeColors) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 500,
                easing: 'easeInOutQuart'
            },
            plugins: {
                legend: {
                    display: true,
                    labels: {
                        color: themeColors.text,
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: true
                }
            },
            scales: {
                x: {
                    grid: {
                        color: themeColors.grid
                    },
                    ticks: {
                        color: themeColors.text
                    }
                },
                y: {
                    grid: {
                        color: themeColors.grid
                    },
                    ticks: {
                        color: themeColors.text
                    },
                    beginAtZero: true
                }
            }
        };
    }
    
    generateLabels() {
        const now = new Date();
        const labels = [];
        for (let i = 6; i >= 0; i--) {
            const time = new Date(now.getTime() - i * 60000);
            labels.push(time.toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' }));
        }
        return labels;
    }
    
    generateRandomData(count, min = 10, max = 100) {
        const data = [];
        for (let i = 0; i < count; i++) {
            data.push(Math.floor(Math.random() * (max - min + 1)) + min);
        }
        return data;
    }
    
    generateColorArray(count, baseColor) {
        const colors = [];
        const baseRgb = this.hexToRgb(baseColor);
        
        for (let i = 0; i < count; i++) {
            const alpha = 0.3 + (i / count) * 0.5;
            colors.push(`rgba(${baseRgb.r}, ${baseRgb.g}, ${baseRgb.b}, ${alpha})`);
        }
        return colors;
    }
    
    hexToRgb(hex) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : { r: 59, g: 130, b: 246 };
    }
    
    startDataUpdate(chartId) {
        const chartData = this.charts.get(chartId);
        if (!chartData) return;
        
        if (chartData.timer) {
            clearInterval(chartData.timer);
        }
        
        chartData.timer = setInterval(() => {
            this.updateChartData(chartId);
        }, chartData.interval);
    }
    
    updateChartData(chartId) {
        const chartData = this.charts.get(chartId);
        if (!chartData || !chartData.chart) return;
        
        const chart = chartData.chart;
        const type = chartData.type;
        
        if (type === 'line' || type === 'bar') {
            chart.data.labels.shift();
            const now = new Date();
            chart.data.labels.push(now.toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' }));
            
            chart.data.datasets.forEach((dataset) => {
                dataset.data.shift();
                dataset.data.push(Math.floor(Math.random() * 90) + 10);
            });
        } else if (type === 'pie') {
            chart.data.datasets[0].data = this.generateRandomData(5);
        } else if (type === 'radar') {
            chart.data.datasets[0].data = this.generateRandomData(6, 20, 90);
        }
        
        chart.update('default');
    }
    
    toggleTheme() {
        const themeToggle = document.getElementById('themeToggle');
        const themeLabel = document.getElementById('themeLabel');
        const body = document.body;
        
        if (themeToggle.checked) {
            body.classList.add('light-mode');
            this.theme = 'light';
            if (themeLabel) themeLabel.textContent = '浅色模式';
        } else {
            body.classList.remove('light-mode');
            this.theme = 'dark';
            if (themeLabel) themeLabel.textContent = '深色模式';
        }
        
        this.updateAllChartsTheme();
    }
    
    updateAllChartsTheme() {
        const themeColors = this.themeColors[this.theme];
        
        this.charts.forEach((chartData, chartId) => {
            if (chartData.chart) {
                const chart = chartData.chart;
                const type = chartData.type;
                
                if (chart.options.plugins.legend) {
                    chart.options.plugins.legend.labels.color = themeColors.text;
                }
                
                if (chart.options.scales) {
                    ['x', 'y'].forEach(scale => {
                        if (chart.options.scales[scale]) {
                            chart.options.scales[scale].grid.color = themeColors.grid;
                            chart.options.scales[scale].ticks.color = themeColors.text;
                        }
                    });
                    
                    if (chart.options.scales.r) {
                        chart.options.scales.r.angleLines.color = themeColors.grid;
                        chart.options.scales.r.grid.color = themeColors.grid;
                        chart.options.scales.r.pointLabels.color = themeColors.text;
                        chart.options.scales.r.ticks.color = themeColors.text;
                    }
                }
                
                chart.update('none');
            }
        });
    }
    
    insertDragPlaceholder(targetCard, e) {
        const container = document.getElementById('dashboardContainer');
        const rect = targetCard.getBoundingClientRect();
        const midY = rect.top + rect.height / 2;
        const midX = rect.left + rect.width / 2;
        
        let placeholder = container.querySelector('.drag-placeholder');
        
        if (!placeholder) {
            placeholder = document.createElement('div');
            placeholder.className = 'drag-placeholder';
            placeholder.style.width = targetCard.offsetWidth + 'px';
            placeholder.style.height = targetCard.offsetHeight + 'px';
        }
        
        const isAfter = e.clientY > midY || (e.clientY <= midY && e.clientX > midX);
        
        if (isAfter) {
            if (targetCard.nextSibling && targetCard.nextSibling.classList.contains('drag-placeholder')) {
                return;
            }
            targetCard.parentNode.insertBefore(placeholder, targetCard.nextSibling);
        } else {
            if (targetCard.previousSibling && targetCard.previousSibling.classList.contains('drag-placeholder')) {
                return;
            }
            targetCard.parentNode.insertBefore(placeholder, targetCard);
        }
    }
    
    handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }
    
    handleDrop(e) {
        e.preventDefault();
        
        if (!this.draggedChartCard) return;
        
        const placeholder = document.querySelector('.drag-placeholder');
        
        if (placeholder && placeholder.parentNode) {
            placeholder.parentNode.insertBefore(this.draggedChartCard, placeholder);
            placeholder.remove();
        }
        
        this.charts.forEach((chartData, chartId) => {
            if (chartData.chart) {
                chartData.chart.resize();
            }
        });
    }
    
    handleDragLeave(e) {
        if (!e.relatedTarget || !e.relatedTarget.closest('.dashboard-container')) {
            const placeholders = document.querySelectorAll('.drag-placeholder');
            placeholders.forEach(p => p.remove());
        }
    }
    
    startResize(e, chartCard) {
        this.isResizing = true;
        this.resizeElement = chartCard;
        this.resizeStartX = e.clientX;
        this.resizeStartY = e.clientY;
        this.resizeStartWidth = chartCard.offsetWidth;
        this.resizeStartHeight = chartCard.offsetHeight;
    }
    
    handleMouseMove(e) {
        if (this.isResizing && this.resizeElement) {
            const deltaX = e.clientX - this.resizeStartX;
            const deltaY = e.clientY - this.resizeStartY;
            
            const newWidth = Math.max(300, this.resizeStartWidth + deltaX);
            const newHeight = Math.max(250, this.resizeStartHeight + deltaY);
            
            this.resizeElement.style.width = `${newWidth}px`;
            this.resizeElement.style.height = `${newHeight}px`;
            
            const chartId = this.resizeElement.dataset.chartId;
            const chartData = this.charts.get(chartId);
            if (chartData && chartData.chart) {
                chartData.chart.resize();
            }
        }
    }
    
    handleMouseUp(e) {
        if (this.isResizing) {
            this.isResizing = false;
            this.resizeElement = null;
        }
    }
    
    openConfigPanel(chartCard) {
        this.currentEditingChart = chartCard;
        const chartId = chartCard.dataset.chartId;
        const chartData = this.charts.get(chartId);
        
        if (!chartData) return;
        
        const panel = document.getElementById('chartConfigPanel');
        const titleInput = document.getElementById('chartTitle');
        const typeSelect = document.getElementById('chartType');
        const intervalInput = document.getElementById('dataInterval');
        const colorInput = document.getElementById('chartColor');
        
        if (titleInput) titleInput.value = chartData.title;
        if (typeSelect) typeSelect.value = chartData.type;
        if (intervalInput) intervalInput.value = chartData.interval;
        if (colorInput) colorInput.value = chartData.color;
        
        panel.classList.add('active');
    }
    
    closeConfigPanel() {
        const panel = document.getElementById('chartConfigPanel');
        panel.classList.remove('active');
        this.currentEditingChart = null;
    }
    
    applyChartConfig() {
        if (!this.currentEditingChart) return;
        
        const chartId = this.currentEditingChart.dataset.chartId;
        const chartData = this.charts.get(chartId);
        
        if (!chartData) return;
        
        const titleInput = document.getElementById('chartTitle');
        const typeSelect = document.getElementById('chartType');
        const intervalInput = document.getElementById('dataInterval');
        const colorInput = document.getElementById('chartColor');
        
        const newTitle = titleInput ? titleInput.value : chartData.title;
        const newType = typeSelect ? typeSelect.value : chartData.type;
        const newInterval = intervalInput ? parseInt(intervalInput.value) : chartData.interval;
        const newColor = colorInput ? colorInput.value : chartData.color;
        
        const titleElement = this.currentEditingChart.querySelector('.chart-title');
        if (titleElement) titleElement.textContent = newTitle;
        
        if (newType !== chartData.type) {
            this.changeChartType(chartId, newType, newTitle);
        } else {
            this.updateChartColor(chartId, newColor);
        }
        
        chartData.title = newTitle;
        chartData.interval = newInterval;
        chartData.color = newColor;
        
        this.startDataUpdate(chartId);
        
        this.closeConfigPanel();
    }
    
    changeChartType(chartId, newType, title) {
        const chartData = this.charts.get(chartId);
        if (!chartData) return;
        
        if (chartData.chart) {
            chartData.chart.destroy();
        }
        
        chartData.type = newType;
        
        const canvas = document.getElementById(chartId);
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const themeColors = this.themeColors[this.theme];
        
        const chartConfig = this.generateChartConfig(newType, title, themeColors);
        chartData.chart = new Chart(ctx, chartConfig);
    }
    
    updateChartColor(chartId, newColor) {
        const chartData = this.charts.get(chartId);
        if (!chartData || !chartData.chart) return;
        
        const chart = chartData.chart;
        const rgb = this.hexToRgb(newColor);
        
        chart.data.datasets.forEach((dataset, index) => {
            if (dataset.borderColor) {
                dataset.borderColor = newColor;
            }
            if (dataset.backgroundColor && index === 0) {
                if (Array.isArray(dataset.backgroundColor)) {
                    dataset.backgroundColor = this.generateColorArray(dataset.backgroundColor.length, newColor);
                } else {
                    dataset.backgroundColor = `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.2)`;
                }
            }
            if (dataset.pointBackgroundColor) {
                dataset.pointBackgroundColor = newColor;
            }
        });
        
        chart.update('default');
    }
    
    deleteCurrentChart() {
        if (!this.currentEditingChart) return;
        
        const chartId = this.currentEditingChart.dataset.chartId;
        const chartData = this.charts.get(chartId);
        
        if (chartData) {
            if (chartData.timer) {
                clearInterval(chartData.timer);
            }
            if (chartData.chart) {
                chartData.chart.destroy();
            }
            this.charts.delete(chartId);
        }
        
        this.currentEditingChart.remove();
        this.closeConfigPanel();
        
        if (this.charts.size === 0) {
            this.showEmptyState();
        }
    }
    
    showEmptyState() {
        const container = document.getElementById('dashboardContainer');
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">📊</div>
                <h2>暂无图表</h2>
                <p>点击"添加图表"按钮创建新的数据可视化图表</p>
            </div>
        `;
    }
    
    async exportAsPng() {
        const container = document.getElementById('dashboardContainer');
        const chartCards = container.querySelectorAll('.chart-card');
        
        if (chartCards.length === 0) {
            alert('没有可导出的图表');
            return;
        }
        
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const scale = 2;
        const headerHeight = 80;
        const padding = 20;
        
        const containerRect = container.getBoundingClientRect();
        const totalWidth = Math.ceil(containerRect.width) + 2 * padding;
        const totalHeight = Math.ceil(containerRect.height) + headerHeight + 2 * padding;
        
        canvas.width = totalWidth * scale;
        canvas.height = totalHeight * scale;
        ctx.scale(scale, scale);
        
        ctx.fillStyle = this.theme === 'dark' ? '#1a1a2e' : '#f8fafc';
        ctx.fillRect(0, 0, totalWidth, totalHeight);
        
        ctx.fillStyle = this.theme === 'dark' ? '#16213e' : '#ffffff';
        ctx.fillRect(0, 0, totalWidth, headerHeight);
        
        ctx.strokeStyle = this.theme === 'dark' ? '#2a2a4a' : '#e2e8f0';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(0, headerHeight);
        ctx.lineTo(totalWidth, headerHeight);
        ctx.stroke();
        
        ctx.fillStyle = this.theme === 'dark' ? '#e0e0e0' : '#1e293b';
        ctx.font = 'bold 20px -apple-system, BlinkMacSystemFont, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('数据可视化仪表板', totalWidth / 2, 45);
        
        ctx.font = '12px -apple-system, BlinkMacSystemFont, sans-serif';
        ctx.fillStyle = this.theme === 'dark' ? '#a0a0a0' : '#64748b';
        ctx.fillText('导出时间: ' + new Date().toLocaleString('zh-CN'), totalWidth / 2, 65);
        
        const bgColor = this.theme === 'dark' ? '#0f0f23' : '#ffffff';
        const borderColor = this.theme === 'dark' ? '#2a2a4a' : '#e2e8f0';
        const textColor = this.theme === 'dark' ? '#e0e0e0' : '#1e293b';
        const titleColor = this.theme === 'dark' ? '#e0e0e0' : '#1e293b';
        
        chartCards.forEach((chartCard, index) => {
            const rect = chartCard.getBoundingClientRect();
            const containerRect = container.getBoundingClientRect();
            
            const x = rect.left - containerRect.left + padding;
            const y = rect.top - containerRect.top + headerHeight + padding;
            const width = rect.width;
            const height = rect.height;
            
            const chartId = chartCard.dataset.chartId;
            const chartData = this.charts.get(chartId);
            
            if (!chartData) return;
            
            ctx.fillStyle = bgColor;
            ctx.fillRect(x, y, width, height);
            
            ctx.strokeStyle = borderColor;
            ctx.lineWidth = 1;
            ctx.strokeRect(x, y, width, height);
            
            const headerHeightCard = 50;
            ctx.fillStyle = titleColor;
            ctx.font = 'bold 16px -apple-system, BlinkMacSystemFont, sans-serif';
            ctx.textAlign = 'left';
            ctx.fillText(chartData.title, x + 20, y + 30);
            
            const chartCanvas = chartData.chart.canvas;
            const chartContainer = chartCard.querySelector('.chart-container');
            const chartContainerRect = chartContainer.getBoundingClientRect();
            
            const chartX = x + (chartContainerRect.left - rect.left);
            const chartY = y + (chartContainerRect.top - rect.top);
            const chartWidth = chartContainerRect.width;
            const chartHeight = chartContainerRect.height;
            
            const scaleX = chartWidth / chartCanvas.width;
            const scaleY = chartHeight / chartCanvas.height;
            
            ctx.save();
            ctx.translate(chartX, chartY);
            ctx.scale(scaleX, scaleY);
            ctx.drawImage(chartCanvas, 0, 0);
            ctx.restore();
        });
        
        const link = document.createElement('a');
        link.download = `dashboard-${Date.now()}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
    }
    
    exportAsCsv() {
        if (this.charts.size === 0) {
            alert('没有可导出的数据');
            return;
        }
        
        let csvContent = '';
        const BOM = '\uFEFF';
        
        this.charts.forEach((chartData, chartId) => {
            const chart = chartData.chart;
            if (!chart || !chart.data) return;
            
            csvContent += `"${chartData.title}"\n`;
            csvContent += `"图表类型","${this.getChartTypeName(chartData.type)}"\n`;
            csvContent += `"更新时间","${new Date().toLocaleString('zh-CN')}"\n`;
            csvContent += '\n';
            
            if (chart.data.labels) {
                csvContent += `"标签",${chart.data.labels.map(l => `"${l}"`).join(',')}\n`;
            }
            
            chart.data.datasets.forEach((dataset, index) => {
                const label = dataset.label || `数据集 ${index + 1}`;
                const data = dataset.data.map(d => {
                    if (typeof d === 'number') {
                        return d.toLocaleString();
                    }
                    return `"${d}"`;
                });
                csvContent += `"${label}",${data.join(',')}\n`;
            });
            
            csvContent += '\n\n';
        });
        
        const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', `dashboard-data-${Date.now()}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    getChartTypeName(type) {
        const names = {
            line: '折线图',
            bar: '柱状图',
            pie: '饼图',
            radar: '雷达图'
        };
        return names[type] || type;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.dashboard = new DashboardManager();
});