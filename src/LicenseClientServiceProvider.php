<?php

declare(strict_types=1);

namespace Finatto\LicenseClient;

use Finatto\LicenseClient\Http\LicenseApiClient;
use Finatto\LicenseClient\Security\KeyPairGenerator;
use Finatto\LicenseClient\Security\PasetoVerifier;
use Finatto\LicenseClient\Storage\CredentialStore;
use Finatto\LicenseClient\Storage\FileCredentialStore;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;

final class LicenseClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/license-client.php', 'license-client');
        $this->app->singleton(CredentialStore::class, fn (Application $app) => new FileCredentialStore((string) $app['config']->get('license-client.credentials.path')));
        $this->app->singleton(KeyPairGenerator::class, fn (Application $app) => new KeyPairGenerator($app['config']->get('license-client.openssl_config')));
        $this->app->singleton(PasetoVerifier::class);
        $this->app->singleton(LicenseApiClient::class, fn (Application $app) => new LicenseApiClient($app->make(HttpFactory::class), (array) $app['config']->get('license-client')));
        $this->app->singleton(LicenseManager::class, function (Application $app): LicenseManager {
            $config = (array) $app['config']->get('license-client');
            $cache = (array) ($config['cache'] ?? []);
            return new LicenseManager(
                $app->make(LicenseApiClient::class), $app->make(CredentialStore::class),
                $app->make(KeyPairGenerator::class), $app->make(PasetoVerifier::class),
                $app->make(CacheFactory::class)->store($cache['store'] ?? null),
                (string) ($cache['key'] ?? 'finatto:license:snapshot'), (int) ($cache['ttl'] ?? 300),
                isset($config['issuer']) ? (string) $config['issuer'] : null,
            );
        });
        $this->app->alias(LicenseManager::class, 'license-client');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) $this->publishes([__DIR__.'/../config/license-client.php' => config_path('license-client.php')], 'license-client-config');
    }
}
