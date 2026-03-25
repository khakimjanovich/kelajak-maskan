<?php

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows current dashboard capabilities and next kelajak-maskan actions', function (): void {
    Project::query()->create([
        'name' => 'Kelajak-Maskan',
        'slug' => 'kelajak-maskan',
    ]);

    $this->artisan('project:refresh-context')
        ->assertExitCode(0);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('history:latest-want');
    $response->assertSee('history:summary');
    $response->assertSee('history:open-cycle');
    $response->assertSee('history:record-cycle');
    $response->assertSee('project:context');
    $response->assertSee('project:refresh-context');
    $response->assertSee('Next kelajak-maskan actions');
    $response->assertSee('Refresh project context');
    $response->assertSee('Inspect latest want');
    $response->assertSee('Inspect open cycle');
    $response->assertSee('Inspect project summary');
    $response->assertSee('Record a new cycle');
    $response->assertDontSee('php artisan test');
});
