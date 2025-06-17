<?php

declare(strict_types=1);

namespace CheeasyTech\Booking\Events;

use CheeasyTech\Booking\BookingStatus;
use CheeasyTech\Booking\Models\Booking;
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
