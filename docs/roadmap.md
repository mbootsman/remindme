# RemindMe - Roadmap

Last aligned with MVP on 2026-02-08.

## Phase 0: Foundations
- [x] Decide [license](/LICENSE), [code of conduct](/CODE_OF_CONDUCT.md), [contribution flow](/docs/CONTRIBUTING.md)
- [x] Define [MVP scope](/docs/mvp.md) and [success metrics](/docs/success-metrics.md)
- [x] Pick tech stack (language, DB, hosting)

## Phase 1: MVP
- [x] Mastodon integration: receive DMs
- [x] Parser (Carbon): in N minutes/hours/days/weeks/months; tomorrow; next monday..sunday; on YYYY-MM-DD; optional: at 14:30 / at 2pm / at 2:30pm
- [x] Create reminder and store in DB
- [x] Scheduler/worker for due reminders
- [x] Send DM notification
- [x] Commands: help, list, cancel
- [x] Basic logging (for storing success metrics)
 - [x] Rate limiting

## Phase 2: Quality
- [ ] Parsing hardening: negative tests, strict validation, clearer error messages
- [ ] Repetitions and retries
- [ ] Monitoring and health endpoints

## Phase 3: V1
- [ ] Smart reminders for posts: let users save a link/status ID and get reminded later (for example: "remind me about this post in 2 days")
- [ ] Recurring reminders
- [ ] Web dashboard (optional)
- [ ] Multi-language (EN/NL)