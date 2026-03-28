<?php
// Suppress PHP errors/warnings for clean JSON output
ini_set('display_errors', 0);
error_reporting(0);
// Returns log metrics for dashboard as JSON
$logPath = __DIR__ . '/logs/remindme.log';
$counts = [];
$labels = [];
if (file_exists($logPath)) {
    $lines = file($logPath);
    foreach ($lines as $line) {
        $entry = json_decode($line, true);
        if (!$entry || !isset($entry['event'])) continue;
        // Count reminders created per day
        if ($entry['event'] === 'reminder.created') {
            $date = substr($entry['timestamp'], 0, 10);
            $counts[$date] = ($counts[$date] ?? 0) + 1;
        }
    }
    ksort($counts);
    $labels = array_keys($counts);
}

// Debug output
if (isset($_GET['debug'])) {
    header('Content-Type: text/plain');
    echo "Log path: $logPath\n";
    echo "Labels: "; print_r($labels);
    echo "Counts: "; print_r($counts);
    exit;
}
header('Content-Type: application/json');
echo json_encode([
    'labels' => $labels,
    'counts' => array_values($counts)
]);
