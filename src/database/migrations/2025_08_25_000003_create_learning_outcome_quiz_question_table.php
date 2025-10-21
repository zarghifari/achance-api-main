<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('learning_outcome_quiz_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_outcome_id')->constrained('learning_outcomes')->onDelete('cascade');
            $table->foreignId('quiz_question_id')->constrained('quiz_questions')->onDelete('cascade');
            $table->decimal('weight', 5, 2)->default(1.00); // Weight for assessment calculation
            $table->timestamps();
            
            $table->unique(['learning_outcome_id', 'quiz_question_id'], 'lo_quiz_question_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('learning_outcome_quiz_question');
    }
};
