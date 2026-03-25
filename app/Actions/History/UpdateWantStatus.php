<?php

namespace App\Actions\History;

use App\Data\History\UpdateWantStatusData;
use App\Models\AuditLog;
use App\Models\Want;
use App\Support\Audit\AuditContext;
use Illuminate\Support\Facades\DB;

class UpdateWantStatus
{
    public function handle(UpdateWantStatusData $data, AuditContext $auditContext): Want
    {
        return DB::transaction(function () use ($data, $auditContext): Want {
            $want = Want::query()->findOrFail($data->wantId);
            $previousStatus = $want->status;

            $want->forceFill([
                'status' => $data->status,
            ])->save();

            AuditLog::query()->create([
                'action_name' => 'history.update_want_status',
                'actor_type' => $auditContext->actorType,
                'actor_ref' => $auditContext->actorRef,
                'target_type' => 'want',
                'target_id' => $want->id,
                'status' => 'success',
                'input_payload' => [
                    'want_id' => $want->id,
                    'previous_status' => $previousStatus,
                    'status' => $data->status,
                ],
                'result_payload' => [
                    'want_id' => $want->id,
                ],
            ]);

            return $want->fresh();
        });
    }
}
