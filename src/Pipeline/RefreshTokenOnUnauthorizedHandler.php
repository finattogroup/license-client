<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Pipeline;

use Finatto\LicenseClient\Data\LicenseSnapshot;
use Finatto\LicenseClient\Exceptions\LicenseRequestException;
use Finatto\LicenseClient\TokenManager;

final class RefreshTokenOnUnauthorizedHandler extends AbstractHandler
{
    public function __construct(private readonly TokenManager $tokens) {}

    public function handle(LicenseRequest $request): LicenseSnapshot
    {
        try {
            return parent::handle($request);
        } catch (LicenseRequestException $e) {
            if ($e->status !== 401 || $request->attempt > 0) {
                throw $e;
            }

            $this->tokens->forget($request->credentials);
            $request->attempt++;
            $request->authorization = null;

            return parent::handle($request);
        }
    }
}
