<?php

use App\Models\GameRoom;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('word_moves', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(GameRoom::class, 'game_room_id');
            $table->foreignIdFor(User::class, 'user_id');
            $table->string('word');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('word_moves');
    }
};
