<?php

declare(strict_types=1);

namespace Creem\Dto\Customer;

final class CreateCustomerBillingPortalLinkRequest
{
    public function __construct(
        public readonly string $customerId,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return ['customer_id' => $this->customerId];
    }
}
