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
        // Main chapters table
        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->integer('chapter_id')->unique()->comment('Quran chapter ID (1-114)');
            $table->enum('revelation_place', ['makkah', 'madinah'])->index();
            $table->integer('revelation_order')->index();
            $table->boolean('bismillah_pre')->default(false);
            $table->string('name_simple', 100)->index();
            $table->string('name_complex', 100);
            $table->string('name_arabic', 100);
            $table->integer('verses_count');
            $table->json('pages')->comment('Start and end page numbers [start, end]');
            $table->timestamps();
            $table->softDeletes();

            // Composite index for common queries
            $table->index(['revelation_place', 'revelation_order']);
        });

        // Chapter translated names table (normalized)
        Schema::create('chapter_translated_names', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained('chapters')->onDelete('cascade');
            $table->string('language_code', 10)->index()->comment('ISO language code: en, ar, fr, etc.');
            $table->string('language_name', 50)->comment('Full language name');
            $table->string('name', 200)->comment('Translated chapter name');
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint: one translation per language per chapter
            $table->unique(['chapter_id', 'language_code'], 'unique_chapter_language');

            // Index for searching by name
            $table->index('name');
        });

        // Chapter info table (normalized for multiple languages)
        Schema::create('chapter_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained('chapters')->onDelete('cascade');
            $table->string('language_code', 10)->index()->comment('ISO language code');
            $table->string('language_name', 50)->comment('Full language name');
            $table->string('locale', 10)->nullable()->comment('Locale fallback: en_US, ar_SA, etc.');
            $table->text('text')->comment('Full HTML formatted chapter information');
            $table->text('short_text')->comment('Brief description/summary');
            $table->string('source', 255)->comment('Source of the information');
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint: prevent duplicate info per chapter+language+locale
            $table->unique(['chapter_id', 'language_code', 'locale'], 'unique_chapter_info');

            // Composite index for faster lookups
            $table->index(['chapter_id', 'language_code']);
        });

        // Chapter cache table (optional - for database caching strategy)
        Schema::create('chapter_cache', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key', 255)->unique();
            $table->json('data')->comment('Cached API response data');
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            // Index for cache cleanup queries
            $table->index(['expires_at', 'created_at']);
        });

        // Add PostgreSQL full-text search indexes
        if (config('database.default') === 'pgsql') {
            // Full-text search on chapter names
            DB::statement('CREATE INDEX chapters_name_simple_fulltext ON chapters USING gin(to_tsvector(\'english\', name_simple))');
            DB::statement('CREATE INDEX chapters_name_arabic_fulltext ON chapters USING gin(to_tsvector(\'arabic\', name_arabic))');

            // Full-text search on translated names
            DB::statement('CREATE INDEX chapter_translated_names_fulltext ON chapter_translated_names USING gin(to_tsvector(\'english\', name))');

            // Full-text search on chapter info
            DB::statement('CREATE INDEX chapter_info_text_fulltext ON chapter_info USING gin(to_tsvector(\'english\', short_text))');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chapter_cache');
        Schema::dropIfExists('chapter_info');
        Schema::dropIfExists('chapter_translated_names');
        Schema::dropIfExists('chapters');
    }
};
