<?php

use App\Actions\History\CreateProject;
use App\Actions\History\CreateWant;
use App\Actions\History\SaveConstraintSnapshot;
use App\Data\History\CreateProjectData;
use App\Data\History\CreateWantData;
use App\Data\History\SaveConstraintSnapshotData;
use App\Models\AuditLog;
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
