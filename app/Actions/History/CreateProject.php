<?php

namespace App\Actions\History;

use App\Data\History\CreateProjectData;
use App\Models\AuditLog;
use App\Models\Project;
use App\Support\Audit\AuditContext;
use Illuminate\Support\Facades\DB;

class CreateProject
{
    public function handle(CreateProjectData $data, AuditContext $auditContext): Project
    {
        return DB::transaction(function () use ($data, $auditContext): Project {
            $project = Project::query()->create([
                'name' => $data->name,
                'slug' => $data->slug,
            ]);

            AuditLog::query()->create([
                'action_name' => 'history.create_project',
                'actor_type' => $auditContext->actorType,
                'actor_ref' => $auditContext->actorRef,
                'target_type' => 'project',
                'target_id' => $project->id,
                'status' => 'success',
                'input_payload' => [
                    'name' => $data->name,
                    'slug' => $data->slug,
                ],
                'result_payload' => [
                    'project_id' => $project->id,
                ],
            ]);

            return $project;
        });
    }
}
