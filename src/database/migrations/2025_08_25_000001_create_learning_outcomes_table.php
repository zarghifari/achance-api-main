<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('learning_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->string('slug')->nullable(); // e.g., LO1, LO2, etc.
            $table->text('description');
            $table->string('cognitive_level')->nullable(); // Bloom's taxonomy level
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['course_id', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('learning_outcomes');
    }
};
