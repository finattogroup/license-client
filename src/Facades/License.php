<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Facades;

use Finatto\LicenseClient\Data\ActivationResult;
use Finatto\LicenseClient\Data\LicenseSnapshot;
use Finatto\LicenseClient\Data\LicenseTenant;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ActivationResult activate(string $voucher)
 * @method static bool isActivated()
 * @method static string|null serial()
 * @method static void deactivate()
 * @method static LicenseSnapshot snapshot()
 * @method static LicenseSnapshot fresh()
 * @method static LicenseSnapshot|null trySnapshot()
 * @method static void forget()
 * @method static string rawToken()
 * @method static bool isActive()
 * @method static string product()
 * @method static string productKey()
 * @method static string licenseKey()
 * @method static array<string, mixed> productData()
 * @method static string plan()
 * @method static string status()
 * @method static LicenseTenant tenant()
 * @method static bool isTrial()
 * @method static bool isExpired()
 * @method static bool inGracePeriod()
 * @method static bool hasEntitlement(string $key)
 * @method static mixed entitlement(string $key, mixed $default = null)
 * @method static mixed limit(string $key, mixed $default = null)
 * @method static int|null integerLimit(string $key, ?int $default = null)
 * @method static bool allowsUsage(string $key, int $current, int $increment = 1)
 * @method static bool hasFeature(string $key)
 * @method static bool hasCanaryFeature(string $key)
 * @method static bool hasAnyFeature(list<string> $keys)
 * @method static bool hasAllFeatures(list<string> $keys)
 * @method static list<string> features()
 * @method static list<string> canaryFeatures()
 * @method static mixed setting(string $key, mixed $default = null)
 */
final class License extends Facade
{
    protected static function getFacadeAccessor(): string { return 'license-client'; }
}
