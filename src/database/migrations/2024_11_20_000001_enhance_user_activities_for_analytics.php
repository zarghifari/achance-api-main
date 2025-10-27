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
        Schema::table('user_activities', function (Blueprint $table) {
            // Add analytics fields
            $table->json('metadata')->nullable()->after('activity_id');
            $table->integer('duration_seconds')->nullable()->after('metadata');
            $table->decimal('progress_percentage', 5, 2)->nullable()->after('duration_seconds');
            $table->string('action')->nullable()->after('progress_percentage'); // 'start', 'progress', 'complete', 'download'
            $table->timestamp('started_at')->nullable()->after('action');
            $table->timestamp('completed_at')->nullable()->after('started_at');
            $table->string('device_type')->nullable()->after('completed_at');
            $table->string('user_agent')->nullable()->after('device_type');
            $table->timestamps(); // Add created_at and updated_at
        });

        // Add indexes for performance
        Schema::table('user_activities', function (Blueprint $table) {
            $table->index(['user_id', 'activity_type', 'action']);
            $table->index(['activity_type', 'activity_id']);
            $table->index(['completed_at']);
            $table->index(['started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_activities', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['user_id', 'activity_type', 'action']);
            $table->dropIndex(['activity_type', 'activity_id']);
            $table->dropIndex(['completed_at']);
            $table->dropIndex(['started_at']);
            
            // Drop columns
            $table->dropColumn([
                'metadata',
                'duration_seconds',
                'progress_percentage',
                'action',
                'started_at',
                'completed_at',
                'device_type',
                'user_agent',
                'created_at',
                'updated_at'
            ]);
        });
    }
};