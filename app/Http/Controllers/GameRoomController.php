<?php

namespace App\Http\Controllers;

use App\Events\DisconnectEvent;
use App\Events\GameRoomClosedEvent;
use App\Events\GameRoomStartEvent;
use App\Events\GameRoomTurnValidatedEvent;
use App\Models\DictionaryWord;
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
        // Include the game room word moves
        $gameRoom = $gameRoom->load('wordMoves');
        // Check if the room is active
        if (! $gameRoom->is_active) {
            return redirect()->route('game-rooms.index')
                ->with('error', 'The room is not active');
        }

        // Check if the user is already in the room
        if (auth()->user()->gameRoom && auth()->user()->gameRoom->is($gameRoom)) {
            return view('game-rooms.show', compact('gameRoom'));
        }

        // Check if the room is full already
        if ($gameRoom->users->count() >= 2) {
            return redirect()->back()
                ->with('error', 'The room is full (maximum 2 players allowed)');
        }

        // Check if the user belongs to a room already is not the current room
        if (auth()->user()->gameRoom && ! auth()->user()->gameRoom->is($gameRoom)) {
            // Send an event to disconnect the user in the old room channel
            event(new DisconnectEvent(auth()->user()));

            // dissociate the user from the room
            auth()->user()->gameRoom()->dissociate()->save();
            // send an event to the old room channel
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
                $gameRoom->update([
                    'is_active' => false,
                    'current_player_id' => null,
                    'in_progress' => false,
                ]);
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

    // Start the game by sending an event to the game room channel
    public function start(Request $request, GameRoom $gameRoom)
    {
        // Validate that the request comes from the host
        if ($request->user()->id !== $gameRoom->host_id) {
            return response()->json([
                'message' => 'Unauthorized. Only the host can start the game.',
            ], 403);
        }

        // Check if there are enough players
        if ($gameRoom->users()->count() < 2) {
            return response()->json([
                'message' => 'Not enough players to start the game.',
            ], 400);
        }

        $firstPlayer = $gameRoom->users()->inRandomOrder()->first();

        // Update game room status
        $gameRoom->update([
            'in_progress' => true,
            'current_player_id' => $firstPlayer->id,
        ]);

        // Broadcast the game started event
        event(new GameRoomStartEvent($gameRoom, $firstPlayer));

        return response()->json(['message' => 'Game started successfully']);
    }

    public function submitWord(Request $request, GameRoom $gameRoom)
    {
        // Validate the users turn
        if ($gameRoom->current_player_id !== $request->user()->id) {
            return response()->json([
                'message' => 'It is not your turn.',
            ], 403);
        }

        // The word should only be a single word and no space
        $request->validate([
            'word' => 'required|string|alpha',
        ]);

        $word = strtolower($request->word);
        // Grab all the words that have been played
        $words = $gameRoom->wordMoves->pluck('word')->toArray();
        $lastWord = end($words);

        $isValid = true;
        $message = 'Valid Word!';

        if (! empty($lastWord)) {
            if (substr($lastWord, -1) !== substr($word, 0, 1)) {
                $isValid = false;
                $message = 'Word must start with the last letter of the word '.$lastWord.'.';
            }
        }

        if ($isValid && in_array($word, $words)) {
            $isValid = false;
            $message = 'Word has already been played.';
        }

        if ($isValid && ! DictionaryWord::where('word', $word)->exists()) {
            $isValid = false;
            $message = 'Word is not found in dictionary.';
        }

        // Additional validation
        if ($isValid) {
            $gameRoom->wordMoves()->create([
                'user_id' => $request->user()->id,
                'word' => $word,
            ]);

            // Next Player
            $nextPlayer = $gameRoom->users()->where('id', '!=', $request->user()->id)->first();
            $gameRoom->update(['current_player_id' => $nextPlayer->id]);
        }

        event(new GameRoomTurnValidatedEvent(
            $gameRoom,
            $request->user(),
            $word,
            $isValid,
            $message,
            $isValid ? $nextPlayer : null
        ));

        return response()->json([
            'status' => $isValid ? 'success' : 'error',
            'message' => $message,
        ]);

    }
}
