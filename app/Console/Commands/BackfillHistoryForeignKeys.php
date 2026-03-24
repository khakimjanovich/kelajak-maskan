<?php

namespace App\Console\Commands;

use App\Actions\History\CreateActionRun;
use App\Actions\History\CreatePlanRevision;
use App\Actions\History\CreateWant;
use App\Actions\History\LogOutcome;
use App\Actions\History\SaveConstraintSnapshot;
use App\Data\History\CreateActionRunData;
use App\Data\History\CreatePlanRevisionData;
use App\Data\History\CreateWantData;
use App\Data\History\LogOutcomeData;
use App\Data\History\SaveConstraintSnapshotData;
use App\Models\ActionRun;
use App\Models\FactSource;
use App\Models\OutcomeLog;
use App\Models\PlanRevision;
use App\Models\ValidationRun;
use App\Support\Audit\AuditContext;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class BackfillHistoryForeignKeys extends Command
{
    protected $signature = 'history:backfill-foreign-keys {--skip-backup} {--skip-history-record}';

    protected $description = 'Backfill live SQLite history tables with real foreign keys while preserving existing data.';

    private const HISTORY_TABLES = [
        'wants',
        'constraint_snapshots',
        'validation_runs',
        'fact_sources',
        'plan_revisions',
        'action_runs',
        'outcome_logs',
    ];

    private const TEMP_SUFFIX = '_backfill_old';

    public function handle(): int
    {
        $connectionName = (string) config('database.default');
        $connectionConfig = config("database.connections.$connectionName");

        if (($connectionConfig['driver'] ?? null) !== 'sqlite') {
            $this->error('SQLite file connection required for history foreign-key backfill.');

            return self::FAILURE;
        }

        $databasePath = $this->resolveSqlitePath($connectionConfig['database'] ?? null);

        if ($databasePath === null || ! is_file($databasePath)) {
            $this->error('SQLite file path required for history foreign-key backfill.');

            return self::FAILURE;
        }

        $requiredTables = ['projects', 'audit_logs', ...self::HISTORY_TABLES];

        try {
            $this->assertRequiredTablesExist($connectionName, $requiredTables);
            $this->assertNoTemporaryBackfillTables($connectionName);
            $this->assertNoOrphans($connectionName);

            $backupPath = $this->option('skip-backup') ? null : $this->createBackup($databasePath);
            $workingPath = $this->createWorkingCopy($databasePath);

            $this->line("Working copy: $workingPath");

            $workingConnectionName = 'sqlite_history_backfill_working';

            try {
                $this->configureSqliteConnection($workingConnectionName, $workingPath);
                $this->assertRequiredTablesExist($workingConnectionName, $requiredTables);
                $this->assertNoTemporaryBackfillTables($workingConnectionName);

                $preCounts = $this->tableCounts($workingConnectionName, [...$requiredTables]);
                $rows = $this->loadRows($workingConnectionName, self::HISTORY_TABLES);

                $this->rebuildWorkingHistoryTables($workingConnectionName, $rows);
                $this->assertCountsMatch($workingConnectionName, $preCounts, self::HISTORY_TABLES);
                $this->assertForeignKeysPresent($workingConnectionName);
                $this->assertForeignKeyCheckPasses($workingConnectionName);

                DB::disconnect($workingConnectionName);
                DB::purge($workingConnectionName);

                $this->replaceDatabaseFile($workingPath, $databasePath);
                $this->reconnectSqlite($connectionName, $databasePath);

                if (! $this->option('skip-history-record')) {
                    $this->recordBackfillHistoryCycle();
                }

                $this->assertForeignKeysPresent($connectionName);
                $this->assertForeignKeyCheckPasses($connectionName);

                $this->info('History foreign-key backfill completed successfully.');

                if ($backupPath !== null) {
                    $this->line("Backup: $backupPath");
                }

                return self::SUCCESS;
            } finally {
                DB::disconnect($workingConnectionName);
                DB::purge($workingConnectionName);

                if (isset($workingPath) && is_file($workingPath)) {
                    @unlink($workingPath);
                }
            }
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());

            return self::FAILURE;
        }
    }

    private function resolveSqlitePath(mixed $database): ?string
    {
        if (! is_string($database) || $database === '' || $database === ':memory:') {
            return null;
        }

        if (str_starts_with($database, DIRECTORY_SEPARATOR)) {
            return $database;
        }

        return base_path($database);
    }

    private function configureSqliteConnection(string $connectionName, string $databasePath): void
    {
        config(["database.connections.$connectionName" => [
            'driver' => 'sqlite',
            'database' => $databasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);

        DB::purge($connectionName);
        DB::reconnect($connectionName);
    }

    private function reconnectSqlite(string $connectionName, string $databasePath): void
    {
        config(["database.connections.$connectionName.database" => $databasePath]);

        DB::disconnect($connectionName);
        DB::purge($connectionName);
        DB::reconnect($connectionName);
    }

    private function assertRequiredTablesExist(string $connectionName, array $tables): void
    {
        $schema = Schema::connection($connectionName);

        foreach ($tables as $table) {
            if (! $schema->hasTable($table)) {
                throw new RuntimeException("Required table missing for backfill: $table");
            }
        }
    }

    private function assertNoTemporaryBackfillTables(string $connectionName): void
    {
        $schema = Schema::connection($connectionName);
        $temporaryTables = [];

        foreach (self::HISTORY_TABLES as $table) {
            $temporaryTable = $this->temporaryTableName($table);

            if ($schema->hasTable($temporaryTable)) {
                $temporaryTables[] = $temporaryTable;
            }
        }

        if ($temporaryTables !== []) {
            throw new RuntimeException('Cannot continue: temporary backfill tables exist from a prior failed run: '.implode(', ', $temporaryTables));
        }
    }

    private function assertNoOrphans(string $connectionName): void
    {
        $connection = DB::connection($connectionName);

        $checks = [
            'wants.project_id -> projects.id' => 'SELECT COUNT(*) FROM wants w LEFT JOIN projects p ON p.id = w.project_id WHERE p.id IS NULL',
            'constraint_snapshots.want_id -> wants.id' => 'SELECT COUNT(*) FROM constraint_snapshots cs LEFT JOIN wants w ON w.id = cs.want_id WHERE w.id IS NULL',
            'validation_runs.want_id -> wants.id' => 'SELECT COUNT(*) FROM validation_runs vr LEFT JOIN wants w ON w.id = vr.want_id WHERE w.id IS NULL',
            'fact_sources.validation_run_id -> validation_runs.id' => 'SELECT COUNT(*) FROM fact_sources fs LEFT JOIN validation_runs vr ON vr.id = fs.validation_run_id WHERE vr.id IS NULL',
            'plan_revisions.want_id -> wants.id' => 'SELECT COUNT(*) FROM plan_revisions pr LEFT JOIN wants w ON w.id = pr.want_id WHERE w.id IS NULL',
            'action_runs.plan_revision_id -> plan_revisions.id' => 'SELECT COUNT(*) FROM action_runs ar LEFT JOIN plan_revisions pr ON pr.id = ar.plan_revision_id WHERE pr.id IS NULL',
            'outcome_logs.action_run_id -> action_runs.id' => 'SELECT COUNT(*) FROM outcome_logs ol LEFT JOIN action_runs ar ON ar.id = ol.action_run_id WHERE ar.id IS NULL',
        ];

        $orphans = [];

        foreach ($checks as $label => $sql) {
            $count = (int) $connection->scalar($sql);

            if ($count > 0) {
                $orphans[$label] = $count;
            }
        }

        if ($orphans !== []) {
            $details = collect($orphans)
                ->map(fn (int $count, string $label): string => "$label ($count)")
                ->implode(', ');

            throw new RuntimeException("Cannot continue: orphan history rows exist: $details");
        }
    }

    private function createBackup(string $databasePath): string
    {
        $backupDirectory = base_path('database/backups');

        File::ensureDirectoryExists($backupDirectory);

        $backupPath = sprintf(
            '%s/kelajak-maskan-history-backfill-%s-%s.sqlite',
            $backupDirectory,
            date('YmdHis'),
            bin2hex(random_bytes(4)),
        );

        if (! copy($databasePath, $backupPath)) {
            throw new RuntimeException('Failed to create SQLite backup before backfill.');
        }

        return $backupPath;
    }

    private function createWorkingCopy(string $databasePath): string
    {
        $workingPath = sprintf(
            '%s/%s-history-backfill-working-%s-%s.sqlite',
            sys_get_temp_dir(),
            pathinfo($databasePath, PATHINFO_FILENAME),
            date('YmdHis'),
            bin2hex(random_bytes(4)),
        );

        if (! copy($databasePath, $workingPath)) {
            throw new RuntimeException('Failed to create SQLite working copy for backfill.');
        }

        return $workingPath;
    }

    /**
     * @return array<string, int>
     */
    private function tableCounts(string $connectionName, array $tables): array
    {
        $connection = DB::connection($connectionName);
        $counts = [];

        foreach ($tables as $table) {
            $counts[$table] = (int) $connection->table($table)->count();
        }

        return $counts;
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function loadRows(string $connectionName, array $tables): array
    {
        $connection = DB::connection($connectionName);
        $rows = [];

        foreach ($tables as $table) {
            $rows[$table] = $connection->table($table)
                ->orderBy('id')
                ->get()
                ->map(static fn (object $row): array => (array) $row)
                ->all();
        }

        return $rows;
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $rows
     */
    private function rebuildWorkingHistoryTables(string $connectionName, array $rows): void
    {
        $connection = DB::connection($connectionName);

        $connection->statement('PRAGMA foreign_keys = OFF');

        try {
            foreach (array_reverse(self::HISTORY_TABLES) as $table) {
                $connection->statement(sprintf(
                    'ALTER TABLE "%s" RENAME TO "%s"',
                    $table,
                    $this->temporaryTableName($table),
                ));
            }

            $this->createHistoryTables($connectionName);

            foreach (self::HISTORY_TABLES as $table) {
                if ($rows[$table] === []) {
                    continue;
                }

                $connection->table($table)->insert($rows[$table]);
                $this->syncAutoincrementSequence($connection, $table);
            }

            $connection->statement('PRAGMA foreign_keys = ON');
            $this->assertForeignKeyCheckPasses($connectionName);

            foreach (array_reverse(self::HISTORY_TABLES) as $table) {
                Schema::connection($connectionName)->drop($this->temporaryTableName($table));
            }
        } catch (Throwable $throwable) {
            $connection->statement('PRAGMA foreign_keys = ON');

            throw $throwable;
        }
    }

    private function createHistoryTables(string $connectionName): void
    {
        $schema = Schema::connection($connectionName);

        $schema->create('wants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('raw_text');
            $table->string('status');
            $table->timestamps();
        });

        $schema->create('constraint_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('want_id')->constrained()->cascadeOnDelete();
            $table->json('payload');
            $table->timestamps();
        });

        $schema->create('validation_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('want_id')->constrained()->cascadeOnDelete();
            $table->string('facts_status');
            $table->string('constraints_status');
            $table->string('experience_status');
            $table->string('ikhlas_status');
            $table->text('summary');
            $table->timestamps();
        });

        $schema->create('fact_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('validation_run_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->text('url');
            $table->string('status');
            $table->text('notes');
            $table->timestamps();
        });

        $schema->create('plan_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('want_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->longText('plan_text');
            $table->text('grounded_summary');
            $table->timestamps();
        });

        $schema->create('action_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_revision_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        $schema->create('outcome_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('action_run_id')->constrained()->cascadeOnDelete();
            $table->text('outcome');
            $table->text('reflection');
            $table->timestamps();
        });
    }

    private function syncAutoincrementSequence(ConnectionInterface $connection, string $table): void
    {
        $maxId = $connection->table($table)->max('id');

        if ($maxId === null) {
            return;
        }

        $connection->statement('DELETE FROM sqlite_sequence WHERE name = ?', [$table]);
        $connection->statement('INSERT INTO sqlite_sequence (name, seq) VALUES (?, ?)', [$table, $maxId]);
    }

    /**
     * @param  array<string, int>  $expectedCounts
     * @param  array<int, string>  $tables
     */
    private function assertCountsMatch(string $connectionName, array $expectedCounts, array $tables): void
    {
        $actualCounts = $this->tableCounts($connectionName, $tables);
        $mismatches = [];

        foreach ($tables as $table) {
            $expected = $expectedCounts[$table] ?? null;
            $actual = $actualCounts[$table] ?? null;

            if ($expected !== $actual) {
                $mismatches[] = "$table(expected=$expected, actual=$actual)";
            }
        }

        if ($mismatches !== []) {
            throw new RuntimeException('Backfill count mismatch detected: '.implode(', ', $mismatches));
        }
    }

    private function assertForeignKeyCheckPasses(string $connectionName): void
    {
        $violations = DB::connection($connectionName)->select('PRAGMA foreign_key_check');

        if ($violations !== []) {
            throw new RuntimeException('Backfill foreign key verification failed.');
        }
    }

    private function assertForeignKeysPresent(string $connectionName): void
    {
        $expected = [
            'wants' => 'projects',
            'constraint_snapshots' => 'wants',
            'validation_runs' => 'wants',
            'fact_sources' => 'validation_runs',
            'plan_revisions' => 'wants',
            'action_runs' => 'plan_revisions',
            'outcome_logs' => 'action_runs',
        ];

        foreach ($expected as $table => $referencedTable) {
            $rows = DB::connection($connectionName)->select("PRAGMA foreign_key_list($table)");
            $tables = collect($rows)->pluck('table')->all();

            if (! in_array($referencedTable, $tables, true)) {
                throw new RuntimeException("Expected foreign key missing after backfill: $table -> $referencedTable");
            }
        }
    }

    private function replaceDatabaseFile(string $workingPath, string $databasePath): void
    {
        DB::disconnect('sqlite');
        DB::purge('sqlite');

        if (! copy($workingPath, $databasePath)) {
            throw new RuntimeException('Failed to replace the live SQLite database with the backfilled copy.');
        }
    }

    private function recordBackfillHistoryCycle(): void
    {
        DB::transaction(function (): void {
            $actor = new AuditContext(
                actorType: 'assistant',
                actorRef: 'history-backfill',
            );

            $want = app(CreateWant::class)->handle(
                new CreateWantData(
                    projectId: 1,
                    title: 'Backfill live SQLite history foreign keys',
                    rawText: 'Upgrade the live root history database so existing history tables enforce the phase-2 foreign key model without losing data.',
                    status: 'completed',
                ),
                $actor,
            );

            app(SaveConstraintSnapshot::class)->handle(
                new SaveConstraintSnapshotData(
                    wantId: $want->id,
                    payload: [
                        'phase' => 'phase_3',
                        'target' => 'live_root_history_schema',
                        'backup_created' => ! $this->option('skip-backup'),
                        'verification_passed' => true,
                        'preserved_ids' => true,
                        'preserved_counts' => true,
                    ],
                ),
                $actor,
            );

            $validationRun = ValidationRun::query()->create([
                'want_id' => $want->id,
                'facts_status' => 'verified',
                'constraints_status' => 'satisfied',
                'experience_status' => 'verified',
                'ikhlas_status' => 'pass',
                'summary' => 'Live history tables were rebuilt with foreign keys, original data was preserved, and verification completed successfully.',
            ]);

            FactSource::query()->insert([
                [
                    'validation_run_id' => $validationRun->id,
                    'label' => 'Foreign key verification',
                    'url' => 'local://pragma/foreign_key_check',
                    'status' => 'verified',
                    'notes' => 'PRAGMA foreign_key_check returned no violations.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'validation_run_id' => $validationRun->id,
                    'label' => 'Schema verification',
                    'url' => 'local://pragma/foreign_key_list',
                    'status' => 'verified',
                    'notes' => 'All rebuilt history tables now expose the expected foreign key relationships.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'validation_run_id' => $validationRun->id,
                    'label' => 'Count verification',
                    'url' => 'local://history/counts',
                    'status' => 'verified',
                    'notes' => 'Original row counts were preserved before the post-upgrade history cycle was recorded.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            $timestamp = now()->toDateTimeString();

            $planRevision = app(CreatePlanRevision::class)->handle(
                new CreatePlanRevisionData(
                    wantId: $want->id,
                    version: 1,
                    planText: 'Backup the live SQLite database, rebuild the existing history tables with real foreign keys on a working copy, replace the runtime database only after verification passes, and then record the upgrade as history.',
                    groundedSummary: 'The live root history schema now matches the phase-2 integrity model with preserved IDs, preserved data, and verified foreign keys.',
                ),
                $actor,
            );

            $actionRun = app(CreateActionRun::class)->handle(
                new CreateActionRunData(
                    planRevisionId: $planRevision->id,
                    status: 'completed',
                    startedAt: $timestamp,
                    finishedAt: $timestamp,
                ),
                $actor,
            );

            app(LogOutcome::class)->handle(
                new LogOutcomeData(
                    actionRunId: $actionRun->id,
                    outcome: 'Backfill completed successfully and the live root history tables now enforce the expected foreign key relationships.',
                    reflection: 'Phase 3 made the live runtime schema truthful by upgrading the existing SQLite history tables instead of only relying on future migrations and code expectations.',
                ),
                $actor,
            );
        });
    }

    private function temporaryTableName(string $table): string
    {
        return $table.self::TEMP_SUFFIX;
    }
}
