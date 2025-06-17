<?php

namespace CheeasyTech\Booking\Tests\Models;

use CheeasyTech\Booking\Contracts\Bookable;
use CheeasyTech\Booking\Traits\HasBookings;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model implements Bookable
{
    use HasBookings;
    use HasFactory;

    protected $fillable = ['name'];

    public function getBookableId(): int
    {
        return $this->id;
    }

    public function getBookableType(): string
    {
        return static::class;
    }

    protected static function newFactory()
    {
        return \CheeasyTech\Booking\Tests\Factories\RoomFactory::new();
    }
}
