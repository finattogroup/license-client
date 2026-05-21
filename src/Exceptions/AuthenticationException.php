<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Exceptions;

final class AuthenticationException extends LicenseClientException
{
    public function __construct(
        string $message,
        public readonly ?string $oauthError = null,
        public readonly int $status = 401,
    ) {
        parent::__construct($message);
    }

    public static function fromResponse(int $status, ?string $error, ?string $description): self
    {
        return new self($description ?? 'Authentication failed.', $error, $status);
    }
}
