<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('action_name');
            $table->string('actor_type');
            $table->string('actor_ref')->nullable();
            $table->string('target_type');
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('status');
            $table->json('input_payload');
            $table->json('result_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
