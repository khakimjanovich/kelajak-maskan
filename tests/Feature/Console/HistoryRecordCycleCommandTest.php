<?php

use App\Models\ActionRun;
use App\Models\AuditLog;
use App\Models\ConstraintSnapshot;
use App\Models\FactSource;
use App\Models\OutcomeLog;
use App\Models\PlanRevision;
use App\Models\Project;
use App\Models\ValidationRun;
use App\Models\Want;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Console\Concerns\SeedsHistoryCycles;

uses(RefreshDatabase::class, SeedsHistoryCycles::class);

it('records a full history cycle through an artisan command', function (): void {
    Project::create([
        'name' => 'Kelajak-Maskan',
        'slug' => 'kelajak-maskan',
    ]);

    $this->artisan('history:record-cycle', [
        'title' => 'Catch up project context phase',
        '--raw-text' => 'I want app history to catch up to the shipped project-context read surface.',
        '--want-status' => 'completed',
        '--constraints' => json_encode([
            'goal' => 'history catch-up',
            'must_use' => ['artisan command', 'app actions'],
        ], JSON_THROW_ON_ERROR),
        '--facts-status' => 'verified',
        '--constraints-status' => 'satisfied',
        '--experience-status' => 'verified',
        '--ikhlas-status' => 'pass',
        '--validation-summary' => 'The project-context read surface exists on main and needs a matching recorded history cycle.',
        '--fact-source' => [
            json_encode([
                'label' => 'Project context command',
                'url' => 'local://artisan/project-context',
                'status' => 'verified',
                'notes' => 'project:context reads the live stored context successfully.',
            ], JSON_THROW_ON_ERROR),
            json_encode([
                'label' => 'Git commit',
                'url' => 'local://git/show/143eb05',
                'status' => 'verified',
                'notes' => 'Project context read surface was added on main.',
            ], JSON_THROW_ON_ERROR),
        ],
        '--plan-text' => 'Record the shipped project-context phase through an app-owned command instead of leaving it only in git history.',
        '--grounded-summary' => 'The app history must catch up to the shipped code through its own command surface.',
        '--action-status' => 'completed',
        '--started-at' => '2026-03-24 12:00:00',
        '--finished-at' => '2026-03-24 12:05:00',
        '--outcome' => 'Project-context phase is now represented in app history.',
        '--reflection' => 'The app can catch up its own history through Artisan instead of raw database access.',
        '--actor-type' => 'assistant',
        '--actor-ref' => 'history-record-cycle-test',
    ])
        ->expectsOutputToContain('Project: Kelajak-Maskan (kelajak-maskan)')
        ->expectsOutputToContain('Want id:')
        ->expectsOutputToContain('Fact sources: 2')
        ->expectsOutputToContain('Outcome log id:')
        ->assertExitCode(0);

    expect(Want::count())->toBe(1)
        ->and(ConstraintSnapshot::count())->toBe(1)
        ->and(ValidationRun::count())->toBe(1)
        ->and(FactSource::count())->toBe(2)
        ->and(PlanRevision::count())->toBe(1)
        ->and(ActionRun::count())->toBe(1)
        ->and(OutcomeLog::count())->toBe(1)
        ->and(AuditLog::count())->toBe(5);

    $want = Want::query()->firstOrFail();
    $validationRun = ValidationRun::query()->firstOrFail();
    $planRevision = PlanRevision::query()->firstOrFail();
    $actionRun = ActionRun::query()->firstOrFail();
    $outcomeLog = OutcomeLog::query()->firstOrFail();

    expect($want->title)->toBe('Catch up project context phase')
        ->and($want->status)->toBe('completed')
        ->and($validationRun->summary)->toContain('project-context read surface')
        ->and($planRevision->grounded_summary)->toContain('app history must catch up')
        ->and($actionRun->status)->toBe('completed')
        ->and($outcomeLog->outcome)->toContain('Project-context phase is now represented');
});

it('can register a minimal want without forcing the rest of the cycle', function (): void {
    Project::create([
        'name' => 'Kelajak-Maskan',
        'slug' => 'kelajak-maskan',
    ]);

    $this->artisan('history:record-cycle', [
        'title' => 'Register planning-only want',
        '--actor-ref' => 'history-record-cycle-test',
    ])
        ->expectsOutputToContain('Want id:')
        ->expectsOutputToContain('Validation run id: none')
        ->expectsOutputToContain('Plan revision id: none')
        ->expectsOutputToContain('Outcome log id: none')
        ->assertExitCode(0);

    expect(Want::count())->toBe(1)
        ->and(ConstraintSnapshot::count())->toBe(0)
        ->and(ValidationRun::count())->toBe(0)
        ->and(FactSource::count())->toBe(0)
        ->and(PlanRevision::count())->toBe(0)
        ->and(ActionRun::count())->toBe(0)
        ->and(OutcomeLog::count())->toBe(0)
        ->and(AuditLog::count())->toBe(1);

    expect(Want::query()->firstOrFail()->raw_text)->toBe('Register planning-only want');
});

it('fails clearly on invalid json input', function (): void {
    Project::create([
        'name' => 'Kelajak-Maskan',
        'slug' => 'kelajak-maskan',
    ]);

    $this->artisan('history:record-cycle', [
        'title' => 'Bad constraints payload',
        '--constraints' => '{bad-json',
    ])
        ->expectsOutputToContain('Invalid JSON for --constraints.')
        ->assertExitCode(1);

    expect(Want::count())->toBe(0)
        ->and(AuditLog::count())->toBe(0);
});

it('can advance an existing want instead of creating a duplicate want', function (): void {
    $project = $this->createHistoryProject();
    $want = $this->createHistoryWant($project, [
        'title' => 'Dashboard wants phase 2',
        'raw_text' => 'Keep the dashboard phase 2 cycle attached to the original want.',
        'status' => 'draft',
        'created_at' => '2026-03-25 09:00:00',
    ]);

    $this->createPlanRevision($want, [
        'version' => 1,
        'plan_text' => 'Initial dashboard plan.',
        'grounded_summary' => 'The first revision exists already.',
        'created_at' => '2026-03-25 09:05:00',
    ]);

    $this->artisan('history:record-cycle', [
        'title' => 'Dashboard wants phase 2',
        '--project' => 'kelajak-maskan',
        '--want-id' => (string) $want->id,
        '--want-status' => 'completed',
        '--plan-text' => 'Reconcile the existing dashboard cycle instead of duplicating it.',
        '--grounded-summary' => 'The original dashboard want should own the final execution record.',
        '--action-status' => 'completed',
        '--started-at' => '2026-03-25 09:10:00',
        '--finished-at' => '2026-03-25 09:15:00',
        '--outcome' => 'Dashboard phase 2 is now attached to the original want.',
        '--reflection' => 'Advancing an existing want keeps the app history cleaner.',
        '--actor-type' => 'assistant',
        '--actor-ref' => 'history-record-cycle-test',
    ])
        ->expectsOutputToContain(sprintf('Want id: %d', $want->id))
        ->expectsOutputToContain('Plan revision id:')
        ->expectsOutputToContain('Outcome log id:')
        ->assertExitCode(0);

    expect(Want::count())->toBe(1)
        ->and(PlanRevision::count())->toBe(2)
        ->and(ActionRun::count())->toBe(1)
        ->and(OutcomeLog::count())->toBe(1)
        ->and(AuditLog::count())->toBe(4);

    $want->refresh();

    expect($want->status)->toBe('completed')
        ->and($want->title)->toBe('Dashboard wants phase 2');

    $latestPlanRevision = PlanRevision::query()->latest('id')->firstOrFail();

    expect($latestPlanRevision->want_id)->toBe($want->id)
        ->and($latestPlanRevision->version)->toBe(2)
        ->and($latestPlanRevision->plan_text)->toContain('Reconcile the existing dashboard cycle');
});
