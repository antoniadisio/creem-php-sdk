<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Support\Contract;

use Antoniadisio\Creem\Resource\CheckoutsResource;
use Antoniadisio\Creem\Resource\CustomersResource;
use Antoniadisio\Creem\Resource\DiscountsResource;
use Antoniadisio\Creem\Resource\LicensesResource;
use Antoniadisio\Creem\Resource\ProductsResource;
use Antoniadisio\Creem\Resource\StatsResource;
use Antoniadisio\Creem\Resource\SubscriptionsResource;
use Antoniadisio\Creem\Resource\TransactionsResource;

use function array_unique;
use function array_values;
use function basename;
use function dirname;
use function ksort;
use function sort;
use function str_replace;

final class CoverageManifest
{
    /**
     * @return array<string, array{
     *     method: string,
     *     path: string,
     *     resource: class-string,
     *     sdk_methods: list<string>,
     *     fixtures: list<string>
     * }>
     */
    public function entries(): array
    {
        $coverage = [
            'activateLicense' => [
                'method' => 'POST',
                'path' => '/v1/licenses/activate',
                'resource' => LicensesResource::class,
                'sdk_methods' => ['activate'],
                'fixtures' => ['license.json'],
            ],
            'cancelSubscription' => [
                'method' => 'POST',
                'path' => '/v1/subscriptions/{id}/cancel',
                'resource' => SubscriptionsResource::class,
                'sdk_methods' => ['cancel'],
                'fixtures' => ['subscription.json'],
            ],
            'createCheckout' => [
                'method' => 'POST',
                'path' => '/v1/checkouts',
                'resource' => CheckoutsResource::class,
                'sdk_methods' => ['create'],
                'fixtures' => ['checkout_create.json'],
            ],
            'createDiscount' => [
                'method' => 'POST',
                'path' => '/v1/discounts',
                'resource' => DiscountsResource::class,
                'sdk_methods' => ['create'],
                'fixtures' => ['discount.json'],
            ],
            'createProduct' => [
                'method' => 'POST',
                'path' => '/v1/products',
                'resource' => ProductsResource::class,
                'sdk_methods' => ['create'],
                'fixtures' => ['product.json'],
            ],
            'deactivateLicense' => [
                'method' => 'POST',
                'path' => '/v1/licenses/deactivate',
                'resource' => LicensesResource::class,
                'sdk_methods' => ['deactivate'],
                'fixtures' => ['license.json'],
            ],
            'deleteDiscount' => [
                'method' => 'DELETE',
                'path' => '/v1/discounts/{id}/delete',
                'resource' => DiscountsResource::class,
                'sdk_methods' => ['delete'],
                'fixtures' => ['discount_deleted.json'],
            ],
            'generateCustomerLinks' => [
                'method' => 'POST',
                'path' => '/v1/customers/billing',
                'resource' => CustomersResource::class,
                'sdk_methods' => ['createBillingPortalLink'],
                'fixtures' => ['customer_links.json'],
            ],
            'getMetricsSummary' => [
                'method' => 'GET',
                'path' => '/v1/stats/summary',
                'resource' => StatsResource::class,
                'sdk_methods' => ['summary'],
                'fixtures' => ['stats_summary.json', 'stats_summary_populated.json'],
            ],
            'getTransactionById' => [
                'method' => 'GET',
                'path' => '/v1/transactions',
                'resource' => TransactionsResource::class,
                'sdk_methods' => ['get'],
                'fixtures' => ['transaction.json'],
            ],
            'listCustomers' => [
                'method' => 'GET',
                'path' => '/v1/customers/list',
                'resource' => CustomersResource::class,
                'sdk_methods' => ['list'],
                'fixtures' => ['customer_page.json'],
            ],
            'pauseSubscription' => [
                'method' => 'POST',
                'path' => '/v1/subscriptions/{id}/pause',
                'resource' => SubscriptionsResource::class,
                'sdk_methods' => ['pause'],
                'fixtures' => ['subscription.json'],
            ],
            'resumeSubscription' => [
                'method' => 'POST',
                'path' => '/v1/subscriptions/{id}/resume',
                'resource' => SubscriptionsResource::class,
                'sdk_methods' => ['resume'],
                'fixtures' => ['subscription.json'],
            ],
            'retrieveCheckout' => [
                'method' => 'GET',
                'path' => '/v1/checkouts',
                'resource' => CheckoutsResource::class,
                'sdk_methods' => ['get'],
                'fixtures' => ['checkout.json'],
            ],
            'retrieveCustomer' => [
                'method' => 'GET',
                'path' => '/v1/customers',
                'resource' => CustomersResource::class,
                'sdk_methods' => ['get', 'findByEmail'],
                'fixtures' => ['customer.json'],
            ],
            'retrieveDiscount' => [
                'method' => 'GET',
                'path' => '/v1/discounts',
                'resource' => DiscountsResource::class,
                'sdk_methods' => ['get', 'getByCode'],
                'fixtures' => ['discount.json'],
            ],
            'retrieveProduct' => [
                'method' => 'GET',
                'path' => '/v1/products',
                'resource' => ProductsResource::class,
                'sdk_methods' => ['get'],
                'fixtures' => ['product.json'],
            ],
            'retrieveSubscription' => [
                'method' => 'GET',
                'path' => '/v1/subscriptions',
                'resource' => SubscriptionsResource::class,
                'sdk_methods' => ['get'],
                'fixtures' => ['subscription.json'],
            ],
            'searchProducts' => [
                'method' => 'GET',
                'path' => '/v1/products/search',
                'resource' => ProductsResource::class,
                'sdk_methods' => ['search'],
                'fixtures' => ['product_page.json'],
            ],
            'searchTransactions' => [
                'method' => 'GET',
                'path' => '/v1/transactions/search',
                'resource' => TransactionsResource::class,
                'sdk_methods' => ['search'],
                'fixtures' => ['transaction_page.json'],
            ],
            'updateSubscription' => [
                'method' => 'POST',
                'path' => '/v1/subscriptions/{id}',
                'resource' => SubscriptionsResource::class,
                'sdk_methods' => ['update'],
                'fixtures' => ['subscription.json'],
            ],
            'upgradeSubscription' => [
                'method' => 'POST',
                'path' => '/v1/subscriptions/{id}/upgrade',
                'resource' => SubscriptionsResource::class,
                'sdk_methods' => ['upgrade'],
                'fixtures' => ['subscription.json'],
            ],
            'validateLicense' => [
                'method' => 'POST',
                'path' => '/v1/licenses/validate',
                'resource' => LicensesResource::class,
                'sdk_methods' => ['validate'],
                'fixtures' => ['license.json'],
            ],
        ];

        ksort($coverage);

        return $coverage;
    }

    /**
     * @return array<string, array{method: string, path: string}>
     */
    public function specOperations(): array
    {
        $operations = [];

        foreach ($this->entries() as $operationId => $coverage) {
            $operations[$operationId] = [
                'method' => $coverage['method'],
                'path' => $coverage['path'],
            ];
        }

        return $operations;
    }

    /**
     * @return list<string>
     */
    public function fixtureNames(): array
    {
        $fixtures = [];

        foreach ($this->entries() as $coverage) {
            $fixtures = [...$fixtures, ...$coverage['fixtures']];
        }

        $fixtures = array_values(array_unique($fixtures));
        sort($fixtures);

        return $fixtures;
    }

    public function integrationTestFileForResource(string $resource): string
    {
        $shortName = basename(str_replace('\\', '/', $resource));

        return dirname(__DIR__, 2).'/Integration/'.$shortName.'Test.php';
    }
}
