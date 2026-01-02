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
        Schema::create('learning_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            
            // VARK Learning Styles (0-100 score each)
            $table->integer('visual_score')->default(25);
            $table->integer('auditory_score')->default(25);
            $table->integer('reading_score')->default(25);
            $table->integer('kinesthetic_score')->default(25);
            
            // Preferences
            $table->enum('preferred_content_type', ['video', 'text', 'audio', 'interactive', 'mixed'])->default('mixed');
            $table->enum('preferred_lesson_length', ['short', 'medium', 'long'])->default('medium')->comment('5-10min, 15-30min, 45min+');
            $table->enum('learning_pace', ['slow', 'moderate', 'fast'])->default('moderate');
            
            // Study habits
            $table->enum('preferred_study_time', ['morning', 'afternoon', 'evening', 'night'])->default('evening');
            $table->integer('daily_study_goal_minutes')->default(30);
            
            // Engagement preferences
            $table->boolean('likes_gamification')->default(true);
            $table->boolean('likes_group_learning')->default(true);
            $table->boolean('likes_challenges')->default(true);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_profiles');
    }
};
