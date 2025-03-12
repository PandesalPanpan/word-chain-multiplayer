<?php

namespace App\Events;

use App\Models\GameRoom;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameRoomTimeoutEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public GameRoom $gameRoom, public User $winner)
    {
        // Delete the word moves
        $gameRoom->wordMoves()->delete();
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('game-rooms.'.$this->gameRoom->id),
        ];
    }
}
