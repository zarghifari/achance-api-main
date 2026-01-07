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
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->onDelete('cascade');
            $table->string('title')->comment('Content title/name');
            $table->string('type', 50)->default('html')->comment('Content type: html, json, mixed');
            $table->text('description')->nullable()->comment('Content description');
            
            // File references
            $table->string('original_filename')->nullable()->comment('Original uploaded filename');
            $table->string('source_file_path', 500)->nullable()->comment('Path to original .doc/.docx file');
            $table->unsignedBigInteger('source_file_size')->nullable()->comment('Original file size in bytes');
            
            // Processed HTML/JSON storage
            $table->longText('html_content')->nullable()->comment('Pre-processed HTML content');
            $table->json('json_content')->nullable()->comment('Structured JSON content for pagination');
            $table->json('metadata')->nullable()->comment('Additional metadata: page_count, images, etc.');
            
            // Images and assets
            $table->json('images')->nullable()->comment('Array of image paths extracted from content');
            $table->json('assets')->nullable()->comment('Array of other assets (GIFs, videos, etc.)');
            
            // Pagination settings
            $table->integer('total_pages')->default(1)->comment('Total number of pages');
            $table->integer('words_per_page')->default(500)->comment('Words per page for pagination');
            
            // Status and ordering
            $table->integer('position')->default(0)->comment('Order within lesson');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_processed')->default(false)->comment('Whether content has been pre-processed');
            $table->timestamp('processed_at')->nullable()->comment('When content was last processed');
            
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('lesson_id');
            $table->index('type');
            $table->index('is_active');
            $table->index('is_processed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
