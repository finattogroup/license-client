<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Support;

use Finatto\LicenseClient\Exceptions\InvalidSerialKeyException;

final readonly class SerialKey
{
    private const string PATTERN = '/^LIC-[A-Z0-9]{8}-[A-Za-z0-9]{24}$/';

    public function __construct(
        public string $clientId,
        public string $clientSecret,
    ) {}

    public static function parse(?string $serial): self
    {
        if ($serial === null || $serial === '') {
            throw InvalidSerialKeyException::missing();
        }

        if (preg_match(self::PATTERN, $serial) !== 1) {
            throw InvalidSerialKeyException::malformed();
        }

        [, $clientId, $clientSecret] = explode('-', $serial, 3);

        return new self($clientId, $clientSecret);
    }
}
