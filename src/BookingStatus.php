<?php

declare(strict_types=1);

namespace CheeasyTech\Booking;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

class BookingStatus
{
    protected string $status;

    protected ?string $reason;

    protected ?Carbon $changedAt;

    protected ?array $metadata;

    public function __construct(
        string $status,
        ?string $reason = null,
        ?Carbon $changedAt = null,
        ?array $metadata = null
    ) {
        $this->validateStatus($status);
        $this->status = $status;
        $this->reason = $reason;
        $this->changedAt = $changedAt ?? Carbon::now();
        $this->metadata = $metadata;
    }

    protected function validateStatus(string $status): void
    {
        $allowedStatuses = Config::get('booking.statuses', ['pending', 'confirmed', 'cancelled']);

        if (! in_array($status, $allowedStatuses)) {
            throw new InvalidArgumentException("Invalid status: {$status}");
        }
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getChangedAt(): Carbon
    {
        return $this->changedAt;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'reason' => $this->reason,
            'changed_at' => $this->changedAt->toDateTimeString(),
            'metadata' => $this->metadata,
        ];
    }

    public static function fromArray(array $data): self
    {
        if (! isset($data['status'])) {
            throw new InvalidArgumentException('Status is required');
        }

        return new self(
            $data['status'],
            $data['reason'] ?? null,
            isset($data['changed_at']) ? Carbon::parse($data['changed_at']) : null,
            $data['metadata'] ?? null
        );
    }
}
