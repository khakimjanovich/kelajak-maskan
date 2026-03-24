<?php

namespace App\Actions\History;

use App\Data\History\CreatePlanRevisionData;
use App\Models\AuditLog;
use App\Models\PlanRevision;
use App\Support\Audit\AuditContext;
use Illuminate\Support\Facades\DB;

class CreatePlanRevision
{
    public function handle(CreatePlanRevisionData $data, AuditContext $auditContext): PlanRevision
    {
        return DB::transaction(function () use ($data, $auditContext): PlanRevision {
            $planRevision = PlanRevision::query()->create([
                'want_id' => $data->wantId,
                'version' => $data->version,
                'plan_text' => $data->planText,
                'grounded_summary' => $data->groundedSummary,
            ]);

            AuditLog::query()->create([
                'action_name' => 'history.create_plan_revision',
                'actor_type' => $auditContext->actorType,
                'actor_ref' => $auditContext->actorRef,
                'target_type' => 'plan_revision',
                'target_id' => $planRevision->id,
                'status' => 'success',
                'input_payload' => [
                    'want_id' => $data->wantId,
                    'version' => $data->version,
                    'plan_text' => $data->planText,
                    'grounded_summary' => $data->groundedSummary,
                ],
                'result_payload' => [
                    'plan_revision_id' => $planRevision->id,
                ],
            ]);

            return $planRevision;
        });
    }
}
