<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Tests\Unit;

use Finatto\LicenseClient\Storage\FileCredentialStore;
use PHPUnit\Framework\TestCase;

final class FileCredentialStoreTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/finatto-license-test-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.'/*') ?: [] as $file) @unlink($file);
        @rmdir($this->directory);
    }

    public function test_it_persists_credentials_with_restricted_permissions_and_indexes_keys(): void
    {
        $store = new FileCredentialStore($this->directory);
        $saved = $store->save('LIC-123', 'private', 'certificate', 'chain', [[
            'kid' => 'key-1', 'alg' => 'paseto-v4-public', 'public_key' => base64_encode(random_bytes(32)), 'expires_at' => null,
        ]]);

        self::assertSame('LIC-123', $saved->serial);
        self::assertArrayHasKey('key-1', $saved->keys);
        self::assertSame('0600', substr(sprintf('%o', fileperms($saved->privateKeyPath)), -4));
        self::assertStringNotContainsString('private', (string) file_get_contents($this->directory.'/credentials.json'));
    }
}
