<?php

declare(strict_types=1);

namespace Creem\Internal\Http\Requests\Discounts;

use Creem\Internal\Http\Requests\QueryRequest;
use Saloon\Enums\Method;

use function sprintf;

final class DeleteDiscountRequest extends QueryRequest
{
    protected Method $method = Method::DELETE;

    public function __construct(
        private readonly string $discountId,
    ) {
        parent::__construct();
    }

    public function resolveEndpoint(): string
    {
        return sprintf('/v1/discounts/%s/delete', $this->discountId);
    }
}
