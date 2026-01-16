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
4) Run tests: vendor/bin/phpunit

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

## Logging
MVP:
- stdout/stderr via cron mail or redirected to a logfile
Suggested:
- redirect cron output to logs/remindme.log (and rotate it)

## Secrets
- Store MASTODON_ACCESS_TOKEN in .env on the server
- Never commit .env