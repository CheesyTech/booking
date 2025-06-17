<?php

declare(strict_types=1);

namespace CheeasyTech\Booking\Contracts;

use Carbon\Carbon;
use CheeasyTech\Booking\Booking;

interface OverlapRule
{
    /**
     * Validate if the booking time slot is allowed
     */
    public function validate(Booking $booking, Carbon $startTime, Carbon $endTime): bool;

    /**
     * Get the error message if validation fails
     */
    public function getErrorMessage(): string;
}
