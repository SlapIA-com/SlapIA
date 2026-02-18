/**
 * AI ROI Calculator
 * Estimates savings based on team size, average salary, and repetitive tasks.
 */

document.addEventListener('DOMContentLoaded', () => {
    const employeesInput = document.getElementById('roi-employees');
    const salaryInput = document.getElementById('roi-salary');

    const employeesValue = document.getElementById('roi-employees-value');
    const salaryValue = document.getElementById('roi-salary-value');

    const resultHours = document.getElementById('roi-result-hours');
    const resultMoney = document.getElementById('roi-result-money');

    if (!employeesInput || !salaryInput) return;

    function calculateROI() {
        // Inputs
        const employees = parseInt(employeesInput.value);
        const salary = parseInt(salaryInput.value);

        // Update UI Input Values
        employeesValue.textContent = employees;
        salaryValue.textContent = salary.toLocaleString('fr-FR') + '€';

        // Calculation assumptions
        // Base: 160 hours / month
        // Efficiency Gain: 20% of Total Time (Conservative but impactful) -> ~32h / month / person
        const MONTHLY_HOURS = 160;
        const EFFICIENCY_GAIN = 0.20;
        const MONTHS_PER_YEAR = 11; // 1 full month vacation excluded ? Or 12? Let's say 11 active months

        // Hours Saved Calculation
        const hoursSavedPerPersonPerMonth = MONTHLY_HOURS * EFFICIENCY_GAIN; // 32
        const totalHoursSavedPerYear = Math.round(hoursSavedPerPersonPerMonth * employees * MONTHS_PER_YEAR);

        // Money Saved Calculation
        const hourlyRate = salary / MONTHLY_HOURS;
        const totalMoneySaved = Math.round(totalHoursSavedPerYear * hourlyRate);

        // Animate Numbers
        animateValue(resultHours, parseInt(resultHours.textContent.replace(/\D/g, '')) || 0, totalHoursSavedPerYear, 500);
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

    // Initial calc
    calculateROI();
});
