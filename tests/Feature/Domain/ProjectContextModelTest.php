<?php

use App\Models\Project;
use App\Models\ProjectContext;

it('resolves project context relationships and array casts', function (): void {
    $project = Project::create([
        'name' => 'Kelajak-Maskan',
        'slug' => 'kelajak-maskan',
    ]);

    $context = ProjectContext::create([
        'project_id' => $project->id,
        'summary' => 'Laravel app with a local SQLite-backed project history spine.',
        'repo_path' => '/Users/example/kelajak-maskan',
        'primary_branch' => 'main',
        'stack' => ['Laravel 13', 'PHP 8.5', 'SQLite'],
        'commands' => ['php artisan history:latest-want', 'php artisan history:summary'],
        'conventions' => ['Use the app history rail before acting.'],
        'key_paths' => ['app/Console/Commands', 'app/Models'],
        'current_phase' => 'project-context-phase-1',
        'source_refs' => ['composer.json', 'app/Console/Commands/HistorySummary.php'],
    ]);

    expect($project->projectContext->is($context))->toBeTrue();
    expect($context->project->is($project))->toBeTrue();
    expect($context->stack)->toBe(['Laravel 13', 'PHP 8.5', 'SQLite']);
    expect($context->commands)->toBe(['php artisan history:latest-want', 'php artisan history:summary']);
    expect($context->conventions)->toBe(['Use the app history rail before acting.']);
    expect($context->key_paths)->toBe(['app/Console/Commands', 'app/Models']);
    expect($context->source_refs)->toBe(['composer.json', 'app/Console/Commands/HistorySummary.php']);
});
