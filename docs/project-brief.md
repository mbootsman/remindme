# Project brief

This document describes the problem, goals, MVP scope, and constraints for the Mastodon Reminder Bot.

## Summary
Build a service that lets Mastodon users create reminders using natural language from inside Mastodon (DMs and mentions). The service confirms the reminder and later notifies the user.

## Goals
- Low-friction reminder creation from Mastodon
- Reliable delivery and confirmations
- Privacy-aware data handling

## Non-goals
- Full task management
- Deep calendar integrations (maybe later/premium?)

## MVP requirements
- DM-based reminder creation
- Relative time parsing ("in X minutes/hours/days")
- Commands: help, list, cancel
- User timezone setting
- Reminder delivery via DM