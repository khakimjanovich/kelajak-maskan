<?php

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ProjectContext;
use App\Models\Want;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

function createProjectContextForCommandTest(Project $project, array $overrides = []): ProjectContext
{
    return ProjectContext::create(array_merge([
        'project_id' => $project->id,
        'summary' => 'Laravel app with a local SQLite-backed project history spine.',
        'repo_path' => '/Users/example/kelajak-maskan',
        'primary_branch' => 'main',
        'stack' => ['Laravel 13', 'PHP 8.5', 'SQLite'],
        'commands' => ['php artisan history:latest-want', 'php artisan history:summary', 'php artisan project:context'],
        'conventions' => ['Use app history commands before planning.', 'Keep context reads read-only.'],
        'key_paths' => ['app/Console/Commands', 'app/Models', 'app/Support/History', 'database/migrations', 'database/database.sqlite'],
        'current_phase' => 'project-context-phase-1',
        'source_refs' => [
            'composer.json',
            'database/database.sqlite',
            'app/Console/Commands/HistoryLatestWant.php',
            'app/Console/Commands/HistorySummary.php',
            'app/Console/Commands/ProjectContext.php',
            'app/Support/History/HistoryReader.php',
        ],
    ], $overrides));
}

it('returns the default project context', function (): void {
    $project = Project::create([
        'name' => 'Kelajak-Maskan',
        'slug' => 'kelajak-maskan',
    ]);

    createProjectContextForCommandTest($project);

    $exitCode = Artisan::call('project:context');
    $output = Artisan::output();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('"slug": "kelajak-maskan"');
    expect($output)->toContain('Laravel app with a local SQLite-backed project history spine.');
    expect($output)->toContain('"primary_branch": "main"');
    expect($output)->toContain('"stack": [');
    expect($output)->toContain('"source_refs": [');
    expect($output)->toContain('app/Console/Commands/ProjectContext.php');
    expect($output)->not->toContain('docs/plans');
    expect($output)->not->toContain('SKILL.md');
    expect($output)->not->toContain('README.md');
});

it('returns the matching project context for a specific slug', function (): void {
    $defaultProject = Project::create([
        'name' => 'Kelajak-Maskan',
        'slug' => 'kelajak-maskan',
    ]);
    createProjectContextForCommandTest($defaultProject);

    $otherProject = Project::create([
        'name' => 'Side Project',
        'slug' => 'side-project',
    ]);
    createProjectContextForCommandTest($otherProject, [
        'summary' => 'Secondary project context.',
        'repo_path' => '/Users/example/side-project',
        'source_refs' => ['README.md'],
    ]);

    $exitCode = Artisan::call('project:context', ['project' => 'side-project']);
    $output = Artisan::output();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('"slug": "side-project"');
    expect($output)->toContain('Secondary project context.');
    expect($output)->toContain('/Users/example/side-project');
    expect($output)->not->toContain('docs/plans');
});

it('fails clearly when the project does not exist', function (): void {
    $this->artisan('project:context', ['project' => 'missing-project'])
        ->expectsOutputToContain('Project [missing-project] not found.')
        ->assertExitCode(1);
});

it('fails clearly when the project has no context row', function (): void {
    Project::create([
        'name' => 'Contextless Project',
        'slug' => 'contextless-project',
    ]);

    $this->artisan('project:context', ['project' => 'contextless-project'])
        ->expectsOutputToContain('Project context for [contextless-project] not found.')
        ->assertExitCode(1);
});

it('does not mutate tracked tables when reading project context', function (): void {
    $project = Project::create([
        'name' => 'Kelajak-Maskan',
        'slug' => 'kelajak-maskan',
    ]);
    createProjectContextForCommandTest($project);

    Want::create([
        'project_id' => $project->id,
        'title' => 'Protect read-only context access',
        'raw_text' => 'I want project context reads to stay non-mutating.',
        'status' => 'active',
    ]);

    AuditLog::create([
        'action_name' => 'history.record',
        'actor_type' => 'user',
        'actor_ref' => 'tester',
        'target_type' => 'want',
        'target_id' => 1,
        'status' => 'completed',
        'input_payload' => ['source' => 'test'],
        'result_payload' => ['ok' => true],
        'error_message' => null,
    ]);

    $countsBefore = [
        'projects' => Project::count(),
        'project_contexts' => ProjectContext::count(),
        'wants' => Want::count(),
        'audit_logs' => AuditLog::count(),
    ];

    $this->artisan('project:context')
        ->assertExitCode(0);

    expect([
        'projects' => Project::count(),
        'project_contexts' => ProjectContext::count(),
        'wants' => Want::count(),
        'audit_logs' => AuditLog::count(),
    ])->toBe($countsBefore);
});
