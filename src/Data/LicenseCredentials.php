<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Data;

use Finatto\LicenseClient\Support\SerialKey;

final readonly class LicenseCredentials
{
    public function __construct(
        public SerialKey $serialKey,
        public string $document,
    ) {}

    public static function make(string $serialKey, string $document): self
    {
        return new self(SerialKey::parse($serialKey), $document);
    }

    public function clientId(): string
    {
        return $this->serialKey->clientId;
    }
}
