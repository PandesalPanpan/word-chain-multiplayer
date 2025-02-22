<?php

use App\Models\GameRoom;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::call(function () {
    // Grab all the game rooms that are not in progress and check last progress time
    $gameRooms = GameRoom::where('in_progress', false)
        ->with('users')
        ->where('created_at', '<=', now()->subMinutes(30))
        ->orWhere('in_progress_at', '<=', now()->subMinutes(30))
        ->get();

    Log::info('Game rooms:');
    Log::info('');
    Log::info($gameRooms);

    // Delete the game room if it has only one user, and it has not been in progress for 30 minutes
    foreach ($gameRooms as $gameRoom) {
        // log game room if they have in_progress_at or created_at only
        if ($gameRoom->in_progress_at && ! $gameRoom->created_at) {
            // This should not happen
            \Log::info('Game room '.$gameRoom->id.' has in_progress_at only');
        } elseif ($gameRoom->created_at && ! $gameRoom->in_progress_at) {
            \Log::info('Game room '.$gameRoom->id.' has created_at only');

            // Set the gameRoom to inactive and disassociate the user
            $gameRoom->users()->update(['game_room_id' => null]);
            $gameRoom->update(['is_active' => false]);

        } else {
            // Check if in_progress_at is past 30 minutes
            if ($gameRoom->in_progress_at->lte(now()->subMinutes(30))) {
                // Dissociate the user from the game room
                $gameRoom->users()->update(['game_room_id' => null]);
                // Delete the game room
                $gameRoom->update(['is_active' => false]);
            }

            \Log::info('Game room '.$gameRoom->id.' has both in_progress_at and created_at');
        }
    }
})->everyMinute();
