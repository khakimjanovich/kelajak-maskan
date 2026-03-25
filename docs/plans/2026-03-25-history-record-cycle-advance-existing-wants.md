# History Record Cycle Existing Want Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Extend `history:record-cycle` so it can append planning and execution records to an existing want, then use that path to reconcile the real open dashboard cycle.

**Architecture:** Keep `history:record-cycle` as the single app-owned write boundary. Add an optional existing-want target, compute the next plan revision version from stored data, and route any status change through a focused audited write action so the command does not bypass the app’s history discipline.

**Tech Stack:** Laravel 13, PHP 8.5, SQLite, Pest 4

---

### Task 1: Lock the command behavior with failing tests

**Files:**
- Modify: `tests/Feature/Console/HistoryRecordCycleCommandTest.php`
- Test: `tests/Feature/Console/HistoryOpenCycleCommandTest.php`
- Test: `tests/Feature/Console/HistorySummaryCommandTest.php`

**Step 1: Write the failing test**

Add one feature test that seeds an existing draft want, runs `history:record-cycle` against that want, and expects:
- no new want row is created
- the existing want status changes to `completed`
- a new plan revision is created with the next version number
- a new action run and outcome log are created
- the command output references the existing want id

Add one console test that proves a once-open cycle no longer appears in `history:open-cycle` after the new command appends a completed plan/action/outcome to that same want.

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Console/HistoryRecordCycleCommandTest.php tests/Feature/Console/HistoryOpenCycleCommandTest.php`

Expected: FAIL because `history:record-cycle` always creates a new want and never updates an existing want status or plan version.

### Task 2: Add the minimal audited write path

**Files:**
- Create: `app/Actions/History/UpdateWantStatus.php`
- Create: `app/Data/History/UpdateWantStatusData.php`
- Modify: `app/Console/Commands/HistoryRecordCycle.php`
- Modify: `tests/Feature/Domain/WriteActionsTest.php`

**Step 1: Write the failing test**

Add a domain write-action test for `UpdateWantStatus` that proves:
- the target want row is updated
- an audit log is written with action name `history.update_want_status`
- the audit payload records old and new status

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Domain/WriteActionsTest.php --filter=update`

Expected: FAIL because the new action does not exist yet.

**Step 3: Write minimal implementation**

Implement `UpdateWantStatus` as a focused audited action and update `HistoryRecordCycle` to:
- accept an optional `--want-id`
- load and validate the existing want belongs to the selected project
- reuse the existing want instead of creating a new one when `--want-id` is provided
- update want status only when the requested status differs
- compute the next plan revision version from existing stored revisions
- preserve the create-new behavior when `--want-id` is absent

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Domain/WriteActionsTest.php tests/Feature/Console/HistoryRecordCycleCommandTest.php tests/Feature/Console/HistoryOpenCycleCommandTest.php`

Expected: PASS

### Task 3: Verify summary behavior and reconcile the real cycle

**Files:**
- Modify: `tests/Feature/Console/HistorySummaryCommandTest.php`

**Step 1: Write the failing test**

Add a summary test proving that once an existing want is advanced to a terminal action with an outcome, it is no longer reported as the open cycle.

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Console/HistorySummaryCommandTest.php --filter=open`

Expected: FAIL before the command change is complete.

**Step 3: Run focused verification**

Run: `php artisan test tests/Feature/Console/HistoryRecordCycleCommandTest.php tests/Feature/Console/HistoryOpenCycleCommandTest.php tests/Feature/Console/HistorySummaryCommandTest.php tests/Feature/Domain/WriteActionsTest.php`

Expected: PASS

**Step 4: Reconcile the live open dashboard cycle**

Run `history:record-cycle` with `--want-id=17` in the worktree and supply the missing plan/action/outcome details so the original dashboard phase-2 want is completed through the app-owned boundary instead of leaving a duplicate completed cycle as the only closed record.

**Step 5: Verify live app state**

Run:
- `php artisan history:open-cycle`
- `php artisan history:summary`

Expected:
- the dashboard phase-2 want is no longer the open cycle because it now has plan/action/outcome records
- summary output reflects the reconciled cycle truthfully

### Task 4: Final verification

**Files:**
- Modify only the files needed by the tasks above

**Step 1: Run the full test suite**

Run: `php artisan test`

Expected: PASS

**Step 2: Review the worktree diff**

Run: `git status --short`

Expected: only the intended history command, action, tests, and plan files are changed in the worktree.

**Step 3: Record the implementation truthfully if needed**

If the new behavior changes the project’s recorded operational facts, use the app-owned history boundary to capture the implementation outcome without creating chat-only state.
