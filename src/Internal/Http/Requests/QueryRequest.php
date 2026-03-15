<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Http\Requests;

use InvalidArgumentException;
use Saloon\Http\Request;

use function preg_match;
use function trim;

abstract class QueryRequest extends Request
{
    private readonly ?string $idempotencyKey;

    /**
     * @param  array<string, string|int|float>  $queryParameters
     */
    public function __construct(
        private readonly array $queryParameters = [],
        ?string $idempotencyKey = null,
    ) {
        $this->idempotencyKey = self::normalizeIdempotencyKey($idempotencyKey);
    }

    /**
     * @return array<string, string|int|float>
     */
    protected function defaultQuery(): array
    {
        return $this->queryParameters;
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
