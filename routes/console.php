<?php

use App\Models\GameRoom;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Grab all the game rooms that are not in progress and check last progress time
// If the game room has been inactive for 30 minutes, delete the game room
// and disassociate the user from the game room
\Illuminate\Support\Facades\Schedule::call(function () {
    $gameRooms = GameRoom::where('in_progress', false)
        ->with('users')
        ->where('updated_at', '<=', now()->subMinutes(30))
        ->get();

    Log::info('Game rooms:');
    Log::info('');
    Log::info($gameRooms);

    foreach ($gameRooms as $gameRoom) {
        // Write a truth table, All data will always be not in progress
        // 1. Game room is inactive and not in progress -> Delete the game room
        if (! $gameRoom->is_active) {
            $gameRoom->users()->update(['game_room_id' => null]);
            $gameRoom->delete();
        }
        // ! This will not occur
        // 2. Game room is inactive and in progress -> Do nothing

        // 3. Game room is active and not in progress for 30 minutes -> Update to be inactive first
        if ($gameRoom->is_active) {
            // Remove users from the game room
            $gameRoom->users()->update(['game_room_id' => null]);
            $gameRoom->update(['is_active' => false]);
        }

        // ! This will not occur
        // 4. Game room is active and in progress -> Do Nothing
    }
})->everyMinute();

// Check for games with expired turn timers
\Illuminate\Support\Facades\Schedule::command('game:check-timeouts')
    ->everyFiveSeconds() // TODO: Double check if this interval is enough
    ->withoutOverlapping();
