<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Data;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class LicenseSnapshot
{
    /**
     * @param array<string, mixed> $entitlements
     * @param array<string, mixed> $limits
     * @param list<string> $flags
     * @param array<string, mixed> $settings
     */
    public function __construct(
        public string $serial,
        public string $type,
        public string $plan,
        public string $status,
        public string $environment,
        public int $graceDays,
        public bool $trial,
        public ?CarbonImmutable $expiresAt,
        public LicenseTenant $licenseTenant,
        public array $entitlements,
        public array $limits,
        public array $flags,
        public array $settings,
        public string $issuer,
        public string $audience,
        public ?CarbonImmutable $issuedAt,
        public ?CarbonImmutable $tokenExpiresAt,
        public int $schema,
        public string $rawToken = '',
    ) {}

    /** @param array<string, mixed> $claims */
    public static function fromClaims(array $claims, string $rawToken = ''): self
    {
        $license = $claims['license'] ?? null;
        if (! is_array($license)) {
            throw new InvalidArgumentException('The signed token does not contain a license object.');
        }

        $serial = self::requiredString($license, 'serial');
        $type = self::requiredString($license, 'type');
        if (($claims['sub'] ?? null) !== $serial || ($claims['aud'] ?? null) !== $type) {
            throw new InvalidArgumentException('The signed token subject or audience does not match its license.');
        }
        if (($claims['schema'] ?? null) !== 1) {
            throw new InvalidArgumentException('Unsupported license token schema.');
        }

        $tenant = $license['tenant'] ?? [];
        if (! is_array($tenant)) {
            throw new InvalidArgumentException('The signed license tenant is invalid.');
        }

        return new self(
            serial: $serial,
            type: $type,
            plan: self::requiredString($license, 'plan'),
            status: self::requiredString($license, 'status'),
            environment: self::requiredString($license, 'environment'),
            graceDays: max(0, (int) ($license['grace_days'] ?? 0)),
            trial: ($license['is_trial'] ?? false) === true,
            expiresAt: self::date($license['expires_at'] ?? null),
            licenseTenant: LicenseTenant::fromArray($tenant),
            entitlements: self::map($license['entitlements'] ?? []),
            limits: self::map($license['limits'] ?? []),
            flags: array_values(array_filter($license['flags'] ?? [], 'is_string')),
            settings: self::map($license['settings'] ?? []),
            issuer: is_string($claims['iss'] ?? null) ? $claims['iss'] : '',
            audience: $type,
            issuedAt: self::date($claims['iat'] ?? null),
            tokenExpiresAt: self::date($claims['exp'] ?? null),
            schema: 1,
            rawToken: $rawToken,
        );
    }

    public function product(): string { return $this->type; }
    public function productKey(): string { return $this->type; }
    public function licenseKey(): string { return $this->serial; }
    public function plan(): string { return $this->plan; }
    public function status(): string { return $this->status; }
    public function environment(): string { return $this->environment; }
    public function isActive(): bool { return $this->status === 'active'; }
    public function isSuspended(): bool { return $this->status === 'suspended'; }
    public function isCancelled(): bool { return in_array($this->status, ['cancelled', 'revoked'], true); }
    public function isExpired(): bool { return $this->status === 'expired' || ($this->expiresAt?->isPast() ?? false); }
    public function isTrial(): bool { return $this->trial; }
    public function tenant(): LicenseTenant { return $this->licenseTenant; }

    public function graceEndsAt(): ?CarbonImmutable
    {
        return $this->expiresAt?->addDays($this->graceDays);
    }

    public function inGracePeriod(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt->isPast() && ($this->graceEndsAt()?->isFuture() ?? false);
    }

    public function hasEntitlement(string $key): bool
    {
        $value = $this->entitlements[$key] ?? false;
        return is_array($value) ? ($value['enabled'] ?? false) === true : $value === true;
    }

    public function entitlement(string $key, mixed $default = null): mixed { return $this->entitlements[$key] ?? $default; }
    public function limit(string $key, mixed $default = null): mixed { return $this->limits[$key] ?? $default; }
    public function integerLimit(string $key, ?int $default = null): ?int { $v = $this->limits[$key] ?? null; return is_int($v) ? $v : $default; }
    public function booleanLimit(string $key, bool $default = false): bool { $v = $this->limits[$key] ?? null; return is_bool($v) ? $v : $default; }

    public function allowsUsage(string $key, int $current, int $increment = 1): bool
    {
        $limit = $this->integerLimit($key);
        return $limit !== null && $current >= 0 && $increment >= 0 && $current + $increment <= $limit;
    }

    public function hasFeature(string $key): bool { return in_array($key, $this->flags, true); }
    public function hasCanaryFeature(string $key): bool { return $this->hasFeature($key); }
    /** @param list<string> $keys */ public function hasAnyFeature(array $keys): bool { foreach ($keys as $key) if ($this->hasFeature($key)) return true; return false; }
    /** @param list<string> $keys */ public function hasAllFeatures(array $keys): bool { foreach ($keys as $key) if (! $this->hasFeature($key)) return false; return true; }
    /** @return list<string> */ public function features(): array { return $this->flags; }
    /** @return list<string> */ public function canaryFeatures(): array { return $this->flags; }
    /** @return array<string, mixed> */ public function productData(): array { return ['key' => $this->type, 'plan' => $this->plan, 'status' => $this->status, 'environment' => $this->environment, 'is_trial' => $this->trial]; }
    public function setting(string $key, mixed $default = null): mixed { return $this->settings[$key] ?? $default; }

    /** @param array<string, mixed> $data */
    private static function requiredString(array $data, string $key): string
    {
        $value = $data[$key] ?? null;
        if (! is_string($value) || trim($value) === '') throw new InvalidArgumentException("Missing signed license field: {$key}.");
        return $value;
    }
    private static function date(mixed $value): ?CarbonImmutable { return is_string($value) && $value !== '' ? CarbonImmutable::parse($value) : null; }
    /** @return array<string, mixed> */ private static function map(mixed $value): array { return is_array($value) ? $value : []; }
}
