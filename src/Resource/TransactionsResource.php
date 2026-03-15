<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Resource;

use Antoniadisio\Creem\Dto\Common\Page;
use Antoniadisio\Creem\Dto\Transaction\SearchTransactionsRequest;
use Antoniadisio\Creem\Dto\Transaction\Transaction;
use Antoniadisio\Creem\Internal\Http\Requests\Transactions\GetTransactionRequest;
use Antoniadisio\Creem\Internal\Http\Requests\Transactions\SearchTransactionsRequest as SearchTransactionsOperation;
use Antoniadisio\Creem\Internal\Hydration\Payload;

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
