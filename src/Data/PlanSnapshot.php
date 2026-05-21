<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Data;

final readonly class PlanSnapshot
{
    public function __construct(
        public string $code,
        public string $name,
        public ?int $maxUsers,
        public ?int $maxVehicles,
    ) {}

    /**
     * @param  array{code: string, name: string, max_users: int|null, max_vehicles: int|null}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            name: $data['name'],
            maxUsers: $data['max_users'] ?? null,
            maxVehicles: $data['max_vehicles'] ?? null,
        );
    }
}
