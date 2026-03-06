<?php

declare(strict_types=1);

namespace CheeasyTech\Booking\Traits;

use CheeasyTech\Booking\Contracts\Bookerable;
use CheeasyTech\Booking\Models\Booking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasBookers
{
    public function bookings(string|array|null $type = null): MorphMany
    {
        /** @var Model $this */
        $query = $this->morphMany(Booking::class, 'bookerable');

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
