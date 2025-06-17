<?php

namespace CheeasyTech\Booking\Tests\Factories;

use Carbon\Carbon;
use CheeasyTech\Booking\Models\Booking;
use CheeasyTech\Booking\Tests\Models\Room;
use CheeasyTech\Booking\Tests\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $startTime = Carbon::instance($this->faker->dateTimeBetween('+1 day', '+1 month'));

        return [
            'bookable_type' => Room::class,
            'bookable_id' => Room::factory(),
            'bookerable_type' => User::class,
            'bookerable_id' => User::factory(),
            'start_time' => $startTime,
            'end_time' => $startTime->copy()->addHours($this->faker->numberBetween(1, 4)),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'cancelled']),
        ];
    }

    public function pending(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
            ];
        });
    }

    public function confirmed(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'confirmed',
            ];
        });
    }

    public function cancelled(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'cancelled',
            ];
        });
    }

    public function forRoom(Room $room): self
    {
        return $this->state(function (array $attributes) use ($room) {
            return [
                'bookable_type' => Room::class,
                'bookable_id' => $room->id,
            ];
        });
    }

    public function forUser(User $user): self
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'bookerable_type' => User::class,
                'bookerable_id' => $user->id,
            ];
        });
    }

    public function duration(int $minutes): self
    {
        return $this->state(function (array $attributes) use ($minutes) {
            $startTime = Carbon::parse($attributes['start_time']);

            return [
                'end_time' => $startTime->copy()->addMinutes($minutes),
            ];
        });
    }
}
