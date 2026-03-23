<?php

use Illuminate\Support\Facades\Schema;

it('creates the local history tables', function (): void {
    expect(Schema::hasTable('projects'))->toBeTrue();
    expect(Schema::hasTable('wants'))->toBeTrue();
    expect(Schema::hasTable('constraint_snapshots'))->toBeTrue();
    expect(Schema::hasTable('validation_runs'))->toBeTrue();
    expect(Schema::hasTable('fact_sources'))->toBeTrue();
    expect(Schema::hasTable('plan_revisions'))->toBeTrue();
    expect(Schema::hasTable('action_runs'))->toBeTrue();
    expect(Schema::hasTable('outcome_logs'))->toBeTrue();
});
