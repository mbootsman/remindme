# RemindMe - A Mastodon Reminder Bot

A Mastodon reminder service that lets you create reminders using natural language, via DM or mention.

Example: "remind me in 2 days about paying the invoice"

## Status
Early stage. Starting with a public project brief and roadmap, then (re)building an MVP.

## What it does (planned MVP)
- Create reminders via DM
- Parse relative time ("in 2 hours", "in 3 days")
- Commands: "help", "list", "cancel", "set timezone"
- Send reminder notifications via DM
- Per-user timezone configuration

## Docs
- Project brief: [docs/project-brief.md](docs/project-brief.md)
- Roadmap: [docs/roadmap.md](docs/roadmap.md)
- MVP Scope: [docs/mvp.md](docs/mvp.md)
- Architecture: [docs/architecture.md](docs/architecture.md)
- Decisions (ADRs): [docs/decisions/](docs/decisions/)
- Operations: [docs/ops.md](docs/ops.md)

## Contributing
See [CONTRIBUTING.md](CONTRIBUTING.MD).

## License
See [LICENSE](LICENSE).

## Metrics / Logging

The bot writes non-PII metrics to a JSON Lines file for simple analytics and monitoring. Configuration is provided via environment variables (see `.env.example`):

- `LOG_PATH`: path to the JSONL log file (default: `logs/remindme.log`).
- `LOG_SECRET`: HMAC secret used to hash user IDs in logs. If empty, a plain SHA-256 prefix is used instead (less secure).

Each line in the log is a JSON object (one event), for example:

{
	"event": "reminder.created",
	"count": 1,
	"days_until_due": 7,
	"time_of_day": "morning",
	"user_hash": "a1b2c3d4",
	"timestamp": "2026-02-09T08:15:00+00:00"
}

Quick inspection commands:

```bash
# show last 20 events
tail -n 20 "${LOG_PATH:-logs/remindme.log}"

# pretty-print one JSONL file (requires jq)
jq -R -s -c 'split("\n")[:-1] | map(fromjson)' "${LOG_PATH:-logs/remindme.log}" | jq .
```

Keep `LOG_SECRET` secret, it prevents rainbow-table style lookups of hashed user IDs.
