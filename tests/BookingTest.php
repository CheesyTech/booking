<?php

namespace CheeasyTech\Booking\Tests;

use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use CheeasyTech\Booking\Events\BookingCreated;
use CheeasyTech\Booking\Events\BookingDeleted;
use CheeasyTech\Booking\Events\BookingStatusChanged;
use CheeasyTech\Booking\Events\BookingUpdated;
use CheeasyTech\Booking\Models\Booking;
use CheeasyTech\Booking\Tests\Models\Room;
use CheeasyTech\Booking\Tests\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class BookingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([
            BookingCreated::class,
            BookingUpdated::class,
            BookingDeleted::class,
            BookingStatusChanged::class,
        ]);
        config(['booking.statuses' => ['pending', 'confirmed', 'cancelled']]);
    }

    #[Test]
    public function it_calculates_duration_in_minutes()
    {
        $booking = Booking::factory()
            ->duration(90)
            ->create(['status' => 'pending']);

        $this->assertEquals(90, $booking->getDurationInMinutes());
    }

    #[Test]
    public function it_calculates_duration_in_hours()
    {
        $booking = Booking::factory()
            ->duration(90)
            ->create(['status' => 'pending']);

        $this->assertEquals(1.5, $booking->getDurationInHours());
    }

    #[Test]
    public function it_calculates_duration_in_days()
    {
        $booking = Booking::factory()
            ->duration(1440) // 24 hours
            ->create(['status' => 'pending']);

        $this->assertEquals(1.0, $booking->getDurationInDays());
    }

    #[Test]
    public function it_validates_time_slot()
    {
        $validData = [
            'start_time' => '2024-01-01 10:00:00',
            'end_time' => '2024-01-01 11:00:00',
        ];

        $this->assertTrue(Booking::validateTimeSlot($validData));

        $this->expectException(\InvalidArgumentException::class);
        Booking::validateTimeSlot([
            'start_time' => '2024-01-01 11:00:00',
            'end_time' => '2024-01-01 10:00:00',
        ]);
    }

    #[Test]
    public function it_changes_status_and_maintains_history()
    {
        $booking = Booking::factory()->create(['status' => 'pending']);

        $booking->changeStatus('confirmed', 'Approved by admin', ['key' => 'value']);

        $this->assertEquals('confirmed', $booking->status);
        $this->assertInstanceOf(Carbon::class, $booking->status_changed_at);

        $history = $booking->getStatusHistory();
        $this->assertCount(1, $history);
        $this->assertEquals('pending', $history->first()->getStatus());

        Event::assertDispatched(BookingStatusChanged::class);
    }

    #[Test]
    public function it_prevents_invalid_status_change()
    {
        $booking = Booking::factory()->create(['status' => 'pending']);

        $this->expectException(\InvalidArgumentException::class);
        $booking->changeStatus('invalid_status');
    }

    #[Test]
    public function it_filters_bookings_by_duration()
    {
        // Create bookings with different durations
        Booking::factory()->duration(30)->create(['status' => 'pending']);  // 30 minutes
        Booking::factory()->duration(60)->create(['status' => 'pending']);  // 1 hour
        Booking::factory()->duration(90)->create(['status' => 'pending']);  // 1.5 hours
        Booking::factory()->duration(120)->create(['status' => 'pending']); // 2 hours

        // Test durationLongerThan
        $this->assertEquals(3, Booking::durationLongerThan(45)->count());

        // Test durationShorterThan
        $this->assertEquals(2, Booking::durationShorterThan(75)->count());

        // Test durationEquals
        $this->assertEquals(1, Booking::durationEquals(60)->count());

        // Test durationBetween
        $this->assertEquals(2, Booking::durationBetween(45, 105)->count());
    }

    #[Test]
    public function it_combines_duration_scopes_with_other_conditions()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        // Create test bookings with non-overlapping time slots
        Booking::factory()
            ->for($room, 'bookable')
            ->for($user, 'bookerable')
            ->create([
                'start_time' => '2024-01-01 10:00:00',
                'end_time' => '2024-01-01 10:30:00',
                'status' => 'confirmed',
            ]);

        Booking::factory()
            ->for($room, 'bookable')
            ->for($user, 'bookerable')
            ->create([
                'start_time' => '2024-01-01 11:00:00',
                'end_time' => '2024-01-01 12:00:00',
                'status' => 'confirmed',
            ]);

        Booking::factory()
            ->for($room, 'bookable')
            ->for($user, 'bookerable')
            ->create([
                'start_time' => '2024-01-01 13:00:00',
                'end_time' => '2024-01-01 14:30:00',
                'status' => 'pending',
            ]);

        // Test complex query with multiple conditions
        $count = Booking::query()
            ->where('status', 'confirmed')
            ->where('bookable_id', $room->id)
            ->durationLongerThan(45)
            ->count();

        $this->assertEquals(1, $count);
    }

    #[Test]
    public function it_prevents_overlapping_bookings()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        // Create first booking
        Booking::factory()
            ->for($room, 'bookable')
            ->for($user, 'bookerable')
            ->create([
                'start_time' => '2024-01-01 10:00:00',
                'end_time' => '2024-01-01 11:00:00',
                'status' => 'pending',
            ]);

        // Try to create overlapping booking
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('This time slot overlaps with an existing booking.');

        Booking::factory()
            ->for($room, 'bookable')
            ->for($user, 'bookerable')
            ->create([
                'start_time' => '2024-01-01 10:30:00',
                'end_time' => '2024-01-01 11:30:00',
                'status' => 'pending',
            ]);
    }

    #[Test]
    public function it_uses_correct_sql_for_duration_filters_with_different_drivers()
    {
        // Test with MySQL
        DB::shouldReceive('getDriverName')
            ->once()
            ->andReturn('mysql');

        $query = Booking::durationLongerThan(60)->toSql();
        $this->assertStringContainsString(
            'TIMESTAMPDIFF(MINUTE, start_time, end_time)',
            $query
        );

        // Test with PostgreSQL
        DB::shouldReceive('getDriverName')
            ->once()
            ->andReturn('pgsql');

        $query = Booking::durationLongerThan(60)->toSql();
        $this->assertStringContainsString(
            'EXTRACT(EPOCH FROM (end_time - start_time)) / 60',
            $query
        );

        // Test with SQLite
        DB::shouldReceive('getDriverName')
            ->once()
            ->andReturn('sqlite');

        $query = Booking::durationLongerThan(60)->toSql();
        $this->assertStringContainsString(
            "(strftime('%s', end_time) - strftime('%s', start_time)) / 60",
            $query
        );
    }

    #[Test]
    public function it_handles_minimum_time_between_bookings()
    {
        config(['booking.overlap.min_time_between' => 30]); // 30 minutes minimum between bookings

        $room = Room::factory()->create();
        $user = User::factory()->create();

        // Create first booking
        Booking::factory()
            ->for($room, 'bookable')
            ->for($user, 'bookerable')
            ->create([
                'start_time' => '2024-01-01 10:00:00',
                'end_time' => '2024-01-01 11:00:00',
                'status' => 'pending',
            ]);

        // Try to create booking too close to the first one
        $this->expectException(\Exception::class);

        Booking::factory()
            ->for($room, 'bookable')
            ->for($user, 'bookerable')
            ->create([
                'start_time' => '2024-01-01 11:15:00', // Only 15 minutes after first booking
                'end_time' => '2024-01-01 12:00:00',
                'status' => 'pending',
            ]);
    }

    #[Test]
    public function it_enforces_maximum_duration()
    {
        config(['booking.overlap.max_duration' => 120]); // Maximum 2 hours

        $room = Room::factory()->create();
        $user = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Booking duration cannot exceed 120 minutes');

        Booking::factory()
            ->for($room, 'bookable')
            ->for($user, 'bookerable')
            ->create([
                'start_time' => '2024-01-01 10:00:00',
                'end_time' => '2024-01-01 13:00:00', // 3 hours
                'status' => 'pending',
            ]);
    }

    #[Test]
    public function it_allows_same_booker_overlap_when_configured()
    {
        config(['booking.overlap.allow_same_booker' => true]);

        $room = Room::factory()->create();
        $user = User::factory()->create();

        // Create first booking
        Booking::factory()
            ->for($room, 'bookable')
            ->for($user, 'bookerable')
            ->create([
                'start_time' => '2024-01-01 10:00:00',
                'end_time' => '2024-01-01 11:00:00',
                'status' => 'pending',
            ]);

        // Create overlapping booking with same booker
        $booking = Booking::factory()
            ->for($room, 'bookable')
            ->for($user, 'bookerable')
            ->create([
                'start_time' => '2024-01-01 10:30:00',
                'end_time' => '2024-01-01 11:30:00',
                'status' => 'pending',
            ]);

        $this->assertNotNull($booking->id);
    }

    #[Test]
    public function it_fires_events_on_model_lifecycle()
    {
        Event::fake([
            BookingCreated::class,
            BookingUpdated::class,
            BookingDeleted::class,
        ]);

        $booking = Booking::factory()->create(['status' => 'pending']);
        Event::assertDispatched(BookingCreated::class);

        $booking->update(['status' => 'confirmed']);
        Event::assertDispatched(BookingUpdated::class);

        $booking->delete();
        Event::assertDispatched(BookingDeleted::class);
    }

    #[Test]
    public function it_returns_current_status()
    {
        $booking = Booking::factory()->create(['status' => 'confirmed']);

        $status = $booking->getCurrentStatus();

        $this->assertEquals('confirmed', $status->getStatus());
        $this->assertNull($status->getReason());
    }

    #[Test]
    public function it_checks_has_status()
    {
        $booking = Booking::factory()->create(['status' => 'pending']);

        $this->assertTrue($booking->hasStatus('pending'));
        $this->assertFalse($booking->hasStatus('confirmed'));
    }

    #[Test]
    public function it_returns_false_for_has_overlap_when_disabled()
    {
        config(['booking.overlap.enabled' => false]);

        $room = Room::factory()->create();
        $user = User::factory()->create();
        $booking = Booking::factory()
            ->for($room, 'bookable')
            ->for($user, 'bookerable')
            ->create([
                'start_time' => '2024-01-01 10:00:00',
                'end_time' => '2024-01-01 11:00:00',
                'status' => 'pending',
            ]);

        $startTime = Carbon::parse('2024-01-01 10:30:00');
        $endTime = Carbon::parse('2024-01-01 11:30:00');

        $this->assertFalse($booking->hasOverlap($startTime, $endTime));
    }

    #[Test]
    public function it_excludes_booking_id_when_checking_overlap()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();
        $booking = Booking::factory()
            ->for($room, 'bookable')
            ->for($user, 'bookerable')
            ->create([
                'start_time' => '2024-01-01 10:00:00',
                'end_time' => '2024-01-01 11:00:00',
                'status' => 'pending',
            ]);

        $this->assertFalse($booking->hasOverlap(
            Carbon::parse('2024-01-01 10:00:00'),
            Carbon::parse('2024-01-01 11:00:00'),
            $booking->id
        ));
    }

    #[Test]
    public function it_enforces_business_hours_rule_when_configured()
    {
        config([
            'booking.overlap.rules' => [
                'business_hours' => [
                    'enabled' => true,
                    'start' => '09:00',
                    'end' => '18:00',
                    'timezone' => 'UTC',
                ],
            ],
        ]);

        $room = Room::factory()->create();
        $user = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Bookings are only allowed between');

        Booking::factory()
            ->for($room, 'bookable')
            ->for($user, 'bookerable')
            ->create([
                'start_time' => '2024-01-01 20:00:00',
                'end_time' => '2024-01-01 21:00:00',
                'status' => 'pending',
            ]);
    }
}
