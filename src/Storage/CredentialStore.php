<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Storage;

use Finatto\LicenseClient\Data\ClientCredentials;

interface CredentialStore
{
    public function exists(): bool;
    public function load(): ClientCredentials;
    /** @param list<array<string, mixed>> $keys */
    public function save(string $serial, string $privateKey, string $certificate, string $caChain, array $keys): ClientCredentials;
    /** @param list<array<string, mixed>> $keys */
    public function saveKeys(array $keys): ClientCredentials;
    public function delete(): void;
}
