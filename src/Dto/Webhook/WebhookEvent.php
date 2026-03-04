<?php

declare(strict_types=1);

namespace Creem\Dto\Webhook;

use Creem\Dto\Common\StructuredObject;
use Creem\Internal\Hydration\Payload;
use DateTimeImmutable;

final readonly class WebhookEvent
{
    private function __construct(
        private string $id,
        private string $eventType,
        private DateTimeImmutable $createdAt,
        private StructuredObject $object,
        private StructuredObject $payload,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        $id = Payload::string($payload, 'id', self::class, true);
        $eventType = Payload::string($payload, 'eventType', self::class, true);
        $createdAt = Payload::dateTime($payload, 'created_at', self::class, true);
        $object = Payload::object($payload, 'object', self::class, true);

        /** @var string $id */
        /** @var string $eventType */
        /** @var DateTimeImmutable $createdAt */
        /** @var StructuredObject $object */
        return new self(
            $id,
            $eventType,
            $createdAt,
            $object,
            StructuredObject::fromArray($payload),
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function eventType(): string
    {
        return $this->eventType;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function object(): StructuredObject
    {
        return $this->object;
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
