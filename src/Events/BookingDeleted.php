<?php
declare(strict_types=1);

namespace CheeeasyTech\Booking\Events;

use CheeeasyTech\Booking\Booking;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public Booking $booking)
    {
    }
} 