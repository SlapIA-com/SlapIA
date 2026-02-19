document.addEventListener('DOMContentLoaded', () => {
    const output = document.getElementById('terminal-output');
    const buttons = document.getElementById('choice-buttons');
    const inputLine = document.querySelector('.input-line');

    // Dialogues
    const linesFR = [
        { text: "Microsoft Windows [Version 10.0.19045.3693]", delay: 100 },
        { text: "(c) Microsoft Corporation. All rights reserved.", delay: 300 },
        { text: "", delay: 500 },
        { text: "C:\\Users\\Visitor> python3 connect_neural_link.py", delay: 1000, type: true },
        { text: "Initialisation du Core IA...", delay: 2000, color: "text-white" },
        { text: "Connexion établie.", delay: 3000, color: "text-green" },
        { text: "", delay: 3200 },
        { text: "ERREUR 404 : PAGE INTROUVABLE", delay: 3500, color: "text-red" },
        { text: "Analyse du problème en cours...", delay: 4000 },
        { text: "[WARNING] L'utilisateur semble perdu dans le cyberespace.", delay: 5000, color: "text-yellow" },
        { text: "SlapIA Assistant : \"Ne vous inquiétez pas. C'est juste un glitch dans la matrice.\"", delay: 6500, color: "text-white" },
        { text: "Voulez-vous retourner à la réalité ?", delay: 8000 }
    ];

    const linesEN = [
        { text: "Microsoft Windows [Version 10.0.19045.3693]", delay: 100 },
        { text: "(c) Microsoft Corporation. All rights reserved.", delay: 300 },
        { text: "", delay: 500 },
        { text: "C:\\Users\\Visitor> python3 connect_neural_link.py", delay: 1000, type: true },
        { text: "Initializing AI Core...", delay: 2000, color: "text-white" },
        { text: "Connection established.", delay: 3000, color: "text-green" },
        { text: "", delay: 3200 },
        { text: "ERROR 404 : PAGE NOT FOUND", delay: 3500, color: "text-red" },
        { text: "Analyzing issue...", delay: 4000 },
        { text: "[WARNING] User appears lost in cyberspace.", delay: 5000, color: "text-yellow" },
        { text: "SlapIA Assistant : \"Don't worry. It's just a glitch in the matrix.\"", delay: 6500, color: "text-white" },
        { text: "Do you want to return to reality?", delay: 8000 }
    ];

    const lines = (typeof LANG !== 'undefined' && LANG === 'en') ? linesEN : linesFR;

    let totalDelay = 0;

    lines.forEach((line, index) => {
        setTimeout(() => {
            const p = document.createElement('p');
            if (line.color) p.classList.add(line.color);

            if (line.type) {
                // Simulate typing input command
                inputLine.style.display = 'block';
                typeWriter(inputLine.querySelector('.prompt'), line.text, 50, () => {
                    inputLine.style.display = 'none'; // Hide input line effectively after type
                    p.textContent = line.text;
                    output.appendChild(p);
                });
            } else {
                p.textContent = line.text;
                output.appendChild(p);
                window.scrollTo(0, document.body.scrollHeight);
            }
        }, line.delay);
        totalDelay = Math.max(totalDelay, line.delay);
    });

    // Show buttons at the end
    setTimeout(() => {
        buttons.classList.remove('hidden');
        // Small reflow to trigger transition
        void buttons.offsetWidth;
        buttons.classList.add('visible');
    }, totalDelay + 500);

    // Simple Typewriter helper (not used for main texts to save time, only simulating input)
    function typeWriter(element, text, speed, callback) {
        // Not implemented fully here for simplicity in this simplified script
        // We just pretend for the command line part if needed, 
        // but for now the script above appends lines. 
        // To make it cooler, we can just append.
    }
});
