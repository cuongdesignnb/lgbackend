<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('video_url')->nullable();     // Direct video URL (from media library)
            $table->text('embed_code')->nullable();       // YouTube/Vimeo embed code
            $table->enum('source', ['embed', 'upload'])->default('embed');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });

        Schema::create('catalogues', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();    // Cover/thumbnail image
            $table->string('file_url');                    // PDF/file download URL
            $table->string('file_name')->nullable();       // Original file name
            $table->unsignedBigInteger('file_size')->nullable(); // In bytes
            $table->integer('download_count')->default(0);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogues');
        Schema::dropIfExists('videos');
    }
};
