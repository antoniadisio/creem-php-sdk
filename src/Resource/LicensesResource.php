<?php

declare(strict_types=1);

namespace Creem\Resource;

use Creem\Dto\License\ActivateLicenseRequest;
use Creem\Dto\License\DeactivateLicenseRequest;
use Creem\Dto\License\License;
use Creem\Dto\License\ValidateLicenseRequest;
use Creem\Internal\Http\Requests\Licenses\ActivateLicenseRequest as ActivateLicenseOperation;
use Creem\Internal\Http\Requests\Licenses\DeactivateLicenseRequest as DeactivateLicenseOperation;
use Creem\Internal\Http\Requests\Licenses\ValidateLicenseRequest as ValidateLicenseOperation;

final class LicensesResource extends Resource
{
    public function activate(ActivateLicenseRequest $request): License
    {
        return License::fromPayload($this->send(new ActivateLicenseOperation($request->toArray())));
    }

    public function deactivate(DeactivateLicenseRequest $request): License
    {
        return License::fromPayload($this->send(new DeactivateLicenseOperation($request->toArray())));
    }

    public function validate(ValidateLicenseRequest $request): License
    {
        return License::fromPayload($this->send(new ValidateLicenseOperation($request->toArray())));
    }
}
