<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Http\Requests\Checkouts;

use Antoniadisio\Creem\Internal\Http\Requests\QueryRequest;
use Saloon\Enums\Method;

final class RetrieveCheckoutRequest extends QueryRequest
{
    protected Method $method = Method::GET;

    public function __construct(string $checkoutId)
    {
        parent::__construct(['checkout_id' => $checkoutId]);
    }

    public function resolveEndpoint(): string
    {
        return '/v1/checkouts';
    }
}
