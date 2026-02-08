<?php

namespace mbootsman\Remindme;

use Carbon\CarbonImmutable;
use DateTimeZone;
use PDO;

final class RemindMeService {
    public function __construct(private Db $db, private Config $cfg) {
    }

    /// Handles an incoming command and returns a reply text.
    public function handleCommand(string $userId, string $userAcct, string $sourceStatusId, string $plainText): ?string {
        $t = trim($plainText);

        if (preg_match("/^(help|\\?)$/i", $t)) {
            return $this->helpText($userAcct);
        }

        if (preg_match("/^list$/i", $t)) {
            return $this->listText($userId, $userAcct);
        }

        if (preg_match("/^(cancel|delete)\\s+(\\d+)$/i", $t, $m)) {
            return $this->cancel((int)$m[2], $userId, $userAcct);
        }

        [$dueUtc, $task] = $this->parseDueAndTask($t);

        if (!$dueUtc || $task === "") {
            // Only show help text if the user seems to be trying to set a reminder, otherwise ignore. 
            // Case insensitive match to be more user-friendly.
            if (preg_match("/\\bremind\\s+me\\b/i", $t)) { 
                return $this->helpText($userAcct, "I could not understand that reminder. Include a time and a task.");
            }
            return null; // No reply for non "remind me" mentions
        }

        $id = $this->insertReminder($userId, $userAcct, $sourceStatusId, $task, $dueUtc);

        $dueLocal = $dueUtc->setTimezone(new DateTimeZone($this->cfg->timezone));
        return "@{$userAcct} Ok! I will remind you on {$dueLocal->format('Y-m-d H:i')} (timezone: {$this->cfg->timezone}). ID: {$id}";
    }

    /**
     * Returns [dueUtc, task]. dueUtc can be null if parsing fails.
     */
    private function parseDueAndTask(string $input): array {
        $tz = new DateTimeZone($this->cfg->timezone);
        $nowLocal = CarbonImmutable::now($tz);

        $text = trim($input);
        $text = preg_replace("/^remind\\s+me\\s*/i", "", $text) ?? $text;
        // 1) "in N unit"
        if (preg_match("/\\bin\\s+(\\d+)\\s+(minutes?|hours?|days?|weeks?|months?)\\b/i", $text, $m, PREG_OFFSET_CAPTURE)) {
            $n = (int)$m[1][0];
            $unitRaw = strtolower($m[2][0]);
            $matchStart = $m[0][1];
            $matchLen = strlen($m[0][0]);

            // Optional time override: "at 09:00"
            $time = $this->extractTime($text, $matchStart + $matchLen);

            // Calculate due date in LOCAL timezone first.
            $dueLocal = match (true) {
                str_starts_with($unitRaw, "min") => $nowLocal->addMinutes($n),
                str_starts_with($unitRaw, "hour") => $nowLocal->addHours($n),
                str_starts_with($unitRaw, "day") => $nowLocal->addDays($n),
                str_starts_with($unitRaw, "week") => $nowLocal->addWeeks($n),
                str_starts_with($unitRaw, "month") => $nowLocal->addMonths($n),
                default => null
            };

            if ($dueLocal) {
                // If the phrase is date-based (days/weeks/months) and the user did not
                // provide an explicit time, default to "now" time-of-day.
                if (!$time && (str_starts_with($unitRaw, "day") || str_starts_with($unitRaw, "week") || str_starts_with($unitRaw, "month"))) {
                    $dueLocal = $dueLocal->setTime((int)$nowLocal->format("H"), (int)$nowLocal->format("i"));
                }

                // If the user DID specify a time, override it.
                if ($time) {
                    $dueLocal = $dueLocal->setTime($time["h"], $time["m"]);
                }

                $task = $this->extractTask($text, $matchStart, $matchLen, $time);
                return [$dueLocal->utc(), $task];
            }
        }

        // 2) "tomorrow"
        if (preg_match("/\\btomorrow\\b/i", $text, $m, PREG_OFFSET_CAPTURE)) {
            $matchStart = $m[0][1];
            $matchLen = strlen($m[0][0]);

            $dueLocal = CarbonImmutable::tomorrow($tz)
                ->setTime((int)$nowLocal->format("H"), (int)$nowLocal->format("i"));

            $timePos = $matchStart + $matchLen;

            $hasAt = $this->hasAtToken($text, $timePos);
            $time  = $this->extractTime($text, $timePos);

            // If user wrote "at ..." but we can't parse a valid time, treat as invalid input.
            if ($hasAt && !$time) {
                return [null, ""];
            }

            if ($time) {
                $dueLocal = $dueLocal->setTime($time["h"], $time["m"]);
            }

            $task = $this->extractTask($text, $matchStart, $matchLen, $time);
            return [$dueLocal->utc(), $task];
        }

        // 3) "next monday"
        if (preg_match("/\\bnext\\s+(monday|tuesday|wednesday|thursday|friday|saturday|sunday)\\b/i", $text, $m, PREG_OFFSET_CAPTURE)) {
            $dow = strtolower($m[1][0]);
            $matchStart = $m[0][1];
            $matchLen = strlen($m[0][0]);

            $dueLocal = CarbonImmutable::parse("next {$dow}", $tz)
                ->setTime((int)$nowLocal->format("H"), (int)$nowLocal->format("i"));

            $time = $this->extractTime($text, $matchStart + $matchLen);
            if ($time) {
                $dueLocal = $dueLocal->setTime($time["h"], $time["m"]);
            }

            $task = $this->extractTask($text, $matchStart, $matchLen, $time);
            return [$dueLocal->utc(), $task];
        }

        // 4) "on YYYY-MM-DD"
        if (preg_match("/\\bon\\s+(\\d{4}-\\d{2}-\\d{2})\\b/i", $text, $m, PREG_OFFSET_CAPTURE)) {
            $date = $m[1][0];
            $matchStart = $m[0][1];
            $matchLen = strlen($m[0][0]);

            $dateOnly = $this->parseStrictDateYmd($date);
            if (!$dateOnly) {
                return [null, ""];
            }

            $dueLocal = $dateOnly->setTime((int)$nowLocal->format("H"), (int)$nowLocal->format("i"));

            $time = $this->extractTime($text, $matchStart + $matchLen);
            if ($time) {
                $dueLocal = $dueLocal->setTime($time["h"], $time["m"]);
            }

            $task = $this->extractTask($text, $matchStart, $matchLen, $time);
            return [$dueLocal->utc(), $task];
        }

        return [null, ""];
    }

    private function extractTime(string $text, int $afterPos): ?array {
        $tail = substr($text, $afterPos);
        if ($tail === false) return null;

        /**
         * Supported formats after "at":
         * - at 14
         * - at 14:30
         * - at 14.30
         * - at 2pm / at 2 pm
         * - at 2:15pm / at 2:15 pm
         * - at 12am / at 12pm
         */
        if (!preg_match(
            "/\\bat\\s+(\\d{1,2})(?:[:\\.](\\d{2}))?\\s*(am|pm)?\\b/i",
            $tail,
            $m
        )) {
            return null;
        }

        $h = (int)$m[1];
        $min = isset($m[2]) && $m[2] !== "" ? (int)$m[2] : 0;
        $ampm = isset($m[3]) && $m[3] !== "" ? strtolower($m[3]) : null;

        // Validate minutes
        if ($min < 0 || $min > 59) return null;

        if ($ampm !== null) {
            // 12-hour clock rules: 1-12 are valid hours
            if ($h < 1 || $h > 12) return null;

            // Convert to 24-hour
            if ($ampm === "am") {
                $h = ($h === 12) ? 0 : $h;
            } else { // pm
                $h = ($h === 12) ? 12 : ($h + 12);
            }
        } else {
            // 24-hour clock rules: 0-23 are valid hours
            if ($h < 0 || $h > 23) return null;
        }

        return ["h" => $h, "m" => $min];
    }

    private function extractTask(string $text, int $matchStart, int $matchLen, ?array $time): string {
        $before = trim(substr($text, 0, $matchStart));
        $after = trim(substr($text, $matchStart + $matchLen));

        if ($time) {
            $after = preg_replace("/\\bat\\s+\\d{1,2}(?:[:\\.]\\d{2})?\\s*(?:am|pm)?\\b/i", "", $after, 1) ?? $after;
            $after = trim($after);
        }

        $candidate = trim($before . " " . $after);
        $candidate = preg_replace("/^\\s*(about|to|that)\\s+/i", "", $candidate) ?? $candidate;
        return trim($candidate);
    }

    private function insertReminder(string $userId, string $userAcct, string $sourceStatusId, string $task, CarbonImmutable $dueUtc): int {
        $pdo = $this->db->pdo();
        $stmt = $pdo->prepare("
            INSERT INTO reminders(user_id, user_acct, source_status_id, task, due_at_utc, created_at_utc)
            VALUES(:uid, :acct, :sid, :task, :due, :created)
        ");

        $nowUtc = CarbonImmutable::now("UTC")->format(DATE_ATOM);

        $stmt->execute([
            ":uid" => $userId,
            ":acct" => $userAcct,
            ":sid" => $sourceStatusId,
            ":task" => $task,
            ":due" => $dueUtc->format(DATE_ATOM),
            ":created" => $nowUtc
        ]);

        return (int)$pdo->lastInsertId();
    }

    private function helpText(string $userAcct, ?string $prefix = null): string {
        $lines = [];
        if ($prefix) $lines[] = "@{$userAcct} {$prefix}";
        $lines[] = "@{$userAcct} Try:";
        $lines[] = "- remind me in 2 days about renew domain";
        $lines[] = "- remind me tomorrow at 09:00 about call the dentist";
        $lines[] = "- remind me next monday at 10:00 about invoicing";
        $lines[] = "- remind me on 2026-01-03 at 14:30 about pay invoice";
        $lines[] = "- list";
        $lines[] = "- cancel 12";
        return implode("\n", $lines);
    }

    private function listText(string $userId, string $userAcct): string {
        $pdo = $this->db->pdo();
        $stmt = $pdo->prepare("
            SELECT id, task, due_at_utc
            FROM reminders
            WHERE user_id = :uid AND sent_at_utc IS NULL AND canceled_at_utc IS NULL
            ORDER BY due_at_utc ASC
            LIMIT 5
        ");
        $stmt->execute([":uid" => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return "@{$userAcct} No upcoming reminders.";
        }

        $tz = new DateTimeZone($this->cfg->timezone);

        $out = ["@{$userAcct} Upcoming reminders:"];
        foreach ($rows as $r) {
            $dueLocal = CarbonImmutable::parse($r["due_at_utc"], "UTC")->setTimezone($tz);
            $out[] = (int)$r["id"] . " - " . $r["task"] . " - " . $dueLocal->format("Y-m-d H:i");
        }
        return implode("\n", $out);
    }

    private function cancel(int $id, string $userId, string $userAcct): string {
        $pdo = $this->db->pdo();
        $stmt = $pdo->prepare("
            UPDATE reminders
            SET canceled_at_utc = :now
            WHERE id = :id AND user_id = :uid AND sent_at_utc IS NULL AND canceled_at_utc IS NULL
        ");

        $nowUtc = CarbonImmutable::now("UTC")->format(DATE_ATOM);

        $stmt->execute([":now" => $nowUtc, ":id" => $id, ":uid" => $userId]);

        if ($stmt->rowCount() === 0) {
            return "@{$userAcct} I could not cancel ID {$id} (maybe it does not exist, or it was already sent or canceled).";
        }

        return "@{$userAcct} Canceled reminder ID {$id}.";
    }

    private function parseStrictDateYmd(string $ymd): ?CarbonImmutable {
        // Strict parsing: must match real calendar date and must not normalize.
        // The "!" resets unspecified fields.
        $dt = CarbonImmutable::createFromFormat("!Y-m-d", $ymd, $this->cfg->timezone);

        if (!$dt) {
            return null;
        }

        // Ensure no normalization happened (e.g. 2026-02-30 -> 2026-03-02).
        if ($dt->format("Y-m-d") !== $ymd) {
            return null;
        }

        return $dt;
    }

    private function hasAtToken(string $text, int $afterPos): bool {
        $tail = substr($text, $afterPos);
        if ($tail === false) {
            return false;
        }
        return (bool)preg_match("/\\bat\\b/i", $tail);
    }
}
