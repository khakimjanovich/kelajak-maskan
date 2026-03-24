<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fact_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('validation_run_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->text('url');
            $table->string('status');
            $table->text('notes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fact_sources');
    }
};
