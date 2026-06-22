<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>CTRX TETRIS by CodeYro</title>
    <style>
        * {
            box-sizing: border-box;
            user-select: none;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
        }

        #codeyroid {
            cursor: pointer;
        }

        #codeyroid:hover {
            color: blue;
        }

        body {
            background: linear-gradient(145deg, #0f172a, #1e293b);
            min-height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', system-ui, monospace;
            touch-action: none;
        }

        .game-wrapper {
            background: #0b1120;
            padding: 1.2rem 1.5rem 1.5rem 1.5rem;
            border-radius: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.7), inset 0 0 0 1px #4b5b7a;
            max-width: 100%;
        }

        .title {
            text-align: center;
            color: #d6e3ff;
            font-size: 1.2rem;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 1rem;
            text-shadow: 0 0 20px #3f6bb0;
        }

        .title small {
            color: #8196c0;
            font-size: 0.7rem;
            font-weight: 400;
        }

        .container {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
            align-items: flex-start;
        }

        .board-section {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        canvas#tetrisCanvas {
            display: block;
            background: #0d162b;
            border-radius: 1.5rem;
            box-shadow: 0 0 0 2px #2d3b55, inset 0 0 0 2px #1a263b;
            width: 300px;
            height: 580px;
        }

        .info-panel {
            background: #111d2f;
            border-radius: 1.5rem;
            padding: 1rem 1.2rem;
            min-width: 160px;
            box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.6), 0 8px 16px rgba(0, 0, 0, 0.5);
            border: 1px solid #2f405f;
            color: #d6e3ff;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .info-item {
            background: #0a1424;
            padding: 0.4rem 0.8rem;
            border-radius: 2rem;
            text-align: center;
            border: 1px solid #34486b;
            box-shadow: inset 0 2px 3px #00000055;
        }

        .info-item span {
            display: block;
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: #b9d0ff;
            text-shadow: 0 0 8px #3f6bb0;
        }

        .info-item small {
            font-size: 0.65rem;
            text-transform: uppercase;
            opacity: 0.7;
            letter-spacing: 1px;
        }

        .combo-badge {
            background: #1f2f4a;
            border-radius: 2rem;
            padding: 0.2rem 0;
            border: 1px solid #facc15;
            color: #fde047;
            font-weight: bold;
            text-shadow: 0 0 10px #fbbf24;
        }

        .high-score-item {
            border-color: #f59e0b;
        }

        .high-score-item span {
            color: #fbbf24;
            font-size: 1.2rem;
        }

        .hold-item {
            border-color: #60a5fa;
            min-height: 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .hold-item canvas {
            background: #0a1424;
            border-radius: 0.5rem;
            margin-top: 0.2rem;
            width: 60px;
            height: 45px;
        }

        .next-item {
            border-color: #a78bfa;
            min-height: 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .next-item canvas {
            background: #0a1424;
            border-radius: 0.5rem;
            margin-top: 0.2rem;
            width: 60px;
            height: 45px;
        }

        .speed-item {
            border-color: #34d399;
        }

        .speed-item span {
            color: #34d399;
            font-size: 1rem;
        }

        .controls {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            margin-top: 0.2rem;
        }

        .btn {
            background: #1f314b;
            border: none;
            border-radius: 2rem;
            padding: 0.5rem 0;
            font-weight: 700;
            font-size: 0.9rem;
            color: #d1e0ff;
            border-bottom: 3px solid #0b1422;
            cursor: pointer;
            transition: 0.1s ease;
            box-shadow: 0 4px 0 #0a111f;
        }

        .btn:active {
            transform: translateY(3px);
            box-shadow: 0 1px 0 #0a111f;
        }

        .btn-primary {
            background: #3b5b8a;
            color: white;
            border-bottom-color: #1b2d4a;
        }

        .key-hint {
            display: flex;
            justify-content: center;
            gap: 0.3rem;
            flex-wrap: wrap;
            color: #8196c0;
            font-size: 0.6rem;
            margin-top: 0.2rem;
        }

        .key-hint kbd {
            background: #0f1c30;
            padding: 0.1rem 0.5rem;
            border-radius: 0.5rem;
            border: 1px solid #384b6b;
            color: #c2d6ff;
        }

        .footer {
            color: #556b90;
            font-size: 0.6rem;
            text-align: center;
            margin-top: 0.3rem;
        }

        .game-over-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            border-radius: 1.5rem;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            flex-direction: column;
            gap: 0.5rem;
        }

        .canvas-wrapper {
            position: relative;
        }

        .mobile-controls {
            display: none;
            gap: 0.5rem;
            margin-top: 0.8rem;
            justify-content: center;
            touch-action: none;
        }

        .ctrl-btn {
            background: #1f314b;
            border: none;
            border-radius: 1rem;
            padding: 0.8rem 1rem;
            font-size: 1.8rem;
            color: #d1e0ff;
            border-bottom: 3px solid #0b1422;
            box-shadow: 0 4px 0 #0a111f;
            cursor: pointer;
            touch-action: none;
            min-width: 85px;
            min-height: 85px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.05s ease;
        }

        .ctrl-btn:active {
            transform: translateY(3px);
            box-shadow: 0 1px 0 #0a111f;
        }

        .ctrl-btn.wide {
            min-width: 70px;
        }

        .ctrl-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.4rem;
            max-width: 280px;
        }

        .ctrl-grid .ctrl-btn {
            min-width: 50px;
            min-height: 50px;
            font-size: 1.5rem;
        }

        @media (max-width: 768px) {
            #highScoreDisplay {
                font-size: 8px;
            }

            .game-wrapper {
                padding: 0.8rem 0.8rem 1rem 0.8rem;
                border-radius: 1.5rem;
            }

            .container {
                flex-direction: column;
                align-items: center;
                gap: 0.8rem;
            }

            .info-panel {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
                min-width: unset;
                padding: 0.6rem 0.8rem;
                gap: 0.4rem;
                border-radius: 1rem;
            }

            .info-item {
                padding: 0.2rem 0.5rem;
                min-width: 60px;
                flex: 1;
            }

            .info-item span {
                font-size: 1rem;
            }

            .info-item small {
                font-size: 0.5rem;
            }

            .hold-item,
            .next-item {
                min-height: 40px;
            }

            .hold-item canvas,
            .next-item canvas {
                width: 40px;
                height: 30px;
            }

            .controls {
                flex: 1;
                min-width: 100px;
            }

            canvas#tetrisCanvas {
                width: 350px;
                height: 400px;
            }

            .mobile-controls {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
            }

            .ctrl-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 0.9rem;
                max-width: 240px;
            }

            .ctrl-grid .ctrl-btn {
                min-width: 62px;
                min-height: 62px;
                font-size: 1.2rem;
                padding: 0.4rem;
            }

            .title {
                font-size: 1rem;
                margin-bottom: 0.5rem;
            }

            .key-hint {
                display: none;
            }

            .footer {
                font-size: 0.5rem;
            }
        }

        @media (max-width: 480px) {
            canvas#tetrisCanvas {
                width: 230px;
                height: 380px;
            }

            .ctrl-grid {
                max-width: 200px;
            }

            .ctrl-grid .ctrl-btn {
                min-width: 56px;
                min-height: 56px;
                font-size: 1rem;
                padding: 0.3rem;
            }

            .info-item {
                padding: 0.15rem 0.3rem;
                min-width: 45px;
            }

            .info-item span {
                font-size: 0.8rem;
            }

            .info-item small {
                font-size: 0.4rem;
            }
        }
    </style>
    <?php if(! defined("mainpath")){
        die("You can't play CTRX game");
    } ?>
    
</head>

<body>
    <div class="game-wrapper">
        <div class="title">CTRX TETRIS <small id="codeyroid">by CodeYro</small></div>
        <div class="container">
            <div class="board-section">
                <div class="canvas-wrapper">
                    <canvas id="tetrisCanvas" width="300" height="600"></canvas>
                    <div class="game-over-overlay" id="gameOverOverlay">
                        <div>GAME OVER</div>
                        <div style="font-size:1rem; opacity:0.8;">press New Game</div>
                    </div>
                </div>
                <!-- Mobile Controls -->
                <div class="mobile-controls" id="mobileControls">
                    <div class="ctrl-grid">
                        <button class="ctrl-btn" id="ctrlLeft">◀</button>
                        <button class="ctrl-btn" id="ctrlRotate">↻</button>
                        <button class="ctrl-btn" id="ctrlRight">▶</button>
                        <button class="ctrl-btn" id="ctrlDrop">⬇⬇</button>
                        <button class="ctrl-btn" id="ctrlHold">⏸</button>
                        <button class="ctrl-btn" id="ctrlDown">▼</button>
                        <button class="ctrl-btn" id="ctrlRestart">↻</button>
                        <button class="ctrl-btn" id="ctrlUp">▲</button>
                    </div>
                </div>
            </div>

            <div class="info-panel">
                <div class="info-item high-score-item">
                    <small>🏆 highest</small>
                    <span id="highScoreDisplay">none</span>
                </div>
                <div class="info-item">
                    <small>⏱️ time</small>
                    <span id="timeDisplay">0</span>
                </div>
                <div class="info-item">
                    <small>🏆 score</small>
                    <span id="scoreDisplay">0</span>
                </div>
                <div class="info-item combo-badge">
                    <small>💥 combo</small>
                    <span id="comboDisplay">0</span>
                </div>
                <div class="info-item speed-item">
                    <small>⚡ speed</small>
                    <span id="speedDisplay">x1.0</span>
                </div>
                <div class="info-item hold-item">
                    <small>hold</small>
                    <canvas id="holdCanvas" width="80" height="60"></canvas>
                </div>
                <div class="info-item next-item">
                    <small>next</small>
                    <canvas id="nextCanvas" width="80" height="60"></canvas>
                </div>
                <div class="controls">
                    <button class="btn btn-primary" id="restartBtn">↻ new game</button>
                    <div class="key-hint">
                        <kbd>←</kbd> <kbd>→</kbd> <kbd>↓</kbd> <kbd>↑</kbd> rotate <kbd>Shift</kbd> hold <kbd>Space</kbd> drop
                    </div>
                </div>
                <div class="footer">
                    Classic Tetris · speed +0.1/min
                    <div>CodeYro June 17 2026 - YRO</div>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function() {
            const COLS = 10,
                ROWS = 20;
            const BLOCK_SIZE = 30;

            const SHAPES = [{
                    matrix: [
                        [1, 1, 1, 1]
                    ],
                    color: '#3cc7f2'
                },
                {
                    matrix: [
                        [1, 1],
                        [1, 1]
                    ],
                    color: '#f2e84a'
                },
                {
                    matrix: [
                        [0, 1, 0],
                        [1, 1, 1]
                    ],
                    color: '#b484e0'
                },
                {
                    matrix: [
                        [0, 1, 1],
                        [1, 1, 0]
                    ],
                    color: '#5fdb6f'
                },
                {
                    matrix: [
                        [1, 1, 0],
                        [0, 1, 1]
                    ],
                    color: '#f25a6b'
                },
                {
                    matrix: [
                        [1, 0, 0],
                        [1, 1, 1]
                    ],
                    color: '#f2a23b'
                },
                {
                    matrix: [
                        [0, 0, 1],
                        [1, 1, 1]
                    ],
                    color: '#4f8cf7'
                }
            ];

            let board = Array.from({
                length: ROWS
            }, () => Array(COLS).fill(0));
            let currentPiece = null;
            let nextPiece = null;
            let gameOver = false;
            let score = 0;
            let timeElapsed = 0;
            let timerInterval = null;
            let gameLoopInterval = null;
            let dropInterval = 500;
            let baseDropInterval = 500;
            let speedLevel = 1.0;

            let comboCount = 0;

            let heldPiece = null;
            let canHold = true;

            let highScore = localStorage.getItem('tetrisHighScore') ? parseInt(localStorage.getItem('tetrisHighScore')) : 0;

            const canvas = document.getElementById('tetrisCanvas');
            const ctx = canvas.getContext('2d');
            const holdCanvas = document.getElementById('holdCanvas');
            const holdCtx = holdCanvas.getContext('2d');
            const nextCanvas = document.getElementById('nextCanvas');
            const nextCtx = nextCanvas.getContext('2d');
            const scoreDisplay = document.getElementById('scoreDisplay');
            const timeDisplay = document.getElementById('timeDisplay');
            const comboDisplay = document.getElementById('comboDisplay');
            const highScoreDisplay = document.getElementById('highScoreDisplay');
            const speedDisplay = document.getElementById('speedDisplay');
            const gameOverOverlay = document.getElementById('gameOverOverlay');

            document.querySelector("#codeyroid").addEventListener("click", () => {
                window.open('https://www.tiktok.com/@codebasixs', '_blank');
            });

            function randomPiece() {
                const idx = Math.floor(Math.random() * SHAPES.length);
                const shape = SHAPES[idx];
                return {
                    matrix: shape.matrix.map(row => [...row]),
                    color: shape.color,
                    row: 0,
                    col: Math.floor((COLS - shape.matrix[0].length) / 2)
                };
            }

            function getDropPosition() {
                if (!currentPiece) return null;
                let row = currentPiece.row;
                while (!collide(currentPiece.matrix, row + 1, currentPiece.col)) {
                    row++;
                }
                return row;
            }

            function collide(matrix, row, col) {
                for (let r = 0; r < matrix.length; r++) {
                    for (let c = 0; c < matrix[0].length; c++) {
                        if (matrix[r][c] !== 0) {
                            const boardR = row + r;
                            const boardC = col + c;
                            if (boardR >= ROWS || boardC < 0 || boardC >= COLS || boardR < 0) return true;
                            if (boardR >= 0 && board[boardR][boardC] !== 0) return true;
                        }
                    }
                }
                return false;
            }

            function mergePiece() {
                if (!currentPiece) return;
                const {
                    matrix,
                    row,
                    col,
                    color
                } = currentPiece;
                for (let r = 0; r < matrix.length; r++) {
                    for (let c = 0; c < matrix[0].length; c++) {
                        if (matrix[r][c] !== 0) {
                            const boardR = row + r;
                            const boardC = col + c;
                            if (boardR >= 0 && boardR < ROWS && boardC >= 0 && boardC < COLS) {
                                board[boardR][boardC] = color;
                            }
                        }
                    }
                }
            }

            function clearLines() {
                let cleared = 0;
                for (let r = ROWS - 1; r >= 0;) {
                    let full = true;
                    for (let c = 0; c < COLS; c++) {
                        if (board[r][c] === 0) {
                            full = false;
                            break;
                        }
                    }
                    if (full) {
                        for (let r2 = r; r2 > 0; r2--) {
                            board[r2] = [...board[r2 - 1]];
                        }
                        board[0] = Array(COLS).fill(0);
                        cleared++;
                    } else {
                        r--;
                    }
                }
                return cleared;
            }

            function spawnPiece() {
                if (nextPiece === null) {
                    nextPiece = randomPiece();
                }
                const piece = {
                    matrix: nextPiece.matrix.map(row => [...row]),
                    color: nextPiece.color,
                    row: 0,
                    col: Math.floor((COLS - nextPiece.matrix[0].length) / 2)
                };

                if (collide(piece.matrix, piece.row, piece.col)) {
                    return false;
                }
                currentPiece = piece;
                nextPiece = randomPiece();
                canHold = true;
                drawNext();
                return true;
            }

            function holdPiece() {
                if (!canHold || !currentPiece || gameOver) return;

                if (heldPiece === null) {
                    heldPiece = {
                        matrix: currentPiece.matrix.map(row => [...row]),
                        color: currentPiece.color
                    };
                    const success = spawnPiece();
                    if (!success) {
                        gameOver = true;
                        stopGameLoop();
                        showGameOver();
                    }
                } else {
                    const temp = {
                        matrix: currentPiece.matrix.map(row => [...row]),
                        color: currentPiece.color
                    };
                    currentPiece.matrix = heldPiece.matrix.map(row => [...row]);
                    currentPiece.color = heldPiece.color;
                    currentPiece.row = 0;
                    currentPiece.col = Math.floor((COLS - currentPiece.matrix[0].length) / 2);
                    heldPiece = temp;

                    if (collide(currentPiece.matrix, currentPiece.row, currentPiece.col)) {
                        gameOver = true;
                        stopGameLoop();
                        showGameOver();
                    }
                }
                canHold = false;
                drawHold();
                drawBoard();
            }

            function drawHold() {
                holdCtx.clearRect(0, 0, 80, 60);
                if (heldPiece) {
                    const matrix = heldPiece.matrix;
                    const color = heldPiece.color;
                    const blockSize = 20;
                    const cols = matrix[0].length;
                    const rows = matrix.length;
                    const offsetX = (80 - cols * blockSize) / 2;
                    const offsetY = (60 - rows * blockSize) / 2;

                    for (let r = 0; r < rows; r++) {
                        for (let c = 0; c < cols; c++) {
                            if (matrix[r][c] !== 0) {
                                holdCtx.fillStyle = color;
                                holdCtx.fillRect(offsetX + c * blockSize, offsetY + r * blockSize, blockSize - 1, blockSize - 1);
                            }
                        }
                    }
                }
            }

            function drawNext() {
                nextCtx.clearRect(0, 0, 80, 60);
                if (nextPiece) {
                    const matrix = nextPiece.matrix;
                    const color = nextPiece.color;
                    const blockSize = 20;
                    const cols = matrix[0].length;
                    const rows = matrix.length;
                    const offsetX = (80 - cols * blockSize) / 2;
                    const offsetY = (60 - rows * blockSize) / 2;

                    for (let r = 0; r < rows; r++) {
                        for (let c = 0; c < cols; c++) {
                            if (matrix[r][c] !== 0) {
                                nextCtx.fillStyle = color;
                                nextCtx.fillRect(offsetX + c * blockSize, offsetY + r * blockSize, blockSize - 1, blockSize - 1);
                            }
                        }
                    }
                }
            }

            function updateDropSpeed() {
                const speedIncrement = Math.floor(timeElapsed / 60) * 0.1;
                speedLevel = 1.0 + speedIncrement;
                if (speedLevel > 10) speedLevel = 10;

                dropInterval = baseDropInterval / speedLevel;
                speedDisplay.textContent = `x${speedLevel.toFixed(1)}`;

                if (gameLoopInterval) {
                    clearInterval(gameLoopInterval);
                    gameLoopInterval = setInterval(() => {
                        if (!gameOver) {
                            gameTick();
                        }
                    }, dropInterval);
                }
            }

            function hardDrop() {
                if (!currentPiece || gameOver) return;
                const dropRow = getDropPosition();
                if (dropRow !== null) {
                    currentPiece.row = dropRow;
                    mergePiece();
                    const cleared = clearLines();

                    if (cleared > 0) {
                        comboCount += cleared;
                        const basePoints = [0, 100, 300, 600, 1000];
                        const addScore = basePoints[Math.min(cleared, 4)] || 0;
                        score += addScore;
                        updateScoreDisplay();
                        updateComboDisplay();
                    } else {
                        comboCount = 0;
                        updateComboDisplay();
                    }

                    const success = spawnPiece();
                    if (!success) {
                        gameOver = true;
                        stopGameLoop();
                        showGameOver();
                    }
                    drawBoard();
                }
            }

            function gameTick() {
                if (gameOver || !currentPiece) return;

                const {
                    matrix,
                    row,
                    col
                } = currentPiece;
                if (!collide(matrix, row + 1, col)) {
                    currentPiece.row++;
                } else {
                    mergePiece();
                    const cleared = clearLines();

                    if (cleared > 0) {
                        comboCount += cleared;
                        const basePoints = [0, 100, 300, 600, 1000];
                        const addScore = basePoints[Math.min(cleared, 4)] || 0;
                        score += addScore;
                        updateScoreDisplay();
                        updateComboDisplay();
                    } else {
                        comboCount = 0;
                        updateComboDisplay();
                    }

                    const success = spawnPiece();
                    if (!success) {
                        gameOver = true;
                        stopGameLoop();
                        showGameOver();
                    }
                }
                drawBoard();
            }

            function showGameOver() {
                gameOverOverlay.style.display = 'flex';
                if (score > highScore) {
                    highScore = score;
                    localStorage.setItem('tetrisHighScore', highScore.toString());
                    updateHighScoreDisplay();
                }
            }

            function drawBoard() {
                ctx.clearRect(0, 0, 300, 600);
                for (let r = 0; r < ROWS; r++) {
                    for (let c = 0; c < COLS; c++) {
                        const color = board[r][c];
                        if (color !== 0) {
                            ctx.fillStyle = color;
                            ctx.fillRect(c * BLOCK_SIZE, r * BLOCK_SIZE, BLOCK_SIZE - 1, BLOCK_SIZE - 1);
                            ctx.fillStyle = 'rgba(255,255,255,0.2)';
                            ctx.fillRect(c * BLOCK_SIZE, r * BLOCK_SIZE, BLOCK_SIZE - 2, 2);
                        } else {
                            ctx.strokeStyle = '#1d2b44';
                            ctx.lineWidth = 0.5;
                            ctx.strokeRect(c * BLOCK_SIZE, r * BLOCK_SIZE, BLOCK_SIZE, BLOCK_SIZE);
                        }
                    }
                }

                if (currentPiece && !gameOver) {
                    const dropRow = getDropPosition();
                    if (dropRow !== null) {
                        const {
                            matrix,
                            col
                        } = currentPiece;
                        for (let r = 0; r < matrix.length; r++) {
                            for (let c = 0; c < matrix[0].length; c++) {
                                if (matrix[r][c] !== 0) {
                                    const x = (col + c) * BLOCK_SIZE;
                                    const y = (dropRow + r) * BLOCK_SIZE;
                                    ctx.fillStyle = 'rgba(255,255,255,0.15)';
                                    ctx.fillRect(x, y, BLOCK_SIZE - 1, BLOCK_SIZE - 1);
                                    ctx.strokeStyle = 'rgba(255,255,255,0.3)';
                                    ctx.lineWidth = 1;
                                    ctx.strokeRect(x, y, BLOCK_SIZE - 1, BLOCK_SIZE - 1);
                                }
                            }
                        }
                    }
                }

                if (currentPiece && !gameOver) {
                    const {
                        matrix,
                        row,
                        col,
                        color
                    } = currentPiece;
                    for (let r = 0; r < matrix.length; r++) {
                        for (let c = 0; c < matrix[0].length; c++) {
                            if (matrix[r][c] !== 0) {
                                const x = (col + c) * BLOCK_SIZE;
                                const y = (row + r) * BLOCK_SIZE;
                                ctx.fillStyle = color;
                                ctx.fillRect(x, y, BLOCK_SIZE - 1, BLOCK_SIZE - 1);
                                ctx.fillStyle = 'rgba(255,255,255,0.3)';
                                ctx.fillRect(x, y, BLOCK_SIZE - 4, 2);
                            }
                        }
                    }
                }
            }

            function updateScoreDisplay() {
                scoreDisplay.textContent = score;
                if (score > highScore && !gameOver) {
                    highScore = score;
                    localStorage.setItem('tetrisHighScore', highScore.toString());
                    updateHighScoreDisplay();
                }
            }

            function updateHighScoreDisplay() {
                highScoreDisplay.textContent = highScore > 0 ? highScore : 'none';
            }

            function updateTimeDisplay() {
                timeDisplay.textContent = timeElapsed;
                updateDropSpeed();
            }

            function updateComboDisplay() {
                comboDisplay.textContent = comboCount;
            }

            function startGameLoop() {
                if (gameLoopInterval) clearInterval(gameLoopInterval);
                gameLoopInterval = setInterval(() => {
                    if (!gameOver) {
                        gameTick();
                    }
                }, dropInterval);
            }

            function stopGameLoop() {
                if (gameLoopInterval) {
                    clearInterval(gameLoopInterval);
                    gameLoopInterval = null;
                }
                drawBoard();
            }

            function startTimer() {
                if (timerInterval) clearInterval(timerInterval);
                timerInterval = setInterval(() => {
                    if (!gameOver) {
                        timeElapsed++;
                        updateTimeDisplay();
                    }
                }, 1000);
            }

            function stopTimer() {
                if (timerInterval) {
                    clearInterval(timerInterval);
                    timerInterval = null;
                }
            }

            function restartGame() {
                stopGameLoop();
                stopTimer();
                board = Array.from({
                    length: ROWS
                }, () => Array(COLS).fill(0));
                gameOver = false;
                score = 0;
                timeElapsed = 0;
                comboCount = 0;
                heldPiece = null;
                canHold = true;
                dropInterval = baseDropInterval;
                speedLevel = 1.0;
                gameOverOverlay.style.display = 'none';

                nextPiece = randomPiece();
                const piece = randomPiece();
                currentPiece = piece;
                if (collide(piece.matrix, piece.row, piece.col)) {
                    gameOver = true;
                    showGameOver();
                }
                updateScoreDisplay();
                updateTimeDisplay();
                updateComboDisplay();
                updateHighScoreDisplay();
                speedDisplay.textContent = 'x1.0';
                drawHold();
                drawNext();
                drawBoard();
                startTimer();
                startGameLoop();
            }

            function handleKey(e) {
                if (e.key === 'Shift') {
                    e.preventDefault();
                    holdPiece();
                    return;
                }

                if (e.key === ' ' || e.key === "Enter") {
                    e.preventDefault();
                    hardDrop();
                    return;
                }

                if (gameOver || !currentPiece) return;
                const key = e.key;
                const {
                    matrix,
                    row,
                    col
                } = currentPiece;

                if (key === 'ArrowLeft' || key === 'a' || key === 'A') {
                    e.preventDefault();
                    if (!collide(matrix, row, col - 1)) currentPiece.col--;
                } else if (key === 'ArrowRight' || key === 'd' || key === 'D') {
                    e.preventDefault();
                    if (!collide(matrix, row, col + 1)) currentPiece.col++;
                } else if (key === 'ArrowDown' || key === 's' || key === 'S') {
                    e.preventDefault();
                    if (!collide(matrix, row + 1, col)) currentPiece.row++;
                } else if (key === 'ArrowUp' || key === 'w' || key === 'W') {
                    e.preventDefault();
                    const rotated = matrix[0].map((_, idx) => matrix.map(row => row[idx]).reverse());
                    if (!collide(rotated, row, col)) {
                        currentPiece.matrix = rotated;
                    }
                } else return;
                drawBoard();
            }

            function setupMobileControls() {
                const ctrlLeft = document.getElementById('ctrlLeft');
                const ctrlRight = document.getElementById('ctrlRight');
                const ctrlDown = document.getElementById('ctrlDown');
                const ctrlUp = document.getElementById('ctrlUp');
                const ctrlRotate = document.getElementById('ctrlRotate');
                const ctrlDrop = document.getElementById('ctrlDrop');
                const ctrlHold = document.getElementById('ctrlHold');
                const ctrlRestart = document.getElementById('ctrlRestart');

                function addTouchListener(el, action) {
                    el.addEventListener('touchstart', function(e) {
                        e.preventDefault();
                        action();
                    }, {
                        passive: false
                    });
                    el.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        action();
                    });
                }

                addTouchListener(ctrlLeft, () => {
                    if (gameOver || !currentPiece) return;
                    if (!collide(currentPiece.matrix, currentPiece.row, currentPiece.col - 1)) {
                        currentPiece.col--;
                        drawBoard();
                    }
                });

                addTouchListener(ctrlRight, () => {
                    if (gameOver || !currentPiece) return;
                    if (!collide(currentPiece.matrix, currentPiece.row, currentPiece.col + 1)) {
                        currentPiece.col++;
                        drawBoard();
                    }
                });

                addTouchListener(ctrlDown, () => {
                    if (gameOver || !currentPiece) return;
                    if (!collide(currentPiece.matrix, currentPiece.row + 1, currentPiece.col)) {
                        currentPiece.row++;
                        drawBoard();
                    }
                });

                addTouchListener(ctrlUp, () => {
                    if (gameOver || !currentPiece) return;
                    const matrix = currentPiece.matrix;
                    const rotated = matrix[0].map((_, idx) => matrix.map(row => row[idx]).reverse());
                    if (!collide(rotated, currentPiece.row, currentPiece.col)) {
                        currentPiece.matrix = rotated;
                        drawBoard();
                    }
                });

                addTouchListener(ctrlRotate, () => {
                    if (gameOver || !currentPiece) return;
                    const matrix = currentPiece.matrix;
                    const rotated = matrix[0].map((_, idx) => matrix.map(row => row[idx]).reverse());
                    if (!collide(rotated, currentPiece.row, currentPiece.col)) {
                        currentPiece.matrix = rotated;
                        drawBoard();
                    }
                });

                addTouchListener(ctrlDrop, hardDrop);
                addTouchListener(ctrlHold, holdPiece);
                addTouchListener(ctrlRestart, restartGame);
            }

            function init() {
                nextPiece = randomPiece();
                const piece = randomPiece();
                currentPiece = piece;
                if (collide(piece.matrix, piece.row, piece.col)) {
                    gameOver = true;
                    showGameOver();
                }
                updateScoreDisplay();
                updateTimeDisplay();
                updateComboDisplay();
                updateHighScoreDisplay();
                speedDisplay.textContent = 'x1.0';
                drawHold();
                drawNext();
                drawBoard();
                startTimer();
                startGameLoop();

                window.addEventListener('keydown', handleKey);
                document.getElementById('restartBtn').addEventListener('click', restartGame);

                setupMobileControls();

                if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
                    document.getElementById('mobileControls').style.display = 'flex';
                }
            }
            init();
        })();
    </script>
</body>

</html>