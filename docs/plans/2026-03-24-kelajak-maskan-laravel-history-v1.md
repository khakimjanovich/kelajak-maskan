# Kelajak-Maskan Laravel History V1 Implementation Plan

> **Status:** Superseded by `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/docs/plans/2026-03-24-kelajak-maskan-migrations-phase-1.md`

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Turn `kelajak-maskan` into a standalone Laravel app that stores wants, constraints, validations, plans, actions, and outcomes in local SQLite so planning can use real project history.

**Architecture:** Keep `kelajak-maskan` as the Laravel app root and preserve the existing `skills/` folder inside the same repository. Build a minimal web app with standard Laravel controllers, Blade views, Eloquent models, and SQLite-backed migrations. Treat local history as the primary experience source; external sources may support facts later, but not replace local experience.

**Tech Stack:** PHP 8.2, Laravel 12, SQLite, Blade, Eloquent, PHPUnit

---

### Task 1: Scaffold Laravel At Project Root

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

**Step 1: Generate a temporary Laravel skeleton**

Run:

```bash
composer create-project laravel/laravel /tmp/kelajak-maskan-laravel
```

Expected: Laravel skeleton created in `/tmp/kelajak-maskan-laravel`

**Step 2: Copy the Laravel skeleton into the project root without deleting `skills/`**

Run:

```bash
rsync -a --exclude='.git' --exclude='vendor' --exclude='node_modules' /tmp/kelajak-maskan-laravel/ /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/
```

Expected: Laravel application files appear in `kelajak-maskan/` and `skills/` remains intact

**Step 3: Install PHP dependencies in the new app root**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && composer install
```

Expected: `vendor/` created and autoload files generated

**Step 4: Commit the scaffold**

```bash
git add .
git commit -m "feat: scaffold kelajak-maskan laravel app"
```

### Task 2: Configure Local SQLite As The Default Persistence Layer

**Files:**
- Modify: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/.env.example`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/database/database.sqlite`
- Modify: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/config/database.php`
- Test: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/tests/Feature/ApplicationBootTest.php`

**Step 1: Write the failing application boot test**

Create:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApplicationBootTest extends TestCase
{
    public function test_home_page_responds_successfully(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }
}
```

**Step 2: Run the test to verify the fresh app boots**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && php artisan test --filter=ApplicationBootTest
```

Expected: PASS after the Laravel scaffold is healthy

**Step 3: Configure SQLite defaults**

- Set `DB_CONNECTION=sqlite` in `.env.example`
- Set `DB_DATABASE=/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/database/database.sqlite` in local `.env`
- Ensure `database/database.sqlite` exists

**Step 4: Verify database connectivity**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && php artisan migrate:fresh
```

Expected: Laravel default migrations run successfully against SQLite

**Step 5: Commit**

```bash
git add .env.example config/database.php database/database.sqlite tests/Feature/ApplicationBootTest.php
git commit -m "feat: configure sqlite for local history"
```

### Task 3: Create The Core Local History Schema

**Files:**
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/database/migrations/*_create_projects_table.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/database/migrations/*_create_wants_table.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/database/migrations/*_create_constraint_snapshots_table.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/database/migrations/*_create_validation_runs_table.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/database/migrations/*_create_fact_sources_table.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/database/migrations/*_create_plan_revisions_table.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/database/migrations/*_create_action_runs_table.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/database/migrations/*_create_outcome_logs_table.php`
- Test: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/tests/Feature/HistorySchemaTest.php`

**Step 1: Write the failing schema test**

Create a feature test that asserts these tables exist after migration:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HistorySchemaTest extends TestCase
{
    public function test_history_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('projects'));
        $this->assertTrue(Schema::hasTable('wants'));
        $this->assertTrue(Schema::hasTable('constraint_snapshots'));
        $this->assertTrue(Schema::hasTable('validation_runs'));
        $this->assertTrue(Schema::hasTable('fact_sources'));
        $this->assertTrue(Schema::hasTable('plan_revisions'));
        $this->assertTrue(Schema::hasTable('action_runs'));
        $this->assertTrue(Schema::hasTable('outcome_logs'));
    }
}
```

**Step 2: Run the test and verify it fails**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && php artisan test --filter=HistorySchemaTest
```

Expected: FAIL because the history tables do not exist yet

**Step 3: Add the migrations**

Use these minimal columns:

- `projects`: `id`, `name`, `slug`, timestamps
- `wants`: `id`, `project_id`, `title`, `raw_text`, `status`, timestamps
- `constraint_snapshots`: `id`, `want_id`, `payload` (json), timestamps
- `validation_runs`: `id`, `want_id`, `facts_status`, `constraints_status`, `experience_status`, `ikhlas_status`, `summary`, timestamps
- `fact_sources`: `id`, `validation_run_id`, `label`, `url`, `status`, `notes`, timestamps
- `plan_revisions`: `id`, `want_id`, `version`, `plan_text`, `grounded_summary`, timestamps
- `action_runs`: `id`, `plan_revision_id`, `status`, `started_at`, `finished_at`, timestamps
- `outcome_logs`: `id`, `action_run_id`, `outcome`, `reflection`, timestamps

**Step 4: Run migrations and the schema test**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && php artisan migrate:fresh && php artisan test --filter=HistorySchemaTest
```

Expected: PASS

**Step 5: Commit**

```bash
git add database/migrations tests/Feature/HistorySchemaTest.php
git commit -m "feat: add local history schema"
```

### Task 4: Add Eloquent Models And Relationships

**Files:**
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/app/Models/Project.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/app/Models/Want.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/app/Models/ConstraintSnapshot.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/app/Models/ValidationRun.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/app/Models/FactSource.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/app/Models/PlanRevision.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/app/Models/ActionRun.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/app/Models/OutcomeLog.php`
- Test: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/tests/Feature/HistoryRelationshipsTest.php`

**Step 1: Write the failing relationships test**

Create a test that seeds a `Project`, related `Want`, and one `PlanRevision`, then asserts the relations resolve correctly.

**Step 2: Run the test and verify it fails**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && php artisan test --filter=HistoryRelationshipsTest
```

Expected: FAIL because the models and relations do not exist yet

**Step 3: Implement minimal models and relations**

Add `fillable` or guarded settings and define only the required relations:
- `Project` has many `Want`
- `Want` belongs to `Project`
- `Want` has many `ConstraintSnapshot`, `ValidationRun`, `PlanRevision`
- `ValidationRun` has many `FactSource`
- `PlanRevision` has many `ActionRun`
- `ActionRun` has many `OutcomeLog`

**Step 4: Run the test**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && php artisan test --filter=HistoryRelationshipsTest
```

Expected: PASS

**Step 5: Commit**

```bash
git add app/Models tests/Feature/HistoryRelationshipsTest.php
git commit -m "feat: add history models and relations"
```

### Task 5: Build The First Web Flow For Wants And Constraints

**Files:**
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/app/Http/Controllers/WantController.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/app/Http/Requests/StoreWantRequest.php`
- Modify: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/routes/web.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/resources/views/wants/create.blade.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/resources/views/wants/show.blade.php`
- Test: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/tests/Feature/WantFlowTest.php`

**Step 1: Write the failing feature test**

Test this flow:
- GET `/wants/create` returns 200
- POST `/wants` stores a want and a constraint snapshot
- GET `/wants/{want}` shows saved data

**Step 2: Run the test and verify it fails**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && php artisan test --filter=WantFlowTest
```

Expected: FAIL because routes and controller do not exist yet

**Step 3: Implement minimal HTTP layer**

Use a single form with:
- want title
- raw want text
- constraint JSON or structured textarea for v1

Persist:
- one `Want`
- one `ConstraintSnapshot`

**Step 4: Run the feature test**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && php artisan test --filter=WantFlowTest
```

Expected: PASS

**Step 5: Commit**

```bash
git add app/Http routes/web.php resources/views/wants tests/Feature/WantFlowTest.php
git commit -m "feat: add want and constraint capture flow"
```

### Task 6: Add Validation Services And Persisted Validation Runs

**Files:**
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/app/Services/Validation/FactsValidator.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/app/Services/Validation/ConstraintsValidator.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/app/Services/Validation/ExperienceValidator.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/app/Services/Validation/IkhlasGate.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/app/Services/Validation/WantValidationPipeline.php`
- Modify: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/app/Http/Controllers/WantController.php`
- Test: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/tests/Feature/ValidationPipelineTest.php`

**Step 1: Write the failing feature test**

Test that running validation for a want:
- creates a `validation_runs` row
- records gate statuses
- writes placeholder fact-source rows when facts are unresolved
- uses local history as the first experience source

**Step 2: Run the test and verify it fails**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && php artisan test --filter=ValidationPipelineTest
```

Expected: FAIL because the pipeline does not exist yet

**Step 3: Implement the minimal validation layer**

Rules for v1:
- `FactsValidator`: return `unknown` or `needs_sources` until real source adapters are added
- `ConstraintsValidator`: validate against saved `ConstraintSnapshot`
- `ExperienceValidator`: inspect local DB history first; if none exists, return `insufficient_history`
- `IkhlasGate`: return a manual-review status for now, not hidden inference

**Step 4: Run the feature test**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && php artisan test --filter=ValidationPipelineTest
```

Expected: PASS

**Step 5: Commit**

```bash
git add app/Services app/Http/Controllers/WantController.php tests/Feature/ValidationPipelineTest.php
git commit -m "feat: add validation pipeline and history persistence"
```

### Task 7: Add Plan Revision And Action Tracking

**Files:**
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/app/Http/Controllers/PlanRevisionController.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/app/Http/Controllers/ActionRunController.php`
- Modify: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/routes/web.php`
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/resources/views/plans/show.blade.php`
- Test: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/tests/Feature/PlanAndActionFlowTest.php`

**Step 1: Write the failing feature test**

Test that a validated want can:
- store a `plan_revisions` row
- create an `action_runs` row
- attach one `outcome_logs` row

**Step 2: Run the test and verify it fails**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && php artisan test --filter=PlanAndActionFlowTest
```

Expected: FAIL because plan and action endpoints do not exist yet

**Step 3: Implement the minimal plan and action flow**

The v1 UI can be simple:
- textarea to save a grounded plan
- button to mark action started/completed
- textarea to save outcome/reflection

**Step 4: Run the feature test**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && php artisan test --filter=PlanAndActionFlowTest
```

Expected: PASS

**Step 5: Commit**

```bash
git add app/Http/Controllers routes/web.php resources/views/plans tests/Feature/PlanAndActionFlowTest.php
git commit -m "feat: add plan revision and action tracking"
```

### Task 8: Document The Local History Contract

**Files:**
- Create: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/docs/local-history.md`
- Modify: `/Users/khakimjanovich/Documents/dev/exp/kelajak-maskan/skills/wants-to-plans/SKILL.md`

**Step 1: Write the documentation**

Document:
- which tables count as local history
- why local history is the primary experience source
- which statuses validators may return in v1
- what remains manual until later source integrations

**Step 2: Align the planning skill with the app contract**

Update the skill so `Experience Gate` references local history as the primary source once the app exists.

**Step 3: Verify the docs match the schema and pipeline**

Run:

```bash
cd /Users/khakimjanovich/Documents/dev/exp/kelajak-maskan && php artisan test
```

Expected: PASS

**Step 4: Commit**

```bash
git add docs/local-history.md skills/wants-to-plans/SKILL.md
git commit -m "docs: describe local history contract"
```
