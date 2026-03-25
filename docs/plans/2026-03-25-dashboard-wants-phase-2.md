# Dashboard Wants Phase 2

Date: 2026-03-25

## Goal

Extend the existing `kelajak-maskan` dashboard so it truthfully shows:

- active wants
- each want's current stage derived from stored history records
- the latest written plan text or grounded summary for a highlighted want
- a read-only want detail surface if the tests justify a dedicated route

The result must stay `kelajak-maskan`-first, project-based in design, read-first in behavior, and must not expose absolute repo paths or GitHub-oriented details.

## Grounded Facts

- The current dashboard already exists on `/` and is backed by `KelajakMaskanDashboardData`.
- Existing history reads already expose the records needed to derive a want stage:
  - want
  - constraint snapshot
  - validation run
  - plan revision
  - action run
  - outcome log
- The current dashboard and `project:context` still surface `repo_path`, which conflicts with want `#15`.
- `project:context` is explicitly read-only and the dashboard must keep the same non-mutation discipline.
- `history:record-cycle` records wants and optional cycle records, but it does not track file-level git diffs. The app history must record truthful implementation cycle state, not become a code-diff log.

## Stage Derivation

Derive a single dashboard stage from real stored history in this order:

1. `completed`
   - latest action run has a terminal completed status and an outcome log exists
2. `blocked`
   - latest action or outcome indicates failure, defect discovery, blockage, abandonment, or similar stuck state
3. `acting`
   - latest plan exists and latest action run exists but is not terminal-complete
4. `planned`
   - latest plan revision exists and no action run exists yet
5. `validated`
   - validation run exists and no plan revision exists yet
6. `constrained`
   - constraint snapshot exists and no validation run exists yet
7. `captured`
   - want exists with none of the later records above

The implementation must rely on stored history records, not fake percentages or guessed progress numbers.

## Delivery Shape

- Keep `/` as the main dashboard route.
- Add an active wants section with stage labels and concise readable summaries.
- Add a highlighted want panel that shows the latest plan text when present, otherwise the latest grounded summary, otherwise a truthful fallback.
- Add a read-only want detail route only if tests show the dashboard needs a dedicated detail surface for readability.
- Replace any repo-path display with safe project facts that do not expose an absolute local path.
- Keep the available actions panel limited to `kelajak-maskan` actions already owned by the app.

## TDD Execution Order

1. Write failing feature tests for dashboard wants rendering.
2. Write failing tests for stage derivation from real history records.
3. Write failing tests that no absolute repo path is rendered on dashboard surfaces.
4. Write failing tests that dashboard reads do not mutate history or audit tables.
5. If adding a want detail route, write failing route and rendering tests first.
6. Extend the dashboard read model with active wants and highlighted want data.
7. Introduce a focused presenter/value object for stage derivation if it keeps the read model clearer.
8. Update the Blade UI for a more readable, project-based layout without adding write controls.
9. Update project context rendering so repo-path facts are not displayed.
10. If necessary for want `#15`, update project context payload/command output and tests so auto-generated context no longer stores or exposes absolute repo paths.
11. Run targeted tests after each slice.
12. Run the full test suite before recording the implementation outcome.

## Verification

Required verification inside the worktree:

- targeted Pest runs for new dashboard and project-context behavior
- full `php artisan test`
- manual route rendering checks for dashboard pages
- explicit proof that dashboard GET requests do not create wants, audit logs, plan revisions, action runs, or outcome logs
- explicit proof that no absolute repo path appears in the rendered dashboard or auto-generated project context output

## Read-Only Discipline

- No dashboard-triggered write actions in this phase.
- No forms, buttons, or links that execute history writes.
- No raw sqlite access.
- No ad hoc PHP scripts.
- Only app-owned commands and existing Laravel boundaries for reads and truthful history recording.

## Expected Files To Change

Expected implementation focus is narrow and may adjust slightly under TDD:

- `app/Support/Dashboard/*`
- `app/Support/History/*` only if needed for reusable stage derivation
- `resources/views/dashboard.blade.php`
- `routes/web.php` if a read-only want detail route is added
- `app/Support/ProjectContext/*` and console tests if required to satisfy want `#15`
- dashboard and project-context feature tests

## Completion Record

Before closing the work:

- record the implementation cycle truthfully through `history:record-cycle`
- refresh project context with `project:refresh-context` only if project facts changed
- verify the refreshed context still does not store or expose an absolute repo path
- do not merge or push
