<?php
require __DIR__ . '/../vendor/autoload.php';
use Carbon\CarbonImmutable;
use mbootsman\Remindme\Config;
use mbootsman\Remindme\Db;
use mbootsman\Remindme\Logger;
use mbootsman\Remindme\RemindMeService;

CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-09 12:34:00','Europe/Amsterdam'));
$cfg = new Config(
    'https://example.invalid',
    'x',
    ':memory:',
    '@remindme',
    'Europe/Amsterdam',
    sys_get_temp_dir() . '/t.log',
    's'
);
$db = new Db(':memory:');
$logger = new Logger(sys_get_temp_dir() . '/t.log', 's');
$svc = new RemindMeService($db, $cfg, $logger);

$cmds = [
    'remind me in 15 minutes about stretch',
    'remind me in 2 hours about drink water',
    'remind me in 2 days about pay invoice',
    'remind me in 1 week about renew domain',
    'remind me in 1 month about do taxes'
];

foreach ($cmds as $i => $c) {
    echo "\n== Command #" . ($i+1) . " ==\n";
    echo "CMD: {$c}\n";
    $reply = $svc->handleCommand('u1', 'marcel', 's' . $i, $c);
    echo "REPLY: "; var_export($reply); echo "\n";

    $rows = $db->pdo()->query("SELECT id, task, created_at_utc FROM reminders ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    echo "DB rows (count=" . count($rows) . "):\n";
    foreach ($rows as $r) {
        echo "  id={$r['id']} task={$r['task']} created_at={$r['created_at_utc']}\n";
    }
}

echo "\nDone.\n";
