<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Pipeline;

use Finatto\LicenseClient\Data\LicenseCredentials;

final class LicenseRequest
{
    public ?string $authorization = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $payload = null;

    public int $attempt = 0;

    public function __construct(
        public readonly LicenseCredentials $credentials,
        public readonly bool $bypassCache = false,
    ) {}
}
