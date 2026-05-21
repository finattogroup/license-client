<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Pipeline;

use Finatto\LicenseClient\Data\LicenseSnapshot;
use Finatto\LicenseClient\Http\LicenseApiClient;

final class RequestLicenseHandler extends AbstractHandler
{
    public function __construct(private readonly LicenseApiClient $api) {}

    public function handle(LicenseRequest $request): LicenseSnapshot
    {
        $request->payload = $this->api->fetchLicense((string) $request->authorization);

        return parent::handle($request);
    }
}
