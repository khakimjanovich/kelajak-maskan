<?php

namespace Tests\Feature\Console\Concerns;

use App\Models\ActionRun;
use App\Models\ConstraintSnapshot;
use App\Models\FactSource;
use App\Models\OutcomeLog;
use App\Models\PlanRevision;
use App\Models\Project;
use App\Models\ValidationRun;
use App\Models\Want;

trait SeedsHistoryCycles
{
    protected function createHistoryProject(array $attributes = []): Project
    {
        $defaults = [
            'name' => 'Kelajak-Maskan',
            'slug' => 'kelajak-maskan',
        ];

        return Project::query()->create([...$defaults, ...$attributes]);
    }

    protected function createHistoryWant(Project $project, array $attributes = []): Want
    {
        $timestamp = $attributes['created_at'] ?? '2026-03-24 00:00:00';

        $defaults = [
            'project_id' => $project->id,
            'title' => 'History cycle',
            'raw_text' => 'History cycle raw text',
            'status' => 'draft',
            'created_at' => $timestamp,
            'updated_at' => $attributes['updated_at'] ?? $timestamp,
        ];

        return Want::query()->forceCreate([...$defaults, ...$attributes]);
    }

    protected function createConstraintSnapshot(Want $want, array $attributes = []): ConstraintSnapshot
    {
        $timestamp = $attributes['created_at'] ?? '2026-03-24 00:00:00';

        $defaults = [
            'want_id' => $want->id,
            'payload' => ['phase' => 'phase_4'],
            'created_at' => $timestamp,
            'updated_at' => $attributes['updated_at'] ?? $timestamp,
        ];

        return ConstraintSnapshot::query()->forceCreate([...$defaults, ...$attributes]);
    }

    protected function createValidationRun(Want $want, array $attributes = []): ValidationRun
    {
        $timestamp = $attributes['created_at'] ?? '2026-03-24 00:00:00';

        $defaults = [
            'want_id' => $want->id,
            'facts_status' => 'verified',
            'constraints_status' => 'satisfied',
            'experience_status' => 'verified',
            'ikhlas_status' => 'pass',
            'summary' => 'Validation summary',
            'created_at' => $timestamp,
            'updated_at' => $attributes['updated_at'] ?? $timestamp,
        ];

        return ValidationRun::query()->forceCreate([...$defaults, ...$attributes]);
    }

    protected function createFactSources(ValidationRun $validationRun, int $count, array $attributes = []): void
    {
        for ($index = 1; $index <= $count; $index++) {
            $timestamp = $attributes['created_at'] ?? sprintf('2026-03-24 00:00:%02d', $index);

            FactSource::query()->forceCreate([
                'validation_run_id' => $validationRun->id,
                'label' => $attributes['label'] ?? "Source $index",
                'url' => $attributes['url'] ?? "local://source/$index",
                'status' => $attributes['status'] ?? 'verified',
                'notes' => $attributes['notes'] ?? "Fact source $index",
                'created_at' => $timestamp,
                'updated_at' => $attributes['updated_at'] ?? $timestamp,
            ]);
        }
    }

    protected function createPlanRevision(Want $want, array $attributes = []): PlanRevision
    {
        $timestamp = $attributes['created_at'] ?? '2026-03-24 00:00:00';

        $defaults = [
            'want_id' => $want->id,
            'version' => 1,
            'plan_text' => 'Plan revision text',
            'grounded_summary' => 'Plan revision summary',
            'created_at' => $timestamp,
            'updated_at' => $attributes['updated_at'] ?? $timestamp,
        ];

        return PlanRevision::query()->forceCreate([...$defaults, ...$attributes]);
    }

    protected function createActionRun(PlanRevision $planRevision, array $attributes = []): ActionRun
    {
        $timestamp = $attributes['created_at'] ?? '2026-03-24 00:00:00';

        $defaults = [
            'plan_revision_id' => $planRevision->id,
            'status' => 'completed',
            'started_at' => $attributes['started_at'] ?? $timestamp,
            'finished_at' => $attributes['finished_at'] ?? $timestamp,
            'created_at' => $timestamp,
            'updated_at' => $attributes['updated_at'] ?? $timestamp,
        ];

        return ActionRun::query()->forceCreate([...$defaults, ...$attributes]);
    }

    protected function createOutcomeLog(ActionRun $actionRun, array $attributes = []): OutcomeLog
    {
        $timestamp = $attributes['created_at'] ?? '2026-03-24 00:00:00';

        $defaults = [
            'action_run_id' => $actionRun->id,
            'outcome' => 'Outcome text',
            'reflection' => 'Reflection text',
            'created_at' => $timestamp,
            'updated_at' => $attributes['updated_at'] ?? $timestamp,
        ];

        return OutcomeLog::query()->forceCreate([...$defaults, ...$attributes]);
    }
}
