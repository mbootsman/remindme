# MVP scope

## Goal
Let Mastodon users create reminders via direct messages or mentions using natural language, and receive a direct reminder at the right time.

## In scope (MVP)
Input:
- Direct messages (visibility: direct) are required for all commands (help, list, cancel, set timezone) and reminders.
  - Commands:
    - help
    - list
    - cancel <id>
    - set timezone <timezone>
- If you mention the bot publicly or in a reply, you will receive a private DM with instructions on how to use the bot. Only DMs are processed for commands and reminders.
  
Parsing:
- English (first)
- Supported time phrases:
  - in N minutes/hours/days/weeks/months
  - tomorrow
  - next monday..sunday
  - on YYYY-MM-DD
  - optional: at 14:30 / at 2pm / at 2:30pm

Delivery of the reminder:
- Send reminder via direct message
- Confirmation message on creation
- Store reminders in SQLite (UTC timestamps)

Operations:
- Poll notifications (cron-friendly)
- Due worker (cron-friendly)
- Minimal logging

## Out of scope (MVP)
- Recurring reminders
- Edits ("change reminder time/text")
- Multi-language parsing (Dutch later)
- Web UI/dashboard
- Streaming API (polling only)
- Multi-instance SaaS (single bot token config)