<?php
declare(strict_types=1);

namespace CheeasyTech\Booking\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasBookers
{
    public function bookers(): MorphToMany
    {
        /** @var Model $this */
        return $this->morphToMany(config('booking.user_model'), 'bookable', 'bookings');
    }
}