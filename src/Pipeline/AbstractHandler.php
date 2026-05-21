<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Pipeline;

use Finatto\LicenseClient\Data\LicenseSnapshot;
use Finatto\LicenseClient\Exceptions\LicenseClientException;

abstract class AbstractHandler implements Handler
{
    protected ?Handler $next = null;

    public function setNext(Handler $handler): Handler
    {
        $this->next = $handler;

        return $handler;
    }

    public function handle(LicenseRequest $request): LicenseSnapshot
    {
        if ($this->next instanceof Handler) {
            return $this->next->handle($request);
        }

        throw new LicenseClientException('License resolution chain terminated without a result.');
    }
}
