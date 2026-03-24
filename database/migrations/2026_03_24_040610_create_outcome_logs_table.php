<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outcome_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('action_run_id')->constrained()->cascadeOnDelete();
            $table->text('outcome');
            $table->text('reflection');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outcome_logs');
    }
};
