<?php
declare(strict_types=1);

namespace CheeasyTech\Booking;

use Carbon\Carbon;
use CheeasyTech\Booking\Contracts\OverlapRule;
use CheeasyTech\Booking\Events\BookingStatusChanged;
use CheeasyTech\Booking\Rules\BusinessHoursRule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Illuminate\Support\Collection;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'bookable_id',
        'bookable_type',
        'bookerable_id',
        'bookerable_type',
        'start_time',
        'end_time',
        'status',
        'status_history',
        'status_changed_at',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'status_history' => 'array',
        'status_changed_at' => 'datetime',
    ];

    public function bookable(): MorphTo
    {
        return $this->morphTo();
    }

    public function bookerable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Change the booking status
     *
     * @param string $status
     * @param string|null $reason
     * @param array|null $metadata
     * @return $this
     * @throws InvalidArgumentException
     */
    public function changeStatus(string $status, ?string $reason = null, ?array $metadata = null): self
    {
        $allowedStatuses = Config::get('booking.statuses', ['pending', 'confirmed', 'cancelled']);
        
        if (!in_array($status, $allowedStatuses)) {
            throw new InvalidArgumentException("Invalid status: {$status}");
        }

        $newStatus = new BookingStatus($status, $reason, null, $metadata);
        
        // Initialize status history if not exists
        if (!$this->status_history) {
            $this->status_history = [];
        }

        // Add current status to history
        if ($this->status) {
            $this->status_history[] = (new BookingStatus(
                $this->status,
                null,
                $this->status_changed_at
            ))->toArray();
        }

        $this->status = $status;
        $this->status_changed_at = $newStatus->getChangedAt();
        $this->save();

        // Fire status changed event
        event(new BookingStatusChanged($this, $newStatus));

        return $this;
    }

    /**
     * Get the current status object
     *
     * @return BookingStatus
     */
    public function getCurrentStatus(): BookingStatus
    {
        return new BookingStatus(
            $this->status,
            null,
            $this->status_changed_at
        );
    }

    /**
     * Get the status history
     *
     * @return Collection
     */
    public function getStatusHistory(): Collection
    {
        if (!$this->status_history) {
            return collect();
        }

        return collect($this->status_history)->map(function ($status) {
            return BookingStatus::fromArray($status);
        });
    }

    /**
     * Check if the booking has a specific status
     *
     * @param string $status
     * @return bool
     */
    public function hasStatus(string $status): bool
    {
        return $this->status === $status;
    }

    /**
     * Validate the time slot
     *
     * @param array $data
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function validateTimeSlot(array $data): bool
    {
        $validator = Validator::make($data, [
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        return true;
    }

    /**
     * Check if the time slot overlaps with existing bookings
     *
     * @param Carbon $startTime
     * @param Carbon $endTime
     * @param int|null $excludeBookingId
     * @return bool
     * @throws InvalidArgumentException
     */
    public function hasOverlap(Carbon $startTime, Carbon $endTime, ?int $excludeBookingId = null): bool
    {
        if (!Config::get('booking.overlap.enabled', true)) {
            return false;
        }

        // Check basic overlap
        $query = static::where('bookable_id', $this->bookable_id)
            ->where('bookable_type', $this->bookable_type)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            });

        // Exclude same booker if configured
        if (Config::get('booking.overlap.allow_same_booker', false)) {
            $query->where('bookerable_id', '!=', $this->bookerable_id)
                ->where('bookerable_type', '!=', $this->bookerable_type);
        }

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        // Check minimum time between bookings
        $minTimeBetween = Config::get('booking.overlap.min_time_between', 0);
        if ($minTimeBetween > 0) {
            $query->orWhere(function ($q) use ($startTime, $endTime, $minTimeBetween) {
                $q->where('end_time', '>=', $startTime->copy()->subMinutes($minTimeBetween))
                    ->where('start_time', '<=', $endTime->copy()->addMinutes($minTimeBetween));
            });
        }

        // Check maximum duration
        $maxDuration = Config::get('booking.overlap.max_duration', 0);
        if ($maxDuration > 0 && $startTime->diffInMinutes($endTime) > $maxDuration) {
            throw new InvalidArgumentException("Booking duration cannot exceed {$maxDuration} minutes");
        }

        // Apply custom rules
        $rules = Config::get('booking.overlap.rules', []);
        foreach ($rules as $ruleName => $ruleConfig) {
            if (!empty($ruleConfig['enabled'])) {
                $rule = $this->resolveRule($ruleName, $ruleConfig);
                if (!$rule->validate($this, $startTime, $endTime)) {
                    throw new InvalidArgumentException($rule->getErrorMessage());
                }
            }
        }

        return $query->exists();
    }

    /**
     * Get the duration of the booking in minutes
     *
     * @return int
     */
    public function getDurationInMinutes(): int
    {
        return $this->start_time->diffInMinutes($this->end_time);
    }

    /**
     * Resolve a rule instance from configuration
     *
     * @param string $ruleName
     * @param array $config
     * @return OverlapRule
     */
    protected function resolveRule(string $ruleName, array $config): OverlapRule
    {
        $ruleMap = [
            'business_hours' => BusinessHoursRule::class,
        ];

        if (!isset($ruleMap[$ruleName])) {
            throw new InvalidArgumentException("Unknown rule: {$ruleName}");
        }

        $ruleClass = $ruleMap[$ruleName];
        return new $ruleClass(
            $config['start'] ?? '09:00',
            $config['end'] ?? '18:00',
            $config['timezone'] ?? 'UTC'
        );
    }
}
