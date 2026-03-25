<?php

use App\Models\Project;
use App\Models\ProjectContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

it('creates the project_contexts table with the expected columns', function (): void {
    expect(Schema::hasTable('project_contexts'))->toBeTrue();
    expect(Schema::hasColumns('project_contexts', [
        'project_id',
        'summary',
        'repo_path',
        'primary_branch',
        'stack',
        'commands',
        'conventions',
        'key_paths',
        'current_phase',
        'source_refs',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

it('enforces one project context row per project', function (): void {
    $project = Project::create([
        'name' => 'Kelajak-Maskan',
        'slug' => 'kelajak-maskan',
    ]);

    ProjectContext::create([
        'project_id' => $project->id,
        'summary' => 'Primary project context.',
        'repo_path' => '/tmp/kelajak-maskan',
        'primary_branch' => 'main',
        'stack' => ['Laravel'],
        'commands' => ['php artisan history:latest-want'],
        'conventions' => ['Read history before planning.'],
        'key_paths' => ['README.md'],
        'current_phase' => 'project-context-phase-1',
        'source_refs' => ['README.md'],
    ]);

    expect(fn (): ProjectContext => ProjectContext::create([
        'project_id' => $project->id,
        'summary' => 'Duplicate context.',
        'repo_path' => '/tmp/kelajak-maskan',
        'primary_branch' => 'main',
        'stack' => ['Laravel'],
        'commands' => ['php artisan history:summary'],
        'conventions' => ['Stay read-only for context reads.'],
        'key_paths' => ['composer.json'],
        'current_phase' => 'project-context-phase-1',
        'source_refs' => ['composer.json'],
    ]))->toThrow(QueryException::class);
});

it('seeds the live project context without docs or skill files', function (): void {
    $project = Project::create([
        'name' => 'Kelajak-Maskan',
        'slug' => 'kelajak-maskan',
    ]);

    $this->artisan('migrate:rollback', [
        '--path' => 'database/migrations/2026_03_24_090000_create_project_contexts_table.php',
        '--realpath' => false,
        '--force' => true,
    ])->assertExitCode(0);

    $this->artisan('migrate', [
        '--path' => 'database/migrations/2026_03_24_090000_create_project_contexts_table.php',
        '--realpath' => false,
        '--force' => true,
    ])->assertExitCode(0);

    $context = $project->fresh()->projectContext;

    expect($context)->not->toBeNull();
    expect($context->repo_path)->toBe('not-recorded-by-policy');
    expect($context->key_paths)->not->toContain('docs/plans');
    expect($context->source_refs)->not->toContain('README.md');
    expect($context->source_refs)->not->toContain('skills/plans-to-action/SKILL.md');
    expect($context->source_refs)->toContain('app/Console/Commands/ProjectContext.php');
});
