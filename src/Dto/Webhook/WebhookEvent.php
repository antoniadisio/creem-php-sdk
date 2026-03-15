<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Dto\Webhook;

use Antoniadisio\Creem\Dto\Common\StructuredObject;
use Antoniadisio\Creem\Exception\HydrationException;
use Antoniadisio\Creem\Internal\Hydration\Payload;
use DateTimeImmutable;
use DateTimeZone;

use function intdiv;
use function is_int;

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
        $createdAt = self::parseCreatedAt($payload);
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

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function parseCreatedAt(array $payload): DateTimeImmutable
    {
        $createdAt = $payload['created_at'] ?? null;

        if (is_int($createdAt)) {
            $epochSeconds = $createdAt > 9_999_999_999 ? intdiv($createdAt, 1000) : $createdAt;

            return new DateTimeImmutable('@'.$epochSeconds)
                ->setTimezone(new DateTimeZone('UTC'));
        }

        $parsedCreatedAt = Payload::dateTime($payload, 'created_at', self::class, true);

        if (! $parsedCreatedAt instanceof \DateTimeImmutable) {
            throw HydrationException::missingField(self::class, 'created_at');
        }

        return $parsedCreatedAt;
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
