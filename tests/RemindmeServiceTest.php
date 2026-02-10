<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Carbon\CarbonImmutable;
use mbootsman\Remindme\Config;
use mbootsman\Remindme\Db;
use mbootsman\Remindme\Logger;
use mbootsman\Remindme\RemindMeService;

final class RemindMeServiceTest extends TestCase {
    protected function setUp(): void {
        // Freeze time for predictable tests
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse("2026-01-09 07:34:00", "Europe/Amsterdam")
        );
    }

    protected function tearDown(): void {
        CarbonImmutable::setTestNow();
    }

    public function testCreatesReminderIn2Days(): void {
        $cfg = new Config("https://example.invalid", "x", ":memory:", "@remindme", "Europe/Amsterdam", sys_get_temp_dir() . "/test.log", "test-secret");
        $db = new Db(":memory:");
        $logger = new Logger(sys_get_temp_dir() . "/test.log", "test-secret");
        $svc = new RemindMeService($db, $cfg, $logger);

        $reply = $svc->handleCommand("u1", "marcel", "s1", "remind me in 1 month about paying the invoice");

        $this->assertStringContainsString("Ok! I will remind you on", $reply);

        $row = $db->pdo()->query("SELECT task, due_at_utc FROM reminders")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame("paying the invoice", $row["task"]);

        $row = $db->pdo()->query("SELECT id, user_acct, task, due_at_utc FROM reminders")->fetch(PDO::FETCH_ASSOC);

        $reminderMessage = "@{$row['user_acct']} Reminder (ID: {$row['id']}): {$row['task']}";

        // Print it (PHPUnit can capture this unless you enable display)
        // Only print it if the test is run with PRINT_REMINDERS=1 to avoid noise in normal test runs.
        if (getenv("PRINT_REMINDERS") === "1") {
            fwrite(STDOUT, "\nWould send: {$reminderMessage}\nDue at (UTC): {$row['due_at_utc']}\n");
        }
    }

    public function testHelpCommand(): void {
        $cfg = new Config("https://example.invalid", "x", ":memory:", "@remindme", "Europe/Amsterdam", sys_get_temp_dir() . "/test.log", "test-secret");
        $db = new Db(":memory:");
        $logger = new Logger(sys_get_temp_dir() . "/test.log", "test-secret");
        $svc = new RemindMeService($db, $cfg, $logger);

        $reply = $svc->handleCommand("u1", "marcel", "s1", "help");

        $this->assertStringContainsString("Try:", $reply);
    }
}
