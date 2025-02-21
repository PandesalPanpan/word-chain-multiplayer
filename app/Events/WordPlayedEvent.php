<?php

namespace App\Events;

use App\Models\GameRoom;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WordPlayedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public GameRoom $gameRoomId,
        public string $word,
        public User $user,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("game-rooms.{$this->gameRoomId}"),
        ];
    }
}
