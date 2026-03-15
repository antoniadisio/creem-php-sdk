<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\Client;
use Antoniadisio\Creem\Config;
use Antoniadisio\Creem\Resource\CheckoutsResource;
use Antoniadisio\Creem\Resource\CustomersResource;
use Antoniadisio\Creem\Resource\DiscountsResource;
use Antoniadisio\Creem\Resource\LicensesResource;
use Antoniadisio\Creem\Resource\ProductsResource;
use Antoniadisio\Creem\Resource\StatsResource;
use Antoniadisio\Creem\Resource\SubscriptionsResource;
use Antoniadisio\Creem\Resource\TransactionsResource;

test('client exposes expected resource accessors', function (): void {
    $client = new Client(new Config('sk_test_123'));

    expect($client->products())->toBeInstanceOf(ProductsResource::class)
        ->and($client->customers())->toBeInstanceOf(CustomersResource::class)
        ->and($client->subscriptions())->toBeInstanceOf(SubscriptionsResource::class)
        ->and($client->checkouts())->toBeInstanceOf(CheckoutsResource::class)
        ->and($client->licenses())->toBeInstanceOf(LicensesResource::class)
        ->and($client->discounts())->toBeInstanceOf(DiscountsResource::class)
        ->and($client->transactions())->toBeInstanceOf(TransactionsResource::class)
        ->and($client->stats())->toBeInstanceOf(StatsResource::class)
        ->and($client->products())->toBe($client->products());
});

test('client retains the supplied config', function (): void {
    $config = new Config('sk_test_123');
    $client = new Client($config);

    expect($client->config())->toBe($config);
});
