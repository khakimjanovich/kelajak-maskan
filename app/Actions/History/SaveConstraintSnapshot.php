<?php

namespace App\Actions\History;

use App\Data\History\SaveConstraintSnapshotData;
use App\Models\AuditLog;
use App\Models\ConstraintSnapshot;
use App\Support\Audit\AuditContext;
use Illuminate\Support\Facades\DB;

class SaveConstraintSnapshot
{
    public function handle(SaveConstraintSnapshotData $data, AuditContext $auditContext): ConstraintSnapshot
    {
        return DB::transaction(function () use ($data, $auditContext): ConstraintSnapshot {
            $constraintSnapshot = ConstraintSnapshot::query()->create([
                'want_id' => $data->wantId,
                'payload' => $data->payload,
            ]);

            AuditLog::query()->create([
                'action_name' => 'history.save_constraint_snapshot',
                'actor_type' => $auditContext->actorType,
                'actor_ref' => $auditContext->actorRef,
                'target_type' => 'constraint_snapshot',
                'target_id' => $constraintSnapshot->id,
                'status' => 'success',
                'input_payload' => [
                    'want_id' => $data->wantId,
                    'payload' => $data->payload,
                ],
                'result_payload' => [
                    'constraint_snapshot_id' => $constraintSnapshot->id,
                ],
            ]);

            return $constraintSnapshot;
        });
    }
}
