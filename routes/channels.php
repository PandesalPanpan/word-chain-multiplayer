<?php

use App\Models\GameRoom;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('game-rooms.{gameRoomId}', function (User $user, GameRoom $gameRoomId) {
    return true;
    //    return $user->gameRooms->contains($gameRoomId);
});
