<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dictionary_words', function (Blueprint $table) {
            $table->id();
            $table->string('word')->unique();
            $table->index('word');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dictionary_words');
    }
};
