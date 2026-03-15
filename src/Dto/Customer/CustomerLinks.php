<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Customer;

use Antoniadisio\Creem\Internal\Hydration\Payload;

final readonly class CustomerLinks
{
    public function __construct(
        public ?string $customerPortalLink,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(Payload::string($payload, 'customer_portal_link'));
    }
}
