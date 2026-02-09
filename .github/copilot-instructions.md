# RemindMe - AI Coding Assistant Instructions

## Project Overview
RemindMe is a Mastodon reminder bot that lets users create reminders via direct message using natural language. The MVP uses PHP 8.1+, SQLite, Carbon for date handling, and two polling cron workers. This is an **early-stage project** with active development.

## Architecture Essentials

### Two-Worker Pattern
The bot operates via two separate cron workers that run every minute:
- **`bin/poll.php`**: Fetches new DM and mention notifications, parses commands, and replies to users
  - Manages `last_notification_id` state for pagination
  - Accepts **all visibility types** on initial fetch, but enforces privacy rule: **only processes direct messages for commands** (rejects public/unlisted mentions with a polite redirect to DM)
  - Routes commands (help, list, cancel) or creates reminders
- **`bin/due.php`**: Sends reminders that are due, marks `sent_at_utc` to prevent duplicates

### Core Components
- **`RemindMeService`** (src/RemindMeService.php): Command parsing & business logic
  - `handleCommand()` is the main entry point - routes to help/list/cancel/reminder parsing
  - `parseDueAndTask()` extracts due_utc and task text using Carbon
- **`Db`** (src/Db.php): SQLite wrapper with WAL pragma for concurrent cron safety
  - Two tables: `state` (k/v pairs like last_notification_id) and `reminders`
- **`Logger`** (src/Logger.php): JSONL metrics logging (non-PII, always-on)
  - Logs: reminders created/sent/canceled, commands, parsing errors
  - Hashes user IDs with HMAC-SHA256 (using LOG_SECRET) to prevent rainbow table attacks
  - Categorizes reminder times (morning/afternoon/evening/night) for trend analysis
- **`MastodonHttp`** (src/MastodonHttp.php): HTTP client wrapper around Guzzle
- **`Text`** (src/Text.php): Static helper methods for HTML→text conversion and command detection
- **`Config`** (src/Config.php): Environment variable reader (no .env access elsewhere)

### Data Model
Reminders table stores:
- `user_id`, `user_acct`: Mastodon account identifiers
- `task`: Reminder text (parsed from user input)
- `due_at_utc`: Calculated due time (UTC always, converted from user timezone)
- `sent_at_utc`: Set when reminder is sent (null = unsent)
- `canceled_at_utc`: Set when canceled (null = active)
- `source_status_id`: Original status that triggered the reminder

**Time handling**: All stored times are UTC. Parsing uses `DEFAULT_TIMEZONE` config. For relative dates like "in 2 days", the default time-of-day is creation time unless "at..." is specified.

## Testing & Development

### Running Tests
```bash
composer install
vendor/bin/phpunit
# To see reminder output: PRINT_REMINDERS=1 vendor/bin/phpunit
```

### Test Patterns
- Tests use `CarbonImmutable::setTestNow()` to freeze time for predictable results
- Prefer in-memory SQLite (`:memory:`) for isolation
- See [RemindmeServiceTest.php](tests/RemindmeServiceTest.php) and [RemindmeServiceSyntaxTest.php](tests/RemindmeServiceSyntaxTest.php)

### Local Development
1. `composer install`
2. Copy `.env.example` to `.env`
3. Add Mastodon access token to `.env` (needs: read:notifications, write:statuses)
4. Run `php bin/poll.php` and `php bin/due.php` manually to test

## Key Patterns & Conventions

### Command Routing
All user input flows through `RemindMeService::handleCommand()`:
- Built-in commands: `help/?` (show syntax), `list` (show active reminders), `cancel <id>` (deactivate reminder)
- Default: parse as reminder creation (`parseDueAndTask()`)
- Return `null` to suppress reply; return string to send as @-mention reply

### Null-Safe Patterns
The codebase extensively uses `??` operators for API data extraction (Mastodon payloads are inconsistent). Example:
```php
$uid = $n["account"]["id"] ?? null;
if (!$uid) continue;
```

### Bootstrap Behavior
On first run, `poll.php` stores the newest notification_id to prevent retroactively processing old mentions. Always handle the bootstrap case when working with state.

### Privacy: Direct Messages Only (for Commands)
The service accepts all message visibilities but **only processes commands in direct messages**. Public/unlisted mentions that look like commands trigger a polite redirect to DM. This distinction is important for privacy while allowing mention-based discovery.

### Configuration
All config comes from `.env` via `Config::fromEnv()`. Required vars:
- `MASTODON_BASE_URL`: Instance URL (rtrim'd of trailing slash)
- `MASTODON_ACCESS_TOKEN`: Bot account token
- `DB_PATH`: Path to SQLite file (e.g., `data/remindme.sqlite`)
- `BOT_HANDLE`: Bot mention handle (e.g., `@remindme`)
- `DEFAULT_TIMEZONE`: Default timezone for parsing (e.g., `Europe/Amsterdam`)
- `LOG_PATH`: JSONL metrics log file (default: `logs/remindme.log`, optional)
- `LOG_SECRET`: Secret key for HMAC hashing of user IDs in logs (prevents rainbow table attacks; generated on setup)

## Common Tasks & Where to Look

| Task | Key Files |
|------|-----------|
| Add new command | `RemindMeService::handleCommand()`, add case before default reminder parsing |
| Change parsing logic | `RemindMeService::parseDueAndTask()`, uses Carbon for relative dates |
| Adjust API behavior | `MastodonHttp`, Guzzle-based wrapper |
| Add a database field | `Db::migrate()`, update reminder insertion/queries |
| Improve text handling | `Text` static methods (HTML→plain, command detection) |

## Dependencies
- **Carbon 3.x**: Date/time parsing and manipulation
- **Guzzle 7.x**: HTTP client for Mastodon API
- **phpdotenv 5.x**: Environment variable loading
- **PHPUnit 12.x**: Testing (dev only)
- **SQLite 3**: Database (PHP ext-sqlite3)

## Documentation Files
- [docs/architecture.md](docs/architecture.md): Deep dive on components and time handling
- [docs/mvp.md](docs/mvp.md): Supported date phrase syntax
- [docs/ops.md](docs/ops.md): Deployment, cron setup, backups
- [docs/CONTRIBUTING.md](docs/CONTRIBUTING.md): PR and code style guidelines
