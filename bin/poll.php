#!/usr/bin/env php
<?php
require __DIR__ . "/../vendor/autoload.php";

use Dotenv\Dotenv;
use mbootsman\Remindme\Config;
use mbootsman\Remindme\Db;
use mbootsman\Remindme\MastodonHttp;
use mbootsman\Remindme\RemindMeService;
use mbootsman\Remindme\Text;

$root = dirname(__DIR__);
Dotenv::createImmutable($root)->load();

$cfg = Config::fromEnv();
date_default_timezone_set($cfg->timezone);

$db = new Db($cfg->dbPath);
$api = new MastodonHttp($cfg);
$svc = new RemindMeService($db, $cfg);

$since = $db->get("last_notification_id");
$notifications = $api->getMentionNotifications($since, 40);

// First run bootstrap: store the newest notification id and do not process old items.
if (!$since) {
    $bootstrapMax = 0;
    foreach ($notifications as $n) {
        $nid = (int)($n["id"] ?? 0);
        if ($nid > $bootstrapMax) $bootstrapMax = $nid;
    }

    if ($bootstrapMax > 0) {
        $db->set("last_notification_id", (string)$bootstrapMax);
    }

    exit(0);
}

$maxId = $since ? (int)$since : 0;

foreach ($notifications as $n) {
    $nid = (int)($n["id"] ?? 0);
    if ($nid > $maxId) $maxId = $nid;

    if (($n["type"] ?? "") !== "mention") continue;

    $acct = $n["account"]["acct"] ?? null;
    $uid  = $n["account"]["id"] ?? null;
    $status = $n["status"] ?? null;
    if (!$acct || !$uid || !$status) continue;

    $statusId = (string)($status["id"] ?? "");
    $visibility = (string)($status["visibility"] ?? "");

    $plain = Text::fromHtml((string)($status["content"] ?? ""));
    $plain = Text::removeLeadingBotHandle($plain, $cfg->botHandle);

    // Privacy rule: only accept direct messages.
    $trimmed = trim($plain);

    // Only respond if the user is actually trying to use the bot.
    $looksLikeCommand = Text::looksLikeCommand($trimmed);


    if ($visibility !== "direct") {
        if ($looksLikeCommand) {
            $api->postStatus(
                "@{$acct} Please send me a direct message so I can store reminders privately. Type: help",
                "public",
                $statusId
            );
        }
        continue;
    }

    $reply = $svc->handleCommand((string)$uid, (string)$acct, $statusId, $plain);

    // If the service returns null, it means "no reply".
    // We use this to avoid auto-replying with help text when someone just mentions the bot
    // without actually trying a "remind me ..." command.
    if ($reply === null) {
        continue;
    }

    // Reply in the same direct thread
    $api->postStatus($reply, "direct", $statusId);
}

if ($maxId > 0) {
    $db->set("last_notification_id", (string)$maxId);
}
