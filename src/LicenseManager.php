<?php

declare(strict_types=1);

namespace Finatto\LicenseClient;

use Finatto\LicenseClient\Data\ActivationResult;
use Finatto\LicenseClient\Data\LicenseSnapshot;
use Finatto\LicenseClient\Data\LicenseTenant;
use Finatto\LicenseClient\Http\LicenseApiClient;
use Finatto\LicenseClient\Security\KeyPairGenerator;
use Finatto\LicenseClient\Security\PasetoVerifier;
use Finatto\LicenseClient\Security\UnknownKeyException;
use Finatto\LicenseClient\Storage\CredentialStore;
use Illuminate\Contracts\Cache\Repository as Cache;

final class LicenseManager
{
    public function __construct(
        private readonly LicenseApiClient $api,
        private readonly CredentialStore $credentials,
        private readonly KeyPairGenerator $keyPairs,
        private readonly PasetoVerifier $verifier,
        private readonly Cache $cache,
        private readonly string $cacheKey,
        private readonly int $defaultTtl,
        private readonly ?string $issuer,
    ) {}

    public function activate(string $voucher): ActivationResult
    {
        if (trim($voucher) === '') throw new \InvalidArgumentException('The activation voucher cannot be empty.');
        $pair = $this->keyPairs->generate();
        $response = $this->api->activate($voucher, $pair->csr);
        $stored = $this->credentials->save(
            serial: (string) $response['serial'], privateKey: $pair->privateKey,
            certificate: (string) $response['certificate'], caChain: (string) $response['ca_chain'],
            keys: is_array($response['keys']) ? array_values(array_filter($response['keys'], 'is_array')) : [],
        );
        $this->forget();
        return new ActivationResult($stored->serial, $stored->certificatePath, $stored->privateKeyPath);
    }

    public function isActivated(): bool { return $this->credentials->exists(); }
    public function serial(): ?string { return $this->isActivated() ? $this->credentials->load()->serial : null; }

    public function deactivate(): void
    {
        $this->forget();
        $this->credentials->delete();
    }

    public function snapshot(): LicenseSnapshot
    {
        $cached = $this->cache->get($this->cacheKey);
        return $cached instanceof LicenseSnapshot ? $cached : $this->fresh();
    }

    public function fresh(): LicenseSnapshot
    {
        $credentials = $this->credentials->load();
        $response = $this->api->license($credentials);
        $token = (string) $response['license_token'];
        try {
            $claims = $this->verifier->verify($token, $credentials->keys, $this->issuer);
        } catch (UnknownKeyException) {
            $credentials = $this->credentials->saveKeys($this->api->keys());
            $claims = $this->verifier->verify($token, $credentials->keys, $this->issuer);
        }
        $snapshot = LicenseSnapshot::fromClaims($claims, $token);
        if ($snapshot->serial !== $credentials->serial) throw new \UnexpectedValueException('The signed license does not belong to this activation.');
        $ttl = max(1, (int) ($response['meta']['cache_ttl'] ?? $this->defaultTtl));
        $this->cache->put($this->cacheKey, $snapshot, $ttl);
        return $snapshot;
    }

    public function trySnapshot(): ?LicenseSnapshot { try { return $this->snapshot(); } catch (\Throwable) { return null; } }
    public function forget(): void { $this->cache->forget($this->cacheKey); }
    public function rawToken(): string { return $this->snapshot()->rawToken; }
    public function isActive(): bool { return $this->snapshot()->isActive(); }
    public function product(): string { return $this->snapshot()->product(); }
    public function productKey(): string { return $this->snapshot()->productKey(); }
    public function licenseKey(): string { return $this->snapshot()->licenseKey(); }
    /** @return array<string, mixed> */ public function productData(): array { return $this->snapshot()->productData(); }
    public function plan(): string { return $this->snapshot()->plan(); }
    public function status(): string { return $this->snapshot()->status(); }
    public function tenant(): LicenseTenant { return $this->snapshot()->tenant(); }
    public function isTrial(): bool { return $this->snapshot()->isTrial(); }
    public function isExpired(): bool { return $this->snapshot()->isExpired(); }
    public function inGracePeriod(): bool { return $this->snapshot()->inGracePeriod(); }
    public function hasEntitlement(string $key): bool { return $this->snapshot()->hasEntitlement($key); }
    public function entitlement(string $key, mixed $default = null): mixed { return $this->snapshot()->entitlement($key, $default); }
    public function limit(string $key, mixed $default = null): mixed { return $this->snapshot()->limit($key, $default); }
    public function integerLimit(string $key, ?int $default = null): ?int { return $this->snapshot()->integerLimit($key, $default); }
    public function allowsUsage(string $key, int $current, int $increment = 1): bool { return $this->snapshot()->allowsUsage($key, $current, $increment); }
    public function hasFeature(string $key): bool { return $this->snapshot()->hasFeature($key); }
    public function hasCanaryFeature(string $key): bool { return $this->snapshot()->hasCanaryFeature($key); }
    /** @param list<string> $keys */ public function hasAnyFeature(array $keys): bool { return $this->snapshot()->hasAnyFeature($keys); }
    /** @param list<string> $keys */ public function hasAllFeatures(array $keys): bool { return $this->snapshot()->hasAllFeatures($keys); }
    /** @return list<string> */ public function features(): array { return $this->snapshot()->features(); }
    /** @return list<string> */ public function canaryFeatures(): array { return $this->snapshot()->canaryFeatures(); }
    public function setting(string $key, mixed $default = null): mixed { return $this->snapshot()->setting($key, $default); }
}
