class MemoryGame {
    constructor() {
        this.boardSize = 4;
        this.cards = [];
        this.flippedCards = [];
        this.matchedPairs = 0;
        this.totalPairs = 0;
        this.moves = 0;
        this.timer = null;
        this.seconds = 0;
        this.isPlaying = false;
        this.canFlip = true;
        
        this.emojis = ['🍎', '🍊', '🍋', '🍇', '🍓', '🍒', '🥝', '🍍', 
                       '🥭', '🍑', '🍐', '🍌', '🫐', '🍈', '🍉', '🥥',
                       '🌸', '🌺', '🌻', '🌼', '🌷', '🌹', '💐', '🍀',
                       '🦋', '🐝', '🐞', '🦀', '🐙', '🦑', '🐠', '🐬'];
        
        this.init();
    }
    
    init() {
        this.boardElement = document.getElementById('game-board');
        this.timeElement = document.getElementById('time');
        this.movesElement = document.getElementById('moves');
        this.victoryModal = document.getElementById('victory-modal');
        this.finalTimeElement = document.getElementById('final-time');
        this.finalMovesElement = document.getElementById('final-moves');
        this.ratingElement = document.getElementById('rating');
        
        this.bindEvents();
        this.startGame();
    }
    
    bindEvents() {
        document.querySelectorAll('.difficulty-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.changeDifficulty(parseInt(e.target.dataset.size));
            });
        });
        
        document.getElementById('restart-btn').addEventListener('click', () => {
            this.startGame();
        });
        
        document.getElementById('play-again-btn').addEventListener('click', () => {
            this.hideVictoryModal();
            this.startGame();
        });
    }
    
    changeDifficulty(size) {
        document.querySelectorAll('.difficulty-btn').forEach(btn => {
            btn.classList.remove('active');
            if (parseInt(btn.dataset.size) === size) {
                btn.classList.add('active');
            }
        });
        
        this.boardSize = size;
        this.startGame();
    }
    
    startGame() {
        this.resetGame();
        this.generateCards();
        this.renderBoard();
        this.startTimer();
    }
    
    resetGame() {
        this.cards = [];
        this.flippedCards = [];
        this.matchedPairs = 0;
        this.moves = 0;
        this.seconds = 0;
        this.isPlaying = false;
        this.canFlip = true;
        this.totalPairs = (this.boardSize * this.boardSize) / 2;
        
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
        
        this.updateMoves();
        this.updateTime();
    }
    
    generateCards() {
        const selectedEmojis = this.emojis.slice(0, this.totalPairs);
        const cardPairs = [...selectedEmojis, ...selectedEmojis];
        
        this.cards = this.shuffleArray(cardPairs).map((emoji, index) => ({
            id: index,
            emoji: emoji,
            isFlipped: false,
            isMatched: false
        }));
    }
    
    shuffleArray(array) {
        const shuffled = [...array];
        for (let i = shuffled.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
        }
        return shuffled;
    }
    
    renderBoard() {
        this.boardElement.innerHTML = '';
        this.boardElement.className = `game-board size-${this.boardSize}`;
        
        this.cards.forEach((card, index) => {
            const cardElement = document.createElement('div');
            cardElement.className = 'card';
            cardElement.dataset.index = index;
            
            cardElement.innerHTML = `
                <div class="card-back">?</div>
                <div class="card-front">${card.emoji}</div>
            `;
            
            cardElement.addEventListener('click', () => {
                this.flipCard(index);
            });
            
            this.boardElement.appendChild(cardElement);
        });
    }
    
    flipCard(index) {
        const card = this.cards[index];
        const cardElement = this.boardElement.children[index];
        
        if (!this.canFlip || card.isFlipped || card.isMatched || this.flippedCards.length >= 2) {
            return;
        }
        
        if (!this.isPlaying) {
            this.isPlaying = true;
        }
        
        card.isFlipped = true;
        cardElement.classList.add('flipped');
        this.flippedCards.push({ index, card, element: cardElement });
        
        if (this.flippedCards.length === 2) {
            this.moves++;
            this.updateMoves();
            this.checkMatch();
        }
    }
    
    checkMatch() {
        const [first, second] = this.flippedCards;
        
        if (first.card.emoji === second.card.emoji) {
            this.handleMatch(first, second);
        } else {
            this.handleMismatch(first, second);
        }
    }
    
    handleMatch(first, second) {
        first.card.isMatched = true;
        second.card.isMatched = true;
        this.matchedPairs++;
        
        first.element.classList.add('matched');
        second.element.classList.add('matched');
        
        this.flippedCards = [];
        
        if (this.matchedPairs === this.totalPairs) {
            this.endGame();
        }
    }
    
    handleMismatch(first, second) {
        this.canFlip = false;
        
        first.element.classList.add('wrong');
        second.element.classList.add('wrong');
        
        setTimeout(() => {
            first.card.isFlipped = false;
            second.card.isFlipped = false;
            
            first.element.classList.remove('flipped', 'wrong');
            second.element.classList.remove('flipped', 'wrong');
            
            this.flippedCards = [];
            this.canFlip = true;
        }, 1000);
    }
    
    updateMoves() {
        this.movesElement.textContent = this.moves;
    }
    
    startTimer() {
        if (this.timer) {
            clearInterval(this.timer);
        }
        
        this.timer = setInterval(() => {
            this.seconds++;
            this.updateTime();
        }, 1000);
    }
    
    updateTime() {
        const minutes = Math.floor(this.seconds / 60);
        const secs = this.seconds % 60;
        this.timeElement.textContent = `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }
    
    endGame() {
        clearInterval(this.timer);
        this.timer = null;
        this.isPlaying = false;
        
        const rating = this.calculateRating();
        this.showVictoryModal(rating);
    }
    
    calculateRating() {
        const optimalMoves = this.totalPairs;
        const moveRatio = this.moves / optimalMoves;
        
        const totalCells = this.boardSize * this.boardSize;
        const timePerCell = this.seconds / totalCells;
        
        let stars = 3;
        
        if (this.boardSize === 4) {
            if (moveRatio > 2.5 || this.seconds > 60) stars = 2;
            if (moveRatio > 4 || this.seconds > 120) stars = 1;
        } else if (this.boardSize === 6) {
            if (moveRatio > 2.5 || this.seconds > 180) stars = 2;
            if (moveRatio > 4 || this.seconds > 360) stars = 1;
        } else if (this.boardSize === 8) {
            if (moveRatio > 2.5 || this.seconds > 300) stars = 2;
            if (moveRatio > 4 || this.seconds > 600) stars = 1;
        }
        
        return '⭐'.repeat(stars);
    }
    
    showVictoryModal(rating) {
        this.finalTimeElement.textContent = this.timeElement.textContent;
        this.finalMovesElement.textContent = this.moves;
        this.ratingElement.textContent = rating;
        
        this.victoryModal.classList.remove('hidden');
    }
    
    hideVictoryModal() {
        this.victoryModal.classList.add('hidden');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new MemoryGame();
});
