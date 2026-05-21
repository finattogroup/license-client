<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Exceptions;

final class InvalidSerialKeyException extends LicenseClientException
{
    public static function missing(): self
    {
        return new self('No serial key provided.');
    }

    public static function malformed(): self
    {
        return new self('Malformed serial key.');
    }
}
