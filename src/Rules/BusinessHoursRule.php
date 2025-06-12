<?php

declare(strict_types=1);

namespace CheeeasyTech\Booking\Rules;

use Carbon\Carbon;
use CheeeasyTech\Booking\Contracts\OverlapRule;
use CheeeasyTech\Booking\Booking;

class BusinessHoursRule implements OverlapRule
{
    protected string $startTime;
    protected string $endTime;
    protected string $timezone;

    public function __construct(string $startTime, string $endTime, string $timezone = 'UTC')
    {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->timezone = $timezone;
    }

    public function validate(Booking $booking, Carbon $startTime, Carbon $endTime): bool
    {
        $startTime = $startTime->setTimezone($this->timezone);
        $endTime = $endTime->setTimezone($this->timezone);

        $businessStart = Carbon::parse($this->startTime, $this->timezone);
        $businessEnd = Carbon::parse($this->endTime, $this->timezone);

        return $startTime->between($businessStart, $businessEnd) &&
               $endTime->between($businessStart, $businessEnd);
    }

    public function getErrorMessage(): string
    {
        return "Bookings are only allowed between {$this->startTime} and {$this->endTime} {$this->timezone}";
    }
} 