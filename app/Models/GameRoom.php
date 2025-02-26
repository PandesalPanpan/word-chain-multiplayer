<?php

namespace App\Models;

use App\Events\GameRoomLobbyEvent;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

use function Illuminate\Events\queueable;

class GameRoom extends Model
{
    use BroadcastsEvents, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'is_active',
        'in_progress',
    ];

    protected static function booted(): void
    {
        static::created(queueable(function ($gameRoom) {
            // If the game room is inactive no need to broadcast
            if (! $gameRoom->is_active) {
                return;
            }

            // Pass the game room with the users count
            $gameRoom->loadCount('users');
            event(new GameRoomLobbyEvent('created', $gameRoom->toArray()));
        }));

        static::updated(queueable(function ($gameRoom) {

            // Get the old attribute
            $old_is_active = $gameRoom->getOriginal('is_active');

            // Get the current attribute
            $current_is_active = $gameRoom->is_active;

            // What are the possibilities
            // 1. Old: False -> New: False = No need to broadcast
            if ($old_is_active === false && $current_is_active === false) {
                return;
            }

            // 2. Old: True -> New: False = Broadcast without user count and also remove users from the game room
            if ($old_is_active === true && $current_is_active === false) {
                $gameRoom->users()->update(['game_room_id' => null]);
                event(new GameRoomLobbyEvent('updated', $gameRoom->toArray()));

                return;
            }

            // 3. Old: False -> New: True = Broadcast
            // 4. Old: True -> New: True = Broadcast
            $gameRoom->loadCount('users');
            event(new GameRoomLobbyEvent('updated', $gameRoom->toArray()));
        }));
    }

    public function wordMoves(): HasMany
    {
        return $this->hasMany(WordMove::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'in_progress' => 'boolean',
        ];
    }
}
