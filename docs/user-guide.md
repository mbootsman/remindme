# RemindMe - User guide

RemindMe is a Mastodon bot that can send you reminders.

## Privacy rules
- RemindMe only processes messages with visibility **direct** (private/direct posts).
- If you mention the bot publicly, it will not respond unless you are clearly trying to use a command (list, cancel, help, remind me).

## Create a reminder

Send the bot a DM:

- `remind me in 2 days about renew domain`
- `remind me in 30 minutes about take a break`
- `remind me tomorrow at 09:00 about call the dentist`
- `remind me next monday at 10:00 about invoicing`
- `remind me on 2026-01-03 at 14:30 about pay invoice`

If it worked, you will get a confirmation message that includes a reminder **ID**.

## List reminders

DM:

- `list`

You will get up to 5 upcoming reminders.

## Cancel a reminder

DM:

- `cancel 12`

Tip: use `list` to find the ID.

## Help

DM:

- `help`
- `?`

## Supported time syntax (MVP)

- `in N minutes/hours/days/weeks/months`
- `tomorrow`
- `next monday..sunday`
- `on YYYY-MM-DD`
- Optional time: `at 14:30` / `at 2pm` / `at 2:30pm`

## Notes and limitations

- Reminder IDs are shared across all users of the bot, but you can only cancel reminders that belong to your account.
- RemindMe is MVP software: expect improvements over time.