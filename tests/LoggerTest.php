<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Carbon\CarbonImmutable;
use mbootsman\Remindme\Logger;

final class LoggerTest extends TestCase {
    private function tempLogPath(): string {
        return sys_get_temp_dir() . "/remindme_logger_test_" . bin2hex(random_bytes(6)) . ".log";
    }

    public function testLogReminderCreatedWithSecretProducesHmacAndTimeOfDay(): void {
        $path = $this->tempLogPath();
        if (file_exists($path)) unlink($path);

        $secret = "my-test-secret";
        $logger = new Logger($path, $secret);

        $due = CarbonImmutable::parse("2026-02-10 08:15", "UTC");
        $now = CarbonImmutable::parse("2026-02-09 08:15", "UTC");

        $logger->logReminderCreated("42", $due, $now);

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->assertCount(1, $lines, "Expected one JSONL line in the log");

        $obj = json_decode($lines[0], true);
        $this->assertIsArray($obj);
        $this->assertSame("reminder.created", $obj["event"]);
        $this->assertArrayHasKey("user_hash", $obj);
        $this->assertSame("morning", $obj["time_of_day"]);

        $expected = substr(hash_hmac('sha256', "42", $secret), 0, 8);
        $this->assertSame($expected, $obj["user_hash"]);

        unlink($path);
    }

    public function testHashFallbackWithoutSecretUsesPlainSha256Prefix(): void {
        $path = $this->tempLogPath();
        if (file_exists($path)) unlink($path);

        $logger = new Logger($path, "");

        $due = CarbonImmutable::parse("2026-02-10 20:00", "UTC");
        $now = CarbonImmutable::parse("2026-02-09 20:00", "UTC");

        $logger->logReminderCreated("user-xyz", $due, $now);

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->assertCount(1, $lines);
        $obj = json_decode($lines[0], true);

        $expected = substr(hash('sha256', "user-xyz"), 0, 8);
        $this->assertSame($expected, $obj["user_hash"]);

        unlink($path);
    }

    public function testMultipleEventsAreWrittenAsSeparateJsonLines(): void {
        $path = $this->tempLogPath();
        if (file_exists($path)) unlink($path);

        $logger = new Logger($path, "s");

        $logger->logCommand("help");
        $logger->logParsingError();
        $logger->logReminderCanceled();
        $logger->logReminderSent();

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->assertCount(4, $lines);

        $events = array_map(fn($l) => json_decode($l, true)["event"], $lines);
        $this->assertSame(["command", "parsing_error", "reminder.canceled", "reminder.sent"], $events);

        unlink($path);
    }
}
