<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Data;

final readonly class TenantSnapshot
{
    public function __construct(
        public string $id,
        public string $slug,
        public string $legalName,
        public ?string $tradeName,
        public string $status,
    ) {}

    /**
     * @param  array{id: string, slug: string, legal_name: string, trade_name: string|null, status: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            slug: $data['slug'],
            legalName: $data['legal_name'],
            tradeName: $data['trade_name'] ?? null,
            status: $data['status'],
        );
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
