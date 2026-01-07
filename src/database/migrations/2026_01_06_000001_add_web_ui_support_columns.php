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
        // Add is_published to courses if it doesn't exist
        if (!Schema::hasColumn('courses', 'is_published')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->boolean('is_published')->default(true)->after('isOpen');
            });
        }

        // Add status to courses if it doesn't exist
        if (!Schema::hasColumn('courses', 'status')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->enum('status', ['draft', 'published', 'archived'])->default('published')->after('is_published');
            });
        }

        // Add is_active to modules if it doesn't exist
        if (!Schema::hasColumn('modules', 'is_active')) {
            Schema::table('modules', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('position');
            });
        }

        // Add is_active to lessons if it doesn't exist
        if (!Schema::hasColumn('lessons', 'is_active')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('position');
            });
        }

        // Add duration to lessons if it doesn't exist
        if (!Schema::hasColumn('lessons', 'duration')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->integer('duration')->nullable()->comment('Duration in minutes')->after('description');
            });
        }

        // Add content_type to lessons if it doesn't exist
        if (!Schema::hasColumn('lessons', 'content_type')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->enum('content_type', ['html', 'video', 'pdf', 'mixed'])->default('html')->after('duration');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('courses', 'is_published')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('is_published');
            });
        }

        if (Schema::hasColumn('courses', 'status')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        if (Schema::hasColumn('modules', 'is_active')) {
            Schema::table('modules', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasColumn('lessons', 'is_active')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasColumn('lessons', 'duration')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->dropColumn('duration');
            });
        }

        if (Schema::hasColumn('lessons', 'content_type')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->dropColumn('content_type');
            });
        }
    }
};
