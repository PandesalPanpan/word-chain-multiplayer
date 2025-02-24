<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Events\GameRoomUpdatedEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use function Illuminate\Events\queueable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'game_room_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::updated(queueable(function ($user) {
            if ($user->isDirty('game_room_id')) {
                if ($user->game_room_id) {
                    // Load the game room with user count when a user joins
                    $gameRoom = GameRoom::where('id', $user->game_room_id)
                        ->withCount('users')
                        ->first();

                } else {
                    // When user leaves, load and broadcast the old game room
                    $gameRoom = GameRoom::where('id', $user->getOriginal('game_room_id'))
                        ->withCount('users')
                        ->first();

                }

                event(new GameRoomUpdatedEvent('updated', $gameRoom->toArray()));
            }
        }));
    }

    public function gameRoom(): belongsTo
    {
        return $this->belongsTo(GameRoom::class, 'game_room_id');
    }
}
