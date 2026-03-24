<?php

use App\Models\ConstraintSnapshot;
use App\Models\Project;
use App\Models\Want;

it('resolves history relationships and casts', function (): void {
    $project = Project::create([
        'name' => 'Kelajak-Maskan',
        'slug' => 'kelajak-maskan',
    ]);

    $want = Want::create([
        'project_id' => $project->id,
        'title' => 'Track grounded wants',
        'raw_text' => 'I want write actions first.',
        'status' => 'draft',
    ]);

    $snapshot = ConstraintSnapshot::create([
        'want_id' => $want->id,
        'payload' => ['scope' => 'phase-2'],
    ]);

    expect($project->wants)->toHaveCount(1);
    expect($want->project->is($project))->toBeTrue();
    expect($snapshot->payload)->toBe(['scope' => 'phase-2']);
});
