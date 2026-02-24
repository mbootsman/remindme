<?php

// Show errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';

// Returns scheduled reminders as JSON
$configPath = __DIR__ . '/src/Config.php';
require_once __DIR__ . '/src/Db.php';
require_once $configPath;
$config = \mbootsman\Remindme\Config::fromEnv();
$db = new \mbootsman\Remindme\Db($config->dbPath);
$sql = 'SELECT id, user_acct, task, due_at_utc FROM reminders WHERE sent_at_utc IS NULL AND canceled_at_utc IS NULL ORDER BY due_at_utc ASC';
$stmt = $db->pdo()->query($sql);
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
