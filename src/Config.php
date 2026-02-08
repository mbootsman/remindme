<?php
namespace mbootsman\Remindme;

use Dotenv\Dotenv;
/**
 * Config
 *
 * Central place to read configuration for the bot from environment variables.
 * We keep this separate so the rest of the code stays testable and does not
 * directly depend on getenv().
 *
 * Values come from .env (via vlucas/phpdotenv).
 */

final class Config {
    public function __construct(
        public readonly string $baseUrl,
        public readonly string $accessToken,
        public readonly string $dbPath,
        public readonly string $botHandle,
        public readonly string $timezone
    ) {
    }

    /**
     * Build a Config instance from environment variables.
     *
     * Throws RuntimeExceptionif any required variable is missing on startup.
     */
    public static function fromEnv(): self {
        
        $root = dirname(__DIR__);
        Dotenv::createImmutable($root)->load();

        /** Base URL of the Mastodon instance, e.g. https://mastodon.social */
        $baseUrl = rtrim((string)$_ENV["MASTODON_BASE_URL"], "/");
        /** Access token for the bot account (needs read notifications + post statuses) */
        $token   = (string)$_ENV["MASTODON_ACCESS_TOKEN"];
        /** Path to the SQLite database file, e.g. data/remindme.sqlite */
        $dbPath  = (string)$_ENV["DB_PATH"];
        /** Bot handle used to strip leading "@remindme" from incoming text */
        $handle  = (string)$_ENV["BOT_HANDLE"];
        /** Default timezone for parsing and formatting reminders */
        $tz      = (string)$_ENV["DEFAULT_TIMEZONE"] ?: "UTC";

        if ($baseUrl === "" || $token === "" || $dbPath === "" || $handle === "") {
            throw new \RuntimeException("Missing required env vars. Check .env");
        }

        return new self($baseUrl, $token, $dbPath, $handle, $tz);
    }
}
