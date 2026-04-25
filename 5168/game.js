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
    const obstacleCount = 5;

    // 游戏状态
    let snake = [];
    let food = {};
    let obstacles = [];
    let direction = { x: 0, y: 0 };
    let nextDirection = { x: 0, y: 0 };
    let score = 0;
    let highScore = 0;
    let gameSpeed = 150;
    let gameLoop = null;
    let isGameRunning = false;
    let isGamePaused = false;
    let isGameOver = false;
    let animationTime = 0;

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

    // 生成障碍物
    function generateObstacles() {
        obstacles = [];
        let attempts = 0;
        const maxAttempts = 1000;

        while (obstacles.length < obstacleCount && attempts < maxAttempts) {
            attempts++;
            const obstacle = {
                x: Math.floor(Math.random() * gridCount),
                y: Math.floor(Math.random() * gridCount)
            };

            const isOnSnake = snake.some(segment => 
                segment.x === obstacle.x && segment.y === obstacle.y
            );

            const isOnOtherObstacle = obstacles.some(obs => 
                obs.x === obstacle.x && obs.y === obstacle.y
            );

            const isNearCenter = Math.abs(obstacle.x - 10) < 3 && Math.abs(obstacle.y - 10) < 3;

            if (!isOnSnake && !isOnOtherObstacle && !isNearCenter) {
                obstacles.push(obstacle);
            }
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
        animationTime = 0;

        currentScoreEl.textContent = score;
        gameOverEl.style.display = 'none';
        gamePausedEl.style.display = 'none';
        gameInfoEl.style.display = 'block';

        generateObstacles();
        generateFood();
    }

    // 生成食物
    function generateFood() {
        let newFood;
        let attempts = 0;
        const maxAttempts = 1000;

        do {
            attempts++;
            newFood = {
                x: Math.floor(Math.random() * gridCount),
                y: Math.floor(Math.random() * gridCount)
            };

            const isOnSnake = snake.some(segment => 
                segment.x === newFood.x && segment.y === newFood.y
            );

            const isOnObstacle = obstacles.some(obs => 
                obs.x === newFood.x && obs.y === newFood.y
            );

            if (!isOnSnake && !isOnObstacle) {
                food = newFood;
                return;
            }
        } while (attempts < maxAttempts);
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
        animationTime++;

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

        // 撞障碍物
        for (let i = 0; i < obstacles.length; i++) {
            if (head.x === obstacles[i].x && head.y === obstacles[i].y) {
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

    // 绘制蛇（扭动效果）
    function drawSnake() {
        const wiggleAmplitude = 3;
        const wiggleFrequency = 0.3;

        for (let i = 0; i < snake.length; i++) {
            const segment = snake[i];
            const x = segment.x * gridSize;
            const y = segment.y * gridSize;

            let offsetX = 0;
            let offsetY = 0;

            if (i > 0) {
                const prevSegment = snake[i - 1];
                const nextSegment = i < snake.length - 1 ? snake[i + 1] : null;

                const dirX = direction.x;
                const dirY = direction.y;

                const wigglePhase = animationTime * wiggleFrequency - i * 0.5;
                const wiggle = Math.sin(wigglePhase) * wiggleAmplitude;

                if (dirX !== 0) {
                    offsetY = wiggle;
                } else {
                    offsetX = wiggle;
                }
            }

            const segmentSize = gridSize - 4;
            const cornerRadius = 6;

            if (i === 0) {
                ctx.fillStyle = '#ffcc00';
                ctx.shadowColor = '#ffcc00';
                ctx.shadowBlur = 15;

                const headX = x + 2 + offsetX;
                const headY = y + 2 + offsetY;

                ctx.beginPath();
                if (direction.x === 1) {
                    ctx.moveTo(headX + segmentSize, headY + segmentSize / 2);
                    ctx.quadraticCurveTo(headX + segmentSize + 3, headY + segmentSize / 2, headX + segmentSize, headY);
                    ctx.lineTo(headX, headY);
                    ctx.quadraticCurveTo(headX - 3, headY, headX, headY + segmentSize);
                    ctx.lineTo(headX + segmentSize, headY + segmentSize);
                } else if (direction.x === -1) {
                    ctx.moveTo(headX, headY + segmentSize / 2);
                    ctx.quadraticCurveTo(headX - 3, headY + segmentSize / 2, headX, headY);
                    ctx.lineTo(headX + segmentSize, headY);
                    ctx.quadraticCurveTo(headX + segmentSize + 3, headY, headX + segmentSize, headY + segmentSize);
                    ctx.lineTo(headX, headY + segmentSize);
                } else if (direction.y === -1) {
                    ctx.moveTo(headX + segmentSize / 2, headY);
                    ctx.quadraticCurveTo(headX + segmentSize / 2, headY - 3, headX + segmentSize, headY);
                    ctx.lineTo(headX + segmentSize, headY + segmentSize);
                    ctx.quadraticCurveTo(headX + segmentSize, headY + segmentSize + 3, headX, headY + segmentSize);
                    ctx.lineTo(headX, headY);
                } else {
                    ctx.moveTo(headX + segmentSize / 2, headY + segmentSize);
                    ctx.quadraticCurveTo(headX + segmentSize / 2, headY + segmentSize + 3, headX + segmentSize, headY + segmentSize);
                    ctx.lineTo(headX + segmentSize, headY);
                    ctx.quadraticCurveTo(headX + segmentSize, headY - 3, headX, headY);
                    ctx.lineTo(headX, headY + segmentSize);
                }
                ctx.closePath();
                ctx.fill();

                ctx.shadowBlur = 0;
                ctx.fillStyle = '#1a1a2e';
                const eyeOffset = 4;
                const eyeSize = 3;

                if (direction.x === 1) {
                    ctx.beginPath();
                    ctx.arc(headX + segmentSize - eyeOffset, headY + eyeOffset, eyeSize, 0, Math.PI * 2);
                    ctx.arc(headX + segmentSize - eyeOffset, headY + segmentSize - eyeOffset, eyeSize, 0, Math.PI * 2);
                    ctx.fill();
                } else if (direction.x === -1) {
                    ctx.beginPath();
                    ctx.arc(headX + eyeOffset, headY + eyeOffset, eyeSize, 0, Math.PI * 2);
                    ctx.arc(headX + eyeOffset, headY + segmentSize - eyeOffset, eyeSize, 0, Math.PI * 2);
                    ctx.fill();
                } else if (direction.y === -1) {
                    ctx.beginPath();
                    ctx.arc(headX + eyeOffset, headY + eyeOffset, eyeSize, 0, Math.PI * 2);
                    ctx.arc(headX + segmentSize - eyeOffset, headY + eyeOffset, eyeSize, 0, Math.PI * 2);
                    ctx.fill();
                } else {
                    ctx.beginPath();
                    ctx.arc(headX + eyeOffset, headY + segmentSize - eyeOffset, eyeSize, 0, Math.PI * 2);
                    ctx.arc(headX + segmentSize - eyeOffset, headY + segmentSize - eyeOffset, eyeSize, 0, Math.PI * 2);
                    ctx.fill();
                }
            } else {
                const alpha = 1 - (i / snake.length) * 0.6;
                ctx.fillStyle = `rgba(255, 215, 0, ${alpha})`;
                ctx.shadowColor = '#ffd700';
                ctx.shadowBlur = 8;

                const bodyX = x + 2 + offsetX;
                const bodyY = y + 2 + offsetY;
                const bodySize = segmentSize * (0.9 - (i / snake.length) * 0.2);
                const bodyOffset = (segmentSize - bodySize) / 2;

                ctx.beginPath();
                ctx.roundRect(
                    bodyX + bodyOffset,
                    bodyY + bodyOffset,
                    bodySize,
                    bodySize,
                    cornerRadius
                );
                ctx.fill();
            }
        }
        ctx.shadowBlur = 0;
    }

    // 绘制障碍物
    function drawObstacles() {
        ctx.fillStyle = '#6c757d';
        ctx.shadowColor = '#6c757d';
        ctx.shadowBlur = 5;

        obstacles.forEach(obstacle => {
            const x = obstacle.x * gridSize;
            const y = obstacle.y * gridSize;

            ctx.beginPath();
            ctx.moveTo(x + gridSize / 2, y + 2);
            ctx.lineTo(x + gridSize - 2, y + gridSize / 2);
            ctx.lineTo(x + gridSize / 2, y + gridSize - 2);
            ctx.lineTo(x + 2, y + gridSize / 2);
            ctx.closePath();
            ctx.fill();

            ctx.fillStyle = '#495057';
            ctx.beginPath();
            ctx.moveTo(x + gridSize / 2, y + 6);
            ctx.lineTo(x + gridSize - 6, y + gridSize / 2);
            ctx.lineTo(x + gridSize / 2, y + gridSize - 6);
            ctx.lineTo(x + 6, y + gridSize / 2);
            ctx.closePath();
            ctx.fill();

            ctx.fillStyle = '#6c757d';
        });
        ctx.shadowBlur = 0;
    }

    // 绘制游戏
    function draw() {
        // 清空画布
        ctx.fillStyle = '#0a192f';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // 绘制网格
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.05)';
        ctx.lineWidth = 1;
        for (let i = 0; i <= gridCount; i++) {
            ctx.beginPath();
            ctx.moveTo(i * gridSize, 0);
            ctx.lineTo(i * gridSize, canvas.height);
            ctx.stroke();

            ctx.beginPath();
            ctx.moveTo(0, i * gridSize);
            ctx.lineTo(canvas.width, i * gridSize);
            ctx.stroke();
        }

        // 绘制障碍物
        drawObstacles();

        // 绘制食物（闪烁效果）
        const pulse = Math.sin(animationTime * 0.2) * 0.3 + 0.7;
        ctx.fillStyle = `rgba(233, 69, 96, ${pulse})`;
        ctx.shadowColor = '#e94560';
        ctx.shadowBlur = 15 * pulse;

        const foodX = food.x * gridSize + gridSize / 2;
        const foodY = food.y * gridSize + gridSize / 2;
        const foodRadius = (gridSize / 2 - 2) * (0.8 + pulse * 0.2);

        ctx.beginPath();
        ctx.arc(foodX, foodY, foodRadius, 0, Math.PI * 2);
        ctx.fill();

        // 食物高光
        ctx.fillStyle = 'rgba(255, 255, 255, 0.3)';
        ctx.beginPath();
        ctx.arc(foodX - 2, foodY - 2, foodRadius / 3, 0, Math.PI * 2);
        ctx.fill();

        ctx.shadowBlur = 0;

        // 绘制蛇
        drawSnake();
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
