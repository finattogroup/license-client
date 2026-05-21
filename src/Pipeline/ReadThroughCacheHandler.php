<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Pipeline;

use Finatto\LicenseClient\Data\LicenseSnapshot;
use Finatto\LicenseClient\Support\CacheKeys;
use Illuminate\Contracts\Cache\Repository as Cache;

final class ReadThroughCacheHandler extends AbstractHandler
{
    /**
     * @param  array{snapshot_ttl: int}  $config
     */
    public function __construct(
        private readonly Cache $cache,
        private readonly CacheKeys $keys,
        private readonly array $config,
    ) {}

    public function handle(LicenseRequest $request): LicenseSnapshot
    {
        $ttl = $this->config['snapshot_ttl'];

        if ($ttl <= 0) {
            return parent::handle($request);
        }

        $key = $this->keys->snapshot($request->credentials);

        if (! $request->bypassCache) {
            $cached = $this->cache->get($key);

            if ($cached instanceof LicenseSnapshot) {
                return $cached;
            }
        }

        $snapshot = parent::handle($request);

        $this->cache->put($key, $snapshot, $ttl);

        return $snapshot;
    }
}
