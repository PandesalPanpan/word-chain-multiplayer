<?php

namespace App\Models;

use App\Events\GameRoomUpdatedEvent;
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
            logger('GameRoom created: '.$gameRoom);
            event(new GameRoomUpdatedEvent('created', $gameRoom));
        }));

        static::deleted(queueable(function ($gameRoom) {
            logger('GameRoom deleted: '.$gameRoom);
            event(new GameRoomUpdatedEvent('deleted', $gameRoom));
        }));

        static::updated(queueable(function ($gameRoom) {
            logger('GameRoom updated: '.$gameRoom);
            event(new GameRoomUpdatedEvent('updated', $gameRoom));
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
        ];
    }
}
