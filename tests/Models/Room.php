<?php

namespace CheeasyTech\Booking\Tests\Models;

use CheeasyTech\Booking\Contracts\Bookable;
use CheeasyTech\Booking\Traits\HasBookings;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @method static \CheeasyTech\Booking\Tests\Factories\RoomFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Room newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Room newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Room query()
 * @mixin \Eloquent
 */
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
