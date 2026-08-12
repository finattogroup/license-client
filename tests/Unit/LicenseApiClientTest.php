<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Tests\Unit;

use Finatto\LicenseClient\Exceptions\ActivationException;
use Finatto\LicenseClient\Http\LicenseApiClient;
use Illuminate\Http\Client\Factory;
use PHPUnit\Framework\TestCase;

final class LicenseApiClientTest extends TestCase
{
    public function test_it_preserves_the_key_service_activation_reason(): void
    {
        $http = new Factory();
        $http->fake(['*/v1/activations' => $http->response([
            'error' => 'license_inactive',
            'message' => 'license is not active',
        ], 422)]);

        $client = new LicenseApiClient($http, ['base_url' => 'https://keys.example.test']);

        try {
            $client->activate('AAAA-BBBB-CCCC-DDDD', 'csr');
            self::fail('Expected activation to be rejected.');
        } catch (ActivationException $exception) {
            self::assertSame('license_inactive', $exception->reason);
            self::assertSame(422, $exception->status);
        }
    }
}
