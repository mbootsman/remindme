# Changelog

All notable changes to this project will be documented in this file.

## [0.1.0] - 2026-02-22

First public MVP release.

- Create reminders via Mastodon DM using natural language (e.g. "remind me in 2 days about …").
- Support core commands: `help`, `list`, `cancel`, `set timezone`.
- Per-user timezone configuration with correct UTC storage for all reminders.
- Two-cron-worker architecture: `poll.php` (ingest + commands) and `due.php` (send due reminders).
- JSONL metrics logging with HMAC-SHA256 user hashing via `LOG_SECRET`.
- Basic per-user rate limiting (per-minute and per-day) driven by environment variables.
