<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Facades;

use Closure;
use Finatto\LicenseClient\Data\AccessToken;
use Finatto\LicenseClient\Data\LicenseCredentials;
use Finatto\LicenseClient\Data\LicenseSnapshot;
use Finatto\LicenseClient\LicenseManager;
use Finatto\LicenseClient\TenantLicense;
use Illuminate\Support\Facades\Facade;

/**
 * @method static TenantLicense for(string $serialKey, string $document)
 * @method static TenantLicense forCredentials(LicenseCredentials $credentials)
 * @method static void resolveUsing(Closure $resolver)
 * @method static TenantLicense current()
 * @method static LicenseSnapshot snapshot()
 * @method static LicenseSnapshot fresh()
 * @method static AccessToken token()
 * @method static bool isActive()
 * @method static bool hasModule(string $code)
 * @method static void flush()
 *
 * @see LicenseManager
 */
final class License extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'license-client';
    }
}
