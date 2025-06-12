<?php
declare(strict_types=1);

namespace CheeeasyTech\Booking\Contracts;

interface Bookable
{
    public function getBookableId(): int;

    public function getBookableType(): string;
}