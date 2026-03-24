<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->sqliteFiles = [];
    $this->originalDefaultConnection = config('database.default');
    $this->originalSqliteDatabase = config('database.connections.sqlite.database');
});

afterEach(function (): void {
    config([
        'database.default' => $this->originalDefaultConnection,
        'database.connections.sqlite.database' => $this->originalSqliteDatabase,
    ]);

    DB::purge('sqlite');

    foreach ($this->sqliteFiles as $path) {
        if (is_string($path) && file_exists($path)) {
            @unlink($path);
        }
    }
});

it('backfills a legacy sqlite history file, preserves original data, creates a backup, and records the upgrade cycle', function (): void {
    $path = createLegacyHistoryDatabase();
    useSqliteFileDatabase($path);

    $backupFilesBefore = backupFiles();

    $this->artisan('history:backfill-foreign-keys')
        ->assertExitCode(0);

    $backupFilesAfter = backupFiles();

    expect(array_values(array_diff($backupFilesAfter, $backupFilesBefore)))->toHaveCount(1);

    expect(backfillForeignKeyTables($path, 'wants'))->toContain('projects');
    expect(backfillForeignKeyTables($path, 'constraint_snapshots'))->toContain('wants');
    expect(backfillForeignKeyTables($path, 'validation_runs'))->toContain('wants');
    expect(backfillForeignKeyTables($path, 'fact_sources'))->toContain('validation_runs');
    expect(backfillForeignKeyTables($path, 'plan_revisions'))->toContain('wants');
    expect(backfillForeignKeyTables($path, 'action_runs'))->toContain('plan_revisions');
    expect(backfillForeignKeyTables($path, 'outcome_logs'))->toContain('action_runs');

    expect(foreignKeyCheckRows($path))->toBe([]);

    expect(tableCount($path, 'projects'))->toBe(1);
    expect(tableCount($path, 'wants'))->toBe(4);
    expect(tableCount($path, 'constraint_snapshots'))->toBe(4);
    expect(tableCount($path, 'validation_runs'))->toBe(4);
    expect(tableCount($path, 'fact_sources'))->toBeGreaterThan(3);
    expect(tableCount($path, 'plan_revisions'))->toBe(4);
    expect(tableCount($path, 'action_runs'))->toBe(4);
    expect(tableCount($path, 'outcome_logs'))->toBe(4);
    expect(tableCount($path, 'audit_logs'))->toBe(7);

    expect(sqliteColumn($path, 'SELECT GROUP_CONCAT(id, ",") FROM wants ORDER BY id'))->toBe('1,2,3,4');
    expect(sqliteColumn($path, 'SELECT title FROM wants WHERE id = 4'))->toContain('Backfill');
    expect(sqliteColumn($path, "SELECT COUNT(*) FROM audit_logs WHERE actor_ref = 'history-backfill'"))->toBe('5');
    expect(sqliteColumn($path, "SELECT GROUP_CONCAT(action_name, '|') FROM (SELECT action_name FROM audit_logs WHERE actor_ref = 'history-backfill' ORDER BY id)"))
        ->toBe('history.create_want|history.save_constraint_snapshot|history.create_plan_revision|history.create_action_run|history.log_outcome');
});

it('supports rehearsal mode without backup or history writes', function (): void {
    $path = createLegacyHistoryDatabase();
    useSqliteFileDatabase($path);

    $backupFilesBefore = backupFiles();

    $this->artisan('history:backfill-foreign-keys', [
        '--skip-backup' => true,
        '--skip-history-record' => true,
    ])->assertExitCode(0);

    expect(backupFiles())->toBe($backupFilesBefore);
    expect(tableCount($path, 'wants'))->toBe(3);
    expect(tableCount($path, 'constraint_snapshots'))->toBe(3);
    expect(tableCount($path, 'validation_runs'))->toBe(3);
    expect(tableCount($path, 'fact_sources'))->toBe(3);
    expect(tableCount($path, 'plan_revisions'))->toBe(3);
    expect(tableCount($path, 'action_runs'))->toBe(3);
    expect(tableCount($path, 'outcome_logs'))->toBe(3);
    expect(tableCount($path, 'audit_logs'))->toBe(2);
    expect(foreignKeyCheckRows($path))->toBe([]);
});

it('aborts when orphan rows exist in the history chain', function (): void {
    $path = createLegacyHistoryDatabase(withOrphan: true);
    useSqliteFileDatabase($path);

    $this->artisan('history:backfill-foreign-keys', [
        '--skip-backup' => true,
        '--skip-history-record' => true,
    ])
        ->expectsOutputToContain('orphan')
        ->assertExitCode(1);
});

it('aborts when leftover temporary backfill tables exist', function (): void {
    $path = createLegacyHistoryDatabase(withTempTable: true);
    useSqliteFileDatabase($path);

    $this->artisan('history:backfill-foreign-keys', [
        '--skip-backup' => true,
        '--skip-history-record' => true,
    ])
        ->expectsOutputToContain('temporary backfill tables')
        ->assertExitCode(1);
});

it('aborts unless the active connection is sqlite', function (): void {
    config([
        'database.default' => 'fake_mysql',
        'database.connections.fake_mysql' => [
            'driver' => 'mysql',
        ],
    ]);

    $this->artisan('history:backfill-foreign-keys')
        ->expectsOutputToContain('SQLite')
        ->assertExitCode(1);
});

function createLegacyHistoryDatabase(bool $withOrphan = false, bool $withTempTable = false): string
{
    $path = tempnam(sys_get_temp_dir(), 'km-history-');

    $trackedFiles = test()->sqliteFiles;
    $trackedFiles[] = $path;
    test()->sqliteFiles = $trackedFiles;

    $pdo = new PDO("sqlite:$path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec(<<<'SQL'
        PRAGMA foreign_keys = OFF;

        CREATE TABLE projects (
            id integer primary key autoincrement not null,
            name varchar not null,
            slug varchar not null,
            created_at datetime,
            updated_at datetime
        );

        CREATE TABLE wants (
            id integer primary key autoincrement not null,
            project_id integer not null,
            title varchar not null,
            raw_text text not null,
            status varchar not null,
            created_at datetime,
            updated_at datetime
        );

        CREATE TABLE constraint_snapshots (
            id integer primary key autoincrement not null,
            want_id integer not null,
            payload text not null,
            created_at datetime,
            updated_at datetime
        );

        CREATE TABLE validation_runs (
            id integer primary key autoincrement not null,
            want_id integer not null,
            facts_status varchar not null,
            constraints_status varchar not null,
            experience_status varchar not null,
            ikhlas_status varchar not null,
            summary text not null,
            created_at datetime,
            updated_at datetime
        );

        CREATE TABLE fact_sources (
            id integer primary key autoincrement not null,
            validation_run_id integer not null,
            label varchar not null,
            url text not null,
            status varchar not null,
            notes text not null,
            created_at datetime,
            updated_at datetime
        );

        CREATE TABLE plan_revisions (
            id integer primary key autoincrement not null,
            want_id integer not null,
            version integer not null,
            plan_text text not null,
            grounded_summary text not null,
            created_at datetime,
            updated_at datetime
        );

        CREATE TABLE action_runs (
            id integer primary key autoincrement not null,
            plan_revision_id integer not null,
            status varchar not null,
            started_at datetime,
            finished_at datetime,
            created_at datetime,
            updated_at datetime
        );

        CREATE TABLE outcome_logs (
            id integer primary key autoincrement not null,
            action_run_id integer not null,
            outcome text not null,
            reflection text not null,
            created_at datetime,
            updated_at datetime
        );

        CREATE TABLE audit_logs (
            id integer primary key autoincrement not null,
            action_name varchar not null,
            actor_type varchar not null,
            actor_ref varchar,
            target_type varchar not null,
            target_id integer,
            status varchar not null,
            input_payload text not null,
            result_payload text,
            error_message text,
            created_at datetime,
            updated_at datetime
        );
    SQL);

    $timestamp = '2026-03-24 00:00:00';

    $projectId = $withOrphan ? 999 : 1;

    $pdo->exec(<<<SQL
        INSERT INTO projects (id, name, slug, created_at, updated_at)
        VALUES (1, 'Kelajak-Maskan', 'kelajak-maskan', '$timestamp', '$timestamp');

        INSERT INTO wants (id, project_id, title, raw_text, status, created_at, updated_at)
        VALUES
            (1, $projectId, 'Initial history cycle', 'Phase 1', 'completed', '$timestamp', '$timestamp'),
            (2, 1, 'Skill fix cycle', 'Phase 2 planning rule', 'completed', '$timestamp', '$timestamp'),
            (3, 1, 'Runtime integration cycle', 'Phase 2 runtime integration', 'completed', '$timestamp', '$timestamp');

        INSERT INTO constraint_snapshots (id, want_id, payload, created_at, updated_at)
        VALUES
            (1, 1, '{"phase":"phase_1"}', '$timestamp', '$timestamp'),
            (2, 2, '{"phase":"skill_fix"}', '$timestamp', '$timestamp'),
            (3, 3, '{"phase":"phase_2_runtime"}', '$timestamp', '$timestamp');

        INSERT INTO validation_runs (id, want_id, facts_status, constraints_status, experience_status, ikhlas_status, summary, created_at, updated_at)
        VALUES
            (1, 1, 'verified', 'satisfied', 'insufficient_history', 'pass', 'Phase 1 summary', '$timestamp', '$timestamp'),
            (2, 2, 'verified', 'satisfied', 'verified', 'pass', 'Skill fix summary', '$timestamp', '$timestamp'),
            (3, 3, 'verified', 'satisfied', 'verified', 'pass', 'Phase 2 runtime summary', '$timestamp', '$timestamp');

        INSERT INTO fact_sources (id, validation_run_id, label, url, status, notes, created_at, updated_at)
        VALUES
            (1, 1, 'Laravel docs', 'https://laravel.com/docs/13.x/releases', 'verified', 'Phase 1 docs', '$timestamp', '$timestamp'),
            (2, 2, 'Local skill file', 'local://skill', 'verified', 'Skill fix docs', '$timestamp', '$timestamp'),
            (3, 3, 'Main runtime check', 'local://runtime', 'verified', 'Phase 2 runtime docs', '$timestamp', '$timestamp');

        INSERT INTO plan_revisions (id, want_id, version, plan_text, grounded_summary, created_at, updated_at)
        VALUES
            (1, 1, 1, 'Phase 1 plan', 'Phase 1 summary', '$timestamp', '$timestamp'),
            (2, 2, 1, 'Skill fix plan', 'Skill fix summary', '$timestamp', '$timestamp'),
            (3, 3, 1, 'Runtime integration plan', 'Runtime integration summary', '$timestamp', '$timestamp');

        INSERT INTO action_runs (id, plan_revision_id, status, started_at, finished_at, created_at, updated_at)
        VALUES
            (1, 1, 'completed', '$timestamp', '$timestamp', '$timestamp', '$timestamp'),
            (2, 2, 'completed', '$timestamp', '$timestamp', '$timestamp', '$timestamp'),
            (3, 3, 'completed', '$timestamp', '$timestamp', '$timestamp', '$timestamp');

        INSERT INTO outcome_logs (id, action_run_id, outcome, reflection, created_at, updated_at)
        VALUES
            (1, 1, 'Phase 1 outcome', 'Phase 1 reflection', '$timestamp', '$timestamp'),
            (2, 2, 'Skill fix outcome', 'Skill fix reflection', '$timestamp', '$timestamp'),
            (3, 3, 'Phase 2 runtime outcome', 'Phase 2 runtime reflection', '$timestamp', '$timestamp');

        INSERT INTO audit_logs (id, action_name, actor_type, actor_ref, target_type, target_id, status, input_payload, result_payload, error_message, created_at, updated_at)
        VALUES
            (1, 'history.create_want', 'assistant', 'phase-2-runtime-integration', 'want', 3, 'success', '{"project_id":1}', '{"want_id":3}', NULL, '$timestamp', '$timestamp'),
            (2, 'history.save_constraint_snapshot', 'assistant', 'phase-2-runtime-integration', 'constraint_snapshot', 3, 'success', '{"want_id":3}', '{"constraint_snapshot_id":3}', NULL, '$timestamp', '$timestamp');
    SQL);

    if ($withTempTable) {
        $pdo->exec('CREATE TABLE wants_backfill_old (id integer primary key autoincrement not null, project_id integer not null);');
    }

    return $path;
}

function useSqliteFileDatabase(string $path): void
{
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => $path,
    ]);

    DB::purge('sqlite');
    DB::reconnect('sqlite');
}

function backupFiles(): array
{
    File::ensureDirectoryExists(base_path('database/backups'));

    $files = glob(base_path('database/backups/*.sqlite'));

    return $files === false ? [] : $files;
}

function backfillForeignKeyTables(string $path, string $table): array
{
    $pdo = new PDO("sqlite:$path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $rows = $pdo->query("PRAGMA foreign_key_list($table)")->fetchAll(PDO::FETCH_ASSOC);

    return array_values(array_map(static fn (array $row): string => (string) $row['table'], $rows));
}

function foreignKeyCheckRows(string $path): array
{
    $pdo = new PDO("sqlite:$path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $pdo->query('PRAGMA foreign_key_check')->fetchAll(PDO::FETCH_ASSOC);
}

function tableCount(string $path, string $table): int
{
    return (int) sqliteColumn($path, "SELECT COUNT(*) FROM $table");
}

function sqliteColumn(string $path, string $sql): ?string
{
    $pdo = new PDO("sqlite:$path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $value = $pdo->query($sql)->fetchColumn();

    return $value === false ? null : (string) $value;
}
