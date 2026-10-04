<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->unique()->numberBetween(100000, 199999),
            'hotel_id' => Hotel::factory(),
            'name' => $this->faker->randomElement(['Standard Room', 'Deluxe Room', 'Suite']),
        ];
    }
}
