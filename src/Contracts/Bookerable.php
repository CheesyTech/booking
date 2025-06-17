<?php

declare(strict_types=1);

namespace CheeasyTech\Booking\Contracts;

interface Bookerable
{
    /**
     * Get the unique identifier for the booker
     */
    public function getBookerableId(): int|string;

    /**
     * Get the type of the booker
     */
    public function getBookerableType(): string;
}
