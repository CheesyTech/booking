<?php

declare(strict_types=1);

namespace CheeeasyTech\Booking\Events;

use CheeeasyTech\Booking\Booking;
use CheeeasyTech\Booking\BookingStatus;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingStatusChanged
{
    use Dispatchable, SerializesModels;

    public Booking $booking;
    public BookingStatus $newStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(Booking $booking, BookingStatus $newStatus)
    {
        $this->booking = $booking;
        $this->newStatus = $newStatus;
    }
} 