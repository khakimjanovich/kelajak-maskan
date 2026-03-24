<?php

use App\Data\History\CreateProjectData;
use App\Data\History\CreateWantData;
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
    expect($auditContext->actorType)->toBe('assistant');
    expect($auditContext->actorRef)->toBe('codex');
});
