<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Customer;

use Antoniadisio\Creem\Internal\Serialization\RequestValueNormalizer;
use InvalidArgumentException;

use function trim;

final readonly class CreateCustomerBillingPortalLinkRequest
{
    public string $customerId;

    public function __construct(
        string $customerId,
    ) {
        $customerId = trim($customerId);

        if ($customerId === '') {
            throw new InvalidArgumentException('The customer ID cannot be blank.');
        }

        $this->customerId = $customerId;
    }

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
