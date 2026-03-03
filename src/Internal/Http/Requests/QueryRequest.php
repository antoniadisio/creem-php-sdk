<?php

declare(strict_types=1);

namespace Creem\Internal\Http\Requests;

use Saloon\Http\Request;

abstract class QueryRequest extends Request
{
    /**
     * @param  array<string, string|int|float>  $query
     */
    public function __construct(
        private readonly array $queryParameters = [],
    ) {}

    /**
     * @return array<string, string|int|float>
     */
    protected function defaultQuery(): array
    {
        return $this->queryParameters;
    }
}
