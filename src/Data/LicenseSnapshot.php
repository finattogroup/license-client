<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Data;

final readonly class LicenseSnapshot
{
    /**
     * @param  list<ModuleSnapshot>  $modules
     */
    public function __construct(
        public TenantSnapshot $tenant,
        public ?SubscriptionSnapshot $subscription,
        public array $modules,
        public ?string $issuedAt = null,
        public ?int $cacheTtl = null,
    ) {}

    /**
     * @param  array{
     *     data: array{tenant: array<string, mixed>, subscription?: array<string, mixed>|null, modules?: list<array<string, mixed>>},
     *     meta?: array{issued_at?: string, cache_ttl?: int}
     * }  $payload
     */
    public static function fromResponse(array $payload): self
    {
        $data = $payload['data'];

        return new self(
            tenant: TenantSnapshot::fromArray($data['tenant']),
            subscription: isset($data['subscription'])
                ? SubscriptionSnapshot::fromArray($data['subscription'])
                : null,
            modules: array_map(
                static fn (array $module): ModuleSnapshot => ModuleSnapshot::fromArray($module),
                $data['modules'] ?? [],
            ),
            issuedAt: $payload['meta']['issued_at'] ?? null,
            cacheTtl: $payload['meta']['cache_ttl'] ?? null,
        );
    }

    public function isActive(): bool
    {
        return $this->tenant->isActive()
            && $this->subscription instanceof SubscriptionSnapshot
            && $this->subscription->isActive();
    }

    public function hasModule(string $code): bool
    {
        foreach ($this->modules as $module) {
            if ($module->code === $code) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function moduleCodes(): array
    {
        return array_map(static fn (ModuleSnapshot $m): string => $m->code, $this->modules);
    }
}
