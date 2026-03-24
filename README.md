# Kelajak-Maskan

Kelajak-Maskan is a place for the future for disciplined builders and developers.

Most people do not lose momentum because they lack ambition. They lose it because intent, constraints, decisions, and outcomes get scattered between sessions. Kelajak-Maskan is being built to give that discipline a home: a grounded record of what you wanted to build, what shaped the plan, what you validated, what you tried, and what happened next.

## Why Kelajak-Maskan Exists

Builders and developers often restart the same thinking over and over:

- what was the actual goal
- what constraints were already known
- what was validated
- what was tried
- what was learned

Kelajak-Maskan exists to turn that lost context into durable continuity. It is meant for people who want a more disciplined way to build, review, and move forward without forgetting the path that got them there.

## What It Does Today

Today, Kelajak-Maskan is a local history engine backed by SQLite.

It currently provides:

- project history stored locally in a structured schema
- durable records for wants, constraint snapshots, validation runs, fact sources, plan revisions, action runs, outcome logs, and audit logs
- audited write actions for core history events
- CLI-first read access to the latest want, newest open cycle, and compact project summary
- a backfill command for upgrading live SQLite history with real foreign keys

Available commands:

```bash
php artisan history:latest-want
php artisan history:open-cycle
php artisan history:summary
php artisan history:backfill-foreign-keys
```

## Who It Is For

Kelajak-Maskan is for:

- builders who want more discipline in how they move from intention to execution
- developers who want continuity across work sessions
- early adopters who value grounded iteration over vague progress

## What's Coming Next

The next version is focused on making this engine easier to use as a product.

That means building a more accessible surface on top of the history foundation that already exists, so capturing wants, reviewing open cycles, and carrying momentum forward no longer depends on living inside the codebase. The direction is forward, but the README will continue to describe what is already real first and what is coming next second.

## Getting Started

```bash
composer install
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan migrate
php artisan serve
```

If you want to inspect the current product surface after setup:

```bash
php artisan history:latest-want
php artisan history:open-cycle
php artisan history:summary
```

## Current Status

Kelajak-Maskan is currently an early product foundation rather than a finished end-user application.

- the history model and CLI read path exist today
- local persistence is SQLite-based
- the product surface is still CLI-first
- a broader UI or API layer is a next-version goal, not a shipped feature yet

Laravel 13 powers the current implementation, but the product story is not Laravel itself. It is disciplined progress with memory.
