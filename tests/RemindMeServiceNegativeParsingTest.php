<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Carbon\CarbonImmutable;
use mbootsman\Remindme\Config;
use mbootsman\Remindme\Db;
use mbootsman\Remindme\Logger;
use mbootsman\Remindme\RemindMeService;

final class RemindMeServiceNegativeParsingTest extends TestCase
{
    private Db $db;
    private RemindMeService $svc;

    protected function setUp(): void
    {
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse("2026-01-09 12:34:00", "Europe/Amsterdam")
        );

        $cfg = new Config(
            "https://example.invalid",
            "token",
            ":memory:",
            "@remindme",
            "Europe/Amsterdam",
            sys_get_temp_dir() . "/test.log",
            "test-secret"
        );

        $this->db = new Db(":memory:");
        $logger = new Logger(sys_get_temp_dir() . "/test.log", "test-secret");
        $this->svc = new RemindMeService($this->db, $cfg, $logger);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
    }

    private function reminderCount(): int
    {
        return (int)$this->db->pdo()->query("SELECT COUNT(*) FROM reminders")->fetchColumn();
    }

    #[DataProvider('invalidInputsProvider')]
    public function testInvalidInputsDoNotCreateReminders(string $input): void
    {
        $before = $this->reminderCount();

        $reply = $this->svc->handleCommand("u1", "marcel", "s1", $input);

        $after = $this->reminderCount();

        // No DB changes
        $this->assertSame($before, $after, "No reminders should be inserted for invalid input");

        // Should respond with the standard failure+help output
        $this->assertStringContainsString("I could not understand that reminder", $reply);
        $this->assertStringContainsString("@marcel Here are some instructions on how to use me. Try:", $reply);
    }

    public static function invalidInputsProvider(): array
    {
        return [
            // Missing number
            ["remind me in days about pay invoice"],
            ["remind me in hours about pay invoice"],

            // Non-numeric number
            ["remind me in two days about pay invoice"],

            // Missing unit
            ["remind me in 2 about pay invoice"],

            // Unsupported unit
            ["remind me in 2 fortnights about pay invoice"],
            ["remind me in 1 years about pay invoice"],

            // Unsupported shortcut spelling
            ["remind me in 5 min about pay invoice"],

            // Invalid day names
            ["remind me next moonday about standup"],

            // Invalid date formats
            ["remind me on 2026/01/03 about book flights"],
            ["remind me on 03-01-2026 about book flights"],

            // Invalid calendar dates (should not parse as on YYYY-MM-DD)
            ["remind me on 2026-13-03 about book flights"],
            ["remind me on 2026-00-10 about book flights"],
            ["remind me on 2026-01-00 about book flights"],
            ["remind me on 2026-02-30 about book flights"],

            // Invalid time (24h)
            ["remind me tomorrow at 25:00 about call dentist"],
            ["remind me tomorrow at 24:01 about call dentist"],
            ["remind me tomorrow at 14:99 about call dentist"],

            // Invalid time (12h)
            ["remind me tomorrow at 13pm about call dentist"],
            ["remind me tomorrow at 0pm about call dentist"],
            ["remind me tomorrow at 2xm about call dentist"],

            // Missing task
            ["remind me in 2 days"],
            ["remind me tomorrow"],
            ["remind me next monday"],
            ["remind me on 2026-01-03"],
        ];
    }
}