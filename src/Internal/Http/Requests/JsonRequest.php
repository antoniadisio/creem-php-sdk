<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Http\Requests;

use InvalidArgumentException;
use Saloon\Contracts\Body\HasBody;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

use function preg_match;
use function trim;

abstract class JsonRequest extends Request implements HasBody
{
    use HasJsonBody;

    private ?string $idempotencyKey;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly array $payload = [],
        ?string $idempotencyKey = null,
    ) {
        $this->idempotencyKey = self::normalizeIdempotencyKey($idempotencyKey);
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->payload;
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        if ($this->idempotencyKey === null) {
            return [];
        }

        return ['Idempotency-Key' => $this->idempotencyKey];
    }

    protected static function normalizeIdempotencyKey(?string $idempotencyKey): ?string
    {
        if ($idempotencyKey === null) {
            return null;
        }

        $idempotencyKey = trim($idempotencyKey);

        if ($idempotencyKey === '') {
            throw new InvalidArgumentException('The Creem idempotency key cannot be blank.');
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $idempotencyKey)) {
            throw new InvalidArgumentException('The Creem idempotency key cannot contain control characters.');
        }

        return $idempotencyKey;
    }
}
