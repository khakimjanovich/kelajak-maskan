<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validation_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('want_id')->constrained()->cascadeOnDelete();
            $table->string('facts_status');
            $table->string('constraints_status');
            $table->string('experience_status');
            $table->string('ikhlas_status');
            $table->text('summary');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validation_runs');
    }
};
