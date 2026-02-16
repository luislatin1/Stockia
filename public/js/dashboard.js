document.addEventListener('DOMContentLoaded', function () {
console.log(dashboardData);
    if (typeof dashboardData === 'undefined') return;

    const ctx = document.getElementById('salesChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dashboardData.dates,
            datasets: [{
                label: 'Ventas',
                data: dashboardData.totals,
                borderWidth: 2,
                tension: 0.3
            }]
        },

        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

});
