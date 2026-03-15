<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Http\Requests\Discounts;

use Antoniadisio\Creem\Internal\Http\Requests\PathIdentifier;
use Antoniadisio\Creem\Internal\Http\Requests\QueryRequest;
use Saloon\Enums\Method;

use function sprintf;

final class DeleteDiscountRequest extends QueryRequest
{
    protected Method $method = Method::DELETE;

    private readonly string $discountId;

    public function __construct(
        string $discountId,
        ?string $idempotencyKey = null,
    ) {
        $this->discountId = PathIdentifier::normalize($discountId, 'discount ID');

        parent::__construct(idempotencyKey: $idempotencyKey);
    }

    public function resolveEndpoint(): string
    {
        return sprintf('/v1/discounts/%s/delete', $this->discountId);
    }
}
