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
        Schema::create('goal_progress_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_goal_id')->constrained()->onDelete('cascade');
            $table->decimal('progress_value', 10, 2);
            $table->text('note')->nullable();
            $table->timestamp('logged_at')->useCurrent();
            
            // Indexes
            $table->index(['learning_goal_id', 'logged_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goal_progress_logs');
    }
};
