<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Http;

use Finatto\LicenseClient\Data\ClientCredentials;
use Finatto\LicenseClient\Exceptions\ActivationException;
use Finatto\LicenseClient\Exceptions\LicenseRequestException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;

final class LicenseApiClient
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly Factory $http, private readonly array $config) {}

    /** @return array<string, mixed> */
    public function activate(string $voucher, string $csr): array
    {
        try {
            $response = $this->baseRequest()->post($this->activationUrl('/v1/activations'), ['voucher' => $voucher, 'csr' => $csr]);
        } catch (\Throwable $e) {
            throw new ActivationException('The key service could not be reached for activation.', 'service_unavailable', previous: $e);
        }
        if (! $response->created()) {
            $code = $response->json('error', 'activation_failed');
            $reason = is_string($code) && $code !== '' ? $code : 'activation_failed';
            throw new ActivationException("Activation was rejected ({$reason}).", $reason, $response->status());
        }
        $data = $response->json();
        if (! is_array($data)) throw new ActivationException('The activation response is invalid.');
        foreach (['serial', 'certificate', 'ca_chain', 'keys'] as $field) if (! isset($data[$field])) throw new ActivationException("The activation response is missing {$field}.");
        return $data;
    }

    /** @return array<string, mixed> */
    public function license(ClientCredentials $credentials): array
    {
        try {
            $response = $this->baseRequest()->withOptions([
                'cert' => $credentials->certificatePath,
                'ssl_key' => $credentials->privateKeyPath,
            ])->get($this->licenseUrl('/v1/license'));
        } catch (\Throwable $e) {
            throw new LicenseRequestException('The key service could not be reached.', previous: $e);
        }
        if (! $response->successful()) throw new LicenseRequestException("The key service rejected the license request ({$response->status()}).", $response->status());
        $data = $response->json();
        if (! is_array($data) || ! is_string($data['license_token'] ?? null)) throw new LicenseRequestException('The key service returned an invalid license response.');
        return $data;
    }

    /** @return list<array<string, mixed>> */
    public function keys(): array
    {
        $response = $this->baseRequest()->get($this->activationUrl('/v1/keys'));
        if (! $response->successful() || ! is_array($response->json('keys'))) throw new LicenseRequestException('Could not refresh license verification keys.');
        return array_values(array_filter($response->json('keys'), 'is_array'));
    }

    private function baseRequest(): PendingRequest
    {
        return $this->http->acceptJson()->asJson()->timeout((int) ($this->config['timeout'] ?? 10))->connectTimeout((int) ($this->config['connect_timeout'] ?? 5));
    }
    private function activationUrl(string $path): string { return rtrim((string) $this->config['base_url'], '/').$path; }
    private function licenseUrl(string $path): string { return rtrim((string) ($this->config['license_url'] ?? $this->config['base_url']), '/').$path; }
}
