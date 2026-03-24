<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function foreignKeyTables(string $table): array
{
    return collect(DB::select("PRAGMA foreign_key_list({$table})"))
        ->pluck('table')
        ->all();
}

it('creates the audit_logs table', function (): void {
    expect(Schema::hasTable('audit_logs'))->toBeTrue();
});

it('enforces foreign keys across history tables', function (): void {
    expect(foreignKeyTables('wants'))->toContain('projects');
    expect(foreignKeyTables('constraint_snapshots'))->toContain('wants');
    expect(foreignKeyTables('validation_runs'))->toContain('wants');
    expect(foreignKeyTables('fact_sources'))->toContain('validation_runs');
    expect(foreignKeyTables('plan_revisions'))->toContain('wants');
    expect(foreignKeyTables('action_runs'))->toContain('plan_revisions');
    expect(foreignKeyTables('outcome_logs'))->toContain('action_runs');
});
