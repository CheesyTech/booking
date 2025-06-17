<?php

namespace CheeasyTech\Booking\Tests\Models;

use CheeasyTech\Booking\Contracts\Bookerable;
use CheeasyTech\Booking\Traits\HasBookers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model implements Bookerable
{
    use HasBookers;
    use HasFactory;

    protected $fillable = ['name', 'email'];

    public function getBookerableId(): int|string
    {
        return $this->id;
    }

    public function getBookerableType(): string
    {
        return static::class;
    }

    protected static function newFactory()
    {
        return \CheeasyTech\Booking\Tests\Factories\UserFactory::new();
    }
}
