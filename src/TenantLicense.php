<?php

declare(strict_types=1);

namespace Finatto\LicenseClient;

use Finatto\LicenseClient\Data\AccessToken;
use Finatto\LicenseClient\Data\LicenseCredentials;
use Finatto\LicenseClient\Data\LicenseSnapshot;
use Finatto\LicenseClient\Pipeline\Handler;
use Finatto\LicenseClient\Pipeline\LicenseRequest;
use Finatto\LicenseClient\Support\CacheKeys;
use Illuminate\Contracts\Cache\Repository as Cache;

final readonly class TenantLicense
{
    public function __construct(
        private Handler            $chain,
        private TokenManager       $tokens,
        private Cache              $cache,
        private CacheKeys          $keys,
        private LicenseCredentials $credentials,
    ) {}

    public function snapshot(): LicenseSnapshot
    {
        return $this->chain->handle(new LicenseRequest($this->credentials));
    }

    public function fresh(): LicenseSnapshot
    {
        return $this->chain->handle(new LicenseRequest($this->credentials, bypassCache: true));
    }

    public function token(): AccessToken
    {
        return $this->tokens->token($this->credentials);
    }

    public function isActive(): bool
    {
        return $this->snapshot()->isActive();
    }

    public function hasModule(string $code): bool
    {
        return $this->snapshot()->hasModule($code);
    }

    public function flush(): void
    {
        $this->tokens->forget($this->credentials);
        $this->cache->forget($this->keys->snapshot($this->credentials));
    }
}
