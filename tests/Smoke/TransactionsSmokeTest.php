<?php

declare(strict_types=1);

namespace Creem\Tests\Smoke;

use Creem\Dto\Transaction\SearchTransactionsRequest;
use Creem\Dto\Transaction\Transaction;
use Creem\Tests\SmokeTestCase;

test('smoke transactions search returns a typed page', function (): void {
    /** @var SmokeTestCase $this */
    $page = $this->smokeClient()->transactions()->search(new SearchTransactionsRequest(pageSize: 1));

    $this->assertTypedSmokePage($page, Transaction::class);
});

test('smoke transaction retrieval returns a typed transaction when a transaction id is configured', function (): void {
    /** @var SmokeTestCase $this */
    $transactionId = $this->requireOptionalSmokeValue('CREEM_TEST_TRANSACTION_ID', 'transactions()->get()');
    $transaction = $this->smokeClient()->transactions()->get($transactionId);

    expect($transaction)->toBeInstanceOf(Transaction::class)
        ->and($transaction->id)->toBe($transactionId);
});
