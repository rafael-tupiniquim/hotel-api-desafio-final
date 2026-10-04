<?php

namespace Database\Factories;

use App\Models\Hotel;
use Illuminate\Database\Eloquent\Factories\Factory;

class HotelFactory extends Factory
{
    protected $model = Hotel::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->unique()->numberBetween(1000000, 1999999),
            'name' => 'Hotel '.$this->faker->company(),
        ];
    }
}
