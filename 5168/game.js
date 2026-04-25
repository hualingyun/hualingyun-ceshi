document.addEventListener('DOMContentLoaded', function() {
    // 获取元素
    const canvas = document.getElementById('game-canvas');
    const ctx = canvas.getContext('2d');
    const currentScoreEl = document.getElementById('current-score');
    const highScoreEl = document.getElementById('high-score');
    const gameInfoEl = document.getElementById('game-info');
    const gameOverEl = document.getElementById('game-over');
    const gamePausedEl = document.getElementById('game-paused');
    const finalScoreEl = document.getElementById('final-score');

    // 游戏配置
    const gridSize = 20;
    const gridCount = canvas.width / gridSize;

    // 游戏状态
    let snake = [];
    let food = {};
    let direction = { x: 0, y: 0 };
    let nextDirection = { x: 0, y: 0 };
    let score = 0;
    let highScore = 0;
    let gameSpeed = 150;
    let gameLoop = null;
    let isGameRunning = false;
    let isGamePaused = false;
    let isGameOver = false;

    // 初始化
    function init() {
        loadHighScore();
        resetGame();
        draw();
    }

    // 加载最高分
    function loadHighScore() {
        const saved = localStorage.getItem('snakeHighScore');
        highScore = saved ? parseInt(saved) : 0;
        highScoreEl.textContent = highScore;
    }

    // 保存最高分
    function saveHighScore() {
        if (score > highScore) {
            highScore = score;
            localStorage.setItem('snakeHighScore', highScore);
            highScoreEl.textContent = highScore;
        }
    }

    // 重置游戏
    function resetGame() {
        snake = [
            { x: 10, y: 10 },
            { x: 9, y: 10 },
            { x: 8, y: 10 }
        ];
        direction = { x: 1, y: 0 };
        nextDirection = { x: 1, y: 0 };
        score = 0;
        gameSpeed = 150;
        isGameRunning = false;
        isGamePaused = false;
        isGameOver = false;
        
        currentScoreEl.textContent = score;
        gameOverEl.style.display = 'none';
        gamePausedEl.style.display = 'none';
        gameInfoEl.style.display = 'block';
        
        generateFood();
    }

    // 生成食物
    function generateFood() {
        let newFood;
        do {
            newFood = {
                x: Math.floor(Math.random() * gridCount),
                y: Math.floor(Math.random() * gridCount)
            };
        } while (snake.some(segment => segment.x === newFood.x && segment.y === newFood.y));
        
        food = newFood;
    }

    // 开始游戏
    function startGame() {
        if (!isGameRunning && !isGameOver) {
            isGameRunning = true;
            gameInfoEl.style.display = 'none';
            gameLoop = setInterval(update, gameSpeed);
        }
    }

    // 暂停游戏
    function togglePause() {
        if (!isGameRunning || isGameOver) return;
        
        isGamePaused = !isGamePaused;
        gamePausedEl.style.display = isGamePaused ? 'block' : 'none';
        
        if (isGamePaused) {
            clearInterval(gameLoop);
        } else {
            gameLoop = setInterval(update, gameSpeed);
        }
    }

    // 更新游戏
    function update() {
        // 更新方向
        direction = nextDirection;
        
        // 移动蛇头
        const head = {
            x: snake[0].x + direction.x,
            y: snake[0].y + direction.y
        };
        
        // 检查碰撞
        if (checkCollision(head)) {
            gameOver();
            return;
        }
        
        // 移动蛇
        snake.unshift(head);
        
        // 检查是否吃到食物
        if (head.x === food.x && head.y === food.y) {
            eatFood();
        } else {
            snake.pop();
        }
        
        draw();
    }

    // 检查碰撞
    function checkCollision(head) {
        // 撞墙
        if (head.x < 0 || head.x >= gridCount || head.y < 0 || head.y >= gridCount) {
            return true;
        }
        
        // 撞身体
        for (let i = 0; i < snake.length; i++) {
            if (head.x === snake[i].x && head.y === snake[i].y) {
                return true;
            }
        }
        
        return false;
    }

    // 吃食物
    function eatFood() {
        score += 10;
        currentScoreEl.textContent = score;
        
        // 加速
        if (gameSpeed > 50) {
            gameSpeed -= 2;
            clearInterval(gameLoop);
            gameLoop = setInterval(update, gameSpeed);
        }
        
        generateFood();
    }

    // 游戏结束
    function gameOver() {
        clearInterval(gameLoop);
        isGameRunning = false;
        isGameOver = true;
        
        saveHighScore();
        finalScoreEl.textContent = score;
        gameOverEl.style.display = 'block';
    }

    // 绘制游戏
    function draw() {
        // 清空画布
        ctx.fillStyle = '#0a192f';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        // 绘制食物
        ctx.fillStyle = '#e94560';
        ctx.shadowColor = '#e94560';
        ctx.shadowBlur = 10;
        ctx.fillRect(
            food.x * gridSize + 2,
            food.y * gridSize + 2,
            gridSize - 4,
            gridSize - 4
        );
        ctx.shadowBlur = 0;
        
        // 绘制蛇
        snake.forEach((segment, index) => {
            // 蛇头颜色稍微深一点
            if (index === 0) {
                ctx.fillStyle = '#ffcc00';
                ctx.shadowColor = '#ffcc00';
                ctx.shadowBlur = 10;
            } else {
                ctx.fillStyle = '#ffd700';
                ctx.shadowColor = '#ffd700';
                ctx.shadowBlur = 5;
            }
            
            ctx.fillRect(
                segment.x * gridSize + 1,
                segment.y * gridSize + 1,
                gridSize - 2,
                gridSize - 2
            );
        });
        ctx.shadowBlur = 0;
    }

    // 键盘事件
    document.addEventListener('keydown', function(e) {
        // 方向键
        if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
            e.preventDefault();
            
            if (isGameOver) {
                resetGame();
                draw();
                return;
            }
            
            if (!isGameRunning) {
                startGame();
            }
            
            switch (e.key) {
                case 'ArrowUp':
                    if (direction.y !== 1) {
                        nextDirection = { x: 0, y: -1 };
                    }
                    break;
                case 'ArrowDown':
                    if (direction.y !== -1) {
                        nextDirection = { x: 0, y: 1 };
                    }
                    break;
                case 'ArrowLeft':
                    if (direction.x !== 1) {
                        nextDirection = { x: -1, y: 0 };
                    }
                    break;
                case 'ArrowRight':
                    if (direction.x !== -1) {
                        nextDirection = { x: 1, y: 0 };
                    }
                    break;
            }
        }
        
        // 空格键暂停
        if (e.key === ' ') {
            e.preventDefault();
            togglePause();
        }
    });

    // 初始化游戏
    init();
});
