<?php

declare(strict_types=1);

namespace Finatto\LicenseClient;

use Finatto\LicenseClient\Data\AccessToken;
use Finatto\LicenseClient\Data\LicenseCredentials;
use Finatto\LicenseClient\Http\LicenseApiClient;
use Finatto\LicenseClient\Support\CacheKeys;
use Illuminate\Contracts\Cache\Repository as Cache;

final class TokenManager
{
    public function __construct(
        private readonly LicenseApiClient $api,
        private readonly Cache $cache,
        private readonly CacheKeys $keys,
        private readonly int $leeway,
    ) {}

    public function token(LicenseCredentials $credentials): AccessToken
    {
        $cached = $this->cache->get($this->keys->token($credentials));

        if ($cached instanceof AccessToken && $cached->secondsUntilExpiry($this->leeway) > 0) {
            return $cached;
        }

        return $this->issueAndStore($credentials);
    }

    public function authorizationHeader(LicenseCredentials $credentials): string
    {
        return $this->token($credentials)->authorizationHeader();
    }

    public function forget(LicenseCredentials $credentials): void
    {
        $this->cache->forget($this->keys->token($credentials));
    }

    private function issueAndStore(LicenseCredentials $credentials): AccessToken
    {
        $token = $this->api->requestToken($credentials->serialKey, $credentials->document);

        $ttl = $token->secondsUntilExpiry($this->leeway);

        if ($ttl > 0) {
            $this->cache->put($this->keys->token($credentials), $token, $ttl);
        }

        return $token;
    }
}
