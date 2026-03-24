<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('constraint_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('want_id')->constrained()->cascadeOnDelete();
            $table->json('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('constraint_snapshots');
    }
};
