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
        $cfg = new Config("https://example.invalid", "x", ":memory:", "@remindme", "Europe/Amsterdam", sys_get_temp_dir() . "/test.log", "test-secret", 1000, 10000);
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
        $cfg = new Config("https://example.invalid", "x", ":memory:", "@remindme", "Europe/Amsterdam", sys_get_temp_dir() . "/test.log", "test-secret", 1000, 10000);
        $db = new Db(":memory:");
        $logger = new Logger(sys_get_temp_dir() . "/test.log", "test-secret");
        $svc = new RemindMeService($db, $cfg, $logger);

        $reply = $svc->handleCommand("u1", "marcel", "s1", "help");

        $this->assertStringContainsString("Try:", $reply);
    }

    public function testPostReplyReminderStoresUrl(): void {
        $cfg = new Config("https://example.invalid", "x", ":memory:", "@remindme", "Europe/Amsterdam", sys_get_temp_dir() . "/test.log", "test-secret", 1000, 10000);
        $db = new Db(":memory:");
        $logger = new Logger(sys_get_temp_dir() . "/test.log", "test-secret");
        $svc = new RemindMeService($db, $cfg, $logger);

        $postUrl = "https://toot.re/@someone/123456789";
        $reply = $svc->handleCommand("u1", "marcel", "s1", "in 2 days", $postUrl);

        $this->assertNotNull($reply);
        $this->assertStringContainsString("I will remind you of this post on", $reply);

        $row = $db->pdo()->query("SELECT task, reply_to_post_url FROM reminders")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame("", $row["task"]);
        $this->assertSame($postUrl, $row["reply_to_post_url"]);
    }

    public function testPostReplyReminderWithTaskStoresBoth(): void {
        $cfg = new Config("https://example.invalid", "x", ":memory:", "@remindme", "Europe/Amsterdam", sys_get_temp_dir() . "/test.log", "test-secret", 1000, 10000);
        $db = new Db(":memory:");
        $logger = new Logger(sys_get_temp_dir() . "/test.log", "test-secret");
        $svc = new RemindMeService($db, $cfg, $logger);

        $postUrl = "https://toot.re/@someone/123456789";
        $reply = $svc->handleCommand("u1", "marcel", "s1", "in 2 days about follow up", $postUrl);

        $this->assertNotNull($reply);

        $row = $db->pdo()->query("SELECT task, reply_to_post_url FROM reminders")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame("follow up", $row["task"]);
        $this->assertSame($postUrl, $row["reply_to_post_url"]);
    }

    public function testPostReplyWithoutTimeExpressionReturnsNull(): void {
        $cfg = new Config("https://example.invalid", "x", ":memory:", "@remindme", "Europe/Amsterdam", sys_get_temp_dir() . "/test.log", "test-secret", 1000, 10000);
        $db = new Db(":memory:");
        $logger = new Logger(sys_get_temp_dir() . "/test.log", "test-secret");
        $svc = new RemindMeService($db, $cfg, $logger);

        $reply = $svc->handleCommand("u1", "marcel", "s1", "great post", "https://toot.re/@someone/123456789");

        $this->assertNull($reply);
        $count = $db->pdo()->query("SELECT COUNT(*) FROM reminders")->fetchColumn();
        $this->assertSame(0, (int)$count);
    }

    public function testRegularReminderWithoutTaskStillFails(): void {
        $cfg = new Config("https://example.invalid", "x", ":memory:", "@remindme", "Europe/Amsterdam", sys_get_temp_dir() . "/test.log", "test-secret", 1000, 10000);
        $db = new Db(":memory:");
        $logger = new Logger(sys_get_temp_dir() . "/test.log", "test-secret");
        $svc = new RemindMeService($db, $cfg, $logger);

        // "remind me in 2 days" with no task and no post URL should return help text
        $reply = $svc->handleCommand("u1", "marcel", "s1", "remind me in 2 days");

        $this->assertStringContainsString("I could not understand", $reply);
        $count = $db->pdo()->query("SELECT COUNT(*) FROM reminders")->fetchColumn();
        $this->assertSame(0, (int)$count);
    }
}
