<?php

declare(strict_types=1);

namespace Creem\Dto\Customer;

use Creem\Internal\Hydration\Payload;

final class CustomerLinks
{
    public function __construct(
        public readonly ?string $customerPortalLink,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(Payload::string($payload, 'customer_portal_link'));
    }
}
