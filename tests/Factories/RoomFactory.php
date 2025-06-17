<?php

namespace CheeasyTech\Booking\Tests\Factories;

use CheeasyTech\Booking\Tests\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word.' Room',
        ];
    }
}
