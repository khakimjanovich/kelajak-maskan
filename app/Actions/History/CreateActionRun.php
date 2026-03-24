<?php

namespace App\Actions\History;

use App\Data\History\CreateActionRunData;
use App\Models\ActionRun;
use App\Models\AuditLog;
use App\Support\Audit\AuditContext;
use Illuminate\Support\Facades\DB;

class CreateActionRun
{
    public function handle(CreateActionRunData $data, AuditContext $auditContext): ActionRun
    {
        return DB::transaction(function () use ($data, $auditContext): ActionRun {
            $actionRun = ActionRun::query()->create([
                'plan_revision_id' => $data->planRevisionId,
                'status' => $data->status,
                'started_at' => $data->startedAt,
                'finished_at' => $data->finishedAt,
            ]);

            AuditLog::query()->create([
                'action_name' => 'history.create_action_run',
                'actor_type' => $auditContext->actorType,
                'actor_ref' => $auditContext->actorRef,
                'target_type' => 'action_run',
                'target_id' => $actionRun->id,
                'status' => 'success',
                'input_payload' => [
                    'plan_revision_id' => $data->planRevisionId,
                    'status' => $data->status,
                    'started_at' => $data->startedAt,
                    'finished_at' => $data->finishedAt,
                ],
                'result_payload' => [
                    'action_run_id' => $actionRun->id,
                ],
            ]);

            return $actionRun;
        });
    }
}
