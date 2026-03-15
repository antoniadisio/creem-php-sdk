<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Http\Requests\Discounts;

use Antoniadisio\Creem\Internal\Http\Requests\QueryRequest;
use Saloon\Enums\Method;

final class RetrieveDiscountRequest extends QueryRequest
{
    protected Method $method = Method::GET;

    public function __construct(?string $discountId = null, ?string $discountCode = null)
    {
        $query = [];

        if ($discountId !== null) {
            $query['discount_id'] = $discountId;
        }

        if ($discountCode !== null) {
            $query['discount_code'] = $discountCode;
        }

        parent::__construct($query);
    }

    public function resolveEndpoint(): string
    {
        return '/v1/discounts';
    }
}
