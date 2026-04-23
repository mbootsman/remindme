# Changelog

All notable changes to this project will be documented in this file.

## [0.3.0] - 2026-04-23

### Added
- Natural month-name dates: `on June 15`, `on March 5` are now accepted in addition to `on YYYY-MM-DD`. The date resolves to the next occurrence of that month/day.
- Written-out numbers in time expressions: `in five minutes`, `in two days`, `in three weeks` now work alongside digit forms like `in 5 minutes`.
- Dashboard timezone support.

### Fixed
- `poll.php` silently crashed on any API error (expired token, network failure, HTTP error response), leaving `last_notification_id` frozen and all subsequent messages unprocessed. Errors are now written to stderr so cron captures them, and a failure in one notification no longer aborts the rest of the batch.

## [0.2.1] - 2026-03-28

- Include original post URL in both the confirmation DM and the reminder DM for post reminders.
- Fix dashboard reminders table to show post URL as a clickable link.
- Update architecture docs to reflect public reply flow and `reply_to_post_url` column.

## [0.2.0] - 2026-03-28

- Reply to any public post with `@remindme in 2 days` (or any supported time expression) to get reminded of that post. The bot will send the reminder as a DM with a link back to the original post.

## [0.1.1] - 2026-02-22

Small UX and documentation improvements.

- Improve public `@remindme help` behavior: keep a privacy-friendly public reply and also send a direct message with full instructions.
- Update examples and docs (README, help text, user guide) to use `@remindme …` as the primary command form.

## [0.1.0] - 2026-02-22

First public MVP release.

- Create reminders via Mastodon DM using natural language (e.g. "@remindme in 2 days about …").
- Support core commands: `help`, `list`, `cancel`, `set timezone`.
- Per-user timezone configuration with correct UTC storage for all reminders.
- Two-cron-worker architecture: `poll.php` (ingest + commands) and `due.php` (send due reminders).
- JSONL metrics logging with HMAC-SHA256 user hashing via `LOG_SECRET`.
- Basic per-user rate limiting (per-minute and per-day) driven by environment variables.
