<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Integration;

use Antoniadisio\Creem\Dto\Common\Pagination;
use Antoniadisio\Creem\Dto\Transaction\SearchTransactionsRequest;
use Antoniadisio\Creem\Dto\Transaction\Transaction;
use Antoniadisio\Creem\Enum\CurrencyCode;
use Antoniadisio\Creem\Enum\TransactionStatus;
use Antoniadisio\Creem\Enum\TransactionType;
use Antoniadisio\Creem\Resource\TransactionsResource;
use Antoniadisio\Creem\Tests\IntegrationTestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

test('transactions resource gets and searches transactions', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('transaction.json')),
        MockResponse::make($this->responseFixture('transaction_page.json')),
    ]);
    $resource = new TransactionsResource($this->connector($mockClient));

    $transaction = $resource->get('tran_fixture_invoice');

    expect($transaction->id)->toBe('tran_fixture_invoice')
        ->and($transaction->currency)->toBe(CurrencyCode::USD)
        ->and($transaction->type)->toBe(TransactionType::Invoice)
        ->and($transaction->refundedAmount)->toBeNull()
        ->and($transaction->status)->toBe(TransactionStatus::Paid);
    $this->assertRequest($mockClient, Method::GET, '/v1/transactions', ['transaction_id' => 'tran_fixture_invoice']);

    $page = $resource->search(new SearchTransactionsRequest(customerId: 'cust_fixture_primary', pageNumber: 1, pageSize: 25));

    expect($page->count())->toBe(1)
        ->and($page->pagination)->toBeInstanceOf(Pagination::class)
        ->and($page->pagination?->currentPage)->toBe(1)
        ->and($page->pagination?->nextPage)->toBe(2)
        ->and($page->get(0))->toBeInstanceOf(Transaction::class)
        ->and($page->get(0)?->type)->toBe(TransactionType::Invoice)
        ->and($page->get(0)?->status)->toBe(TransactionStatus::Paid);
    $this->assertRequest(
        $mockClient,
        Method::GET,
        '/v1/transactions/search',
        ['customer_id' => 'cust_fixture_primary', 'page_number' => '1', 'page_size' => '25'],
    );
});

test('transactions resource omits query parameters when search request is omitted', function (): void {
    /** @var IntegrationTestCase $this */
    $mockClient = new MockClient([
        MockResponse::make($this->responseFixture('transaction_page.json')),
    ]);
    $resource = new TransactionsResource($this->connector($mockClient));

    $page = $resource->search();

    expect($page->count())->toBe(1)
        ->and($page->get(0))->toBeInstanceOf(Transaction::class);
    $this->assertRequest($mockClient, Method::GET, '/v1/transactions/search');
});
