<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Tests\Unit;

use Finatto\LicenseClient\Security\KeyPairGenerator;
use PHPUnit\Framework\TestCase;

final class KeyPairGeneratorTest extends TestCase
{
    public function test_it_generates_a_signed_ec_csr_and_keeps_the_private_key_separate(): void
    {
        $pair = (new KeyPairGenerator())->generate();
        self::assertStringContainsString('BEGIN PRIVATE KEY', $pair->privateKey);
        self::assertStringContainsString('BEGIN CERTIFICATE REQUEST', $pair->csr);
        self::assertStringNotContainsString('PRIVATE KEY', $pair->csr);
        self::assertNotFalse(openssl_csr_get_public_key($pair->csr));
    }
}
