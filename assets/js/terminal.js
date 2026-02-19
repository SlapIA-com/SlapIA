document.addEventListener('DOMContentLoaded', () => {
    const output = document.getElementById('terminal-output');
    const buttons = document.getElementById('choice-buttons');
    const inputLine = document.querySelector('.input-line');
    const promptSpan = inputLine.querySelector('.prompt');
    const cursor = inputLine.querySelector('.cursor');

    // Content configuration
    const config = {
        fr: {
            header: [
                "Microsoft Windows [Version 10.0.19045.3693]",
                "(c) Microsoft Corporation. All rights reserved."
            ],
            command: "python3 connect_neural_link.py",
            logs: [
                { text: "Initialisation du Core IA...", delay: 800, color: "text-white" },
                { text: "Chargement des modules cognitifs...", delay: 400 },
                { text: "Connexion à la base de connaissances...", delay: 400 },
                { text: "ERREUR CRITIQUE : CHEMIN INTROUVABLE (404)", delay: 600, color: "text-red" },
                { text: "Analyse de la singularité...", delay: 1000 },
                { text: "[WARNING] L'utilisateur a dérivé hors du spectre connu.", delay: 800, color: "text-yellow" },
                { text: "SlapIA Security : \"Ne panequez pas. Ce n'est qu'un pixel mort.\"", delay: 1500, color: "text-green" }
            ]
        },
        en: {
            header: [
                "Microsoft Windows [Version 10.0.19045.3693]",
                "(c) Microsoft Corporation. All rights reserved."
            ],
            command: "python3 connect_neural_link.py",
            logs: [
                { text: "Initializing AI Core...", delay: 800, color: "text-white" },
                { text: "Loading cognitive modules...", delay: 400 },
                { text: "Connecting to knowledge base...", delay: 400 },
                { text: "CRITICAL ERROR : PATH NOT FOUND (404)", delay: 600, color: "text-red" },
                { text: "Analyzing singularity...", delay: 1000 },
                { text: "[WARNING] User has drifted outside known spectrum.", delay: 800, color: "text-yellow" },
                { text: "SlapIA Security : \"Don't panic. It's just a dead pixel.\"", delay: 1500, color: "text-green" }
            ]
        }
    };

    const currentLang = (typeof LANG !== 'undefined' && LANG === 'en') ? 'en' : 'fr';
    const data = config[currentLang];

    // Helper: Wait function
    const wait = (ms) => new Promise(resolve => setTimeout(resolve, ms));

    // Helper: Typewriter effect
    async function typeText(element, text, speed = 50) {
        for (let char of text) {
            element.textContent += char;
            await wait(speed + (Math.random() * 20)); // Subtle typing variance
        }
    }

    // Helper: Append log line
    function appendLog(text, color) {
        const p = document.createElement('p');
        p.textContent = text;
        if (color) p.classList.add(color);
        output.appendChild(p);
        window.scrollTo(0, document.body.scrollHeight);
    }

    // Main Sequence
    async function runSequence() {
        // 1. Static Header
        for (let line of data.header) {
            appendLog(line, 'text-secondary');
            await wait(200);
        }
        await wait(500);

        // 2. Command Line Typing
        inputLine.style.display = 'block'; // Show "C:\Users... >"
        await wait(500);

        // Remove the default prompt text from HTML if we want to type it, 
        // OR just type the command AFTER the prompt. 
        // HTML has: <span class="prompt">C:\Users\Visitor></span><span class="cursor">_</span>
        // We will type into a new span or just append text node before cursor.
        const cmdSpan = document.createElement('span');
        // Insert before cursor
        inputLine.insertBefore(cmdSpan, cursor);

        await typeText(cmdSpan, data.command, 40);
        await wait(300);

        // "Enter" key simulation
        inputLine.style.display = 'none'; // Hide the active input line
        // Append the full command line to history
        const historyLine = document.createElement('p');
        historyLine.textContent = `C:\\Users\\Visitor> ${data.command}`;
        output.appendChild(historyLine);

        await wait(500);

        // 3. System Logs
        for (let log of data.logs) {
            appendLog(log.text, log.color);
            await wait(log.delay);
        }

        // 4. Show Buttons
        await wait(1000);
        buttons.classList.remove('hidden');
        void buttons.offsetWidth; // force reflow
        buttons.classList.add('visible');
    }

    // Start
    runSequence();

});
