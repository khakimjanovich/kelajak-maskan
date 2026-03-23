# Kelajak-Maskan Migrations Phase 1 Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Create the Laravel 13 application root for `kelajak-maskan` and implement only the SQLite-backed migration layer for local history.

**Architecture:** `kelajak-maskan` becomes the Laravel app root while preserving the existing `skills/` directory in the same repository. Phase 1 stops after the app boots, Pest is configured, SQLite is active, and the local-history tables can be migrated and verified by tests. Filament 5 is intentionally deferred until the UI phase.

**Tech Stack:** PHP 8.5, Laravel 13, SQLite, Eloquent, Pest 4, Composer 2.9

---

### Task 0: Initialize Git For The Project Root

**Files:**
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/.git/`
- Preserve: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/skills/`
- Preserve: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/docs/`

**Step 1: Initialize the local repository**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && git init
```

Expected: a local Git repository exists in `kelajak-maskan/.git`

**Step 2: Set the default branch name**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && git branch -M main
```

Expected: the unborn default branch is named `main`

**Step 3: Attach the GitHub remote**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && git remote add origin https://github.com/khakimjanovich/kelajak-maskan.git
```

Expected: `origin` points at `https://github.com/khakimjanovich/kelajak-maskan.git`

**Step 4: Verify repository state before continuing**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && git status --short --branch && git remote -v
```

Expected: status reports `main` as the current branch with no commits yet, and `origin` is configured for fetch and push

**Step 5: Prepare an in-repo worktree directory**

- Add `.worktrees/` to `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/.gitignore`
- Ensure the directory is ignored before using project-local git worktrees

Expected: `.worktrees/` is ignored by git

**Step 6: Create the baseline commit required for worktree-based execution**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && git add . && git commit -m "chore: initialize repository baseline"
```

Expected: the repository has an initial commit containing the existing plan, skills directory, and git bootstrap files

**Step 7: Create the isolated worktree for implementation**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && git worktree add .worktrees/migrations-phase-1 -b migrations-phase-1
```

Expected: an isolated worktree exists at `.worktrees/migrations-phase-1` on branch `migrations-phase-1`

---

### Task 1: Scaffold Laravel 13 At The Project Root

**Files:**
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/app/`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/bootstrap/`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/config/`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/database/`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/public/`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/resources/`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/routes/`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/storage/`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/tests/`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/artisan`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/composer.json`
- Preserve: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/skills/`

**Step 1: Generate a temporary Laravel 13 skeleton**

Run:

```bash
composer create-project laravel/laravel:^13.0 /tmp/kelajak-maskan-laravel
```

Expected: Laravel 13 application created in `/tmp/kelajak-maskan-laravel`

**Step 2: Copy the skeleton into the existing project root**

Run:

```bash
rsync -a --exclude='.git' --exclude='vendor' --exclude='node_modules' /tmp/kelajak-maskan-laravel/ /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/
```

Expected: Laravel files appear in `kelajak-maskan/` and `skills/` remains intact

**Step 3: Install dependencies**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && composer install
```

Expected: `vendor/` exists and autoload is generated

**Step 4: Verify the framework boots**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && php artisan --version
```

Expected: output includes `Laravel Framework 13`

**Step 5: Commit**

```bash
git add .
git commit -m "feat: scaffold laravel 13 app root"
```

### Task 2: Configure SQLite And Pest 4

**Files:**
- Modify: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/.env.example`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/database/database.sqlite`
- Modify: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/composer.json`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/tests/Pest.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/tests/Feature/ApplicationBootTest.php`

**Step 1: Replace PHPUnit-first defaults with Pest 4**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && composer remove phpunit/phpunit --dev && composer require pestphp/pest:^4.0 pestphp/pest-plugin-laravel:^4.0 --dev
```

Expected: Pest 4 packages installed successfully

**Step 2: Initialize Pest**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && ./vendor/bin/pest --init
```

Expected: `tests/Pest.php` exists and test scaffolding is updated for Pest

**Step 3: Configure SQLite as the default local database**

- Set `DB_CONNECTION=sqlite` in `.env.example`
- Set `DB_DATABASE=/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/database/database.sqlite` in local `.env`
- Ensure `database/database.sqlite` exists

**Step 4: Write the failing boot test**

Create `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/tests/Feature/ApplicationBootTest.php`:

```php
<?php

it('boots the application', function (): void {
    $this->get('/')->assertOk();
});
```

**Step 5: Run the boot test**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && php artisan test tests/Feature/ApplicationBootTest.php
```

Expected: PASS

**Step 6: Commit**

```bash
git add .env.example composer.json composer.lock database/database.sqlite tests
git commit -m "feat: configure sqlite and pest"
```

### Task 3: Add The Local History Migrations

**Files:**
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/database/migrations/*_create_projects_table.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/database/migrations/*_create_wants_table.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/database/migrations/*_create_constraint_snapshots_table.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/database/migrations/*_create_validation_runs_table.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/database/migrations/*_create_fact_sources_table.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/database/migrations/*_create_plan_revisions_table.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/database/migrations/*_create_action_runs_table.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/database/migrations/*_create_outcome_logs_table.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/tests/Feature/Database/HistorySchemaTest.php`

**Step 1: Write the failing schema test**

Create `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/tests/Feature/Database/HistorySchemaTest.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;

it('creates the local history tables', function (): void {
    expect(Schema::hasTable('projects'))->toBeTrue();
    expect(Schema::hasTable('wants'))->toBeTrue();
    expect(Schema::hasTable('constraint_snapshots'))->toBeTrue();
    expect(Schema::hasTable('validation_runs'))->toBeTrue();
    expect(Schema::hasTable('fact_sources'))->toBeTrue();
    expect(Schema::hasTable('plan_revisions'))->toBeTrue();
    expect(Schema::hasTable('action_runs'))->toBeTrue();
    expect(Schema::hasTable('outcome_logs'))->toBeTrue();
});
```

**Step 2: Run the test to confirm it fails**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && php artisan test tests/Feature/Database/HistorySchemaTest.php
```

Expected: FAIL because the tables do not exist yet

**Step 3: Add the migrations with the minimal schema**

Use these columns:

- `projects`: `id`, `name`, `slug`, timestamps
- `wants`: `id`, `project_id`, `title`, `raw_text`, `status`, timestamps
- `constraint_snapshots`: `id`, `want_id`, `payload` (json), timestamps
- `validation_runs`: `id`, `want_id`, `facts_status`, `constraints_status`, `experience_status`, `ikhlas_status`, `summary`, timestamps
- `fact_sources`: `id`, `validation_run_id`, `label`, `url`, `status`, `notes`, timestamps
- `plan_revisions`: `id`, `want_id`, `version`, `plan_text`, `grounded_summary`, timestamps
- `action_runs`: `id`, `plan_revision_id`, `status`, `started_at`, `finished_at`, timestamps
- `outcome_logs`: `id`, `action_run_id`, `outcome`, `reflection`, timestamps

**Step 4: Run the migrations**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && php artisan migrate:fresh
```

Expected: all history migrations run successfully against SQLite

**Step 5: Run the schema test again**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && php artisan test tests/Feature/Database/HistorySchemaTest.php
```

Expected: PASS

**Step 6: Commit**

```bash
git add database/migrations tests/Feature/Database/HistorySchemaTest.php
git commit -m "feat: add local history migrations"
```

### Task 4: Verify The Migration Phase End State

**Files:**
- Modify: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/README.md`

**Step 1: Document the phase boundary**

Add a short section to `README.md` stating:
- Phase 1 ends at migrations
- Filament 5 UI is deferred
- local history is now persisted in SQLite

**Step 2: Run the full phase-1 verification**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && php artisan test && php artisan migrate:fresh
```

Expected: tests pass and migrations rebuild cleanly

**Step 3: Commit**

```bash
git add README.md
git commit -m "docs: mark migration phase boundary"
```
