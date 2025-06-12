<?php

declare(strict_types=1);

namespace CheeeasyTech\Booking\Contracts;

use Carbon\Carbon;
use CheeeasyTech\Booking\Booking;

interface OverlapRule
{
    /**
     * Validate if the booking time slot is allowed
     *
     * @param Booking $booking
     * @param Carbon $startTime
     * @param Carbon $endTime
     * @return bool
     */
    public function validate(Booking $booking, Carbon $startTime, Carbon $endTime): bool;

    /**
     * Get the error message if validation fails
     *
     * @return string
     */
    public function getErrorMessage(): string;
} 