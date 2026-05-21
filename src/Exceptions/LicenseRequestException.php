<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Exceptions;

final class LicenseRequestException extends LicenseClientException
{
    public function __construct(
        string $message,
        public readonly int $status = 0,
    ) {
        parent::__construct($message);
    }
}
