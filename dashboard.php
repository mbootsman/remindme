<?php
require_once __DIR__ . '/vendor/autoload.php';
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}
if (empty($_ENV['DASHBOARD_USER']) || empty($_ENV['DASHBOARD_PASS'])) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'Dashboard credentials not set. Please configure DASHBOARD_USER and DASHBOARD_PASS in your .env file.';
    exit;
}
$user = $_ENV['DASHBOARD_USER'];
$pass = $_ENV['DASHBOARD_PASS'];
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) ||
    $_SERVER['PHP_AUTH_USER'] !== $user || $_SERVER['PHP_AUTH_PW'] !== $pass) {
    header('WWW-Authenticate: Basic realm="RemindMe Dashboard"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Authentication required.';
    exit;
}
?>
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
        #logsChart { 
            width: 100%; 
            max-width: 320px; 
            max-height: 200px;
            display: block; 
            margin-left: 0;
            border-radius: 8px; 
            background: #fff; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.04); 
        }
        table { border-collapse: collapse; table-layout: auto; background: #fff; }
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
            type: 'line',
            data: {
                labels: logs.labels,
                datasets: [{
                    label: 'Reminders Created',
                    data: logs.counts,
                    borderColor: '#4e79a7',
                    backgroundColor: 'rgba(78,121,167,0.1)',
                    fill: false,
                    tension: 0.7,
                    pointRadius: 2
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
                },
                plugins: {
                    legend: {
                        display: false
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
