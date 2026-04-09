<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->date('session_date');
            $table->time('session_time')->nullable();
            $table->enum('session_type', ['training', 'match', 'fitness', 'trial'])->default('training');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['batch_id', 'session_date']);
            $table->index('session_date');
        });
    }
    public function down(): void { Schema::dropIfExists('training_sessions'); }
};
