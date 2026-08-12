<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Tests\Unit;

use Finatto\LicenseClient\Data\LicenseSnapshot;
use PHPUnit\Framework\TestCase;

final class LicenseSnapshotTest extends TestCase
{
    public function test_it_exposes_product_entitlements_limits_and_canary_features(): void
    {
        $snapshot = LicenseSnapshot::fromClaims([
            'iss' => 'finatto-key-service', 'sub' => 'LIC-123', 'aud' => 'frota',
            'iat' => '2026-08-12T10:00:00Z', 'exp' => '2026-08-12T11:00:00Z', 'schema' => 1,
            'license' => [
                'serial' => 'LIC-123', 'type' => 'frota', 'plan' => 'enterprise',
                'status' => 'active', 'environment' => 'production', 'grace_days' => 7,
                'is_trial' => false, 'expires_at' => '2027-01-01T00:00:00Z',
                'tenant' => ['id' => 't1', 'slug' => 'acme', 'legal_name' => 'Acme Ltda', 'status' => 'active'],
                'entitlements' => ['tracking' => ['enabled' => true], 'reports' => ['enabled' => false]],
                'limits' => ['concurrent-users' => 50, 'tracking-requests' => 3000],
                'flags' => ['new-dashboard', 'route-canary'],
                'settings' => ['system_url' => 'https://frota.example.com'],
            ],
        ]);

        self::assertSame('frota', $snapshot->product());
        self::assertSame('enterprise', $snapshot->plan());
        self::assertTrue($snapshot->isActive());
        self::assertTrue($snapshot->hasEntitlement('tracking'));
        self::assertFalse($snapshot->hasEntitlement('reports'));
        self::assertFalse($snapshot->hasEntitlement('missing'));
        self::assertSame(50, $snapshot->integerLimit('concurrent-users'));
        self::assertNull($snapshot->integerLimit('missing'));
        self::assertTrue($snapshot->allowsUsage('concurrent-users', 49));
        self::assertFalse($snapshot->allowsUsage('concurrent-users', 50));
        self::assertTrue($snapshot->hasFeature('route-canary'));
        self::assertTrue($snapshot->hasAnyFeature(['missing', 'route-canary']));
        self::assertTrue($snapshot->hasAllFeatures(['new-dashboard', 'route-canary']));
        self::assertSame(['new-dashboard', 'route-canary'], $snapshot->canaryFeatures());
        self::assertSame('frota', $snapshot->productData()['key']);
        self::assertSame('acme', $snapshot->tenant()->slug);
        self::assertSame('https://frota.example.com', $snapshot->setting('system_url'));
    }

    public function test_malformed_or_cross_license_claims_are_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LicenseSnapshot::fromClaims([
            'sub' => 'LIC-A', 'aud' => 'frota', 'schema' => 1,
            'license' => ['serial' => 'LIC-B', 'type' => 'frota', 'status' => 'active'],
        ]);
    }
}
