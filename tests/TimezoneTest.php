<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Carbon\CarbonImmutable;
use mbootsman\Remindme\Config;
use mbootsman\Remindme\Db;
use mbootsman\Remindme\Logger;
use mbootsman\Remindme\RemindMeService;

final class TimezoneTest extends TestCase
{
    private Config $cfg;
    private Db $db;
    private RemindMeService $svc;

    protected function setUp(): void
    {
        // Freeze time for predictable tests
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse("2026-01-09 12:34:00", "Europe/Amsterdam")
        );

        $this->cfg = new Config(
            "https://example.invalid",
            "x",
            ":memory:",
            "@remindme",
            "Europe/Amsterdam",
            sys_get_temp_dir() . "/test.log",
            "test-secret",
            1000,
            10000
        );

        $this->db = new Db(":memory:");
        $logger = new Logger(sys_get_temp_dir() . "/test.log", "test-secret");
        $this->svc = new RemindMeService($this->db, $this->cfg, $logger);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
    }

    public function testSetValidTimezone(): void
    {
        $reply = $this->svc->handleCommand("u1", "marcel", "s1", "set timezone America/New_York");
        
        $this->assertStringContainsString("Timezone set to America/New_York", $reply);

        // Verify it was stored
        $stmt = $this->db->pdo()->prepare("SELECT timezone FROM user_settings WHERE user_id = ?");
        $stmt->execute(["u1"]);
        $tz = $stmt->fetchColumn();
        $this->assertSame("America/New_York", $tz);
    }

    public function testSetInvalidTimezone(): void
    {
        $reply = $this->svc->handleCommand("u1", "marcel", "s1", "set timezone InvalidTimezone");
        
        $this->assertStringContainsString("Invalid timezone", $reply);

        // Verify it was NOT stored
        $stmt = $this->db->pdo()->prepare("SELECT COUNT(*) FROM user_settings WHERE user_id = ?");
        $stmt->execute(["u1"]);
        $count = (int)$stmt->fetchColumn();
        $this->assertSame(0, $count);
    }

    public function testReminderUsesUserTimezone(): void
    {
        // First set user's timezone to New York (UTC-5 in winter)
        $this->svc->handleCommand("u1", "marcel", "s1", "set timezone America/New_York");

        // Now create a reminder "in 1 day" at the current time of day
        // Current time in Amsterdam: 2026-01-09 12:34:00
        // Current time in New York: 2026-01-09 06:34:00
        // In 1 day at same time in New York: 2026-01-10 06:34:00 EST
        $reply = $this->svc->handleCommand("u1", "marcel", "s1", "remind me in 1 day about test");

        $this->assertStringContainsString("Ok! I will remind you on", $reply);
        $this->assertStringContainsString("America/New_York", $reply);

        $stmt = $this->db->pdo()->prepare("SELECT due_at_utc FROM reminders WHERE user_id = ?");
        $stmt->execute(["u1"]);
        $dueUtc = $stmt->fetchColumn();

        // Convert back to check:
        // Expected: 2026-01-10 06:34:00 in New York = 2026-01-10 11:34:00 UTC
        $expectedUtc = CarbonImmutable::parse("2026-01-10 06:34:00", "America/New_York")
            ->utc()
            ->format(DATE_ATOM);

        $this->assertSame($expectedUtc, $dueUtc);
    }

    public function testListShowsRemindersInUserTimezone(): void
    {
        // Set user's timezone
        $this->svc->handleCommand("u1", "marcel", "s1", "set timezone America/New_York");

        // Create a reminder
        $this->svc->handleCommand("u1", "marcel", "s1", "remind me tomorrow at 14:00 about meeting");

        // List reminders
        $reply = $this->svc->handleCommand("u1", "marcel", "s1", "list");

        $this->assertStringContainsString("Upcoming reminders:", $reply);
        $this->assertStringContainsString("meeting", $reply);
        
        // The time should be displayed in user's timezone (New York)
        // Tomorrow in Amsterdam at 14:00 = Tomorrow in New York at 08:00 (because New York is 6 hours behind in winter)
        // So in the list we should see "2026-01-10 08:00" or similar in New York time
        $this->assertStringContainsString("2026-01-10", $reply);
    }

    public function testDefaultsToServerTimezoneIfNotSet(): void
    {
        // Create a reminder without setting a user timezone
        $this->svc->handleCommand("u2", "alice", "s1", "remind me in 1 day about default tz");

        $reply = $this->svc->handleCommand("u2", "alice", "s1", "list");

        // Should show default timezone (Europe/Amsterdam)
        $this->assertStringContainsString("default tz", $reply);

        // The reminder should use Amsterdam time
        $stmt = $this->db->pdo()->prepare("SELECT due_at_utc FROM reminders WHERE user_id = ?");
        $stmt->execute(["u2"]);
        $dueUtc = $stmt->fetchColumn();

        // Expected: tomorrow at 12:34 in Amsterdam
        $expectedUtc = CarbonImmutable::parse("2026-01-10 12:34:00", "Europe/Amsterdam")
            ->utc()
            ->format(DATE_ATOM);

        $this->assertSame($expectedUtc, $dueUtc);
    }

    public function testUpdateTimezone(): void
    {
        // Set initial timezone
        $this->svc->handleCommand("u1", "marcel", "s1", "set timezone America/New_York");

        // Update to different timezone
        $this->svc->handleCommand("u1", "marcel", "s1", "set timezone Asia/Tokyo");

        // Verify it was updated
        $stmt = $this->db->pdo()->prepare("SELECT timezone FROM user_settings WHERE user_id = ?");
        $stmt->execute(["u1"]);
        $tz = $stmt->fetchColumn();
        $this->assertSame("Asia/Tokyo", $tz);
    }

    public function testMultipleUsersIndependentTimezones(): void
    {
        // Set up: current time frozen at 2026-01-09 12:34:00 in Amsterdam (UTC+1)
        // This is 2026-01-09 11:34:00 UTC
        // This is 2026-01-09 06:34:00 in New York (UTC-5)
        // This is 2026-01-09 20:34:00 in Tokyo (UTC+9)

        // User 1 sets one timezone
        $this->svc->handleCommand("u1", "marcel", "s1", "set timezone America/New_York");

        // User 2 sets another
        $this->svc->handleCommand("u2", "alice", "s2", "set timezone Asia/Tokyo");

        // User 3 doesn't set any (uses default Amsterdam)

        // Create reminders for each "in 1 day"
        // - User 1: in 1 day from 06:34 NY = 2026-01-10 06:34 NY time = 2026-01-10 11:34 UTC
        // - User 2: in 1 day from 20:34 Tokyo = 2026-01-10 20:34 Tokyo time = 2026-01-10 11:34 UTC
        // - User 3: in 1 day from 12:34 Amsterdam = 2026-01-10 12:34 Amsterdam time = 2026-01-10 11:34 UTC

        $this->svc->handleCommand("u1", "marcel", "s1", "remind me in 1 day about u1 reminder");
        $this->svc->handleCommand("u2", "alice", "s2", "remind me in 1 day about u2 reminder");
        $this->svc->handleCommand("u3", "bob", "s3", "remind me in 1 day about u3 reminder");

        // Verify each has the SAME UTC time because they use the same local time-of-day in their timezone
        $stmt = $this->db->pdo()->prepare("SELECT user_id, due_at_utc FROM reminders ORDER BY user_id");
        $stmt->execute();
        $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // All should be 2026-01-10 11:34:00 UTC because they all use current time-of-day + 1 day
        $expectedUtc = CarbonImmutable::parse("2026-01-10 11:34:00", "UTC")
            ->format(DATE_ATOM);

        $this->assertSame($expectedUtc, $reminders[0]["due_at_utc"]);
        $this->assertSame($expectedUtc, $reminders[1]["due_at_utc"]);
        $this->assertSame($expectedUtc, $reminders[2]["due_at_utc"]);
    }

    public function testConfirmationMessageShowsUserTimezone(): void
    {
        $this->svc->handleCommand("u1", "marcel", "s1", "set timezone Asia/Tokyo");
        $reply = $this->svc->handleCommand("u1", "marcel", "s1", "remind me tomorrow at 14:00 about meeting");

        $this->assertStringContainsString("Asia/Tokyo", $reply);
    }
}
