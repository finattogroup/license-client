<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Data;

final readonly class ActivationResult
{
    public function __construct(public string $serial, public string $certificatePath, public string $privateKeyPath) {}
}
