<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Console\Concerns\SeedsHistoryCycles;

uses(RefreshDatabase::class, SeedsHistoryCycles::class);

it('prints the latest three wants in descending order', function (): void {
    $project = $this->createHistoryProject();

    $this->createHistoryWant($project, [
        'title' => 'Want one',
        'created_at' => '2026-03-24 08:00:00',
    ]);
    $this->createHistoryWant($project, [
        'title' => 'Want two',
        'created_at' => '2026-03-24 09:00:00',
    ]);
    $this->createHistoryWant($project, [
        'title' => 'Want three',
        'created_at' => '2026-03-24 10:00:00',
    ]);
    $this->createHistoryWant($project, [
        'title' => 'Want four',
        'created_at' => '2026-03-24 11:00:00',
    ]);

    $this->artisan('history:summary')
        ->expectsOutputToContain('Want four')
        ->expectsOutputToContain('Want three')
        ->expectsOutputToContain('Want two')
        ->assertExitCode(0);
});

it('includes the latest completed outcome in the summary', function (): void {
    $project = $this->createHistoryProject();

    $olderWant = $this->createHistoryWant($project, [
        'title' => 'Older completed want',
        'status' => 'completed',
        'created_at' => '2026-03-24 08:00:00',
    ]);
    $olderPlan = $this->createPlanRevision($olderWant, [
        'created_at' => '2026-03-24 08:01:00',
    ]);
    $olderAction = $this->createActionRun($olderPlan, [
        'status' => 'completed',
        'created_at' => '2026-03-24 08:02:00',
    ]);
    $this->createOutcomeLog($olderAction, [
        'outcome' => 'Older completed outcome',
        'created_at' => '2026-03-24 08:03:00',
    ]);

    $latestWant = $this->createHistoryWant($project, [
        'title' => 'Latest completed want',
        'status' => 'completed',
        'created_at' => '2026-03-24 09:00:00',
    ]);
    $latestPlan = $this->createPlanRevision($latestWant, [
        'created_at' => '2026-03-24 09:01:00',
    ]);
    $latestAction = $this->createActionRun($latestPlan, [
        'status' => 'completed',
        'created_at' => '2026-03-24 09:02:00',
    ]);
    $this->createOutcomeLog($latestAction, [
        'outcome' => 'Latest completed outcome',
        'created_at' => '2026-03-24 09:03:00',
    ]);

    $this->artisan('history:summary')
        ->expectsOutputToContain('Latest completed outcome')
        ->assertExitCode(0);
});

it('includes the latest open cycle state in the summary', function (): void {
    $project = $this->createHistoryProject();

    $want = $this->createHistoryWant($project, [
        'title' => 'Summary open cycle',
        'status' => 'active',
        'created_at' => '2026-03-24 10:00:00',
    ]);
    $plan = $this->createPlanRevision($want, [
        'version' => 3,
        'created_at' => '2026-03-24 10:01:00',
    ]);
    $this->createActionRun($plan, [
        'status' => 'running',
        'finished_at' => null,
        'created_at' => '2026-03-24 10:02:00',
    ]);

    $this->artisan('history:summary')
        ->expectsOutputToContain('Open cycle')
        ->expectsOutputToContain('Summary open cycle')
        ->expectsOutputToContain('running')
        ->assertExitCode(0);
});

it('surfaces defect wording only from stored outcome or reflection text', function (): void {
    $project = $this->createHistoryProject();

    $want = $this->createHistoryWant($project, [
        'title' => 'Defect review cycle',
        'status' => 'completed',
        'created_at' => '2026-03-24 10:00:00',
    ]);
    $plan = $this->createPlanRevision($want, [
        'created_at' => '2026-03-24 10:01:00',
    ]);
    $action = $this->createActionRun($plan, [
        'status' => 'completed_with_defect_discovery',
        'created_at' => '2026-03-24 10:02:00',
    ]);
    $this->createOutcomeLog($action, [
        'outcome' => 'defect: missing live read verification',
        'reflection' => 'Reflection notes for defect triage',
        'created_at' => '2026-03-24 10:03:00',
    ]);

    $this->artisan('history:summary')
        ->expectsOutputToContain('Unresolved issues')
        ->expectsOutputToContain('defect: missing live read verification')
        ->assertExitCode(0);
});

it('stops reporting a want as the open cycle after record cycle closes that same want', function (): void {
    $project = $this->createHistoryProject();

    $want = $this->createHistoryWant($project, [
        'title' => 'Dashboard wants phase 2',
        'status' => 'draft',
        'created_at' => '2026-03-25 09:00:00',
    ]);
    $this->createPlanRevision($want, [
        'version' => 1,
        'created_at' => '2026-03-25 09:05:00',
    ]);

    $this->artisan('history:record-cycle', [
        'title' => 'Dashboard wants phase 2',
        '--project' => 'kelajak-maskan',
        '--want-id' => (string) $want->id,
        '--want-status' => 'completed',
        '--plan-text' => 'Attach the shipped dashboard work to the existing want.',
        '--grounded-summary' => 'The summary should read the original want as completed.',
        '--action-status' => 'completed',
        '--started-at' => '2026-03-25 09:10:00',
        '--finished-at' => '2026-03-25 09:15:00',
        '--outcome' => 'Dashboard phase 2 now has a terminal recorded outcome.',
        '--reflection' => 'Closing the original want removes the stale open-cycle reason.',
        '--actor-ref' => 'history-record-cycle-test',
    ])->assertExitCode(0);

    $this->artisan('history:summary')
        ->expectsOutputToContain('Latest completed outcome')
        ->expectsOutputToContain('Dashboard phase 2 now has a terminal recorded outcome.')
        ->expectsOutputToContain('Unresolved issues')
        ->expectsOutputToContain('- none')
        ->assertExitCode(0);
});
