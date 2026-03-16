<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Http\Requests;

use Saloon\Http\Request;

/** @internal */
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
        $this->idempotencyKey = IdempotencyKey::normalize($idempotencyKey);
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
        return IdempotencyKey::header($this->idempotencyKey);
    }
}
