<?php
declare(strict_types=1);

namespace CheeeasyTech\Booking\Contracts;

interface Bookerable
{
    public function getBookerId(): int;

    public function getBookerType(): string;
} 