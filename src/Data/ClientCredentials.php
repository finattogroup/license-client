<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Data;

final readonly class ClientCredentials
{
    /** @param array<string, array{kid:string,alg:string,public_key:string,expires_at:mixed}> $keys */
    public function __construct(public string $serial, public string $certificatePath, public string $privateKeyPath, public array $keys) {}
}
