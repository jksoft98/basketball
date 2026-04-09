<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->integer('age')->nullable();
            $table->string('parent_name')->nullable();
            $table->string('parent_contact')->nullable();
            $table->string('photo_path');
            $table->string('photo_thumb_path')->nullable();
            $table->integer('jersey_number')->nullable();
            $table->enum('position', ['guard', 'forward', 'centre'])->nullable();
            $table->enum('skill_level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->enum('injury_status', ['fit', 'injured', 'recovering'])->default('fit');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['batch_id', 'is_active']);
            $table->index('injury_status');
        });
    }
    public function down(): void { Schema::dropIfExists('students'); }
};
