<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Tests\Unit;

use Finatto\LicenseClient\Exceptions\InvalidLicenseTokenException;
use Finatto\LicenseClient\Security\PasetoVerifier;
use ParagonIE\Paseto\Protocol\Version4;
use PHPUnit\Framework\TestCase;

final class PasetoVerifierTest extends TestCase
{
    public function test_it_verifies_v4_public_signature_footer_key_and_expiration(): void
    {
        $secret = Version4::generateAsymmetricSecretKey();
        $footer = json_encode(['kid' => 'key-1'], JSON_THROW_ON_ERROR);
        $claims = ['iss' => 'key-service', 'exp' => gmdate(DATE_RFC3339, time() + 300)];
        $token = Version4::sign(json_encode($claims, JSON_THROW_ON_ERROR), $secret, $footer);
        $keys = ['key-1' => ['public_key' => base64_encode($secret->getPublicKey()->raw())]];

        self::assertSame($claims, (new PasetoVerifier())->verify($token, $keys, 'key-service'));
    }

    public function test_it_rejects_a_tampered_token(): void
    {
        $secret = Version4::generateAsymmetricSecretKey();
        $footer = json_encode(['kid' => 'key-1'], JSON_THROW_ON_ERROR);
        $token = Version4::sign(json_encode(['exp' => gmdate(DATE_RFC3339, time() + 300)], JSON_THROW_ON_ERROR), $secret, $footer);
        $keys = ['key-1' => ['public_key' => base64_encode($secret->getPublicKey()->raw())]];
        $token[20] = $token[20] === 'a' ? 'b' : 'a';

        $this->expectException(InvalidLicenseTokenException::class);
        (new PasetoVerifier())->verify($token, $keys);
    }
}
