<?php

use App\Models\Project;
use App\Models\ProjectContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('refreshes the default project context with current app commands and source refs', function (): void {
    $project = Project::create([
        'name' => 'Kelajak-Maskan',
        'slug' => 'kelajak-maskan',
    ]);

    ProjectContext::create([
        'project_id' => $project->id,
        'summary' => 'Stale summary.',
        'repo_path' => '/tmp/stale',
        'primary_branch' => 'main',
        'stack' => ['Laravel 13'],
        'commands' => ['php artisan project:context'],
        'conventions' => ['Old convention'],
        'key_paths' => ['app/Console/Commands'],
        'current_phase' => 'project-context-phase-1',
        'source_refs' => ['app/Console/Commands/ProjectContext.php'],
    ]);

    $exitCode = Artisan::call('project:refresh-context');
    $output = Artisan::output();

    $context = $project->fresh()->projectContext;

    expect($exitCode)->toBe(0);
    expect($output)->toContain('Project context refreshed for Kelajak-Maskan (kelajak-maskan).');
    expect($context)->not->toBeNull();
    expect($context->summary)->toContain('project context');
    expect($context->summary)->toContain('wants, plans, actions, and outcomes');
    expect($context->commands)->toContain('php artisan history:record-cycle');
    expect($context->commands)->toContain('php artisan project:refresh-context');
    expect($context->source_refs)->toContain('app/Console/Commands/HistoryRecordCycle.php');
    expect($context->source_refs)->toContain('app/Console/Commands/ProjectRefreshContext.php');
    expect($context->current_phase)->toBe('app-owned-history-cycle-recording');
});

it('creates a missing project context row when refreshing', function (): void {
    $project = Project::create([
        'name' => 'Kelajak-Maskan',
        'slug' => 'kelajak-maskan',
    ]);

    $this->artisan('project:refresh-context')
        ->expectsOutputToContain('Project context refreshed for Kelajak-Maskan (kelajak-maskan).')
        ->assertExitCode(0);

    $context = $project->fresh()->projectContext;

    expect($context)->not->toBeNull();
    expect($context->commands)->toContain('php artisan project:context');
});

it('fails clearly when the project does not exist', function (): void {
    $this->artisan('project:refresh-context', ['project' => 'missing-project'])
        ->expectsOutputToContain('Project [missing-project] not found.')
        ->assertExitCode(1);
});
