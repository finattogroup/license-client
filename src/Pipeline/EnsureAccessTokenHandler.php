<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Pipeline;

use Finatto\LicenseClient\Data\LicenseSnapshot;
use Finatto\LicenseClient\TokenManager;

final class EnsureAccessTokenHandler extends AbstractHandler
{
    public function __construct(private readonly TokenManager $tokens) {}

    public function handle(LicenseRequest $request): LicenseSnapshot
    {
        if ($request->authorization === null) {
            $request->authorization = $this->tokens->authorizationHeader($request->credentials);
        }

        return parent::handle($request);
    }
}
