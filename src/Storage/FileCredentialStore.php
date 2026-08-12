<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Storage;

use Finatto\LicenseClient\Data\ClientCredentials;
use Finatto\LicenseClient\Exceptions\NotActivatedException;
use RuntimeException;

final class FileCredentialStore implements CredentialStore
{
    public function __construct(private readonly string $directory) {}

    public function exists(): bool { return is_file($this->path('credentials.json')) && is_file($this->path('client.key')) && is_file($this->path('client.crt')); }

    public function load(): ClientCredentials
    {
        if (! $this->exists()) throw new NotActivatedException('This application has not been activated.');
        $data = json_decode((string) file_get_contents($this->path('credentials.json')), true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($data) || ! is_string($data['serial'] ?? null) || ! is_array($data['keys'] ?? null)) {
            throw new RuntimeException('Stored license credentials are invalid.');
        }
        $keys = [];
        foreach ($data['keys'] as $key) if (is_array($key) && is_string($key['kid'] ?? null)) $keys[$key['kid']] = $key;
        return new ClientCredentials($data['serial'], $this->path('client.crt'), $this->path('client.key'), $keys);
    }

    public function save(string $serial, string $privateKey, string $certificate, string $caChain, array $keys): ClientCredentials
    {
        $this->ensureDirectory();
        $this->atomicWrite('client.key', $privateKey, 0600);
        $this->atomicWrite('client.crt', rtrim($certificate)."\n".ltrim($caChain), 0600);
        $this->atomicWrite('credentials.json', json_encode(['serial' => $serial, 'keys' => $keys], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT), 0600);
        return $this->load();
    }

    public function saveKeys(array $keys): ClientCredentials
    {
        $current = $this->load();
        $this->atomicWrite('credentials.json', json_encode(['serial' => $current->serial, 'keys' => $keys], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT), 0600);
        return $this->load();
    }

    public function delete(): void
    {
        foreach (['credentials.json', 'client.crt', 'client.key'] as $file) if (is_file($this->path($file)) && ! unlink($this->path($file))) throw new RuntimeException("Could not delete {$file}.");
    }

    private function ensureDirectory(): void
    {
        if (! is_dir($this->directory) && ! mkdir($this->directory, 0700, true) && ! is_dir($this->directory)) throw new RuntimeException('Could not create the license credential directory.');
        chmod($this->directory, 0700);
    }
    private function path(string $name): string { return rtrim($this->directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$name; }
    private function atomicWrite(string $name, string $contents, int $mode): void
    {
        $tmp = $this->path('.'.$name.'.'.bin2hex(random_bytes(6)));
        if (file_put_contents($tmp, $contents, LOCK_EX) === false) throw new RuntimeException("Could not write {$name}.");
        chmod($tmp, $mode);
        if (! rename($tmp, $this->path($name))) { @unlink($tmp); throw new RuntimeException("Could not store {$name}."); }
    }
}
