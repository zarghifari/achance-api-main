<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // Add indexes for better query performance
            $table->index('module_id', 'idx_lessons_module_id');
            $table->index('position', 'idx_lessons_position');
            $table->index(['module_id', 'position'], 'idx_lessons_module_position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // Drop indexes in reverse order
            $table->dropIndex('idx_lessons_module_position');
            $table->dropIndex('idx_lessons_position');
            $table->dropIndex('idx_lessons_module_id');
        });
    }
};
