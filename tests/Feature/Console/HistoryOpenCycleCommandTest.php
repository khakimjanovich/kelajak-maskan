<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Console\Concerns\SeedsHistoryCycles;

uses(RefreshDatabase::class, SeedsHistoryCycles::class);

it('returns the newest open cycle when the latest action is not terminal', function (): void {
    $project = $this->createHistoryProject();

    $completedWant = $this->createHistoryWant($project, [
        'title' => 'Completed cycle',
        'status' => 'completed',
        'created_at' => '2026-03-24 09:00:00',
    ]);
    $completedPlan = $this->createPlanRevision($completedWant, [
        'version' => 1,
        'created_at' => '2026-03-24 09:01:00',
    ]);
    $completedAction = $this->createActionRun($completedPlan, [
        'status' => 'completed',
        'created_at' => '2026-03-24 09:02:00',
    ]);
    $this->createOutcomeLog($completedAction, [
        'outcome' => 'Completed cycle outcome',
        'created_at' => '2026-03-24 09:03:00',
    ]);

    $openWant = $this->createHistoryWant($project, [
        'title' => 'Newest open cycle',
        'status' => 'active',
        'created_at' => '2026-03-24 10:00:00',
    ]);
    $this->createValidationRun($openWant, ['created_at' => '2026-03-24 10:01:00']);
    $openPlan = $this->createPlanRevision($openWant, [
        'version' => 2,
        'created_at' => '2026-03-24 10:02:00',
    ]);
    $this->createActionRun($openPlan, [
        'status' => 'running',
        'finished_at' => null,
        'created_at' => '2026-03-24 10:03:00',
    ]);

    $this->artisan('history:open-cycle')
        ->expectsOutputToContain('Newest open cycle')
        ->expectsOutputToContain('Action status: running')
        ->expectsOutputToContain('Reason:')
        ->assertExitCode(0);
});

it('ignores newer completed cycles and returns the newest still-open cycle', function (): void {
    $project = $this->createHistoryProject();

    $openWant = $this->createHistoryWant($project, [
        'title' => 'Older open cycle',
        'status' => 'active',
        'created_at' => '2026-03-24 09:00:00',
    ]);
    $openPlan = $this->createPlanRevision($openWant, [
        'version' => 1,
        'created_at' => '2026-03-24 09:01:00',
    ]);
    $this->createActionRun($openPlan, [
        'status' => 'running',
        'finished_at' => null,
        'created_at' => '2026-03-24 09:02:00',
    ]);

    $completedWant = $this->createHistoryWant($project, [
        'title' => 'Latest completed cycle',
        'status' => 'completed',
        'created_at' => '2026-03-24 10:00:00',
    ]);
    $completedPlan = $this->createPlanRevision($completedWant, [
        'version' => 2,
        'created_at' => '2026-03-24 10:01:00',
    ]);
    $completedAction = $this->createActionRun($completedPlan, [
        'status' => 'completed',
        'created_at' => '2026-03-24 10:02:00',
    ]);
    $this->createOutcomeLog($completedAction, [
        'outcome' => 'Latest completed outcome',
        'created_at' => '2026-03-24 10:03:00',
    ]);

    $this->artisan('history:open-cycle')
        ->expectsOutputToContain('Older open cycle')
        ->expectsOutputToContain('running')
        ->assertExitCode(0);
});

it('prints an explicit message when no open cycle exists', function (): void {
    $project = $this->createHistoryProject();

    $want = $this->createHistoryWant($project, [
        'title' => 'Closed cycle',
        'status' => 'completed',
        'created_at' => '2026-03-24 09:00:00',
    ]);
    $plan = $this->createPlanRevision($want, [
        'version' => 1,
        'created_at' => '2026-03-24 09:01:00',
    ]);
    $action = $this->createActionRun($plan, [
        'status' => 'completed_with_defect_discovery',
        'created_at' => '2026-03-24 09:02:00',
    ]);
    $this->createOutcomeLog($action, [
        'outcome' => 'Closed cycle outcome',
        'created_at' => '2026-03-24 09:03:00',
    ]);

    $this->artisan('history:open-cycle')
        ->expectsOutputToContain('no open cycle')
        ->assertExitCode(0);
});
