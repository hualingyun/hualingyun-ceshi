const templates = {
    responsive: {
        html: `<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>响应式布局示例</title>
</head>
<body>
    <header class="header">
        <h1>响应式网站</h1>
        <nav class="nav">
            <a href="#">首页</a>
            <a href="#">关于</a>
            <a href="#">服务</a>
            <a href="#">联系</a>
        </nav>
    </header>
    <main class="main">
        <section class="hero">
            <h2>欢迎访问我们的网站</h2>
            <p>这是一个响应式布局示例，调整浏览器窗口大小查看效果。</p>
        </section>
        <div class="cards">
            <div class="card">
                <h3>功能一</h3>
                <p>简洁优雅的设计，适配各种设备屏幕。</p>
            </div>
            <div class="card">
                <h3>功能二</h3>
                <p>响应式布局，在手机和平板上都有完美体验。</p>
            </div>
            <div class="card">
                <h3>功能三</h3>
                <p>现代 CSS 技术，Flexbox 和 Grid 布局。</p>
            </div>
        </div>
    </main>
    <footer class="footer">
        <p>&copy; 2024 响应式网站示例</p>
    </footer>
</body>
</html>`,
        css: `* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    line-height: 1.6;
}

.header {
    background: #333;
    color: #fff;
    padding: 1rem;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
}

.nav {
    display: flex;
    gap: 20px;
}

.nav a {
    color: #fff;
    text-decoration: none;
}

.hero {
    background: #f4f4f4;
    padding: 2rem;
    text-align: center;
}

.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    padding: 2rem;
}

.card {
    background: #fff;
    padding: 1.5rem;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.footer {
    background: #333;
    color: #fff;
    text-align: center;
    padding: 1rem;
}

@media (max-width: 768px) {
    .header {
        flex-direction: column;
        text-align: center;
    }
    
    .nav {
        margin-top: 1rem;
    }
}`,
        js: `console.log('响应式布局示例已加载');
console.log('尝试调整浏览器窗口大小来查看响应式效果');`
    },
    todo: {
        html: `<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todo List</title>
</head>
<body>
    <div class="container">
        <h1>待办事项</h1>
        <div class="input-group">
            <input type="text" id="taskInput" placeholder="添加新任务...">
            <button id="addBtn">添加</button>
        </div>
        <div id="message" class="message"></div>
        <ul id="taskList"></ul>
    </div>
</body>
</html>`,
        css: `* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.container {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    width: 100%;
    max-width: 400px;
}

h1 {
    text-align: center;
    color: #333;
    margin-bottom: 1.5rem;
}

.input-group {
    display: flex;
    gap: 10px;
    margin-bottom: 1rem;
}

input {
    flex: 1;
    padding: 10px;
    border: 2px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
}

button {
    padding: 10px 20px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

button:hover {
    background: #5a67d8;
}

ul {
    list-style: none;
}

li {
    padding: 12px;
    background: #f9f9f9;
    margin-bottom: 8px;
    border-radius: 5px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
}

li.completed {
    text-decoration: line-through;
    opacity: 0.6;
}

.delete-btn {
    background: #e53e3e;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 3px;
    cursor: pointer;
}

.message {
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 5px;
    text-align: center;
    display: none;
}

.message.show {
    display: block;
}

.message.error {
    background: #fff3f3;
    color: #e53e3e;
    border: 1px solid #feb2b2;
}

.message.success {
    background: #f0fff4;
    color: #38a169;
    border: 1px solid #9ae6b4;
}`,
        js: `const taskInput = document.getElementById('taskInput');
const addBtn = document.getElementById('addBtn');
const taskList = document.getElementById('taskList');
const messageEl = document.getElementById('message');

function showMessage(msg, type) {
    messageEl.textContent = msg;
    messageEl.className = 'message show ' + type;
    console[type === 'error' ? 'warn' : 'log'](msg);
    setTimeout(function() {
        messageEl.className = 'message';
    }, 2000);
}

function addTask() {
    const text = taskInput.value.trim();
    if (text === '') {
        showMessage('请输入任务内容', 'error');
        return;
    }
    
    const li = document.createElement('li');
    li.innerHTML = '<span>' + text + '</span>' +
        '<button class="delete-btn">删除</button>';
    
    li.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-btn')) {
            li.remove();
            console.log('任务已删除:', text);
        } else {
            li.classList.toggle('completed');
            const status = li.classList.contains('completed') ? '完成' : '未完成';
            console.log('任务状态更新:', text, '-', status);
        }
    });
    
    taskList.appendChild(li);
    taskInput.value = '';
    showMessage('任务添加成功', 'success');
}

addBtn.addEventListener('click', addTask);

taskInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        addTask();
    }
});

console.log('Todo List 应用已加载');
console.log('请输入任务并点击添加按钮开始使用');`
    },
    carousel: {
        html: `<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>轮播图示例</title>
</head>
<body>
    <div class="carousel">
        <div class="slides">
            <div class="slide active">
                <img src="https://picsum.photos/800/400?random=1" alt="图片1">
                <div class="caption">第一张幻灯片</div>
            </div>
            <div class="slide">
                <img src="https://picsum.photos/800/400?random=2" alt="图片2">
                <div class="caption">第二张幻灯片</div>
            </div>
            <div class="slide">
                <img src="https://picsum.photos/800/400?random=3" alt="图片3">
                <div class="caption">第三张幻灯片</div>
            </div>
            <div class="slide">
                <img src="https://picsum.photos/800/400?random=4" alt="图片4">
                <div class="caption">第四张幻灯片</div>
            </div>
        </div>
        <button class="prev">&#10094;</button>
        <button class="next">&#10095;</button>
        <div class="dots"></div>
    </div>
</body>
</html>`,
        css: `* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background: #f0f0f0;
}

.carousel {
    position: relative;
    width: 800px;
    max-width: 95%;
    height: 400px;
    overflow: hidden;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.slides {
    position: relative;
    width: 100%;
    height: 100%;
}

.slide {
    position: absolute;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 0.5s ease;
}

.slide.active {
    opacity: 1;
}

.slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.caption {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0, 0, 0, 0.6);
    color: white;
    padding: 20px;
    text-align: center;
    font-size: 18px;
}

.prev, .next {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0, 0, 0, 0.5);
    color: white;
    border: none;
    padding: 15px 20px;
    cursor: pointer;
    font-size: 24px;
    border-radius: 50%;
}

.prev:hover, .next:hover {
    background: rgba(0, 0, 0, 0.8);
}

.prev {
    left: 10px;
}

.next {
    right: 10px;
}

.dots {
    position: absolute;
    bottom: 60px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 10px;
}

.dot {
    width: 12px;
    height: 12px;
    background: rgba(255, 255, 255, 0.5);
    border-radius: 50%;
    cursor: pointer;
    transition: background 0.3s;
}

.dot.active {
    background: white;
}`,
        js: `const slides = document.querySelectorAll('.slide');
const dotsContainer = document.querySelector('.dots');
const prevBtn = document.querySelector('.prev');
const nextBtn = document.querySelector('.next');

let currentIndex = 0;
let intervalId;

slides.forEach((_, index) => {
    const dot = document.createElement('div');
    dot.classList.add('dot');
    if (index === 0) dot.classList.add('active');
    dot.addEventListener('click', () => goToSlide(index));
    dotsContainer.appendChild(dot);
});

const dots = document.querySelectorAll('.dot');

function updateSlider() {
    slides.forEach((slide, index) => {
        slide.classList.toggle('active', index === currentIndex);
    });
    dots.forEach((dot, index) => {
        dot.classList.toggle('active', index === currentIndex);
    });
    console.log('当前显示第', currentIndex + 1, '张幻灯片');
}

function nextSlide() {
    currentIndex = (currentIndex + 1) % slides.length;
    updateSlider();
}

function prevSlide() {
    currentIndex = (currentIndex - 1 + slides.length) % slides.length;
    updateSlider();
}

function goToSlide(index) {
    currentIndex = index;
    updateSlider();
    resetInterval();
}

function startAutoPlay() {
    intervalId = setInterval(nextSlide, 3000);
}

function resetInterval() {
    clearInterval(intervalId);
    startAutoPlay();
}

nextBtn.addEventListener('click', () => {
    nextSlide();
    resetInterval();
});

prevBtn.addEventListener('click', () => {
    prevSlide();
    resetInterval();
});

startAutoPlay();
console.log('轮播图已启动，自动切换时间间隔: 3秒');
console.log('点击左右箭头或下方指示器可以手动切换');`
    }
};
