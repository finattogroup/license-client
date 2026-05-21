<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Http;

use Finatto\LicenseClient\Data\AccessToken;
use Finatto\LicenseClient\Exceptions\AuthenticationException;
use Finatto\LicenseClient\Exceptions\LicenseRequestException;
use Finatto\LicenseClient\Support\SerialKey;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

final readonly class LicenseApiClient
{
    /**
     * @param  array{base_url: string, http: array{timeout: int, retry_times: int, retry_sleep: int}}  $config
     */
    public function __construct(
        private HttpFactory $http,
        private array       $config,
    ) {}

    public function requestToken(SerialKey $serialKey, string $document): AccessToken
    {
        $response = $this->send(fn (PendingRequest $request): Response => $request->post('/api/v1/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $serialKey->clientId,
            'client_secret' => $serialKey->clientSecret,
            'document' => $document,
        ]));

        if ($response->failed()) {
            throw AuthenticationException::fromResponse(
                status: $response->status(),
                error: $response->json('error'),
                description: $response->json('error_description'),
            );
        }

        /** @var array{access_token: string, token_type?: string, expires_in?: int, scope?: string|null} $payload */
        $payload = $response->json();

        return AccessToken::fromResponse($payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchLicense(string $authorizationHeader): array
    {
        $response = $this->send(fn (PendingRequest $request): Response => $request
            ->withHeader('Authorization', $authorizationHeader)
            ->get('/api/v1/license'));

        if ($response->failed()) {
            throw new LicenseRequestException(
                "GET /api/v1/license responded {$response->status()}.",
                $response->status(),
            );
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json();

        return $payload;
    }

    /**
     * @param  callable(PendingRequest): Response  $callback
     */
    private function send(callable $callback): Response
    {
        $request = $this->http
            ->baseUrl(rtrim($this->config['base_url'], '/'))
            ->acceptJson()
            ->asJson()
            ->timeout($this->config['http']['timeout'])
            ->retry(
                $this->config['http']['retry_times'],
                $this->config['http']['retry_sleep'],
                throw: false,
            );

        try {
            return $callback($request);
        } catch (ConnectionException $e) {
            throw new LicenseRequestException(
                "Could not reach the License Manager: {$e->getMessage()}",
            );
        }
    }
}
