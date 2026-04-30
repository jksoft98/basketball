<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();

            // 0 = Sunday, 1 = Monday, ..., 6 = Saturday
            $table->tinyInteger('day_of_week');

            $table->time('session_time');

            $table->enum('session_type', ['training', 'match', 'fitness', 'trial'])
                  ->default('training');

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['batch_id', 'day_of_week']);
            $table->index(['batch_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_schedules');
    }
};
