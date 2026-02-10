/**
 * Matrix Easter Egg 🐇
 * Type "MATRIX" to enter the matrix.
 */

document.addEventListener('DOMContentLoaded', () => {
    let input = '';
    const secretCode = 'MATRIX';
    let isMatrixActive = false;
    let canvas, ctx, letters, fontSize, columns, drops;
    let matrixInterval;

    document.addEventListener('keydown', (e) => {
        // Append char to input buffer
        input += e.key.toUpperCase();

        // Keep buffer minimal
        if (input.length > secretCode.length) {
            input = input.substr(input.length - secretCode.length);
        }

        // Check sequence
        if (input === secretCode) {
            if (!isMatrixActive) {
                activateMatrix();
            } else {
                deactivateMatrix();
            }
            input = ''; // Reset buffer
        }
    });

    function activateMatrix() {
        isMatrixActive = true;
        document.body.classList.add('matrix-mode');

        // Add Canvas
        canvas = document.createElement('canvas');
        canvas.id = 'matrix-canvas';
        canvas.style.position = 'fixed';
        canvas.style.top = '0';
        canvas.style.left = '0';
        canvas.style.width = '100%';
        canvas.style.height = '100%';
        canvas.style.zIndex = '-1'; // Behind text but visible on dark background
        canvas.style.pointerEvents = 'none';
        document.body.appendChild(canvas);

        ctx = canvas.getContext('2d');

        // Dimensions
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        // Characters
        letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$%^&*';
        letters = letters.split('');

        fontSize = 14;
        columns = canvas.width / fontSize;
        drops = [];
        for (let i = 0; i < columns; i++) {
            drops[i] = 1;
        }

        // Loop
        matrixInterval = setInterval(draw, 33);

        // Play sound if possible (optional, keeping it silent for now)
        console.log("Welcome to the Real World.");
    }

    function deactivateMatrix() {
        isMatrixActive = false;
        document.body.classList.remove('matrix-mode');

        if (canvas) {
            canvas.remove();
            canvas = null;
        }

        if (matrixInterval) {
            clearInterval(matrixInterval);
            matrixInterval = null;
        }

        window.removeEventListener('resize', resizeCanvas);
        console.log("Blue pill swallowed.");
    }

    function resizeCanvas() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        columns = canvas.width / fontSize;
        drops = []; // Reset drops
        for (let i = 0; i < columns; i++) {
            drops[i] = 1;
        }
    }

    function draw() {
        // Semi-transparent black to create trail effect
        ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        ctx.fillStyle = '#0F0'; // Matrix Green
        ctx.font = fontSize + 'px monospace';

        for (let i = 0; i < drops.length; i++) {
            const text = letters[Math.floor(Math.random() * letters.length)];
            ctx.fillText(text, i * fontSize, drops[i] * fontSize);

            if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
                drops[i] = 0;
            }
            drops[i]++;
        }
    }
});
