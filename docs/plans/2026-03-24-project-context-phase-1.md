# Project Context Phase 1 Plan

## Summary

Add the first project-context layer to `kelajak-maskan` so AI can understand a tracked project before planning or acting. This phase adds a single context snapshot model for each project and a read-only CLI surface that returns the current context in one stable, source-backed shape.

This phase is intentionally narrow:
- add project context storage
- add project context read path
- seed or write the first context record for `kelajak-maskan`

This phase does **not** include:
- dashboard work
- feature projection
- git traceability
- external repo scanning
- HTTP or API endpoints

## Goals

1. Preserve the current history spine.
2. Add a separate project-context layer for project identity and conventions.
3. Make project context readable by AI without reconstructing it from chat or scattered files.
4. Keep the first shape source-backed and simple.

## Scope

### Add a context snapshot table

Create a new `project_contexts` table with one current record per project. Keep it snapshot-first rather than fully normalized.

Recommended columns:
- `id`
- `project_id`
- `summary`
- `repo_path`
- `primary_branch`
- `stack` JSON
- `commands` JSON
- `conventions` JSON
- `key_paths` JSON
- `current_phase`
- `source_refs` JSON
- timestamps

Constraints:
- `project_id` must be a foreign key to `projects`
- add a uniqueness constraint on `project_id` for this first phase so each project has one current context row

### Add a model

Create:
- `app/Models/ProjectContext.php`

Update:
- `app/Models/Project.php`

Relationships:
- `Project` has one `ProjectContext`
- `ProjectContext` belongs to `Project`

Model casts:
- `stack`
- `commands`
- `conventions`
- `key_paths`
- `source_refs`

### Add a read-only command

Create:
- `app/Console/Commands/ProjectContext.php`

Expose:
- `php artisan project:context {project=kelajak-maskan}`

Behavior:
- default to `kelajak-maskan`
- load the project and its context row
- fail clearly if the project or context does not exist
- print one stable human/AI-readable structure with:
  - project identity
  - summary
  - repo path
  - primary branch
  - stack
  - commands
  - conventions
  - key paths
  - current phase
  - source refs

This command is read-only.

### Add a first context record

After migration, create the first `project_contexts` row for `kelajak-maskan`.

It should reflect the current project truthfully from repo evidence:
- Laravel app
- SQLite history
- project-history pipeline
- current commands actually used
- current conventions already enforced by the app and skills

Record source refs explicitly, for example:
- `README.md`
- `composer.json`
- `database/database.sqlite`
- `skills/wants-to-plans/SKILL.md`
- `skills/plans-to-action/SKILL.md`

This can be written directly for this phase. A dedicated audited write action for context is a later phase.

## Tests

Add coverage for:

1. schema and model
- `project_contexts` table exists
- unique `project_id`
- casts work as arrays
- relationships resolve

2. command contracts
- `project:context` returns the default project context
- `project:context some-slug` returns the matching project context
- missing project fails clearly
- project without context fails clearly

3. non-mutation guarantee
- running `project:context` does not change row counts in:
  - `projects`
  - `project_contexts`
  - `wants`
  - `audit_logs`

## Execution Order

1. Write failing tests for schema/model/command behavior.
2. Add migration for `project_contexts`.
3. Add `ProjectContext` model and `Project` relationship.
4. Add `project:context` read-only command.
5. Insert the first `kelajak-maskan` context row.
6. Run full test suite.
7. Run `php artisan project:context` against the live root DB and verify output.

## Verification

Required commands:

```bash
php artisan test
php artisan project:context
```

Verify:
- tests pass
- command prints the current `kelajak-maskan` context
- command performs no DB writes
- live root DB has a usable `project_contexts` row for `kelajak-maskan`

## Assumptions

- one context row per project is enough for the first phase
- snapshot-first is acceptable
- context writes can be manual/seed-like in this phase
- CLI-first read surface is enough for AI and human verification

## Deferred Work

- audited write actions for project context
- historical context revisions
- feature projection layer
- dashboard
- git traceability
- external repo scanning and auto-detection
- HTTP/API delivery for project context
