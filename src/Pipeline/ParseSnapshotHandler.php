<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Pipeline;

use Finatto\LicenseClient\Data\LicenseSnapshot;

final class ParseSnapshotHandler extends AbstractHandler
{
    public function handle(LicenseRequest $request): LicenseSnapshot
    {
        return LicenseSnapshot::fromResponse($request->payload ?? []);
    }
}
