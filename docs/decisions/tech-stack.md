# Tech stack (language, DB, hosting)

## Status
Accepted

## Context
We want a small, reliable MVP that can be run locally and deployed cheaply. The bot must:
- Read Mastodon direct message/mention notifications
- Parse natural language reminders
- Store reminders
- Send direct message reminders at the right time

We prioritize simplicity, low operational overhead, and easy local testing.

## Decision

### Language
- PHP 8.4 (CLI-first)

Key libraries:
- guzzlehttp/guzzle (HTTP client)
- vlucas/phpdotenv (local config via .env)
- nesbot/carbon (date/time handling, test time freezing)
- phpunit/phpunit (tests)

### Database
- SQLite (via PDO + pdo_sqlite)

Reasons:
- Zero infrastructure for MVP
- Fast local dev and testing
- Easy to migrate later to Postgres if needed

### Execution model
- Polling worker: `bin/poll.php`
- Due worker: `bin/due.php`
- Run both via cron (every minute in production)

### Hosting
MVP deployment target:
- A single small VPS (Ubuntu) running cron

Requirements:
- PHP 8.4 CLI
- php8.4-sqlite3 extension enabled
- Ability to run cron jobs
- Persistent disk for the SQLite database file

Optional future:
- Containerized deployment (Docker) if we want portability.
- Managed Postgres when SQLite is outgrown.

## Consequences
Positive:
- Very fast iteration locally
- Minimal infrastructure
- Simple operational story (cron + SQLite)

Negative:
- Polling is less real-time than streaming
- SQLite requires careful handling if we later add concurrency or multiple instances
- Hosting as a single VPS is a single point of failure (acceptable for MVP)