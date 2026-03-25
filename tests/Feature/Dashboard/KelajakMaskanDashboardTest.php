<?php

use App\Models\Project;
use App\Models\ProjectContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Console\Concerns\SeedsHistoryCycles;

uses(RefreshDatabase::class, SeedsHistoryCycles::class);

function createDashboardProjectContext(Project $project): ProjectContext
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
            'php artisan test',
        ],
        'conventions' => [
            'Read app history before planning or acting.',
            'Keep planning and execution isolated in a worktree.',
            'Use Laravel code paths and artisan commands instead of raw sqlite or ad hoc PHP scripts.',
            'Do not claim completion until app state and verification match the work.',
        ],
        'key_paths' => [
            'app/Console/Commands',
            'app/Models',
            'app/Support/History',
            'app/Support/ProjectContext',
            'database/migrations',
            'database/database.sqlite',
        ],
        'current_phase' => 'app-owned-history-cycle-recording',
        'source_refs' => [
            'composer.json',
            'database/database.sqlite',
            'app/Console/Commands/HistoryLatestWant.php',
            'app/Console/Commands/HistorySummary.php',
            'app/Console/Commands/HistoryRecordCycle.php',
            'app/Console/Commands/ProjectContext.php',
            'app/Console/Commands/ProjectRefreshContext.php',
            'app/Support/History/HistoryReader.php',
            'app/Support/ProjectContext/ProjectContextPayload.php',
        ],
    ]);
}

it('shows the first kelajak-maskan dashboard view on the root page', function (): void {
    $project = $this->createHistoryProject();
    createDashboardProjectContext($project);

    $this->createHistoryWant($project, [
        'title' => 'Add project context read surface',
        'status' => 'completed',
        'created_at' => '2026-03-24 10:00:00',
    ]);

    $completedWant = $this->createHistoryWant($project, [
        'title' => 'Refresh stored project context from app facts',
        'status' => 'completed',
        'created_at' => '2026-03-25 09:00:00',
    ]);
    $completedPlan = $this->createPlanRevision($completedWant, [
        'created_at' => '2026-03-25 09:01:00',
    ]);
    $completedAction = $this->createActionRun($completedPlan, [
        'status' => 'completed',
        'created_at' => '2026-03-25 09:02:00',
    ]);
    $this->createOutcomeLog($completedAction, [
        'outcome' => 'Stored project context refresh is now live on main and represented in app history.',
        'created_at' => '2026-03-25 09:03:00',
    ]);

    $this->createHistoryWant($project, [
        'title' => 'Help builders make better decisions with less chat',
        'status' => 'active',
        'created_at' => '2026-03-25 10:00:00',
    ]);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Kelajak-Maskan');
    $response->assertSee('app-owned-history-cycle-recording');
    $response->assertSee('Stored project context refresh is now live on main and represented in app history.');
    $response->assertSee('Help builders make better decisions with less chat');
    $response->assertSee('Available actions');
});

it('shows active wants with real derived stages and a highlighted written plan', function (): void {
    $project = $this->createHistoryProject();
    createDashboardProjectContext($project);

    $capturedWant = $this->createHistoryWant($project, [
        'title' => 'Capture dashboard stage needs',
        'status' => 'active',
        'created_at' => '2026-03-25 10:00:00',
    ]);

    $constrainedWant = $this->createHistoryWant($project, [
        'title' => 'Constrain dashboard to read-only behavior',
        'status' => 'active',
        'created_at' => '2026-03-25 10:10:00',
    ]);
    $this->createConstraintSnapshot($constrainedWant, [
        'created_at' => '2026-03-25 10:11:00',
    ]);

    $validatedWant = $this->createHistoryWant($project, [
        'title' => 'Validate dashboard stage derivation',
        'status' => 'active',
        'created_at' => '2026-03-25 10:20:00',
    ]);
    $this->createValidationRun($validatedWant, [
        'summary' => 'The stored history records are enough to derive stage without fake percentages.',
        'created_at' => '2026-03-25 10:21:00',
    ]);

    $actingWant = $this->createHistoryWant($project, [
        'title' => 'Render acting wants without write controls',
        'status' => 'active',
        'created_at' => '2026-03-25 10:30:00',
    ]);
    $actingPlan = $this->createPlanRevision($actingWant, [
        'plan_text' => 'Keep the dashboard beautiful and readable while the want is still acting.',
        'grounded_summary' => 'The active want still has implementation work in progress.',
        'created_at' => '2026-03-25 10:31:00',
    ]);
    $this->createActionRun($actingPlan, [
        'status' => 'in_progress',
        'finished_at' => null,
        'created_at' => '2026-03-25 10:32:00',
    ]);

    $blockedWant = $this->createHistoryWant($project, [
        'title' => 'Block repo path leakage from the dashboard',
        'status' => 'active',
        'created_at' => '2026-03-25 10:40:00',
    ]);
    $blockedPlan = $this->createPlanRevision($blockedWant, [
        'plan_text' => 'Remove path exposure from both the dashboard and project context output.',
        'grounded_summary' => 'Absolute repo paths remain a blocker until both read surfaces are sanitized.',
        'created_at' => '2026-03-25 10:41:00',
    ]);
    $blockedAction = $this->createActionRun($blockedPlan, [
        'status' => 'failed',
        'created_at' => '2026-03-25 10:42:00',
    ]);
    $this->createOutcomeLog($blockedAction, [
        'outcome' => 'Blocked by absolute repo path leakage in the current dashboard surface.',
        'reflection' => 'Treat want #15 as a hard requirement before resuming implementation.',
        'created_at' => '2026-03-25 10:43:00',
    ]);

    $plannedWant = $this->createHistoryWant($project, [
        'title' => 'Show a readable want focus panel',
        'status' => 'active',
        'created_at' => '2026-03-25 10:50:00',
    ]);
    $this->createPlanRevision($plannedWant, [
        'plan_text' => 'Write the readable want focus panel and route to a read-only detail page.',
        'grounded_summary' => 'The dashboard should surface the latest written plan for a selected active want.',
        'created_at' => '2026-03-25 10:51:00',
    ]);

    $completedWant = $this->createHistoryWant($project, [
        'title' => 'Ship the first dashboard shell',
        'status' => 'completed',
        'created_at' => '2026-03-25 09:00:00',
    ]);
    $completedPlan = $this->createPlanRevision($completedWant, [
        'created_at' => '2026-03-25 09:01:00',
    ]);
    $completedAction = $this->createActionRun($completedPlan, [
        'status' => 'completed',
        'created_at' => '2026-03-25 09:02:00',
    ]);
    $this->createOutcomeLog($completedAction, [
        'outcome' => 'The first dashboard shell is live and still read-only.',
        'created_at' => '2026-03-25 09:03:00',
    ]);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Active wants');
    $response->assertSee($capturedWant->title);
    $response->assertSee($constrainedWant->title);
    $response->assertSee($validatedWant->title);
    $response->assertSee($actingWant->title);
    $response->assertSee($blockedWant->title);
    $response->assertSee($plannedWant->title);
    $response->assertSee('captured');
    $response->assertSee('constrained');
    $response->assertSee('validated');
    $response->assertSee('acting');
    $response->assertSee('blocked');
    $response->assertSee('planned');
    $response->assertSee('Want focus');
    $response->assertSee('Write the readable want focus panel and route to a read-only detail page.');
    $response->assertSee('Latest completed outcome');
    $response->assertDontSee('/Users/example/kelajak-maskan');
    $response->assertDontSee('Repo path');
}
);

it('shows a safe dashboard shell before stored project data exists', function (): void {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Kelajak-Maskan');
    $response->assertSee('Stored project context has not been written yet.');
});
