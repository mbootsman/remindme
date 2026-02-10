# Operations

## Local development
Requirements:
- PHP 8.4 CLI
- php8.4-sqlite3 installed and enabled
- Composer

Setup:
1) composer install
2) cp .env.example .env
3) Set Mastodon token in .env
4) **Regenerate LOG_SECRET** for security: `php -r "echo bin2hex(random_bytes(32));"` and copy to .env
5) Run tests: vendor/bin/phpunit

Manual run:
- php bin/poll.php
- php bin/due.php

## Production (VPS + cron)
Install:
- PHP 8.4 CLI
- php8.4-sqlite3
- cron enabled

Cron (every minute):
* * * * * php /path/to/repo/bin/poll.php
* * * * * php /path/to/repo/bin/due.php

## Backups
- Back up the SQLite file: data/remindme.sqlite
- Consider stopping workers briefly or using SQLite backup tooling if you start doing frequent backups.

### Logging
The bot writes non-PII metrics to `logs/remindme.log` in JSONL format (one JSON object per line).

**Security**: User IDs are hashed with HMAC-SHA256 using the `LOG_SECRET` key, preventing rainbow table attacks and pre-computed hash lookups.

### Log events
- `reminder.created`: Reminder created (includes days_until_due, time_of_day categorization, anonymized user hash)
- `reminder.sent`: Reminder delivered
- `reminder.canceled`: Reminder canceled
- `command`: Built-in command executed (help, list, cancel)
- `parsing_error`: Failed to parse user input as reminder
- `api_error`: Mastodon API error

### Querying logs
```bash
# Count created reminders
grep reminder.created logs/remindme.log | wc -l

# Count unique users (last 24h, anonymized hashes)
grep reminder.created logs/remindme.log | jq -r .user_hash | sort -u | wc -l

# Peak times for reminders
grep reminder.created logs/remindme.log | jq -r .time_of_day | sort | uniq -c

# Average days until due
grep reminder.created logs/remindme.log | jq -r .days_until_due | awk '{sum+=$1} END {print sum/NR}'
```

### Log rotation
Use `logrotate` or similar to manage log file growth:
```bash
/path/to/logs/remindme.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
}
```

## Secrets
- Store `MASTODON_ACCESS_TOKEN` in .env on the server
- Store `LOG_SECRET` in .env on the server (regenerate per deployment with `php -r "echo bin2hex(random_bytes(32));"`; .env.example provides a starter key)
- Never commit .env

## Rate limiting

The bot enforces simple per-user rate limits to prevent misuse. These are configurable via environment variables:

- `RATE_LIMIT_PER_MINUTE` — maximum number of reminders a single user may create within a 60-second window (default: `3`).
- `RATE_LIMIT_PER_DAY` — maximum number of reminders a single user may create within 24 hours (default: `50`).

Adjust these values in your `.env` for the instance. The service enforces the limits and will reject attempts that exceed them; rejected creations are rolled back server-side.