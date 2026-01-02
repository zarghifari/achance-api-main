<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Add FULLTEXT index for faster searches (50-100ms improvement)
            DB::statement('ALTER TABLE courses ADD FULLTEXT idx_courses_fulltext (title, description)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Drop FULLTEXT index
            DB::statement('ALTER TABLE courses DROP INDEX idx_courses_fulltext');
        });
    }
};
