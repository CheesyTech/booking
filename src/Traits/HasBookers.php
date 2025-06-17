<?php

declare(strict_types=1);

namespace CheeasyTech\Booking\Traits;

use CheeasyTech\Booking\Booking;
use CheeasyTech\Booking\Contracts\Bookerable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasBookers
{
    public function bookings(string|array|null $type = null): MorphToMany
    {
        /** @var Model $this */
        $query = $this->morphToMany(
            Booking::class,
            'bookable',
            'bookings',
            'bookable_id',
            'bookerable_id'
        );

        if ($type) {
            $types = is_array($type) ? $type : [$type];

            $validTypes = array_filter($types, function ($type) {
                return class_exists($type) && in_array(Bookerable::class, class_implements($type));
            });

            $query->whereIn('bookerable_type', $validTypes);
        }

        return $query;
    }
}
