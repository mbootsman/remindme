<?php

namespace mbootsman\Remindme;

use Carbon\CarbonImmutable;

/**
 * Logger
 * 
 * Writes non-PII metrics to JSONL (JSON Lines) format.
 * Each line is a complete JSON object representing one event.
 * 
 * User privacy: user_id is hashed with HMAC-SHA256 using a secret key.
 * This prevents rainbow table attacks and pre-computed hash lookups.
 */
final class Logger {
    private string $logPath;
    private string $logSecret;

    public function __construct(string $logPath, string $logSecret = "") {
        $this->logPath = $logPath;
        $this->logSecret = $logSecret;
        // Ensure parent directory exists.
        $dir = dirname($logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }

    /**
     * Log a reminder creation event.
     * 
     * @param string $userId Mastodon user ID (will be hashed)
     * @param CarbonImmutable $dueUtc Due time in UTC
     * @param CarbonImmutable $nowUtc Current time in UTC (for calculating days_until_due)
     */
    public function logReminderCreated(string $userId, CarbonImmutable $dueUtc, CarbonImmutable $nowUtc): void {
        $daysDiff = $dueUtc->diffInDays($nowUtc, false);
        $timeOfDay = $this->timeOfDay($dueUtc);

        $event = [
            "event" => "reminder.created",
            "count" => 1,
            "days_until_due" => $daysDiff,
            "time_of_day" => $timeOfDay,
            "user_hash" => $this->hashUserId($userId),
            "timestamp" => $nowUtc->format(DATE_ATOM)
        ];

        $this->write($event);
    }

    /**
     * Log a reminder sent event.
     */
    public function logReminderSent(): void {
        $event = [
            "event" => "reminder.sent",
            "count" => 1,
            "timestamp" => CarbonImmutable::now("UTC")->format(DATE_ATOM)
        ];

        $this->write($event);
    }

    /**
     * Log a reminder canceled event.
     */
    public function logReminderCanceled(): void {
        $event = [
            "event" => "reminder.canceled",
            "count" => 1,
            "timestamp" => CarbonImmutable::now("UTC")->format(DATE_ATOM)
        ];

        $this->write($event);
    }

    /**
     * Log a command issued (help, list, cancel, invalid).
     */
    public function logCommand(string $type): void {
        $event = [
            "event" => "command",
            "command_type" => $type,
            "timestamp" => CarbonImmutable::now("UTC")->format(DATE_ATOM)
        ];

        $this->write($event);
    }

    /**
     * Log an API error.
     */
    public function logApiError(string $errorType, string $message = ""): void {
        $event = [
            "event" => "api_error",
            "error_type" => $errorType,
            "message" => $message,
            "timestamp" => CarbonImmutable::now("UTC")->format(DATE_ATOM)
        ];

        $this->write($event);
    }

    /**
     * Log a parsing error (user input that couldn't be parsed).
     */
    public function logParsingError(): void {
        $event = [
            "event" => "parsing_error",
            "timestamp" => CarbonImmutable::now("UTC")->format(DATE_ATOM)
        ];

        $this->write($event);
    }

    /**
     * Write an event as a single JSON line.
     */
    private function write(array $event): void {
        $json = json_encode($event, JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return; // Silently fail on encode error to avoid breaking the bot.
        }

        $handle = fopen($this->logPath, "a");
        if ($handle) {
            fwrite($handle, $json . "\n");
            fclose($handle);
        }
    }

    /**
     * Hash a user ID so we can count unique users without storing PII.
     * Uses HMAC-SHA256 with a secret key to prevent rainbow table attacks.
     * Truncated to 8 characters for readability.
     */
    private function hashUserId(string $userId): string {
        if ($this->logSecret === "") {
            // Fallback to plain SHA-256 if no secret is configured
            return substr(hash("sha256", $userId), 0, 8);
        }
        $hmac = hash_hmac("sha256", $userId, $this->logSecret);
        return substr($hmac, 0, 8);
    }

    /**
     * Categorize time into rough periods for analysis.
     */
    private function timeOfDay(CarbonImmutable $time): string {
        $hour = (int)$time->format("H");

        return match (true) {
            $hour >= 5 && $hour < 12 => "morning",
            $hour >= 12 && $hour < 17 => "afternoon",
            $hour >= 17 && $hour < 21 => "evening",
            default => "night"
        };
    }
}
