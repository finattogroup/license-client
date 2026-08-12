<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Data;

final readonly class LicenseTenant
{
    public function __construct(public string $id, public string $slug, public string $legalName, public ?string $tradeName, public string $status) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: is_string($data['id'] ?? null) ? $data['id'] : '',
            slug: is_string($data['slug'] ?? null) ? $data['slug'] : '',
            legalName: is_string($data['legal_name'] ?? null) ? $data['legal_name'] : '',
            tradeName: is_string($data['trade_name'] ?? null) ? $data['trade_name'] : null,
            status: is_string($data['status'] ?? null) ? $data['status'] : '',
        );
    }
}
