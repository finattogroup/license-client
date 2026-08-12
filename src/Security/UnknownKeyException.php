<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Security;

final class UnknownKeyException extends \RuntimeException
{
    public function __construct(public readonly string $kid) { parent::__construct("Unknown PASETO key: {$kid}"); }
}
