/**
 * AI ROI Calculator
 * Estimates savings based on team size, average salary, and repetitive tasks.
 */

document.addEventListener('DOMContentLoaded', () => {
    const employeesInput = document.getElementById('roi-employees');
    const salaryInput = document.getElementById('roi-salary');
    const repetitiveInput = document.getElementById('roi-repetitive');

    const employeesValue = document.getElementById('roi-employees-value');
    const salaryValue = document.getElementById('roi-salary-value');
    const repetitiveValue = document.getElementById('roi-repetitive-value');

    const resultHours = document.getElementById('roi-result-hours');
    const resultMoney = document.getElementById('roi-result-money');

    if (!employeesInput || !salaryInput || !repetitiveInput) return;

    function calculateROI() {
        // Inputs
        const employees = parseInt(employeesInput.value);
        const salary = parseInt(salaryInput.value);
        const repetitiveHours = parseFloat(repetitiveInput.value);

        // Update UI Input Values
        employeesValue.textContent = employees;
        salaryValue.textContent = salary.toLocaleString('fr-FR') + '€';
        repetitiveValue.textContent = repetitiveHours + 'h';

        // Calculation assumptions
        const EFFICIENCY_GAIN = 0.40; // 40% gain on repetitive tasks
        const WEEKS_PER_YEAR = 47; // 52 weeks - 5 weeks vacation

        // Hours Saved Calculation
        // (Repetitive Hours * Efficiency) * Team Size * Weeks
        const weeklyHoursSaved = (repetitiveHours * EFFICIENCY_GAIN) * employees;
        const totalHoursSaved = Math.round(weeklyHoursSaved * WEEKS_PER_YEAR);

        // Money Saved Calculation
        // Hourly rate = Salary / 151.67 (standard monthly hours in France) or 160 simplified
        const hourlyRate = salary / 151.67;
        const totalMoneySaved = Math.round(totalHoursSaved * hourlyRate);

        // Animate Numbers
        animateValue(resultHours, parseInt(resultHours.textContent.replace(/\D/g, '')) || 0, totalHoursSaved, 500);
        animateValue(resultMoney, parseInt(resultMoney.textContent.replace(/\D/g, '')) || 0, totalMoneySaved, 500);
    }

    function animateValue(obj, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const current = Math.floor(progress * (end - start) + start);

            // Format currency if money
            obj.innerHTML = obj.id.includes('money') ? current.toLocaleString('fr-FR') + '€' : current.toLocaleString('fr-FR');

            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    // Event Listeners
    employeesInput.addEventListener('input', calculateROI);
    salaryInput.addEventListener('input', calculateROI);
    repetitiveInput.addEventListener('input', calculateROI);

    // Initial calc
    calculateROI();
});
