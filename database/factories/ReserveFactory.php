<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\Reserve;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReserveFactory extends Factory
{
    protected $model = Reserve::class;

    public function definition(): array
    {
        $checkIn = $this->faker->dateTimeBetween('+1 day', '+10 days');
        $checkOut = (clone $checkIn)->modify('+'.$this->faker->numberBetween(1, 5).' days');

        return [
            'id' => $this->faker->unique()->numberBetween(900000, 999999),
            'hotel_id' => Hotel::factory(),
            'room_id' => Room::factory(),
            'check_in' => $checkIn->format('Y-m-d'),
            'check_out' => $checkOut->format('Y-m-d'),
            'total' => $this->faker->randomFloat(2, 100, 1000),
        ];
    }
}
