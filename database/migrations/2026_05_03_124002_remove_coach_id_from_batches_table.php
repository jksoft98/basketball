<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropForeign(['coach_id']);
            $table->dropColumn('coach_id');
        });
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->foreignId('coach_id')
                  ->nullable()
                  ->constrained('users');
        });
    }
};