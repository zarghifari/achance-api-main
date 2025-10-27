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
        Schema::create('epubs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->onDelete('cascade');
            $table->string('title')->comment('EPUB title/name');
            $table->string('file_path', 500)->nullable()->comment('Relative path to EPUB file');
            $table->string('original_filename')->nullable()->comment('Original uploaded filename');
            $table->unsignedBigInteger('file_size')->nullable()->comment('File size in bytes');
            $table->string('mime_type')->nullable()->comment('MIME type of the EPUB file');
            $table->integer('position')->default(0)->comment('Order within lesson');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['lesson_id', 'position']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('epubs');
    }
};
