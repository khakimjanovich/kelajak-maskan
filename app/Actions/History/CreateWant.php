<?php

namespace App\Actions\History;

use App\Data\History\CreateWantData;
use App\Models\AuditLog;
use App\Models\Want;
use App\Support\Audit\AuditContext;
use Illuminate\Support\Facades\DB;

class CreateWant
{
    public function handle(CreateWantData $data, AuditContext $auditContext): Want
    {
        return DB::transaction(function () use ($data, $auditContext): Want {
            $want = Want::query()->create([
                'project_id' => $data->projectId,
                'title' => $data->title,
                'raw_text' => $data->rawText,
                'status' => $data->status,
            ]);

            AuditLog::query()->create([
                'action_name' => 'history.create_want',
                'actor_type' => $auditContext->actorType,
                'actor_ref' => $auditContext->actorRef,
                'target_type' => 'want',
                'target_id' => $want->id,
                'status' => 'success',
                'input_payload' => [
                    'project_id' => $data->projectId,
                    'title' => $data->title,
                    'raw_text' => $data->rawText,
                    'status' => $data->status,
                ],
                'result_payload' => [
                    'want_id' => $want->id,
                ],
            ]);

            return $want;
        });
    }
}
