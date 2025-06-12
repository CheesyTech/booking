<?php
declare(strict_types=1);

namespace CheeasyTech\Booking\Contracts;

interface Bookerable
{
    public function getBookerableId(): int;

    public function getBookerableType(): string;
} 