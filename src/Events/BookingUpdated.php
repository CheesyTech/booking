<?php

namespace CheeasyTech\Booking\Events;

use CheeasyTech\Booking\Booking;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Booking $booking)
    {
    }
} 