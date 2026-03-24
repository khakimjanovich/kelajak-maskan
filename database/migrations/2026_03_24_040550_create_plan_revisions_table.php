<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('want_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->longText('plan_text');
            $table->text('grounded_summary');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_revisions');
    }
};
