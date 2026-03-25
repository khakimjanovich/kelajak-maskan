<?php

namespace App\Support\ProjectContext;

final class ProjectContextPayload
{
    public const REPO_PATH_POLICY = 'not-recorded-by-policy';

    /**
     * @return array{
     *     summary: string,
     *     repo_path: string,
     *     primary_branch: string,
     *     stack: array<int, string>,
     *     commands: array<int, string>,
     *     conventions: array<int, string>,
     *     key_paths: array<int, string>,
     *     current_phase: string,
     *     source_refs: array<int, string>
     * }
     */
    public static function forKelajakMaskan(string $basePath): array
    {
        return [
            'summary' => 'Laravel 13 application that stores project context, wants, plans, actions, and outcomes in a local SQLite spine.',
            'repo_path' => self::REPO_PATH_POLICY,
            'primary_branch' => 'main',
            'stack' => ['Laravel 13', 'PHP 8.5', 'SQLite', 'Pest'],
            'commands' => [
                'php artisan history:latest-want',
                'php artisan history:summary',
                'php artisan history:open-cycle',
                'php artisan history:record-cycle',
                'php artisan project:context',
                'php artisan project:refresh-context',
                'php artisan test',
            ],
            'conventions' => [
                'Read app history before planning or acting.',
                'Keep planning and execution isolated in a worktree.',
                'Use Laravel code paths and artisan commands instead of raw sqlite or ad hoc PHP scripts.',
                'Do not claim completion until app state and verification match the work.',
            ],
            'key_paths' => [
                'app/Console/Commands',
                'app/Models',
                'app/Support/History',
                'app/Support/ProjectContext',
                'database/migrations',
                'database/database.sqlite',
            ],
            'current_phase' => 'app-owned-history-cycle-recording',
            'source_refs' => [
                'composer.json',
                'database/database.sqlite',
                'app/Console/Commands/HistoryLatestWant.php',
                'app/Console/Commands/HistorySummary.php',
                'app/Console/Commands/HistoryRecordCycle.php',
                'app/Console/Commands/ProjectContext.php',
                'app/Console/Commands/ProjectRefreshContext.php',
                'app/Support/History/HistoryReader.php',
                'app/Support/ProjectContext/ProjectContextPayload.php',
            ],
        ];
    }

    public static function safeRepoPath(): string
    {
        return self::REPO_PATH_POLICY;
    }
}
