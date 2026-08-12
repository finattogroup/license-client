<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Exceptions;

use Throwable;

final class ActivationException extends LicenseClientException
{
    public function __construct(
        string $message,
        public readonly string $reason = 'activation_failed',
        public readonly int $status = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }
}
