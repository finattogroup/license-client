<?php

declare(strict_types=1);

namespace Finatto\LicenseClient;

use Closure;
use Finatto\LicenseClient\Data\AccessToken;
use Finatto\LicenseClient\Data\LicenseCredentials;
use Finatto\LicenseClient\Data\LicenseSnapshot;
use Finatto\LicenseClient\Exceptions\LicenseClientException;
use Finatto\LicenseClient\Pipeline\Handler;
use Finatto\LicenseClient\Support\CacheKeys;
use Illuminate\Contracts\Cache\Repository as Cache;

final class LicenseManager
{
    /**
     * @var (Closure(): LicenseCredentials)|null
     */
    private ?Closure $resolver = null;

    public function __construct(
        private readonly Handler $chain,
        private readonly TokenManager $tokens,
        private readonly Cache $cache,
        private readonly CacheKeys $keys,
    ) {}

    public function for(string $serialKey, string $document): TenantLicense
    {
        return $this->forCredentials(LicenseCredentials::make($serialKey, $document));
    }

    public function forCredentials(LicenseCredentials $credentials): TenantLicense
    {
        return new TenantLicense(
            chain: $this->chain,
            tokens: $this->tokens,
            cache: $this->cache,
            keys: $this->keys,
            credentials: $credentials,
        );
    }

    /**
     * @param  Closure(): LicenseCredentials  $resolver
     */
    public function resolveUsing(Closure $resolver): void
    {
        $this->resolver = $resolver;
    }

    public function current(): TenantLicense
    {
        if (! $this->resolver instanceof Closure) {
            throw new LicenseClientException(
                'No credential resolver registered. Use License::for($serialKey, $document) or License::resolveUsing(...).',
            );
        }

        return $this->forCredentials(($this->resolver)());
    }

    public function snapshot(): LicenseSnapshot
    {
        return $this->current()->snapshot();
    }

    public function fresh(): LicenseSnapshot
    {
        return $this->current()->fresh();
    }

    public function token(): AccessToken
    {
        return $this->current()->token();
    }

    public function isActive(): bool
    {
        return $this->current()->isActive();
    }

    public function hasModule(string $code): bool
    {
        return $this->current()->hasModule($code);
    }

    public function flush(): void
    {
        $this->current()->flush();
    }
}
