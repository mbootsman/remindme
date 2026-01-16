# RemindMe - Roadmap

## Phase 0: Foundations
- [x] Decide [license](/LICENSE), [code of conduct](/CODE_OF_CONDUCT.md), [contribution flow](/docs/CONTRIBUTING.md)
- [x] Define [MVP scope](/docs/mvp.md) and [success metrics](/docs/success-metrics.md)
- [ ] Pick tech stack (language, DB, hosting)

## Phase 1: MVP
- [ ] Mastodon integration: receive DMs
- [ ] Parser: relative time ("in X minutes/hours/days") use Carbon?
- [ ] Create reminder and store in DB
- [ ] Scheduler/worker for due reminders
- [ ] Send DM notification
- [ ] Commands: help, list, cancel
- [ ] Basic logging (for storing success metrics) and rate limiting

## Phase 2: Quality
- [ ] Better parsing (tomorrow, next Friday)
- [ ] Repetitions and retries
- [ ] Monitoring and health endpoints

## Phase 3: V1
- [ ] Recurring reminders
- [ ] Web dashboard (optional)
- [ ] Multi-language (EN/NL)