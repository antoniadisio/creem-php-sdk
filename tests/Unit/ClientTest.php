<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Client;
use Creem\Config;
use Creem\Resource\CheckoutsResource;
use Creem\Resource\CustomersResource;
use Creem\Resource\DiscountsResource;
use Creem\Resource\LicensesResource;
use Creem\Resource\ProductsResource;
use Creem\Resource\StatsResource;
use Creem\Resource\SubscriptionsResource;
use Creem\Resource\TransactionsResource;

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
