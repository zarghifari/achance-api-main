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
        // Add indexes for courses table
        Schema::table('courses', function (Blueprint $table) {
            // Check if index doesn't exist before adding
            if (!$this->indexExists('courses', 'idx_courses_active_created')) {
                $table->index(['isOpen', 'created_at'], 'idx_courses_active_created');
            }
            if (!$this->indexExists('courses', 'idx_courses_slug')) {
                $table->index('slug', 'idx_courses_slug');
            }
            if (!$this->indexExists('courses', 'idx_courses_title')) {
                $table->index('title', 'idx_courses_title');
            }
        });

        // Add indexes for modules table
        Schema::table('modules', function (Blueprint $table) {
            if (!$this->indexExists('modules', 'idx_modules_course_position')) {
                $table->index(['course_id', 'position'], 'idx_modules_course_position');
            }
        });

        // Add indexes for lessons table
        Schema::table('lessons', function (Blueprint $table) {
            if (!$this->indexExists('lessons', 'idx_lessons_module_position')) {
                $table->index(['module_id', 'position'], 'idx_lessons_module_position');
            }
        });

        // Add indexes for tasks table
        Schema::table('tasks', function (Blueprint $table) {
            if (!$this->indexExists('tasks', 'idx_tasks_module')) {
                $table->index('module_id', 'idx_tasks_module');
            }
        });

        // Add indexes for user_activities table
        Schema::table('user_activities', function (Blueprint $table) {
            if (!$this->indexExists('user_activities', 'idx_user_activities_user_type')) {
                $table->index(['user_id', 'activity_type'], 'idx_user_activities_user_type');
            }
            if (!$this->indexExists('user_activities', 'idx_user_activities_activity')) {
                $table->index('activity_id', 'idx_user_activities_activity');
            }
        });

        // Add indexes for quiz-related tables
        Schema::table('quiz_questions', function (Blueprint $table) {
            if (!$this->indexExists('quiz_questions', 'idx_quiz_questions_quiz')) {
                $table->index('quiz_id', 'idx_quiz_questions_quiz');
            }
        });

        Schema::table('quiz_answers', function (Blueprint $table) {
            if (!$this->indexExists('quiz_answers', 'idx_quiz_answers_question')) {
                $table->index('quiz_question_id', 'idx_quiz_answers_question');
            }
        });

        Schema::table('attempt_quizzes', function (Blueprint $table) {
            if (!$this->indexExists('attempt_quizzes', 'idx_attempt_quizzes_user_quiz')) {
                $table->index(['user_id', 'quiz_id'], 'idx_attempt_quizzes_user_quiz');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex('idx_courses_active_created');
            $table->dropIndex('idx_courses_slug');
            $table->dropIndex('idx_courses_title');
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->dropIndex('idx_modules_course_position');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropIndex('idx_lessons_module_position');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('idx_tasks_module');
        });

        Schema::table('user_activities', function (Blueprint $table) {
            $table->dropIndex('idx_user_activities_user_type');
            $table->dropIndex('idx_user_activities_activity');
        });

        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropIndex('idx_quiz_questions_quiz');
        });

        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->dropIndex('idx_quiz_answers_question');
        });

        Schema::table('attempt_quizzes', function (Blueprint $table) {
            $table->dropIndex('idx_attempt_quizzes_user_quiz');
        });
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists($table, $index)
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = '{$index}'");
        return !empty($indexes);
    }
};
