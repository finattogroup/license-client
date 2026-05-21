<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Data;

final readonly class ModuleSnapshot
{
    public function __construct(
        public string $code,
        public string $name,
    ) {}

    /**
     * @param  array{code: string, name: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(code: $data['code'], name: $data['name']);
    }
}
