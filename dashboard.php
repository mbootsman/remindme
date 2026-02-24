<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>RemindMe Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; margin: 2em; background: #f9f9f9; }
        h1 { color: #333; }
        .section { margin-bottom: 2em; }
        #logsChart { width: 100%; max-width: 600px; height: 300px; }
        table { border-collapse: collapse; width: 100%; background: #fff; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h1>RemindMe Dashboard</h1>
    <div class="section">
        <h2>Log Metrics</h2>
        <canvas id="logsChart"></canvas>
    </div>
    <div class="section">
        <h2>Scheduled Reminders</h2>
        <table id="remindersTable">
            <thead>
                <tr><th>ID</th><th>User</th><th>Task</th><th>Due (UTC)</th></tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    async function fetchLogs() {
        const res = await fetch('dashboard_logs.php');
        return res.json();
    }
    async function fetchReminders() {
        const res = await fetch('dashboard_reminders.php');
        return res.json();
    }
    async function renderLogsChart() {
        const logs = await fetchLogs();
        const ctx = document.getElementById('logsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: logs.labels,
                datasets: [{
                    label: 'Reminders Created',
                    data: logs.counts,
                    backgroundColor: '#4e79a7'
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
    async function renderRemindersTable() {
        const reminders = await fetchReminders();
        const tbody = document.querySelector('#remindersTable tbody');
        tbody.innerHTML = reminders.map(r =>
            `<tr><td>${r.id}</td><td>${r.user_acct}</td><td>${r.task}</td><td>${r.due_at_utc}</td></tr>`
        ).join('');
    }
    renderLogsChart();
    renderRemindersTable();
    </script>
</body>
</html>
