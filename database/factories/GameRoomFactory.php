<?php

namespace Database\Factories;

use App\Models\GameRoom;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class GameRoomFactory extends Factory
{
    protected $model = GameRoom::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'is_active' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
