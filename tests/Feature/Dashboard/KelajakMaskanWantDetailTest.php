<?php

use App\Models\ActionRun;
use App\Models\AuditLog;
use App\Models\ConstraintSnapshot;
use App\Models\OutcomeLog;
use App\Models\PlanRevision;
use App\Models\Project;
use App\Models\ProjectContext;
use App\Models\ValidationRun;
use App\Models\Want;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Console\Concerns\SeedsHistoryCycles;

uses(RefreshDatabase::class, SeedsHistoryCycles::class);

function createWantDetailProjectContext(Project $project): ProjectContext
{
    return ProjectContext::query()->create([
        'project_id' => $project->id,
        'summary' => 'Laravel 13 application that stores project context, wants, plans, actions, and outcomes in a local SQLite spine.',
        'repo_path' => '/Users/example/kelajak-maskan',
        'primary_branch' => 'main',
        'stack' => ['Laravel 13', 'PHP 8.5', 'SQLite', 'Pest'],
        'commands' => [
            'php artisan history:latest-want',
            'php artisan history:summary',
            'php artisan history:open-cycle',
            'php artisan history:record-cycle',
            'php artisan project:context',
            'php artisan project:refresh-context',
        ],
        'conventions' => [
            'Read app history before planning or acting.',
            'Keep planning and execution isolated in a worktree.',
        ],
        'key_paths' => [
            'app/Console/Commands',
            'app/Support/Dashboard',
            'app/Support/History',
        ],
        'current_phase' => 'app-owned-history-cycle-recording',
        'source_refs' => [
            'app/Console/Commands/HistorySummary.php',
            'app/Support/Dashboard/KelajakMaskanDashboardData.php',
        ],
    ]);
}

it('shows a read-only want detail page with stage and latest plan text', function (): void {
    $project = $this->createHistoryProject();
    createWantDetailProjectContext($project);

    $want = $this->createHistoryWant($project, [
        'title' => 'Focus a single want on a readable detail page',
        'status' => 'active',
        'created_at' => '2026-03-25 11:00:00',
    ]);

    $this->createConstraintSnapshot($want, [
        'created_at' => '2026-03-25 11:01:00',
    ]);
    $this->createValidationRun($want, [
        'summary' => 'A dedicated detail page keeps longer plan text readable.',
        'created_at' => '2026-03-25 11:02:00',
    ]);
    $this->createPlanRevision($want, [
        'plan_text' => 'Show the latest plan text, grounded summary, and derived stage on a read-only want detail page.',
        'grounded_summary' => 'The detail page should help a human read the current plan without mutating history.',
        'created_at' => '2026-03-25 11:03:00',
    ]);

    $response = $this->get("/wants/{$want->id}");

    $response->assertOk();
    $response->assertSee('Focus a single want on a readable detail page');
    $response->assertSee('planned');
    $response->assertSee('Show the latest plan text, grounded summary, and derived stage on a read-only want detail page.');
    $response->assertSee('The detail page should help a human read the current plan without mutating history.');
    $response->assertDontSee('/Users/example/kelajak-maskan');
});

it('keeps dashboard reads fully read-only for history tables', function (): void {
    $project = $this->createHistoryProject();
    createWantDetailProjectContext($project);

    $want = $this->createHistoryWant($project, [
        'title' => 'Verify dashboard reads stay read-only',
        'status' => 'active',
        'created_at' => '2026-03-25 11:10:00',
    ]);

    $constraintSnapshot = $this->createConstraintSnapshot($want, [
        'created_at' => '2026-03-25 11:11:00',
    ]);
    $validationRun = $this->createValidationRun($want, [
        'created_at' => '2026-03-25 11:12:00',
    ]);
    $planRevision = $this->createPlanRevision($want, [
        'created_at' => '2026-03-25 11:13:00',
    ]);
    $actionRun = $this->createActionRun($planRevision, [
        'status' => 'in_progress',
        'finished_at' => null,
        'created_at' => '2026-03-25 11:14:00',
    ]);

    $countsBefore = [
        'projects' => Project::count(),
        'project_contexts' => ProjectContext::count(),
        'wants' => Want::count(),
        'constraint_snapshots' => ConstraintSnapshot::count(),
        'validation_runs' => ValidationRun::count(),
        'plan_revisions' => PlanRevision::count(),
        'action_runs' => ActionRun::count(),
        'outcome_logs' => OutcomeLog::count(),
        'audit_logs' => AuditLog::count(),
    ];

    $this->get('/')->assertOk();
    $this->get("/wants/{$want->id}")->assertOk();

    expect([
        'projects' => Project::count(),
        'project_contexts' => ProjectContext::count(),
        'wants' => Want::count(),
        'constraint_snapshots' => ConstraintSnapshot::count(),
        'validation_runs' => ValidationRun::count(),
        'plan_revisions' => PlanRevision::count(),
        'action_runs' => ActionRun::count(),
        'outcome_logs' => OutcomeLog::count(),
        'audit_logs' => AuditLog::count(),
    ])->toBe($countsBefore);

    expect($constraintSnapshot->exists)->toBeTrue();
    expect($validationRun->exists)->toBeTrue();
    expect($planRevision->exists)->toBeTrue();
    expect($actionRun->exists)->toBeTrue();
});
