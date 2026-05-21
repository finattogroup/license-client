<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Data;

use Carbon\CarbonImmutable;

final readonly class SubscriptionSnapshot
{
    public function __construct(
        public string $uuid,
        public string $kind,
        public string $status,
        public string $billingCycle,
        public ?PlanSnapshot $plan,
        public ?CarbonImmutable $currentPeriodStart,
        public ?CarbonImmutable $currentPeriodEnd,
        public ?CarbonImmutable $trialEndsAt,
    ) {}

    /**
     * @param  array{
     *     uuid: string,
     *     kind: string,
     *     status: string,
     *     billing_cycle: string,
     *     plan: array{code: string, name: string, max_users: int|null, max_vehicles: int|null}|null,
     *     current_period_start: string|null,
     *     current_period_end: string|null,
     *     trial_ends_at: string|null
     * }  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            uuid: $data['uuid'],
            kind: $data['kind'],
            status: $data['status'],
            billingCycle: $data['billing_cycle'],
            plan: isset($data['plan']) ? PlanSnapshot::fromArray($data['plan']) : null,
            currentPeriodStart: self::date($data['current_period_start'] ?? null),
            currentPeriodEnd: self::date($data['current_period_end'] ?? null),
            trialEndsAt: self::date($data['trial_ends_at'] ?? null),
        );
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing'], true);
    }

    private static function date(?string $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::parse($value);
    }
}
