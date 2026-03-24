<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Console\Concerns\SeedsHistoryCycles;

uses(RefreshDatabase::class, SeedsHistoryCycles::class);

it('selects the latest want by created_at then id and prints its status', function (): void {
    $project = $this->createHistoryProject();

    $this->createHistoryWant($project, [
        'title' => 'Older want',
        'status' => 'completed',
        'created_at' => '2026-03-24 08:00:00',
    ]);

    $this->createHistoryWant($project, [
        'title' => 'Same time lower id',
        'status' => 'blocked',
        'created_at' => '2026-03-24 09:00:00',
    ]);

    $latestWant = $this->createHistoryWant($project, [
        'title' => 'Same time higher id',
        'status' => 'in_review',
        'created_at' => '2026-03-24 09:00:00',
    ]);

    $this->createConstraintSnapshot($latestWant, [
        'payload' => ['phase' => 'phase_4', 'scope' => 'latest-want'],
        'created_at' => '2026-03-24 09:01:00',
    ]);

    $this->artisan('history:latest-want')
        ->expectsOutputToContain('Same time higher id')
        ->expectsOutputToContain('in_review')
        ->expectsOutputToContain((string) $latestWant->id)
        ->assertExitCode(0);
});

it('prints the latest linked history context for the latest want', function (): void {
    $project = $this->createHistoryProject();
    $want = $this->createHistoryWant($project, [
        'title' => 'Read command rollout',
        'status' => 'active',
        'created_at' => '2026-03-24 10:00:00',
    ]);

    $this->createConstraintSnapshot($want, [
        'payload' => ['phase' => 'phase_4', 'marker' => 'older-constraint'],
        'created_at' => '2026-03-24 10:01:00',
    ]);

    $this->createConstraintSnapshot($want, [
        'payload' => ['phase' => 'phase_4', 'marker' => 'latest-constraint'],
        'created_at' => '2026-03-24 10:02:00',
    ]);

    $olderValidation = $this->createValidationRun($want, [
        'facts_status' => 'stale',
        'constraints_status' => 'stale',
        'experience_status' => 'stale',
        'ikhlas_status' => 'warn',
        'summary' => 'Older validation summary',
        'created_at' => '2026-03-24 10:03:00',
    ]);
    $this->createFactSources($olderValidation, 1, ['created_at' => '2026-03-24 10:03:30']);

    $latestValidation = $this->createValidationRun($want, [
        'facts_status' => 'verified',
        'constraints_status' => 'satisfied',
        'experience_status' => 'verified',
        'ikhlas_status' => 'pass',
        'summary' => 'Latest validation summary',
        'created_at' => '2026-03-24 10:04:00',
    ]);
    $this->createFactSources($latestValidation, 2, ['created_at' => '2026-03-24 10:04:30']);

    $olderPlan = $this->createPlanRevision($want, [
        'version' => 1,
        'grounded_summary' => 'Older plan summary',
        'created_at' => '2026-03-24 10:05:00',
    ]);
    $olderAction = $this->createActionRun($olderPlan, [
        'status' => 'completed',
        'created_at' => '2026-03-24 10:05:30',
    ]);
    $this->createOutcomeLog($olderAction, [
        'outcome' => 'Older outcome text',
        'reflection' => 'Older reflection text',
        'created_at' => '2026-03-24 10:05:45',
    ]);

    $latestPlan = $this->createPlanRevision($want, [
        'version' => 2,
        'grounded_summary' => 'Latest grounded summary',
        'created_at' => '2026-03-24 10:06:00',
    ]);
    $latestAction = $this->createActionRun($latestPlan, [
        'status' => 'running',
        'finished_at' => null,
        'created_at' => '2026-03-24 10:06:30',
    ]);
    $this->createOutcomeLog($latestAction, [
        'outcome' => 'Latest outcome text preview',
        'reflection' => 'Latest reflection text',
        'created_at' => '2026-03-24 10:06:45',
    ]);

    $this->artisan('history:latest-want')
        ->expectsOutputToContain('latest-constraint')
        ->expectsOutputToContain('verified')
        ->expectsOutputToContain('satisfied')
        ->expectsOutputToContain('pass')
        ->expectsOutputToContain('Fact sources: 2')
        ->expectsOutputToContain('Plan revision: v2')
        ->expectsOutputToContain('Action status: running')
        ->expectsOutputToContain('Latest outcome text preview')
        ->assertExitCode(0);
});

it('returns a clear failure when the project does not exist', function (): void {
    $this->artisan('history:latest-want', ['project' => 'missing-project'])
        ->expectsOutputToContain('missing-project')
        ->assertExitCode(1);
});

it('reports when a project has no wants yet', function (): void {
    $this->createHistoryProject();

    $this->artisan('history:latest-want')
        ->expectsOutputToContain('no wants recorded yet')
        ->assertExitCode(0);
});
