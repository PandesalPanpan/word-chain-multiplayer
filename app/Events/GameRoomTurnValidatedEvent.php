<?php

namespace App\Events;

use App\Models\GameRoom;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameRoomTurnValidatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public GameRoom $gameRoom,
        public User $user,
        public string $word,
        public bool $isValid,
        public string $message,
        public ?User $nextPlayer = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('game-rooms.'.$this->gameRoom->id),
        ];
    }
}
