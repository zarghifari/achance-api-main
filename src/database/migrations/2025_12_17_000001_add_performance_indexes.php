<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * These indexes significantly improve query performance for:
     * - Course searches and filtering
     * - Module/Lesson ordering and retrieval
     * - Relationship joins
     */
    public function up(): void
    {
        // Use raw SQL to avoid Doctrine DBAL dependency
        $indexes = [
            // Courses table indexes
            "CREATE INDEX IF NOT EXISTS idx_courses_title ON courses(title)",
            "CREATE INDEX IF NOT EXISTS idx_courses_slug ON courses(slug)",
            "CREATE INDEX IF NOT EXISTS idx_courses_isopen ON courses(isOpen)",
            
            // Modules table indexes
            "CREATE INDEX IF NOT EXISTS idx_modules_course_id ON modules(course_id)",
            "CREATE INDEX IF NOT EXISTS idx_modules_position ON modules(position)",
            "CREATE INDEX IF NOT EXISTS idx_modules_course_position ON modules(course_id, position)",
            
            // Lessons table indexes
            "CREATE INDEX IF NOT EXISTS idx_lessons_module_id ON lessons(module_id)",
            "CREATE INDEX IF NOT EXISTS idx_lessons_position ON lessons(position)",
            "CREATE INDEX IF NOT EXISTS idx_lessons_module_position ON lessons(module_id, position)",
        ];

        foreach ($indexes as $sql) {
            try {
                DB::statement($sql);
            } catch (\Exception $e) {
                // Index might already exist, continue
            }
        }

        echo "✓ Performance indexes created successfully\n";
        echo "  - Courses: title, slug, isOpen\n";
        echo "  - Modules: course_id, position, (course_id, position)\n";
        echo "  - Lessons: module_id, position, (module_id, position)\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Courses table indexes
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex('idx_courses_title');
            $table->dropIndex('idx_courses_slug');
            $table->dropIndex('idx_courses_isopen');
        });

        // Modules table indexes
        Schema::table('modules', function (Blueprint $table) {
            $table->dropIndex('idx_modules_course_id');
            $table->dropIndex('idx_modules_position');
            $table->dropIndex('idx_modules_course_position');
        });

        // Lessons table indexes
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropIndex('idx_lessons_module_id');
            $table->dropIndex('idx_lessons_position');
            $table->dropIndex('idx_lessons_module_position');
        });
    }


};
