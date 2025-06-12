<?php

declare(strict_types=1);

namespace CheeasyTech\Booking;

use Carbon\Carbon;

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
        $this->status = $status;
        $this->reason = $reason;
        $this->changedAt = $changedAt ?? Carbon::now();
        $this->metadata = $metadata;
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
            'changed_at' => $this->changedAt->toIso8601String(),
            'metadata' => $this->metadata,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['status'],
            $data['reason'] ?? null,
            isset($data['changed_at']) ? Carbon::parse($data['changed_at']) : null,
            $data['metadata'] ?? null
        );
    }
} 