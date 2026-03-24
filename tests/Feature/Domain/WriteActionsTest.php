<?php

use App\Actions\History\CreateProject;
use App\Actions\History\CreateActionRun;
use App\Actions\History\CreatePlanRevision;
use App\Actions\History\CreateWant;
use App\Actions\History\LogOutcome;
use App\Actions\History\SaveConstraintSnapshot;
use App\Data\History\CreateActionRunData;
use App\Data\History\CreatePlanRevisionData;
use App\Data\History\CreateProjectData;
use App\Data\History\CreateWantData;
use App\Data\History\LogOutcomeData;
use App\Data\History\SaveConstraintSnapshotData;
use App\Models\ActionRun;
use App\Models\AuditLog;
use App\Models\PlanRevision;
use App\Models\Project;
use App\Models\Want;
use App\Support\Audit\AuditContext;

it('writes a project row and audit log through create project', function (): void {
    $project = app(CreateProject::class)->handle(
        new CreateProjectData(
            name: 'Kelajak-Maskan',
            slug: 'kelajak-maskan',
        ),
        new AuditContext(
            actorType: 'assistant',
            actorRef: 'codex',
        ),
    );

    $this->assertDatabaseCount('projects', 1);
    $this->assertDatabaseCount('audit_logs', 1);

    $auditLog = AuditLog::query()->sole();

    expect($auditLog->action_name)->toBe('history.create_project');
    expect($auditLog->actor_type)->toBe('assistant');
    expect($auditLog->actor_ref)->toBe('codex');
    expect($auditLog->target_type)->toBe('project');
    expect($auditLog->target_id)->toBe($project->id);
    expect($auditLog->status)->toBe('success');
    expect($auditLog->input_payload)->toBe([
        'name' => 'Kelajak-Maskan',
        'slug' => 'kelajak-maskan',
    ]);
});

it('writes a want row and audit log through create want', function (): void {
    $project = Project::create([
        'name' => 'Kelajak-Maskan',
        'slug' => 'kelajak-maskan',
    ]);

    $want = app(CreateWant::class)->handle(
        new CreateWantData(
            projectId: $project->id,
            title: 'Track grounded wants',
            rawText: 'I want write actions first.',
            status: 'draft',
        ),
        new AuditContext(
            actorType: 'assistant',
            actorRef: 'codex',
        ),
    );

    $this->assertDatabaseCount('wants', 1);
    $this->assertDatabaseCount('audit_logs', 1);

    $auditLog = AuditLog::query()->sole();

    expect($auditLog->action_name)->toBe('history.create_want');
    expect($auditLog->actor_type)->toBe('assistant');
    expect($auditLog->actor_ref)->toBe('codex');
    expect($auditLog->target_type)->toBe('want');
    expect($auditLog->target_id)->toBe($want->id);
    expect($auditLog->status)->toBe('success');
    expect($auditLog->input_payload)->toBe([
        'project_id' => $project->id,
        'title' => 'Track grounded wants',
        'raw_text' => 'I want write actions first.',
        'status' => 'draft',
    ]);
});

it('writes a constraint snapshot row and audit log through save constraint snapshot', function (): void {
    $project = Project::create([
        'name' => 'Kelajak-Maskan',
        'slug' => 'kelajak-maskan',
    ]);

    $want = Want::create([
        'project_id' => $project->id,
        'title' => 'Track grounded wants',
        'raw_text' => 'I want write actions first.',
        'status' => 'draft',
    ]);

    $snapshot = app(SaveConstraintSnapshot::class)->handle(
        new SaveConstraintSnapshotData(
            wantId: $want->id,
            payload: ['scope' => 'phase-2'],
        ),
        new AuditContext(
            actorType: 'assistant',
            actorRef: 'codex',
        ),
    );

    $this->assertDatabaseCount('constraint_snapshots', 1);
    $this->assertDatabaseCount('audit_logs', 1);

    $auditLog = AuditLog::query()->sole();

    expect($auditLog->action_name)->toBe('history.save_constraint_snapshot');
    expect($auditLog->actor_type)->toBe('assistant');
    expect($auditLog->actor_ref)->toBe('codex');
    expect($auditLog->target_type)->toBe('constraint_snapshot');
    expect($auditLog->target_id)->toBe($snapshot->id);
    expect($auditLog->status)->toBe('success');
    expect($auditLog->input_payload)->toBe([
        'want_id' => $want->id,
        'payload' => ['scope' => 'phase-2'],
    ]);
});

it('writes a plan revision row and audit log through create plan revision', function (): void {
    $project = Project::create([
        'name' => 'Kelajak-Maskan',
        'slug' => 'kelajak-maskan',
    ]);

    $want = Want::create([
        'project_id' => $project->id,
        'title' => 'Machine discipline',
        'raw_text' => 'Finish the missing audited write actions.',
        'status' => 'active',
    ]);

    $planRevision = app(CreatePlanRevision::class)->handle(
        new CreatePlanRevisionData(
            wantId: $want->id,
            version: 2,
            planText: 'Add the remaining audited write actions.',
            groundedSummary: 'The app can record plan revisions without direct model writes.',
        ),
        new AuditContext(
            actorType: 'assistant',
            actorRef: 'codex',
        ),
    );

    $this->assertDatabaseCount('plan_revisions', 1);
    $this->assertDatabaseCount('audit_logs', 1);

    $auditLog = AuditLog::query()->sole();

    expect($planRevision->want_id)->toBe($want->id);
    expect($planRevision->version)->toBe(2);
    expect($planRevision->plan_text)->toBe('Add the remaining audited write actions.');
    expect($planRevision->grounded_summary)->toBe('The app can record plan revisions without direct model writes.');
    expect($auditLog->action_name)->toBe('history.create_plan_revision');
    expect($auditLog->actor_type)->toBe('assistant');
    expect($auditLog->actor_ref)->toBe('codex');
    expect($auditLog->target_type)->toBe('plan_revision');
    expect($auditLog->target_id)->toBe($planRevision->id);
    expect($auditLog->status)->toBe('success');
    expect($auditLog->input_payload)->toBe([
        'want_id' => $want->id,
        'version' => 2,
        'plan_text' => 'Add the remaining audited write actions.',
        'grounded_summary' => 'The app can record plan revisions without direct model writes.',
    ]);
    expect($auditLog->result_payload)->toBe([
        'plan_revision_id' => $planRevision->id,
    ]);
});

it('writes an action run row and audit log through create action run', function (): void {
    $project = Project::create([
        'name' => 'Kelajak-Maskan',
        'slug' => 'kelajak-maskan',
    ]);

    $want = Want::create([
        'project_id' => $project->id,
        'title' => 'Machine discipline',
        'raw_text' => 'Finish the missing audited write actions.',
        'status' => 'active',
    ]);

    $planRevision = PlanRevision::create([
        'want_id' => $want->id,
        'version' => 1,
        'plan_text' => 'Create the next action boundary.',
        'grounded_summary' => 'The app should track action runs through audited writes.',
    ]);

    $actionRun = app(CreateActionRun::class)->handle(
        new CreateActionRunData(
            planRevisionId: $planRevision->id,
            status: 'completed',
            startedAt: '2026-03-24 09:00:00',
            finishedAt: '2026-03-24 09:05:00',
        ),
        new AuditContext(
            actorType: 'assistant',
            actorRef: 'codex',
        ),
    );

    $this->assertDatabaseCount('action_runs', 1);
    $this->assertDatabaseCount('audit_logs', 1);

    $auditLog = AuditLog::query()->sole();

    expect($actionRun->plan_revision_id)->toBe($planRevision->id);
    expect($actionRun->status)->toBe('completed');
    expect($actionRun->started_at)->toBe('2026-03-24 09:00:00');
    expect($actionRun->finished_at)->toBe('2026-03-24 09:05:00');
    expect($auditLog->action_name)->toBe('history.create_action_run');
    expect($auditLog->actor_type)->toBe('assistant');
    expect($auditLog->actor_ref)->toBe('codex');
    expect($auditLog->target_type)->toBe('action_run');
    expect($auditLog->target_id)->toBe($actionRun->id);
    expect($auditLog->status)->toBe('success');
    expect($auditLog->input_payload)->toBe([
        'plan_revision_id' => $planRevision->id,
        'status' => 'completed',
        'started_at' => '2026-03-24 09:00:00',
        'finished_at' => '2026-03-24 09:05:00',
    ]);
    expect($auditLog->result_payload)->toBe([
        'action_run_id' => $actionRun->id,
    ]);
});

it('writes an outcome log row and audit log through log outcome', function (): void {
    $project = Project::create([
        'name' => 'Kelajak-Maskan',
        'slug' => 'kelajak-maskan',
    ]);

    $want = Want::create([
        'project_id' => $project->id,
        'title' => 'Machine discipline',
        'raw_text' => 'Finish the missing audited write actions.',
        'status' => 'active',
    ]);

    $planRevision = PlanRevision::create([
        'want_id' => $want->id,
        'version' => 1,
        'plan_text' => 'Create the next action boundary.',
        'grounded_summary' => 'The app should track outcomes through audited writes.',
    ]);

    $actionRun = ActionRun::create([
        'plan_revision_id' => $planRevision->id,
        'status' => 'completed',
        'started_at' => '2026-03-24 09:00:00',
        'finished_at' => '2026-03-24 09:05:00',
    ]);

    $outcomeLog = app(LogOutcome::class)->handle(
        new LogOutcomeData(
            actionRunId: $actionRun->id,
            outcome: 'The full planning cycle is now inside audited actions.',
            reflection: 'Future Codex flows can build on the app-owned write boundary.',
        ),
        new AuditContext(
            actorType: 'assistant',
            actorRef: 'codex',
        ),
    );

    $this->assertDatabaseCount('outcome_logs', 1);
    $this->assertDatabaseCount('audit_logs', 1);

    $auditLog = AuditLog::query()->sole();

    expect($outcomeLog->action_run_id)->toBe($actionRun->id);
    expect($outcomeLog->outcome)->toBe('The full planning cycle is now inside audited actions.');
    expect($outcomeLog->reflection)->toBe('Future Codex flows can build on the app-owned write boundary.');
    expect($auditLog->action_name)->toBe('history.log_outcome');
    expect($auditLog->actor_type)->toBe('assistant');
    expect($auditLog->actor_ref)->toBe('codex');
    expect($auditLog->target_type)->toBe('outcome_log');
    expect($auditLog->target_id)->toBe($outcomeLog->id);
    expect($auditLog->status)->toBe('success');
    expect($auditLog->input_payload)->toBe([
        'action_run_id' => $actionRun->id,
        'outcome' => 'The full planning cycle is now inside audited actions.',
        'reflection' => 'Future Codex flows can build on the app-owned write boundary.',
    ]);
    expect($auditLog->result_payload)->toBe([
        'outcome_log_id' => $outcomeLog->id,
    ]);
});
