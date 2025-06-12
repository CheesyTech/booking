<?php
declare(strict_types=1);

namespace CheeasyTech\Booking\Traits;

use CheeasyTech\Booking\Contracts\Bookable;
use CheeasyTech\Booking\Events\BookingCreated;
use CheeasyTech\Booking\Events\BookingDeleted;
use CheeasyTech\Booking\Events\BookingUpdated;
use CheeasyTech\Booking\Booking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasBookings
{
    public function bookings(string|array $type = null): MorphMany
    {
        /** @var Model $this */
        $query = $this->morphMany(Booking::class, 'bookable');

        if ($type) {
            $types = is_array($type) ? $type : [$type];

            $validTypes = array_filter($types, function ($type) {
                return class_exists($type) && in_array(Bookable::class, class_implements($type));
            });

            $query->whereIn('bookable_type', $validTypes);
        }

        return $query;
    }

    public function newBooking(Bookable $bookable): Booking
    {
        $booking = $this->bookings()->create([
            'bookable_id' => $bookable->getBookableId(),
            'bookable_type' => $bookable->getBookableType(),
        ]);
        event(new BookingCreated($booking));
        return $booking;
    }

    public function deleteBooking(Bookable $bookable): bool
    {
        $booking = $this->findBooking($bookable);
        if (!$booking) {
            return false;
        }
        $deleted = $booking->delete();
        if ($deleted) {
            event(new BookingDeleted($booking));
        }
        return $deleted;
    }

    public function findBooking(Bookable $bookable): ?Booking
    {
        return $this->bookings()
            ->where([
                'bookable_id' => $bookable->getBookableId(),
                'bookable_type' => $bookable->getBookableType(),
            ])
            ->first();
    }

    public function updateBooking(Bookable $bookable, array $attributes): bool
    {
        $booking = $this->findBooking($bookable);
        if (!$booking) {
            return false;
        }
        $updated = $booking->update($attributes);
        if ($updated) {
            event(new BookingUpdated($booking));
        }
        return $updated;
    }
}