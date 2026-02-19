document.addEventListener('DOMContentLoaded', function () {
    const output = document.getElementById('cmd-output');
    if (!output) return;

    const lines = [
        "Initializing SlapIA Core...",
        "Loading dependencies...",
        "Accessing /var/www/html/requested_page...",
        "ERROR: 404 - Page Not Found",
        "Searching for alternative routes...",
        "tracepath -h 127.0.0.1",
        "Failed to locate target.",
        "System halted.",
        " ",
        "Suggested action: Return to Home Base."
    ];

    let lineIndex = 0;
    let charIndex = 0;
    const typingSpeed = 30; // ms per char
    const lineDelay = 400;  // ms between lines

    function typeLine() {
        if (lineIndex < lines.length) {
            const currentLine = lines[lineIndex];

            // Create a new div for the line
            if (charIndex === 0) {
                const lineDiv = document.createElement('div');
                lineDiv.className = 'cmd-line';
                // Add specific styling for error/success
                if (currentLine.includes("ERROR") || currentLine.includes("Failed")) {
                    lineDiv.style.color = "#ff4d4d"; // Red
                } else if (currentLine.includes("suggested") || currentLine.includes("Return")) {
                    lineDiv.style.color = "#bf5af2"; // Purple accent
                }

                output.appendChild(lineDiv);
            }

            // Type character
            const activeLineDiv = output.lastElementChild;
            activeLineDiv.textContent += currentLine.charAt(charIndex);
            charIndex++;

            if (charIndex < currentLine.length) {
                setTimeout(typeLine, typingSpeed);
            } else {
                // Line finished
                charIndex = 0;
                lineIndex++;
                output.scrollTop = output.scrollHeight; // Auto scroll
                setTimeout(typeLine, lineDelay);
            }
        } else {
            // Finished all lines
            document.querySelector('.cmd-cursor').classList.add('blinking');
        }
    }

    // Start typing after a short delay
    setTimeout(typeLine, 800);
});
