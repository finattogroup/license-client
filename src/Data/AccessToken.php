<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Data;

use Carbon\CarbonImmutable;

final readonly class AccessToken
{
    public function __construct(
        public string $accessToken,
        public string $tokenType,
        public int $expiresIn,
        public ?string $scope,
        public CarbonImmutable $obtainedAt,
    ) {}

    /**
     * @param  array{access_token: string, token_type?: string, expires_in?: int, scope?: string|null}  $payload
     */
    public static function fromResponse(array $payload): self
    {
        return new self(
            accessToken: $payload['access_token'],
            tokenType: $payload['token_type'] ?? 'Bearer',
            expiresIn: (int) ($payload['expires_in'] ?? 0),
            scope: $payload['scope'] ?? null,
            obtainedAt: CarbonImmutable::now(),
        );
    }

    public function expiresAt(): CarbonImmutable
    {
        return $this->obtainedAt->addSeconds($this->expiresIn);
    }

    public function secondsUntilExpiry(int $leeway = 0): int
    {
        $deadline = $this->expiresAt()->subSeconds($leeway);

        return (int) max(0, CarbonImmutable::now()->diffInSeconds($deadline, false));
    }

    public function authorizationHeader(): string
    {
        return "{$this->tokenType} {$this->accessToken}";
    }
}
