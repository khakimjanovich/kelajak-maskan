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

it('shows a safe dashboard shell before stored project data exists', function (): void {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Kelajak-Maskan');
    $response->assertSee('Stored project context has not been written yet.');
});
