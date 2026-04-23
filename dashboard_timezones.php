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

$dbPath = $_ENV['DB_PATH'] ?? 'data/remindme.sqlite';
if (!str_starts_with($dbPath, '/')) {
    $dbPath = __DIR__ . '/' . $dbPath;
}
$pdo = new PDO('sqlite:' . $dbPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$stmt = $pdo->query('SELECT user_acct, timezone, updated_at_utc FROM user_settings ORDER BY timezone ASC, user_acct ASC');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($rows);
