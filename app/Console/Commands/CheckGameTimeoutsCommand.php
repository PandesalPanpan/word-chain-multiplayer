<?php

namespace App\Console\Commands;

use App\Events\GameRoomTimeoutEvent;
use App\Models\GameRoom;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

class CheckGameTimeoutsCommand extends Command
{
    protected $signature = 'game:check-timeouts';

    protected $description = 'Check for games with expired turn timers';

    public function handle(): int
    {
        $this->info('Checking for game timeouts...');
        // Find all in-progress games with expired deadlines
        $expiredGames = GameRoom::where('in_progress', true)
            ->whereNotNull('turn_deadline')
            ->whereNotNull('current_player_id')
            ->where('turn_deadline', '<', now()->toIso8601String())
            ->get();

        $this->info("Found {$expiredGames->count()} games with expired timers");

        foreach ($expiredGames as $gameRoom) {
            $this->processTimeoutForGame($gameRoom);
        }

        return CommandAlias::SUCCESS;
    }

    private function processTimeoutForGame(GameRoom $gameRoom): void
    {
        // Get the current player who timed out
        $timedOutUser = User::find($gameRoom->current_player_id);
        if (! $timedOutUser) {
            Log::warning("Cannot find timed out user for game {$gameRoom->id}");

            return;
        }

        $this->info("Processing timeout for game {$gameRoom->id}, user {$timedOutUser->name}");

        // Find winner (the user who didn't time out)
        $winner = $gameRoom->users()
            ->where('id', '!=', $timedOutUser->id)
            ->first();

        if (! $winner) {
            Log::warning("Cannot find winner for game {$gameRoom->id}");

            return;
        }

        // End the game
        $gameRoom->update([
            'in_progress' => false,
        ]);

        // Broadcast timeout event with game ending
        event(new GameRoomTimeoutEvent(
            $gameRoom,
            $winner
        ));
    }
}
