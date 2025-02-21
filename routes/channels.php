<?php

use App\Models\GameRoom;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('game-rooms.{gameRoomId}', function ($user, $id) {
    return $user->only('id', 'name');
});


