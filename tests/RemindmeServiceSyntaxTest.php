<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Carbon\CarbonImmutable;
use mbootsman\Remindme\Config;
use mbootsman\Remindme\Db;
use mbootsman\Remindme\Logger;
use mbootsman\Remindme\RemindMeService;

final class RemindMeServiceSyntaxTest extends TestCase
{
    private Config $cfg;
    private Db $db;
    private RemindMeService $svc;

    protected function setUp(): void
    {
        // Freeze time so tests are deterministic.
        // Winter date avoids DST edge cases for Europe/Amsterdam.
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

    private function fetchLastReminder(): array
    {
        $row = $this->db->pdo()
            ->query("SELECT id, task, due_at_utc FROM reminders ORDER BY id DESC LIMIT 1")
            ->fetch(PDO::FETCH_ASSOC);

        $this->assertIsArray($row);
        return $row;
    }

    private function expectedUtcFromLocal(string $localDateTime): string
    {
        return CarbonImmutable::parse($localDateTime, "Europe/Amsterdam")
            ->utc()
            ->format(DATE_ATOM);
    }

    /**
     * MVP: "in N minutes/hours/days/weeks/months"
     *
     * - minutes/hours: exact now + offset
     * - days/weeks/months: keep the time-of-day of "now" unless "at" is provided
     */
    public function testInNTimeUnitsWithoutAt(): void
    {
        // in 15 minutes
        $this->svc->handleCommand("u1", "marcel", "s1", "remind me in 15 minutes about stretch");
        $r = $this->fetchLastReminder();
        $this->assertSame("stretch", $r["task"]);
        $this->assertSame($this->expectedUtcFromLocal("2026-01-09 12:49:00"), $r["due_at_utc"]);

        // in 2 hours
        $this->svc->handleCommand("u1", "marcel", "s1", "remind me in 2 hours about drink water");
        $r = $this->fetchLastReminder();
        $this->assertSame("drink water", $r["task"]);
        $this->assertSame($this->expectedUtcFromLocal("2026-01-09 14:34:00"), $r["due_at_utc"]);

        // in 2 days (keep 12:34)
        $this->svc->handleCommand("u1", "marcel", "s1", "remind me in 2 days about pay invoice");
        $r = $this->fetchLastReminder();
        $this->assertSame("pay invoice", $r["task"]);
        $this->assertSame($this->expectedUtcFromLocal("2026-01-11 12:34:00"), $r["due_at_utc"]);

        // in 1 week (keep 12:34)
        $this->svc->handleCommand("u1", "marcel", "s1", "remind me in 1 week about renew domain");
        $r = $this->fetchLastReminder();
        $this->assertSame("renew domain", $r["task"]);
        $this->assertSame($this->expectedUtcFromLocal("2026-01-16 12:34:00"), $r["due_at_utc"]);

        // in 1 month (keep 12:34)
        $this->svc->handleCommand("u1", "marcel", "s1", "remind me in 1 month about do taxes");
        $r = $this->fetchLastReminder();
        $this->assertSame("do taxes", $r["task"]);
        $this->assertSame($this->expectedUtcFromLocal("2026-02-09 12:34:00"), $r["due_at_utc"]);
    }

    /**
     * MVP optional: "at 14:30" / "at 2pm" / "at 2:30pm"
     *
     * We test these on a date-based relative phrase (in 2 days),
     * because that is where time override matters most.
     *
     * @dataProvider timeOverrideProvider
     */
    #[DataProvider('timeOverrideProvider')]
    public function testInDaysWithAtTimeOverride(string $atToken, string $expectedLocalTime): void
    {
        $this->svc->handleCommand(
            "u1",
            "marcel",
            "s1",
            "remind me in 2 days {$atToken} about paying the invoice"
        );

        $r = $this->fetchLastReminder();
        $this->assertSame("paying the invoice", $r["task"]);

        // 2 days after 2026-01-09 is 2026-01-11, time overridden by "at ..."
        $this->assertSame(
            $this->expectedUtcFromLocal("2026-01-11 {$expectedLocalTime}:00"),
            $r["due_at_utc"]
        );
    }

    public static function timeOverrideProvider(): array
    {
        return [
            ["at 14:30", "14:30"],
            ["at 2pm", "14:00"],
            ["at 2:30pm", "14:30"],
        ];
    }

    /**
     * MVP: "tomorrow"
     * Default rule: keep the time-of-day of "now" unless "at" is provided.
     */
    public function testTomorrowWithoutAtKeepsCurrentTimeOfDay(): void
    {
        $this->svc->handleCommand("u1", "marcel", "s1", "remind me tomorrow about call the dentist");

        $r = $this->fetchLastReminder();
        $this->assertSame("call the dentist", $r["task"]);
        $this->assertSame($this->expectedUtcFromLocal("2026-01-10 12:34:00"), $r["due_at_utc"]);
    }

    public function testTomorrowWithAtOverridesTimeOfDay(): void
    {
        $this->svc->handleCommand("u1", "marcel", "s1", "remind me tomorrow at 14:30 about call the dentist");

        $r = $this->fetchLastReminder();
        $this->assertSame("call the dentist", $r["task"]);
        $this->assertSame($this->expectedUtcFromLocal("2026-01-10 14:30:00"), $r["due_at_utc"]);
    }

    /**
     * MVP: "next monday..sunday"
     * With frozen now at Friday 2026-01-09, next Monday is 2026-01-12.
     */
    public function testNextMondayWithoutAtKeepsCurrentTimeOfDay(): void
    {
        $this->svc->handleCommand("u1", "marcel", "s1", "remind me next monday about standup prep");

        $r = $this->fetchLastReminder();
        $this->assertSame("standup prep", $r["task"]);
        $this->assertSame($this->expectedUtcFromLocal("2026-01-12 12:34:00"), $r["due_at_utc"]);
    }

    public function testNextMondayWithAtOverridesTimeOfDay(): void
    {
        $this->svc->handleCommand("u1", "marcel", "s1", "remind me next monday at 2pm about standup prep");

        $r = $this->fetchLastReminder();
        $this->assertSame("standup prep", $r["task"]);
        $this->assertSame($this->expectedUtcFromLocal("2026-01-12 14:00:00"), $r["due_at_utc"]);
    }

    /**
     * MVP: "on YYYY-MM-DD"
     * Default rule: keep the time-of-day of "now" unless "at" is provided.
     */
    public function testOnDateWithoutAtKeepsCurrentTimeOfDay(): void
    {
        $this->svc->handleCommand("u1", "marcel", "s1", "remind me on 2026-01-03 about book flights");

        $r = $this->fetchLastReminder();
        $this->assertSame("book flights", $r["task"]);
        $this->assertSame($this->expectedUtcFromLocal("2026-01-03 12:34:00"), $r["due_at_utc"]);
    }

    public function testOnDateWithAtOverridesTimeOfDay(): void
    {
        $this->svc->handleCommand("u1", "marcel", "s1", "remind me on 2026-01-03 at 2:30pm about book flights");

        $r = $this->fetchLastReminder();
        $this->assertSame("book flights", $r["task"]);
        $this->assertSame($this->expectedUtcFromLocal("2026-01-03 14:30:00"), $r["due_at_utc"]);
    }
}
