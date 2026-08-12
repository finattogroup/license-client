<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Security;

final readonly class KeyPair
{
    public function __construct(public string $privateKey, public string $csr) {}
}
