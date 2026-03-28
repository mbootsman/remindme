# Architecture

## Overview
The system is a Mastodon reminder bot that accepts direct messages and mentions and later sends reminders via direct message.

The MVP uses polling, a SQLite database, and two cron-run workers.

## Components

### 1) Polling worker (bin/poll.php)
Responsibilities:
- Fetch new mention notifications from Mastodon using since_id paging
- Convert status HTML to plain text
- Route by visibility:
  - **direct**: accept all commands (help, list, cancel, set timezone, create reminder)
  - **public reply to a post**: if the mention contains a time expression, create a reminder for the original post and confirm via DM; otherwise redirect to DM with instructions
  - **other public mentions**: redirect to DM with instructions
- Reply to the user via a direct message

State:
- Reads and updates `state.last_notification_id` in SQLite

### 2) Due worker (bin/due.php)
Responsibilities:
- Query due reminders where:
  - due_at_utc <= now_utc
  - sent_at_utc is null
  - canceled_at_utc is null
- Send each reminder via direct message
- Mark each reminder as sent
- Once sent, `sent_at_utc` is set so it is not sent again.

## Data model

### Table: state
- k (primary key)
- v

Used for:
- last_notification_id

### Table: reminders
- id (primary key autoincrement)
- user_id (Mastodon account id)
- user_acct (acct string, e.g. "user" or "user@instance")
- source_status_id (status id that created the reminder)
- task (reminder text)
- due_at_utc (ISO8601 string)
- created_at_utc (ISO8601 string)
- sent_at_utc (nullable ISO8601 string)
- canceled_at_utc (nullable ISO8601 string)
- reply_to_post_url (nullable URL string — set when reminder was created from a public reply to a post)

### Table: user_settings
- user_id (primary key, Mastodon account id)
- user_acct (acct string)
- timezone (IANA timezone string, e.g. "Europe/Amsterdam")
- updated_at_utc (ISO8601 string)

## Time handling
- All stored times are UTC (due_at_utc, created_at_utc, sent_at_utc, canceled_at_utc)
- Parsing and display use the user's configured timezone (from user_settings), falling back to DEFAULT_TIMEZONE if not set
- For relative date-based phrases like "in 2 days", the default time-of-day is the time the reminder was created unless "at ..." is supplied.

## Parsing (MVP)
Supported time phrases:
  - in N minutes/hours/days/weeks/months
  - tomorrow
  - next monday..sunday
  - on YYYY-MM-DD
  - optional: at 14:30 / at 2pm / at 2:30pm

More info in [MVP Scope](/docs/mvp.md)

## Security and privacy
- All commands and reminders are accepted via direct message
- Public replies to posts containing a time expression are accepted and processed; confirmation and reminder are always sent as DMs
- Other public mentions redirect the user to DM with instructions
- Store the minimum required data in SQLite
- Access token stays in .env (never committed)