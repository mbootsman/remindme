<?php

namespace mbootsman\Remindme;

use PDO;

/**
 * Db
 *
 * SQLite wrapper responsible for:
 * - Creating the SQLite file path (data/ folder)
 * - Running migrations on startup
 * - Providing a simple key/value state store (used for since_id paging)
 *
 * Why SQLite:
 * - Perfect for MVP and local dev
 * - Easy to move to Postgres later by swapping this layer
 */

final class Db {
    private PDO $pdo;

    /**
     * @param string $path Absolute or relative path to the SQLite database file.
     */
    public function __construct(string $path) {
        // Ensure parent dir exists so SQLite can create the file.
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        // SQLite connection via PDO
        $this->pdo = new PDO("sqlite:" . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // WAL improves concurrency and durability for "cron every minute" style workloads.
        $this->pdo->exec("PRAGMA journal_mode=WAL;");
        $this->migrate();
    }

    /**
     * Apply schema migrations.
     *
     * For now: simple CREATE TABLE IF NOT EXISTS statements.
     * Later: we can add a schema_version table and incremental migrations.
     */
    private function migrate(): void {
        // "state" holds small pieces of bot state, like last_notification_id.
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS state (
                k TEXT PRIMARY KEY,
                v TEXT NOT NULL
            );
        ");

        // "reminders" is the core table.
        // We store due_at in UTC so comparisons are always correct.
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS reminders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id TEXT NOT NULL,
                user_acct TEXT NOT NULL,
                source_status_id TEXT NOT NULL,
                task TEXT NOT NULL,
                due_at_utc TEXT NOT NULL,
                created_at_utc TEXT NOT NULL,
                sent_at_utc TEXT NULL,
                canceled_at_utc TEXT NULL
            );
        ");

        // Index to speed up the due-reminder query.
        $this->pdo->exec("
            CREATE INDEX IF NOT EXISTS idx_due
            ON reminders(due_at_utc)
            WHERE sent_at_utc IS NULL AND canceled_at_utc IS NULL;
        ");
    }

    /**
     * Read a value from the key/value state store.
     *
     * @param string $key
     * @param string|null $default Returned when the key does not exist.
     */

    public function get(string $key, ?string $default = null): ?string {
        $stmt = $this->pdo->prepare("SELECT v FROM state WHERE k = :k");
        $stmt->execute([":k" => $key]);
        $v = $stmt->fetchColumn();
        return $v === false ? $default : (string)$v;
    }

    /**
     * Write a value to the key/value state store.
     *
     * Uses an UPSERT so it works for both create and update.
     */
    public function set(string $key, string $value): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO state(k, v) VALUES(:k, :v)
            ON CONFLICT(k) DO UPDATE SET v = excluded.v
        ");
        $stmt->execute([":k" => $key, ":v" => $value]);
    }

    /**
     * Expose the raw PDO connection for queries in services.
     */
    public function pdo(): PDO {
        return $this->pdo;
    }
}
