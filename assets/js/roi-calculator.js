/**
 * AI ROI Calculator
 * Estimates savings based on team size and average salary.
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
        const employees = parseInt(employeesInput.value);
        const salary = parseInt(salaryInput.value);

        // Update UI values
        employeesValue.textContent = employees;
        salaryValue.textContent = salary.toLocaleString() + '€';

        // Assumptions:
        // - AI saves ~5-10% of time (Conservative "No BS" estimate)
        // - ~8 hours/month per person (approx 2h/week)

        // Assumptions:
        // - AI saves ~1-2% of time (Ultra-conservative / safe bet)
        // - ~2 hours/month per person (30min/week)

        const hoursSavedPerPerson = 2; // "Réduit à fond"
        const totalHoursSaved = employees * hoursSavedPerPerson;

        const hourlyRate = salary / 160; // Approximate monthly hours
        const moneySaved = Math.round(totalHoursSaved * hourlyRate);

        // Animate Numbers
        animateValue(resultHours, parseInt(resultHours.textContent.replace(/\D/g, '')) || 0, totalHoursSaved, 500);
        animateValue(resultMoney, parseInt(resultMoney.textContent.replace(/\D/g, '')) || 0, moneySaved, 500);
    }

    function animateValue(obj, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const current = Math.floor(progress * (end - start) + start);

            // Format currency if money
            obj.innerHTML = obj.id.includes('money') ? current.toLocaleString() + '€' : current;

            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    employeesInput.addEventListener('input', calculateROI);
    salaryInput.addEventListener('input', calculateROI);

    // Initial calc
    calculateROI();
});
