<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_contexts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('summary');
            $table->string('repo_path');
            $table->string('primary_branch');
            $table->json('stack');
            $table->json('commands');
            $table->json('conventions');
            $table->json('key_paths');
            $table->string('current_phase');
            $table->json('source_refs');
            $table->timestamps();
        });

        $projectId = DB::table('projects')
            ->where('slug', 'kelajak-maskan')
            ->value('id');

        if ($projectId === null) {
            return;
        }

        $now = now();

        DB::table('project_contexts')->updateOrInsert(
            ['project_id' => $projectId],
            [
                'summary' => 'Laravel 13 application that stores project wants, plans, actions, and outcomes in a local SQLite history spine.',
                'repo_path' => $this->projectContextRepoPath(),
                'primary_branch' => 'main',
                'stack' => json_encode(['Laravel 13', 'PHP 8.5', 'SQLite', 'Pest']),
                'commands' => json_encode([
                    'php artisan history:latest-want',
                    'php artisan history:summary',
                    'php artisan history:open-cycle',
                    'php artisan project:context',
                    'php artisan test',
                ]),
                'conventions' => json_encode([
                    'Read app history before planning or acting.',
                    'Keep planning and execution isolated in a worktree.',
                    'Use Laravel code paths and artisan commands instead of raw sqlite or ad hoc PHP scripts.',
                    'Do not claim completion until app state and verification match the work.',
                ]),
                'key_paths' => json_encode([
                    'app/Console/Commands',
                    'app/Models',
                    'app/Support/History',
                    'database/migrations',
                    'database/database.sqlite',
                ]),
                'current_phase' => 'project-context-phase-1',
                'source_refs' => json_encode([
                    'composer.json',
                    'database/database.sqlite',
                    'app/Console/Commands/HistoryLatestWant.php',
                    'app/Console/Commands/HistorySummary.php',
                    'app/Console/Commands/ProjectContext.php',
                    'app/Support/History/HistoryReader.php',
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('project_contexts');
    }

    private function projectContextRepoPath(): string
    {
        $basePath = base_path();
        $normalizedBasePath = str_replace('\\', '/', $basePath);

        if (str_contains($normalizedBasePath, '/.worktrees/')) {
            return dirname(dirname($basePath));
        }

        return $basePath;
    }
};
