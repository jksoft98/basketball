<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create pivot table
        Schema::create('batch_coach', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')
                  ->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')
                  ->constrained()->cascadeOnDelete();
            $table->timestamps();

            // A coach can only be assigned once per batch
            $table->unique(['batch_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_coach');
    }
};