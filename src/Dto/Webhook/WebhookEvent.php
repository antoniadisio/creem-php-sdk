<?php

declare(strict_types=1);

namespace Creem\Dto\Webhook;

use Creem\Dto\Common\StructuredObject;

final readonly class WebhookEvent
{
    private function __construct(
        private StructuredObject $payload,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(StructuredObject::fromArray($payload));
    }

    public function payload(): StructuredObject
    {
        return $this->payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->payload->all();
    }
}
