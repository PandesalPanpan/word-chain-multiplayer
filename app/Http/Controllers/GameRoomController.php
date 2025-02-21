<?php

namespace App\Http\Controllers;

use App\Models\GameRoom;

class GameRoomController extends Controller
{
    public function index()
    {
        $gameRooms = GameRoom::where('is_active', true)
            ->withCount('wordMoves')
            ->latest()
            ->get();

        return view('game-rooms.index', compact('gameRooms'));
    }
}
