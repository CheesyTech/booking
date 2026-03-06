<?php

namespace CheeasyTech\Booking\Tests;

use CheeasyTech\Booking\Models\Booking;
use CheeasyTech\Booking\Tests\Models\Room;
use CheeasyTech\Booking\Tests\Models\User;
use PHPUnit\Framework\Attributes\Test;

class HasBookersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['booking.statuses' => ['pending', 'confirmed', 'cancelled']]);
    }

    #[Test]
    public function it_returns_bookings_relation_for_booker()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        Booking::factory()
            ->for($room, 'bookable')
            ->for($user, 'bookerable')
            ->create([
                'start_time' => '2024-01-01 10:00:00',
                'end_time' => '2024-01-01 11:00:00',
                'status' => 'pending',
            ]);

        $this->assertCount(1, $user->bookings);
        $this->assertEquals($user->id, $user->bookings->first()->bookerable_id);
    }
}
