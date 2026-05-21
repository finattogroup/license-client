<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Support;

use Finatto\LicenseClient\Data\LicenseCredentials;

final readonly class CacheKeys
{
    public function __construct(private string $prefix) {}

    public function token(LicenseCredentials $credentials): string
    {
        return "{$this->prefix}:token:{$credentials->clientId()}";
    }

    public function snapshot(LicenseCredentials $credentials): string
    {
        return "{$this->prefix}:snapshot:{$credentials->clientId()}";
    }
}
