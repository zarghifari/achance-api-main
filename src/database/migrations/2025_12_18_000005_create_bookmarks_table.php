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
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('bookmarkable_type', 255)->comment('Lesson, Course, Module');
            $table->unsignedBigInteger('bookmarkable_id');
            $table->text('note')->nullable()->comment('why bookmarked');
            $table->timestamps();
            
            // Indexes
            $table->unique(['user_id', 'bookmarkable_type', 'bookmarkable_id'], 'unique_bookmark');
            $table->index(['user_id', 'bookmarkable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
    }
};
