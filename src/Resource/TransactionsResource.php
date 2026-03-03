<?php

declare(strict_types=1);

namespace Creem\Resource;

use Creem\Dto\Common\Page;
use Creem\Dto\Transaction\SearchTransactionsRequest;
use Creem\Dto\Transaction\Transaction;
use Creem\Internal\Http\Requests\Transactions\GetTransactionRequest;
use Creem\Internal\Http\Requests\Transactions\SearchTransactionsRequest as SearchTransactionsOperation;
use Creem\Internal\Hydration\Payload;

final class TransactionsResource extends Resource
{
    public function get(string $id): Transaction
    {
        return Transaction::fromPayload($this->send(new GetTransactionRequest($id)));
    }

    /**
     * @return Page<Transaction>
     */
    public function search(?SearchTransactionsRequest $request = null): Page
    {
        $request ??= new SearchTransactionsRequest;

        return Payload::page(
            $this->send(new SearchTransactionsOperation($request->toQuery())),
            static fn (array $item): Transaction => Transaction::fromPayload($item),
        );
    }
}
