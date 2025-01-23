
var chartInstance; // Declare chart globally

function renderChart(labels, values) {
    const ctx = document.getElementById('trendChart').getContext('2d');

    // Destroy previous chart instance if it exists
    if (chartInstance) {
        chartInstance.destroy();
    }

    // Create new chart
    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Trend',
                data: values,
                borderColor: 'rgba(75, 192, 192, 1)',
                fill: false
            }]
        }
    });
}

$(document).ready(function () {
    fetch('/data/alchx')  // Replace with actual table name
        .then(response => response.json())
        .then(data => {
            const labels = data.map(row => row.date);  
            const values = data.map(row => row.value);

            $("#sparkline").sparkline(values, {
                type: "line",
                width: "200",
                height: "40",
                lineColor: "#007bff",
                fillColor: "#cce5ff"
            });

            // Render the chart
            renderChart(labels, values);
        })
        .catch(error => console.error("Error fetching data:", error));
});
