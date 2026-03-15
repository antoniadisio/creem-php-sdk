<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Http\Requests\Transactions;

use Antoniadisio\Creem\Internal\Http\Requests\QueryRequest;
use Saloon\Enums\Method;

final class GetTransactionRequest extends QueryRequest
{
    protected Method $method = Method::GET;

    public function __construct(string $transactionId)
    {
        parent::__construct(['transaction_id' => $transactionId]);
    }

    public function resolveEndpoint(): string
    {
        return '/v1/transactions';
    }
}
