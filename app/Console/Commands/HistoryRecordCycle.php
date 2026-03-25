<?php

namespace App\Console\Commands;

use App\Actions\History\CreateActionRun;
use App\Actions\History\CreatePlanRevision;
use App\Actions\History\CreateWant;
use App\Actions\History\LogOutcome;
use App\Actions\History\SaveConstraintSnapshot;
use App\Actions\History\UpdateWantStatus;
use App\Data\History\CreateActionRunData;
use App\Data\History\CreatePlanRevisionData;
use App\Data\History\CreateWantData;
use App\Data\History\LogOutcomeData;
use App\Data\History\SaveConstraintSnapshotData;
use App\Data\History\UpdateWantStatusData;
use App\Models\FactSource;
use App\Models\PlanRevision;
use App\Models\Project;
use App\Models\ValidationRun;
use App\Models\Want;
use App\Support\Audit\AuditContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use JsonException;

class HistoryRecordCycle extends Command
{
    protected $signature = 'history:record-cycle
        {title : Want title}
        {--project=kelajak-maskan : Project slug}
        {--want-id= : Existing want id to advance}
        {--raw-text= : Raw want text}
        {--want-status=draft : Want status}
        {--constraints= : JSON object for constraint snapshot payload}
        {--facts-status= : Validation facts status}
        {--constraints-status= : Validation constraints status}
        {--experience-status= : Validation experience status}
        {--ikhlas-status= : Validation ikhlas status}
        {--validation-summary= : Validation summary}
        {--fact-source=* : JSON objects for fact sources}
        {--plan-text= : Plan revision text}
        {--grounded-summary= : Grounded plan summary}
        {--action-status= : Action run status}
        {--started-at= : Action start timestamp}
        {--finished-at= : Action finish timestamp}
        {--outcome= : Outcome text}
        {--reflection= : Reflection text}
        {--actor-type=assistant : Audit actor type}
        {--actor-ref=codex : Audit actor ref}';

    protected $description = 'Record a want and optional planning/execution cycle through the app-owned history boundary.';

    public function handle(): int
    {
        $project = Project::query()
            ->where('slug', (string) $this->option('project'))
            ->first();

        if ($project === null) {
            $this->error(sprintf('Project [%s] not found.', (string) $this->option('project')));

            return self::FAILURE;
        }

        $existingWant = $this->resolveExistingWant($project);

        if ($this->targetsExistingWant() && ! $existingWant instanceof Want) {
            return self::FAILURE;
        }

        try {
            $constraintsPayload = $this->parseJsonOptionObject('constraints');
            $factSources = $this->parseFactSources();
        } catch (JsonException) {
            return self::FAILURE;
        }

        if ($this->shouldCreateValidationRun() && ! $this->hasCompleteValidationInputs()) {
            $this->error('Validation requires --facts-status, --constraints-status, --experience-status, --ikhlas-status, and --validation-summary.');

            return self::FAILURE;
        }

        if ($this->shouldCreateFactSources() && ! $this->shouldCreateValidationRun()) {
            $this->error('Fact sources require a validation run in the same command.');

            return self::FAILURE;
        }

        if ($this->shouldCreatePlanRevision() && ! $this->hasCompletePlanInputs()) {
            $this->error('Plan revision requires --plan-text and --grounded-summary.');

            return self::FAILURE;
        }

        if ($this->shouldCreateActionRun() && ! $this->hasActionStatus()) {
            $this->error('Action run requires --action-status.');

            return self::FAILURE;
        }

        if ($this->shouldCreateOutcome() && ! $this->shouldCreateActionRun()) {
            $this->error('Outcome logging requires an action run in the same command.');

            return self::FAILURE;
        }

        $auditContext = new AuditContext(
            actorType: (string) $this->option('actor-type'),
            actorRef: (string) $this->option('actor-ref'),
        );

        $result = DB::transaction(function () use ($project, $auditContext, $constraintsPayload, $factSources, $existingWant): array {
            $want = $existingWant;

            if (! $want instanceof Want) {
                $want = app(CreateWant::class)->handle(
                    new CreateWantData(
                        projectId: $project->id,
                        title: (string) $this->argument('title'),
                        rawText: (string) ($this->option('raw-text') ?: $this->argument('title')),
                        status: (string) $this->option('want-status'),
                    ),
                    $auditContext,
                );
            } elseif ($this->shouldUpdateExistingWantStatus($want)) {
                $want = app(UpdateWantStatus::class)->handle(
                    new UpdateWantStatusData(
                        wantId: $want->id,
                        status: (string) $this->option('want-status'),
                    ),
                    $auditContext,
                );
            }

            $constraintSnapshot = null;
            if ($constraintsPayload !== null) {
                $constraintSnapshot = app(SaveConstraintSnapshot::class)->handle(
                    new SaveConstraintSnapshotData(
                        wantId: $want->id,
                        payload: $constraintsPayload,
                    ),
                    $auditContext,
                );
            }

            $validationRun = null;
            if ($this->shouldCreateValidationRun()) {
                $validationRun = ValidationRun::query()->create([
                    'want_id' => $want->id,
                    'facts_status' => (string) $this->option('facts-status'),
                    'constraints_status' => (string) $this->option('constraints-status'),
                    'experience_status' => (string) $this->option('experience-status'),
                    'ikhlas_status' => (string) $this->option('ikhlas-status'),
                    'summary' => (string) $this->option('validation-summary'),
                ]);

                foreach ($factSources as $factSource) {
                    FactSource::query()->create([
                        'validation_run_id' => $validationRun->id,
                        'label' => $factSource['label'],
                        'url' => $factSource['url'],
                        'status' => $factSource['status'],
                        'notes' => $factSource['notes'],
                    ]);
                }
            }

            $planRevision = null;
            if ($this->shouldCreatePlanRevision()) {
                $planRevision = app(CreatePlanRevision::class)->handle(
                    new CreatePlanRevisionData(
                        wantId: $want->id,
                        version: $this->nextPlanRevisionVersion($want),
                        planText: (string) $this->option('plan-text'),
                        groundedSummary: (string) $this->option('grounded-summary'),
                    ),
                    $auditContext,
                );
            }

            $actionRun = null;
            if ($this->shouldCreateActionRun() && $planRevision instanceof PlanRevision) {
                $startedAt = (string) ($this->option('started-at') ?: now()->toDateTimeString());
                $finishedAt = $this->option('finished-at');

                if ($finishedAt === null && str_contains((string) $this->option('action-status'), 'completed')) {
                    $finishedAt = $startedAt;
                }

                $actionRun = app(CreateActionRun::class)->handle(
                    new CreateActionRunData(
                        planRevisionId: $planRevision->id,
                        status: (string) $this->option('action-status'),
                        startedAt: $startedAt,
                        finishedAt: is_string($finishedAt) ? $finishedAt : null,
                    ),
                    $auditContext,
                );
            }

            $outcomeLog = null;
            if ($this->shouldCreateOutcome() && $actionRun !== null) {
                $outcomeLog = app(LogOutcome::class)->handle(
                    new LogOutcomeData(
                        actionRunId: $actionRun->id,
                        outcome: (string) $this->option('outcome'),
                        reflection: (string) ($this->option('reflection') ?: ''),
                    ),
                    $auditContext,
                );
            }

            return [
                'project' => $project,
                'want_id' => $want->id,
                'constraint_snapshot_id' => $constraintSnapshot?->id,
                'validation_run_id' => $validationRun?->id,
                'fact_sources_count' => count($factSources),
                'plan_revision_id' => $planRevision?->id,
                'action_run_id' => $actionRun?->id,
                'outcome_log_id' => $outcomeLog?->id,
            ];
        });

        $this->line(sprintf('Project: %s (%s)', $result['project']->name, $result['project']->slug));
        $this->line(sprintf('Want id: %d', $result['want_id']));
        $this->line(sprintf('Constraint snapshot id: %s', $result['constraint_snapshot_id'] ?? 'none'));
        $this->line(sprintf('Validation run id: %s', $result['validation_run_id'] ?? 'none'));
        $this->line(sprintf('Fact sources: %d', $result['fact_sources_count']));
        $this->line(sprintf('Plan revision id: %s', $result['plan_revision_id'] ?? 'none'));
        $this->line(sprintf('Action run id: %s', $result['action_run_id'] ?? 'none'));
        $this->line(sprintf('Outcome log id: %s', $result['outcome_log_id'] ?? 'none'));

        return self::SUCCESS;
    }

    private function resolveExistingWant(Project $project): ?Want
    {
        if (! $this->targetsExistingWant()) {
            return null;
        }

        $wantId = (int) $this->option('want-id');

        $want = Want::query()
            ->whereKey($wantId)
            ->where('project_id', $project->id)
            ->first();

        if ($want === null) {
            $this->error(sprintf('Want [%d] not found for project [%s].', $wantId, $project->slug));
        }

        return $want;
    }

    private function targetsExistingWant(): bool
    {
        $wantId = $this->option('want-id');

        return is_scalar($wantId) && trim((string) $wantId) !== '';
    }

    private function shouldUpdateExistingWantStatus(Want $want): bool
    {
        if (! $this->input->hasParameterOption('--want-status')) {
            return false;
        }

        return $want->status !== (string) $this->option('want-status');
    }

    private function nextPlanRevisionVersion(Want $want): int
    {
        $latestVersion = (int) ($want->planRevisions()->max('version') ?? 0);

        return $latestVersion + 1;
    }

    /**
     * @return array<string, mixed>|null
     *
     * @throws JsonException
     */
    private function parseJsonOptionObject(string $option): ?array
    {
        $value = $this->option($option);

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->error(sprintf('Invalid JSON for --%s.', $option));

            throw $exception;
        }

        if (! is_array($decoded)) {
            $this->error(sprintf('Invalid JSON for --%s.', $option));

            throw new JsonException('Decoded JSON is not an object.');
        }

        return $decoded;
    }

    /**
     * @return array<int, array{label: string, url: string, status: string, notes: string}>
     *
     * @throws JsonException
     */
    private function parseFactSources(): array
    {
        $decodedSources = [];

        foreach ((array) $this->option('fact-source') as $factSource) {
            if (! is_string($factSource) || trim($factSource) === '') {
                continue;
            }

            try {
                $decoded = json_decode($factSource, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                $this->error('Invalid JSON for --fact-source.');

                throw $exception;
            }

            if (! is_array($decoded)
                || ! isset($decoded['label'], $decoded['url'], $decoded['status'], $decoded['notes'])
                || ! is_string($decoded['label'])
                || ! is_string($decoded['url'])
                || ! is_string($decoded['status'])
                || ! is_string($decoded['notes'])) {
                $this->error('Invalid JSON for --fact-source.');

                throw new JsonException('Decoded fact source is missing required keys.');
            }

            $decodedSources[] = $decoded;
        }

        return $decodedSources;
    }

    private function shouldCreateValidationRun(): bool
    {
        return $this->option('facts-status') !== null
            || $this->option('constraints-status') !== null
            || $this->option('experience-status') !== null
            || $this->option('ikhlas-status') !== null
            || $this->option('validation-summary') !== null
            || $this->shouldCreateFactSources();
    }

    private function hasCompleteValidationInputs(): bool
    {
        return $this->option('facts-status') !== null
            && $this->option('constraints-status') !== null
            && $this->option('experience-status') !== null
            && $this->option('ikhlas-status') !== null
            && $this->option('validation-summary') !== null;
    }

    private function shouldCreateFactSources(): bool
    {
        return count((array) $this->option('fact-source')) > 0;
    }

    private function shouldCreatePlanRevision(): bool
    {
        return $this->option('plan-text') !== null
            || $this->option('grounded-summary') !== null
            || $this->shouldCreateActionRun();
    }

    private function hasCompletePlanInputs(): bool
    {
        return $this->option('plan-text') !== null
            && $this->option('grounded-summary') !== null;
    }

    private function shouldCreateActionRun(): bool
    {
        return $this->option('action-status') !== null
            || $this->option('started-at') !== null
            || $this->option('finished-at') !== null
            || $this->shouldCreateOutcome();
    }

    private function hasActionStatus(): bool
    {
        return $this->option('action-status') !== null;
    }

    private function shouldCreateOutcome(): bool
    {
        return $this->option('outcome') !== null
            || $this->option('reflection') !== null;
    }
}
