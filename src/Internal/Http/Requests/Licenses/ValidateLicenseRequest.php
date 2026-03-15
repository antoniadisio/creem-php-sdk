<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Http\Requests\Licenses;

use Antoniadisio\Creem\Internal\Http\Requests\JsonRequest;
use Saloon\Enums\Method;

final class ValidateLicenseRequest extends JsonRequest
{
    protected Method $method = Method::POST;

    public function resolveEndpoint(): string
    {
        return '/v1/licenses/validate';
    }
}
