<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Security;

use Finatto\LicenseClient\Exceptions\InvalidLicenseTokenException;
use ParagonIE\Paseto\Keys\Version4\AsymmetricPublicKey;
use ParagonIE\Paseto\Protocol\Version4;

final class PasetoVerifier
{
    /**
     * @param array<string, array<string, mixed>> $keys
     * @return array<string, mixed>
     */
    public function verify(string $token, array $keys, ?string $expectedIssuer = null): array
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 4 || $parts[0] !== 'v4' || $parts[1] !== 'public') throw new \UnexpectedValueException('Invalid token format.');
            $footer = self::decode($parts[3]);
            $footerData = json_decode($footer, true, flags: JSON_THROW_ON_ERROR);
            $kid = is_array($footerData) ? ($footerData['kid'] ?? null) : null;
            if (! is_string($kid) || ! isset($keys[$kid])) throw new UnknownKeyException(is_string($kid) ? $kid : '');
            $encoded = $keys[$kid]['public_key'] ?? null;
            if (! is_string($encoded) || ($raw = base64_decode($encoded, true)) === false) throw new \UnexpectedValueException('Invalid public key.');
            $json = Version4::verify($token, new AsymmetricPublicKey($raw), $footer);
            $claims = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($claims)) throw new \UnexpectedValueException('Invalid claims.');
            if ($expectedIssuer !== null && ($claims['iss'] ?? null) !== $expectedIssuer) throw new \UnexpectedValueException('Unexpected issuer.');
            $exp = isset($claims['exp']) && is_string($claims['exp']) ? strtotime($claims['exp']) : false;
            if ($exp === false || $exp <= time()) throw new \UnexpectedValueException('Expired token.');
            return $claims;
        } catch (UnknownKeyException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new InvalidLicenseTokenException('The license token is invalid or its signature could not be verified.', previous: $e);
        }
    }

    private static function decode(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/').str_repeat('=', (4 - strlen($value) % 4) % 4), true);
        if ($decoded === false) throw new \UnexpectedValueException('Invalid token encoding.');
        return $decoded;
    }
}
