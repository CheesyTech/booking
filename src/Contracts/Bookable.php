<?php

declare(strict_types=1);

namespace CheeasyTech\Booking\Contracts;

interface Bookable
{
    public function getBookableId(): int;

    public function getBookableType(): string;
}
