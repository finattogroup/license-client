<?php

declare(strict_types=1);

namespace Finatto\LicenseClient;

use Finatto\LicenseClient\Http\LicenseApiClient;
use Finatto\LicenseClient\Pipeline\EnsureAccessTokenHandler;
use Finatto\LicenseClient\Pipeline\Handler;
use Finatto\LicenseClient\Pipeline\ParseSnapshotHandler;
use Finatto\LicenseClient\Pipeline\ReadThroughCacheHandler;
use Finatto\LicenseClient\Pipeline\RefreshTokenOnUnauthorizedHandler;
use Finatto\LicenseClient\Pipeline\RequestLicenseHandler;
use Finatto\LicenseClient\Support\CacheKeys;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;

final class LicenseClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/license-client.php', 'license-client');

        $this->app->singleton(CacheKeys::class, function (Application $app): CacheKeys {
            return new CacheKeys((string) $app['config']->get('license-client.cache.prefix'));
        });

        $this->app->singleton(LicenseApiClient::class, function (Application $app): LicenseApiClient {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('license-client');

            return new LicenseApiClient(
                http: $app->make(HttpFactory::class),
                config: [
                    'base_url' => $config['base_url'],
                    'http' => $config['http'],
                ],
            );
        });

        $this->app->singleton(TokenManager::class, function (Application $app): TokenManager {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('license-client');

            return new TokenManager(
                api: $app->make(LicenseApiClient::class),
                cache: $this->cacheStore($app, $config),
                keys: $app->make(CacheKeys::class),
                leeway: (int) $config['cache']['token_leeway'],
            );
        });

        $this->app->singleton(Handler::class, function (Application $app): Handler {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('license-client');

            $head = new ReadThroughCacheHandler(
                cache: $this->cacheStore($app, $config),
                keys: $app->make(CacheKeys::class),
                config: ['snapshot_ttl' => (int) $config['cache']['snapshot_ttl']],
            );

            $head
                ->setNext(new RefreshTokenOnUnauthorizedHandler($app->make(TokenManager::class)))
                ->setNext(new EnsureAccessTokenHandler($app->make(TokenManager::class)))
                ->setNext(new RequestLicenseHandler($app->make(LicenseApiClient::class)))
                ->setNext(new ParseSnapshotHandler());

            return $head;
        });

        $this->app->singleton(LicenseManager::class, function (Application $app): LicenseManager {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('license-client');

            return new LicenseManager(
                chain: $app->make(Handler::class),
                tokens: $app->make(TokenManager::class),
                cache: $this->cacheStore($app, $config),
                keys: $app->make(CacheKeys::class),
            );
        });

        $this->app->alias(LicenseManager::class, 'license-client');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/license-client.php' => config_path('license-client.php'),
            ], 'license-client-config');
        }
    }

    /**
     * @param  array{cache: array{store: string|null}}  $config
     */
    private function cacheStore(Application $app, array $config): Cache
    {
        return $app->make(CacheFactory::class)->store($config['cache']['store']);
    }
}
