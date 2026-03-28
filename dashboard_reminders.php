<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once __DIR__ . '/vendor/autoload.php';
if (file_exists(__DIR__ . '/.env')) {
    Dotenv\Dotenv::createImmutable(__DIR__)->load();
}
$user = $_ENV['DASHBOARD_USER'] ?? '';
$pass = $_ENV['DASHBOARD_PASS'] ?? '';
if (!$user || !$pass ||
    ($_SERVER['PHP_AUTH_USER'] ?? '') !== $user ||
    ($_SERVER['PHP_AUTH_PW'] ?? '') !== $pass) {
    header('WWW-Authenticate: Basic realm="RemindMe Dashboard"');
    http_response_code(401);
    exit;
}

// Returns scheduled reminders as JSON (read-only connection, no WAL pragma needed)
$dbPath = $_ENV['DB_PATH'] ?? 'data/remindme.sqlite';
if (!str_starts_with($dbPath, '/')) {
    $dbPath = __DIR__ . '/' . $dbPath;
}
$pdo = new PDO('sqlite:' . $dbPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$sql = 'SELECT id, user_acct, task, due_at_utc FROM reminders WHERE sent_at_utc IS NULL AND canceled_at_utc IS NULL ORDER BY due_at_utc ASC';
$stmt = $pdo->query($sql);
$reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug output
if (isset($_GET['debug'])) {
    header('Content-Type: text/plain');
    echo "SQL: $sql\n";
    echo "Reminders count: ".count($reminders)."\n";
    print_r($reminders);
    exit;
}

header('Content-Type: application/json');
echo json_encode($reminders);
