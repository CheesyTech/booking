<?php

namespace CheeasyTech\Booking\Tests;

use CheeasyTech\Booking\Events\BookingCreated;
use CheeasyTech\Booking\Events\BookingDeleted;
use CheeasyTech\Booking\Events\BookingUpdated;
use CheeasyTech\Booking\Models\Booking;
use CheeasyTech\Booking\Tests\Models\Room;
use CheeasyTech\Booking\Tests\Models\User;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;

class HasBookingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([
            BookingCreated::class,
            BookingUpdated::class,
            BookingDeleted::class,
        ]);
        config(['booking.statuses' => ['pending', 'confirmed', 'cancelled']]);
    }

    #[Test]
    public function it_returns_bookings_relation()
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

        $this->assertCount(1, $room->bookings);
        $this->assertEquals($room->id, $room->bookings->first()->bookable_id);
    }

    #[Test]
    public function it_filters_bookings_by_type()
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

        $this->assertCount(1, $room->bookings(Room::class)->get());
        $this->assertCount(1, $room->bookings([Room::class])->get());
    }

    #[Test]
    public function it_creates_new_booking_via_new_booking()
    {
        config(['booking.overlap.enabled' => false]);

        $room = Room::factory()->create();
        $user = User::factory()->create();

        $booking = $room->newBooking($room, [
            'bookerable_id' => $user->id,
            'bookerable_type' => User::class,
            'start_time' => '2024-01-01 10:00:00',
            'end_time' => '2024-01-01 11:00:00',
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals($room->id, $booking->bookable_id);
        $this->assertEquals(Room::class, $booking->bookable_type);
        Event::assertDispatched(BookingCreated::class);
    }

    #[Test]
    public function it_finds_booking_via_find_booking()
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

        $found = $room->findBooking($room);
        $this->assertNotNull($found);
        $this->assertEquals($room->id, $found->bookable_id);
    }

    #[Test]
    public function it_returns_null_when_find_booking_not_found()
    {
        $room = Room::factory()->create();
        $otherRoom = Room::factory()->create();
        $user = User::factory()->create();

        Booking::factory()
            ->for($otherRoom, 'bookable')
            ->for($user, 'bookerable')
            ->create([
                'start_time' => '2024-01-01 10:00:00',
                'end_time' => '2024-01-01 11:00:00',
                'status' => 'pending',
            ]);

        $this->assertNull($room->findBooking($room));
    }

    #[Test]
    public function it_updates_booking_via_update_booking()
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

        $updated = $room->updateBooking($room, ['status' => 'confirmed']);
        $this->assertTrue($updated);
        $this->assertEquals('confirmed', $room->findBooking($room)->status);
        Event::assertDispatched(BookingUpdated::class);
    }

    #[Test]
    public function it_returns_false_when_update_booking_not_found()
    {
        $room = Room::factory()->create();
        $otherRoom = Room::factory()->create();

        $this->assertFalse($room->updateBooking($otherRoom, ['status' => 'confirmed']));
    }

    #[Test]
    public function it_deletes_booking_via_delete_booking()
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

        $deleted = $room->deleteBooking($room);
        $this->assertTrue($deleted);
        $this->assertNull($room->findBooking($room));
        Event::assertDispatched(BookingDeleted::class);
    }

    #[Test]
    public function it_returns_false_when_delete_booking_not_found()
    {
        $room = Room::factory()->create();
        $otherRoom = Room::factory()->create();

        $this->assertFalse($room->deleteBooking($otherRoom));
    }
}
