<?php

namespace App\Http\Controllers;

use App\Events\GameRoomClosedEvent;
use App\Models\GameRoom;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameRoomController extends Controller
{
    public function index()
    {
        return view('game-rooms.index');
    }

    public function getActiveRooms()
    {

        $gameRoom = GameRoom::where('is_active', true)
            ->withCount('users')
            ->latest()
            ->get();

        return response()->json($gameRoom);
    }

    public function show(GameRoom $gameRoom)
    {
        // Check if the room is active
        if (! $gameRoom->is_active) {
            return redirect()->route('game-rooms.index')
                ->with('error', 'The room is not active');
        }

        // Check if the room is full already
        if ($gameRoom->users->count() >= 2) {
            return redirect()->back()
                ->with('error', 'The room is full (maximum 2 players allowed)');
        }

        // Check if the user belongs to a room already
        if (auth()->user()->gameRoom) {
            // dissociate the user from the room
            auth()->user()->gameRoom()->dissociate()->save();
            // send an event to the old room channel
            // TODO: Implement the event
        }

        // Associate the authenticated user with the game room
        auth()->user()->gameRoom()->associate($gameRoom)->save();

        return view('game-rooms.show', compact('gameRoom'));
    }

    public function leave(Request $request, GameRoom $gameRoom)
    {
        logger('-=-LEAVE game room called-=-');
        $case = match (true) {
            ! $request->user()->gameRoom->is($gameRoom) => 1, // User not in room
            $gameRoom->host_id === $request->user()->id => 2, // User is host
            $request->user()->gameRoom->is($gameRoom) => 3,   // User in room
            $gameRoom->users->count() === 0 => 4,             // Room has no users
            default => 0
        };

        switch ($case) {
            case 1: // User not in room
                return redirect()->route('game-rooms.index');

            case 2: // User is host
                // Check if there are other users in the room
                if ($gameRoom->users->count() > 1) {
                    // Make all users leave the room
                    $gameRoom->users()->update(['game_room_id' => null]);
                    $gameRoom->update(['is_active' => false]);
                    // TODO: Trigger an event to notify non host users to be redirected to the index page using alpine
                    event(new GameRoomClosedEvent($gameRoom));
                } else {
                    try {
                        DB::transaction(function () use ($gameRoom, $request) {
                            $request->user()->withoutEvents(function () use ($request) {
                                $request->user()->gameRoom()->dissociate()->save();
                            });
                            $gameRoom->update(['is_active' => false]);
                        });
                    } catch (\Throwable $e) {
                        report($e);

                        return redirect()->route('game-rooms.index');
                    }
                }
                break;

            case 3: // User was in room
                $request->user()->gameRoom()->dissociate()->save();
                break;

            case 4: // Room has no users
                $gameRoom->update(['is_active' => false]);
                break;
        }

        return redirect()->route('game-rooms.index');
    }

    public function create(Request $request)
    {
        // Check if user has a game room
        if ($request->user()->gameRoom) {
            $request->user()->gameRoom()->dissociate()->save();
        }

        // Route to create page

        return view('game-rooms.create');
    }

    public function store(Request $request)
    {
        // Validate request with a short name
        $request->validate([
            'name' => 'required|string|max:16',
        ]);

        $gameRoom = GameRoom::create([
            'host_id' => auth()->id(),
            'name' => $request->name,
        ]);

        return redirect()->route('game-rooms.show', $gameRoom);
    }
}
