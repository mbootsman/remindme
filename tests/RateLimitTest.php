<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Carbon\CarbonImmutable;
use mbootsman\Remindme\Config;
use mbootsman\Remindme\Db;
use mbootsman\Remindme\Logger;
use mbootsman\Remindme\RemindMeService;

final class RateLimitTest extends TestCase {
    protected function setUp(): void {
        CarbonImmutable::setTestNow(CarbonImmutable::parse("2026-02-10 10:00:00", "UTC"));
    }

    protected function tearDown(): void {
        CarbonImmutable::setTestNow();
    }

    public function testPerMinuteLimitTriggers(): void {
        $cfg = new Config("https://example.invalid", "x", ":memory:", "@remindme", "UTC", sys_get_temp_dir() . "/test.log", "s", 3, 50);
        $db = new Db(":memory:");
        $logger = new Logger(sys_get_temp_dir() . "/test.log", "s");
        $svc = new RemindMeService($db, $cfg, $logger);

        // Insert 4 reminders quickly for the same user to exceed per-minute threshold.
        for ($i = 0; $i < 4; $i++) {
            $reply = $svc->handleCommand("u1", "acct", "s{$i}", "remind me in 1 hour about task {$i}");
        }

        // The last reply should be a rate limit message string (not null)
        $this->assertStringContainsString("creating reminders too quickly", (string)$reply);
    }

    public function testPerDayLimitTriggers(): void {
        $cfg = new Config("https://example.invalid", "x", ":memory:", "@remindme", "UTC", sys_get_temp_dir() . "/test.log", "s", 1000, 50);
        $db = new Db(":memory:");
        $logger = new Logger(sys_get_temp_dir() . "/test.log", "s");
        $svc = new RemindMeService($db, $cfg, $logger);

        // Create 51 reminders by manipulating created_at_utc timestamps
        $pdo = $db->pdo();
        $stmt = $pdo->prepare("INSERT INTO reminders(user_id, user_acct, source_status_id, task, due_at_utc, created_at_utc) VALUES(:uid, :acct, :sid, :task, :due, :created)");
        for ($i = 0; $i < 51; $i++) {
            $stmt->execute([
                ":uid" => "u2",
                ":acct" => "acct2",
                ":sid" => "s{$i}",
                ":task" => "t{$i}",
                ":due" => CarbonImmutable::now('UTC')->addHours(1)->format(DATE_ATOM),
                ":created" => CarbonImmutable::now('UTC')->subHours(2)->format(DATE_ATOM)
            ]);
        }

        // Now attempt to create one more via service and expect daily limit message
        $reply = $svc->handleCommand("u2", "acct2", "s-new", "remind me in 1 hour about extra");
        $this->assertStringContainsString("daily limit", (string)$reply);
    }
}
