<?php
declare(strict_types=1);

namespace CheeasyTech\Booking\Contracts;

interface Bookerable
{
    /**
     * Get the unique identifier for the booker
     *
     * @return int|string
     */
    public function getBookerableId(): int|string;

    /**
     * Get the type of the booker
     *
     * @return string
     */
    public function getBookerableType(): string;
} 