<?php

use App\Data\History\CreateProjectData;
use App\Data\History\CreateActionRunData;
use App\Data\History\CreatePlanRevisionData;
use App\Data\History\CreateWantData;
use App\Data\History\LogOutcomeData;
use App\Data\History\SaveConstraintSnapshotData;
use App\Support\Audit\AuditContext;

it('stores readonly constructor data for history write objects', function (): void {
    $projectData = new CreateProjectData(
        name: 'Kelajak-Maskan',
        slug: 'kelajak-maskan',
    );

    $wantData = new CreateWantData(
        projectId: 1,
        title: 'Track grounded wants',
        rawText: 'I want write actions first.',
        status: 'draft',
    );

    $constraintSnapshotData = new SaveConstraintSnapshotData(
        wantId: 2,
        payload: ['scope' => 'phase-2'],
    );

    $planRevisionData = new CreatePlanRevisionData(
        wantId: 2,
        version: 3,
        planText: 'Finish the missing audited write actions.',
        groundedSummary: 'The planning cycle can be completed without direct model writes.',
    );

    $actionRunData = new CreateActionRunData(
        planRevisionId: 5,
        status: 'completed',
        startedAt: '2026-03-24 09:00:00',
        finishedAt: '2026-03-24 09:05:00',
    );

    $outcomeData = new LogOutcomeData(
        actionRunId: 7,
        outcome: 'The audited action boundary was completed.',
        reflection: 'Future Codex-driven flows can now stay inside the app boundary.',
    );

    $auditContext = new AuditContext(
        actorType: 'assistant',
        actorRef: 'codex',
    );

    expect($projectData->name)->toBe('Kelajak-Maskan');
    expect($projectData->slug)->toBe('kelajak-maskan');
    expect($wantData->projectId)->toBe(1);
    expect($wantData->title)->toBe('Track grounded wants');
    expect($wantData->rawText)->toBe('I want write actions first.');
    expect($wantData->status)->toBe('draft');
    expect($constraintSnapshotData->wantId)->toBe(2);
    expect($constraintSnapshotData->payload)->toBe(['scope' => 'phase-2']);
    expect($planRevisionData->wantId)->toBe(2);
    expect($planRevisionData->version)->toBe(3);
    expect($planRevisionData->planText)->toBe('Finish the missing audited write actions.');
    expect($planRevisionData->groundedSummary)->toBe('The planning cycle can be completed without direct model writes.');
    expect($actionRunData->planRevisionId)->toBe(5);
    expect($actionRunData->status)->toBe('completed');
    expect($actionRunData->startedAt)->toBe('2026-03-24 09:00:00');
    expect($actionRunData->finishedAt)->toBe('2026-03-24 09:05:00');
    expect($outcomeData->actionRunId)->toBe(7);
    expect($outcomeData->outcome)->toBe('The audited action boundary was completed.');
    expect($outcomeData->reflection)->toBe('Future Codex-driven flows can now stay inside the app boundary.');
    expect($auditContext->actorType)->toBe('assistant');
    expect($auditContext->actorRef)->toBe('codex');
});
