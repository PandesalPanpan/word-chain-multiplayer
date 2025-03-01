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
