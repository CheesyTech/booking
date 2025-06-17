<?php

namespace CheeasyTech\Booking\Tests;

use Carbon\Carbon;
use CheeasyTech\Booking\BookingStatus;
use InvalidArgumentException;

class BookingStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['booking.statuses' => ['pending', 'confirmed', 'cancelled']]);
    }

    /** @test */
    public function it_creates_status_with_required_fields()
    {
        $status = new BookingStatus('pending');

        $this->assertEquals('pending', $status->getStatus());
        $this->assertNull($status->getReason());
        $this->assertNull($status->getMetadata());
        $this->assertInstanceOf(Carbon::class, $status->getChangedAt());
    }

    /** @test */
    public function it_creates_status_with_all_fields()
    {
        $changedAt = now();
        $metadata = ['key' => 'value'];

        $status = new BookingStatus(
            'confirmed',
            'Approved by admin',
            $changedAt,
            $metadata
        );

        $this->assertEquals('confirmed', $status->getStatus());
        $this->assertEquals('Approved by admin', $status->getReason());
        $this->assertEquals($changedAt, $status->getChangedAt());
        $this->assertEquals($metadata, $status->getMetadata());
    }

    /** @test */
    public function it_converts_status_to_array()
    {
        $changedAt = Carbon::parse('2024-01-01 10:00:00');
        $metadata = ['key' => 'value'];

        $status = new BookingStatus(
            'confirmed',
            'Approved by admin',
            $changedAt,
            $metadata
        );

        $array = $status->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('confirmed', $array['status']);
        $this->assertEquals('Approved by admin', $array['reason']);
        $this->assertEquals('2024-01-01 10:00:00', $array['changed_at']);
        $this->assertEquals($metadata, $array['metadata']);
    }

    /** @test */
    public function it_creates_status_from_array()
    {
        $changedAt = Carbon::parse('2024-01-01 10:00:00');
        $array = [
            'status' => 'confirmed',
            'reason' => 'Approved by admin',
            'changed_at' => '2024-01-01 10:00:00',
            'metadata' => ['key' => 'value'],
        ];

        $status = BookingStatus::fromArray($array);

        $this->assertEquals('confirmed', $status->getStatus());
        $this->assertEquals('Approved by admin', $status->getReason());
        $this->assertEquals($changedAt->toDateTimeString(), $status->getChangedAt()->toDateTimeString());
        $this->assertEquals(['key' => 'value'], $status->getMetadata());
    }

    /** @test */
    public function it_handles_missing_optional_fields_in_array()
    {
        $array = [
            'status' => 'confirmed',
        ];

        $status = BookingStatus::fromArray($array);

        $this->assertEquals('confirmed', $status->getStatus());
        $this->assertNull($status->getReason());
        $this->assertInstanceOf(Carbon::class, $status->getChangedAt());
        $this->assertNull($status->getMetadata());
    }

    /** @test */
    public function it_throws_exception_for_invalid_array()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Status is required');

        BookingStatus::fromArray([
            'reason' => 'Missing status field',
        ]);
    }

    /** @test */
    public function it_validates_status_against_config()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status: invalid_status');

        new BookingStatus('invalid_status');
    }

    /** @test */
    public function it_handles_custom_statuses_from_config()
    {
        config(['booking.statuses' => ['custom_status', 'another_status']]);

        $status = new BookingStatus('custom_status');

        $this->assertEquals('custom_status', $status->getStatus());
    }
}
