<?php

namespace App\Http\Controllers;

use App\Models\GameRoom;
use App\Models\User;
use Illuminate\Http\Request;

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

    public function show(GameRoom $gameRoom)
    {
        $gameRoom->load('wordMoves');

        // Associate the authenticated user with the game room
        auth()->user()->gameRoom()->associate($gameRoom)->save();

        return view('game-rooms.show', compact('gameRoom'));
    }

    public function leave(Request $request, GameRoom $gameRoom)
    {
        // Check if user belongs to the room
        if ($request->user()->gameRoom->isNot($gameRoom)) {
            // do nothing
            return redirect()->route('game-rooms.index');
        }

        $request->user()->gameRoom()->dissociate()->save();

        return redirect()->route('game-rooms.index');
    }
}
