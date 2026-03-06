<?php

namespace CheeasyTech\Booking\Tests;

use Carbon\Carbon;
use CheeasyTech\Booking\Models\Booking;
use CheeasyTech\Booking\Rules\BusinessHoursRule;
use CheeasyTech\Booking\Tests\Models\Room;
use CheeasyTech\Booking\Tests\Models\User;
use PHPUnit\Framework\Attributes\Test;

class BusinessHoursRuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['booking.statuses' => ['pending', 'confirmed', 'cancelled']]);
    }

    #[Test]
    public function it_constructs_with_business_hours()
    {
        $rule = new BusinessHoursRule('09:00', '18:00', 'UTC');

        $this->assertInstanceOf(BusinessHoursRule::class, $rule);
    }

    #[Test]
    public function it_validates_booking_within_business_hours()
    {
        $rule = new BusinessHoursRule('09:00', '18:00', 'UTC');

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

        $startTime = Carbon::today('UTC')->setTime(10, 0);
        $endTime = Carbon::today('UTC')->setTime(11, 0);

        $this->assertTrue($rule->validate($booking, $startTime, $endTime));
    }

    #[Test]
    public function it_rejects_booking_outside_business_hours()
    {
        $rule = new BusinessHoursRule('09:00', '18:00', 'UTC');

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

        $startTime = Carbon::today('UTC')->setTime(20, 0);
        $endTime = Carbon::today('UTC')->setTime(21, 0);

        $this->assertFalse($rule->validate($booking, $startTime, $endTime));
    }

    #[Test]
    public function it_returns_error_message()
    {
        $rule = new BusinessHoursRule('09:00', '18:00', 'UTC');

        $message = $rule->getErrorMessage();

        $this->assertStringContainsString('09:00', $message);
        $this->assertStringContainsString('18:00', $message);
        $this->assertStringContainsString('UTC', $message);
    }
}
