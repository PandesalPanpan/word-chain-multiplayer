<?php

namespace App\Models;

use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class GameRoom extends Model
{
    use BroadcastsEvents, HasFactory, Notifiable;

    protected $guarded = [];

    public function wordMoves(): HasMany
    {
        return $this->hasMany(WordMove::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'game_room_id');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'in_progress' => 'boolean',
        ];
    }
}
