<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameRoom extends Model
{
    protected $fillable = [
        'name',
        'is_active',
    ];

    public function wordMoves(): HasMany
    {
        return $this->hasMany(WordMove::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
