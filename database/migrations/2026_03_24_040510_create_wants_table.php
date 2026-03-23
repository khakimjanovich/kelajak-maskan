<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id');
            $table->string('title');
            $table->text('raw_text');
            $table->string('status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wants');
    }
};
