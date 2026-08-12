# Finatto License Client

Laravel package used by Finatto products to activate and consume licenses. It
talks only to the key-service. The private key is generated locally, stored with
restricted filesystem permissions, and never sent over the network.

## Install

```bash
composer require finatto/license-client
php artisan vendor:publish --tag=license-client-config
```

The production key-service URLs are the defaults. They can be overridden when
needed:

```dotenv
FINATTO_KEY_SERVICE_URL=https://key-service.memphislab.com.br
FINATTO_KEY_SERVICE_LICENSE_URL=https://key-service.memphislab.com.br:8443
```

## Activate once

The license-manager provisions `FINATTO_LICENSE_ACTIVATION_VOUCHER`. The client
uses only that voucher; the key-service resolves and returns the license serial.

```php
use Finatto\LicenseClient\Facades\License;

$activation = License::activate(config('services.finatto.activation_voucher'));
$activation->serial;
```

After activation, the certificate, private key and pinned PASETO public keys are
kept in `storage/app/finatto-license`. Persist this directory across deployments
and never commit or expose it.

## Use the license

```php
License::isActivated();
License::serial();
License::isActive();
License::product();                 // "frota"
License::productData();             // key, plan, status, environment, is_trial
License::licenseKey();              // activation serial
License::plan();                    // "enterprise"
License::status();
License::isTrial();
License::inGracePeriod();

License::hasEntitlement('tracking');
License::entitlement('tracking');
License::integerLimit('concurrent-users');
License::allowsUsage('concurrent-users', $activeUsers);

License::hasFeature('new-dashboard');
License::hasCanaryFeature('route-canary');
License::hasAnyFeature(['route-canary', 'beta-map']);
License::hasAllFeatures(['new-dashboard', 'route-canary']);
License::canaryFeatures();

License::tenant();
License::setting('system_url');
```

`snapshot()` returns the cached, verified license. `fresh()` forces a network
refresh. Both throw on activation, network, signature or license errors. For
non-critical UI paths, `trySnapshot()` returns `null` on failure. Authorization
checks should use the strict methods and fail closed.

```php
$license = License::snapshot();

$license->entitlements;
$license->limits;
$license->features();
$license->expiresAt;
$license->graceEndsAt();
```

Use `License::deactivate()` only when intentionally removing the local client
certificate and private key.
