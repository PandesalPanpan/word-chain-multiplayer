<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('game_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'host_id');
            $table->foreignIdFor(User::class, 'current_player_id')->nullable();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->boolean('in_progress')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_rooms');
    }
};
