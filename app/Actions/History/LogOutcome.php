<?php

namespace App\Actions\History;

use App\Data\History\LogOutcomeData;
use App\Models\AuditLog;
use App\Models\OutcomeLog;
use App\Support\Audit\AuditContext;
use Illuminate\Support\Facades\DB;

class LogOutcome
{
    public function handle(LogOutcomeData $data, AuditContext $auditContext): OutcomeLog
    {
        return DB::transaction(function () use ($data, $auditContext): OutcomeLog {
            $outcomeLog = OutcomeLog::query()->create([
                'action_run_id' => $data->actionRunId,
                'outcome' => $data->outcome,
                'reflection' => $data->reflection,
            ]);

            AuditLog::query()->create([
                'action_name' => 'history.log_outcome',
                'actor_type' => $auditContext->actorType,
                'actor_ref' => $auditContext->actorRef,
                'target_type' => 'outcome_log',
                'target_id' => $outcomeLog->id,
                'status' => 'success',
                'input_payload' => [
                    'action_run_id' => $data->actionRunId,
                    'outcome' => $data->outcome,
                    'reflection' => $data->reflection,
                ],
                'result_payload' => [
                    'outcome_log_id' => $outcomeLog->id,
                ],
            ]);

            return $outcomeLog;
        });
    }
}
