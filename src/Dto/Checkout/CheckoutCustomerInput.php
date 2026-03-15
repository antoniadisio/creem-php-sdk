<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Checkout;

use Antoniadisio\Creem\Internal\Serialization\RequestValueNormalizer;

final readonly class CheckoutCustomerInput
{
    public function __construct(
        public ?string $id = null,
        public ?string $email = null,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        /** @var array<string, string> */
        return RequestValueNormalizer::payload([
            'id' => $this->id,
            'email' => $this->email,
        ]);
    }
}
