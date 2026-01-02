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
        Schema::create('learning_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('goal_type', ['skill', 'certification', 'project', 'career', 'time_based', 'custom'])->default('custom');
            $table->string('title', 255);
            $table->text('description')->nullable();
            
            // Goal specifics
            $table->date('target_date')->nullable();
            $table->string('target_metric', 100)->nullable()->comment('complete 5 courses, study 100 hours, etc');
            $table->decimal('current_value', 10, 2)->default(0);
            $table->decimal('target_value', 10, 2)->nullable();
            
            $table->enum('status', ['active', 'achieved', 'paused', 'abandoned'])->default('active');
            $table->timestamp('achieved_at')->nullable();
            
            // Linked resources
            $table->json('related_courses')->nullable()->comment('array of course IDs');
            $table->json('related_skills')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index('target_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_goals');
    }
};
