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
        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attempt_id');
            $table->unsignedBigInteger('quiz_question_id');
            $table->unsignedBigInteger('selected_answer_id');
            $table->string('answer_text')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->foreign('attempt_id')->references('id')->on('attempt_quizzes')->onDelete('cascade');
            $table->foreign('quiz_question_id')->references('id')->on('quiz_questions')->onDelete('cascade');
            $table->foreign('selected_answer_id')->references('id')->on('quiz_answers')->nullable()->onDelete('cascade');
            $table->timestamps();
            //ID attmpt, ID jawaban, is_correct
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attempt_answers');
    }
};
