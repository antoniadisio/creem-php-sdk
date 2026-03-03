<?php

declare(strict_types=1);

namespace Creem\Dto\Customer;

use Creem\Internal\Serialization\RequestValueNormalizer;

final readonly class CreateCustomerBillingPortalLinkRequest
{
    public function __construct(
        public string $customerId,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        /** @var array<string, string> */
        return RequestValueNormalizer::payload([
            'customer_id' => $this->customerId,
        ]);
    }
}
