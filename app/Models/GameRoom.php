<?php

namespace App\Models;

use App\Events\GameRoomLobbyEvent;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'host_id',
    ];

    protected static function booted(): void
    {
        static::created(queueable(function ($gameRoom) {
            logger('Game room created booted called');
            // If the game room is inactive no need to broadcast
            if (! $gameRoom->is_active) {
                return;
            }

            // Pass the game room with the users count
            $gameRoom->loadCount('users');
            event(new GameRoomLobbyEvent('created', $gameRoom->toArray()));
        }));

        static::updated(queueable(function ($gameRoom) {
            $gameRoom->loadCount('users');
            $gameRoomArray = $gameRoom->only([
                'id',
                'host_id',
                'name',
                'is_active',
                'in_progress',
                'created_at',
                'updated_at',
                'users_count',
            ]);

            logger('Game room updated booted called with: '.json_encode($gameRoomArray));

            $old_is_active = $gameRoom->getOriginal('is_active');
            $current_is_active = $gameRoom->is_active;

            if ($old_is_active === false && $current_is_active === false) {
                return;
            }

            if ($old_is_active === true && $current_is_active === false) {
                event(new GameRoomLobbyEvent('updated', $gameRoomArray));

                return;
            }

            event(new GameRoomLobbyEvent('updated', $gameRoomArray));
        }));
    }

    public function wordMoves(): HasMany
    {
        return $this->hasMany(WordMove::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
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
