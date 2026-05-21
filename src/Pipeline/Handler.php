<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Pipeline;

use Finatto\LicenseClient\Data\LicenseSnapshot;

interface Handler
{
    public function setNext(Handler $handler): Handler;

    public function handle(LicenseRequest $request): LicenseSnapshot;
}
