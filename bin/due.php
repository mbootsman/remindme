#!/usr/bin/env php
<?php
require __DIR__ . "/../vendor/autoload.php";

use Dotenv\Dotenv;
use Carbon\CarbonImmutable;
use mbootsman\Remindme\Config;
use mbootsman\Remindme\Db;
use mbootsman\Remindme\MastodonHttp;
use PDO;

Dotenv::createImmutable(__DIR__ . "/..")->safeLoad();

$cfg = Config::fromEnv();
$db = new Db($cfg->dbPath);
$api = new MastodonHttp($cfg);

$pdo = $db->pdo();
$nowUtc = CarbonImmutable::now("UTC")->format(DATE_ATOM);

$stmt = $pdo->prepare("
    SELECT id, user_acct, task
    FROM reminders
    WHERE due_at_utc <= :now
      AND sent_at_utc IS NULL
      AND canceled_at_utc IS NULL
    ORDER BY due_at_utc ASC
    LIMIT 25
");
$stmt->execute([":now" => $nowUtc]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    $id = (int)$r["id"];
    $acct = (string)$r["user_acct"];
    $task = (string)$r["task"];

    $api->postStatus("@{$acct} Reminder (ID: {$id}): {$task}", "direct", null);

    $upd = $pdo->prepare("UPDATE reminders SET sent_at_utc = :now WHERE id = :id");
    $upd->execute([":now" => $nowUtc, ":id" => $id]);
}
