<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->integer('language_id')->unique(); // API language ID
            $table->string('name');
            $table->string('iso_code', 10)->unique();
            $table->string('native_name');
            $table->enum('direction', ['ltr', 'rtl'])->default('ltr');
            $table->integer('translations_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('language_id');
            $table->index('iso_code');
            $table->index('direction');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
