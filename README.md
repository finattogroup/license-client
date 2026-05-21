# finatto/license-client

Cliente Laravel para a **Finatto License Manager**. Recebe as credenciais de
cada tenant em tempo de execução e expõe uma API tipada, escondendo HTTP,
tokens e cache da aplicação consumidora.

```php
use Finatto\LicenseClient\Facades\License;

$license = License::for($tenant->serial_key, $tenant->cnpj);

if (! $license->hasModule('fleet')) {
    abort(403);
}
```

> As credenciais pertencem à aplicação (uma por tenant, persistidas onde o
> cliente decidir) — o pacote apenas as transporta.

## Instalação

```bash
composer require finatto/license-client
php artisan vendor:publish --tag=license-client-config
```

Auto-discovery registra o provider e a Facade `License`.

### Configuração (`.env`)

```env
LICENSE_MANAGER_URL=https://license.test

# opcionais
LICENSE_HTTP_TIMEOUT=10
LICENSE_HTTP_RETRY_TIMES=2
LICENSE_TOKEN_LEEWAY=60
LICENSE_SNAPSHOT_TTL=300
LICENSE_CACHE_STORE=
```

## Uso

### Por tenant

```php
use Finatto\LicenseClient\Facades\License;

$license = License::for($tenant->serial_key, $tenant->cnpj);

$license->snapshot();             // cacheado por LICENSE_SNAPSHOT_TTL
$license->fresh();                // ignora o cache
$license->isActive();
$license->hasModule('checklist');
$license->token();
$license->flush();

$snapshot = $license->snapshot();
$snapshot->tenant->legalName;
$snapshot->subscription?->plan?->maxVehicles;
$snapshot->moduleCodes();
```

### Tenant atual via resolver

```php
use Finatto\LicenseClient\Data\LicenseCredentials;
use Finatto\LicenseClient\Facades\License;

License::resolveUsing(fn () => LicenseCredentials::make(
    serialKey: CurrentTenant::get()->serial_key,
    document:  CurrentTenant::get()->cnpj,
));

License::hasModule('fleet');
License::snapshot();
```

## Erros

Todas as exceções estendem `LicenseClientException`:

| Exceção | |
|---|---|
| `InvalidSerialKeyException` | serial key ausente ou inválida |
| `AuthenticationException` | falha ao autenticar — expõe `->oauthError` e `->status` |
| `LicenseRequestException` | falha ao consultar a licença — expõe `->status` |

## Análise estática

```bash
composer install
vendor/bin/phpstan analyse
```

## Licença

Proprietário
