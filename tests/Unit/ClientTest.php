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

test('client exposes stable resource accessors', function (): void {
    $client = new Client(new Config('sk_test_123'));

    $this->assertInstanceOf(ProductsResource::class, $client->products());
    $this->assertInstanceOf(CustomersResource::class, $client->customers());
    $this->assertInstanceOf(SubscriptionsResource::class, $client->subscriptions());
    $this->assertInstanceOf(CheckoutsResource::class, $client->checkouts());
    $this->assertInstanceOf(LicensesResource::class, $client->licenses());
    $this->assertInstanceOf(DiscountsResource::class, $client->discounts());
    $this->assertInstanceOf(TransactionsResource::class, $client->transactions());
    $this->assertInstanceOf(StatsResource::class, $client->stats());
    $this->assertSame($client->products(), $client->products());
});

test('client retains the supplied config', function (): void {
    $config = new Config('sk_test_123');
    $client = new Client($config);

    $this->assertSame($config, $client->config());
});
