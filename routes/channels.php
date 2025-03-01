<?php

use App\Models\GameRoom;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('game-rooms.{gameRoomId}', function ($user, $id) {

    $gameRoom = GameRoom::find($id);

    if ($gameRoom->users() && $gameRoom->users()->where('id', $user->id)->exists()) {
        return $user->only('id', 'name');
    }

    return false;
});

