# RemindMe - User guide

RemindMe is a Mastodon bot that can send you reminders.


## Privacy rules
- RemindMe processes commands sent via **direct messages** (private/direct posts).
- You can also reply to any public post with a time expression to be reminded of that post (see below).
- If you mention the bot publicly without a time expression, the bot will send you a private DM with instructions.


## Get reminded of a post

Reply to any public post mentioning the bot with a time expression:

- `@remindme in 2 days`
- `@remindme tomorrow at 09:00`
- `@remindme next monday`

The bot will confirm via DM. When the reminder is due, you will receive a DM with a link back to the original post.

No task description needed — the post itself is what you will be reminded of.

## Create a reminder

Send the bot a DM that mentions it:

- `@remindme in 2 days about renew domain`
- `@remindme in 30 minutes about take a break`
- `@remindme tomorrow at 09:00 about call the dentist`
- `@remindme next monday at 10:00 about invoicing`
- `@remindme on 2026-01-03 at 14:30 about pay invoice`

Most Mastodon clients will add `@remindme` for you when you start a DM thread with the bot, so both `@remindme in 2 days…` and `remind me in 2 days…` work.

If it worked, you will get a confirmation message that includes a reminder **ID**.

## List reminders

DM:

- `list`

You will get up to 5 upcoming reminders.

## Cancel a reminder

DM:

- `cancel 12`

Tip: use `list` to find the ID.

## Set your timezone

DM:

- `set timezone Europe/Amsterdam`
- `set timezone America/New_York`

This setting determines how relative times (like "tomorrow" or "at 14:30") are interpreted. Use IANA timezone names (examples: `Europe/Amsterdam`, `America/New_York`, `Asia/Tokyo`).

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

All times are interpreted in your configured timezone (set via `set timezone`). If no timezone is set, the server's default (Europe/Amsterdam) is used.

## Notes and limitations

- Reminder IDs are shared across all users of the bot, but you can only cancel reminders that belong to your account.
- RemindMe is MVP software: expect improvements over time.