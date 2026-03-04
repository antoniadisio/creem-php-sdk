<?php

declare(strict_types=1);

namespace Creem\Tests\Support;

use Creem\Resource\CheckoutsResource;
use Creem\Resource\CustomersResource;
use Creem\Resource\DiscountsResource;
use Creem\Resource\LicensesResource;
use Creem\Resource\ProductsResource;
use Creem\Resource\StatsResource;
use Creem\Resource\SubscriptionsResource;
use Creem\Resource\TransactionsResource;
use JsonException;

use function array_is_list;
use function ctype_digit;
use function dirname;
use function explode;
use function file_get_contents;
use function is_array;
use function is_string;
use function json_decode;
use function ksort;
use function sprintf;
use function strtoupper;

trait InteractsWithOpenApiSpec
{
    /**
     * @return list<int|string>
     *
     * @throws JsonException
     */
    public function specEnumValuesAtPath(string $path): array
    {
        $node = $this->spec();

        foreach (explode('.', $path) as $segment) {
            $this->assertIsArray($node, sprintf('Spec path %s must resolve at every segment.', $path));

            $key = ctype_digit($segment) ? (int) $segment : $segment;

            $this->assertArrayHasKey($key, $node, sprintf('Spec path %s is missing segment %s.', $path, $segment));

            $node = $node[$key];
        }

        $this->assertIsArray($node, sprintf('Spec path %s must resolve to an enum schema.', $path));
        $this->assertArrayHasKey('enum', $node, sprintf('Spec path %s must expose an enum.', $path));
        $this->assertIsArray($node['enum'], sprintf('Spec path %s must expose enum values.', $path));

        /** @var list<int|string> $enum */
        $enum = $node['enum'];

        return $enum;
    }

    /**
     * @return array<string, array{method: string, path: string}>
     *
     * @throws JsonException
     */
    public function specOperations(): array
    {
        $spec = $this->spec();
        $paths = $this->objectValue($spec, 'paths', 'OpenAPI spec');
        $operations = [];

        foreach ($paths as $path => $methods) {
            if (! is_array($methods) || array_is_list($methods)) {
                continue;
            }

            /** @var array<string, mixed> $methods */
            foreach ($methods as $method => $operation) {
                if (! is_array($operation) || array_is_list($operation)) {
                    continue;
                }

                /** @var array<string, mixed> $operation */
                $operationId = $operation['operationId'] ?? null;

                if (! is_string($operationId)) {
                    continue;
                }

                $this->assertArrayNotHasKey($operationId, $operations, 'OpenAPI operation IDs must be unique.');

                $operations[$operationId] = [
                    'method' => strtoupper($method),
                    'path' => $path,
                ];
            }
        }

        ksort($operations);

        return $operations;
    }

    /**
     * @return array<string, array{
     *     method: string,
     *     path: string,
     *     resource: class-string,
     *     sdk_methods: list<string>,
     *     coverage_test: string,
     *     fixtures: list<string>
     * }>
     */
    public function coverageManifest(): array
    {
        $coverage = [
            'activateLicense' => [
                'method' => 'POST',
                'path' => '/v1/licenses/activate',
                'resource' => LicensesResource::class,
                'sdk_methods' => ['activate'],
                'coverage_test' => ResourceBehaviorTestCatalog::LICENSES,
                'fixtures' => ['license.json'],
            ],
            'cancelSubscription' => [
                'method' => 'POST',
                'path' => '/v1/subscriptions/{id}/cancel',
                'resource' => SubscriptionsResource::class,
                'sdk_methods' => ['cancel'],
                'coverage_test' => ResourceBehaviorTestCatalog::SUBSCRIPTIONS,
                'fixtures' => ['subscription.json'],
            ],
            'createCheckout' => [
                'method' => 'POST',
                'path' => '/v1/checkouts',
                'resource' => CheckoutsResource::class,
                'sdk_methods' => ['create'],
                'coverage_test' => ResourceBehaviorTestCatalog::CHECKOUTS,
                'fixtures' => ['checkout.json'],
            ],
            'createDiscount' => [
                'method' => 'POST',
                'path' => '/v1/discounts',
                'resource' => DiscountsResource::class,
                'sdk_methods' => ['create'],
                'coverage_test' => ResourceBehaviorTestCatalog::DISCOUNTS,
                'fixtures' => ['discount.json'],
            ],
            'createProduct' => [
                'method' => 'POST',
                'path' => '/v1/products',
                'resource' => ProductsResource::class,
                'sdk_methods' => ['create'],
                'coverage_test' => ResourceBehaviorTestCatalog::PRODUCTS,
                'fixtures' => ['product.json'],
            ],
            'deactivateLicense' => [
                'method' => 'POST',
                'path' => '/v1/licenses/deactivate',
                'resource' => LicensesResource::class,
                'sdk_methods' => ['deactivate'],
                'coverage_test' => ResourceBehaviorTestCatalog::LICENSES,
                'fixtures' => ['license.json'],
            ],
            'deleteDiscount' => [
                'method' => 'DELETE',
                'path' => '/v1/discounts/{id}/delete',
                'resource' => DiscountsResource::class,
                'sdk_methods' => ['delete'],
                'coverage_test' => ResourceBehaviorTestCatalog::DISCOUNTS,
                'fixtures' => ['discount.json'],
            ],
            'generateCustomerLinks' => [
                'method' => 'POST',
                'path' => '/v1/customers/billing',
                'resource' => CustomersResource::class,
                'sdk_methods' => ['createBillingPortalLink'],
                'coverage_test' => ResourceBehaviorTestCatalog::CUSTOMERS,
                'fixtures' => ['customer_links.json'],
            ],
            'getMetricsSummary' => [
                'method' => 'GET',
                'path' => '/v1/stats/summary',
                'resource' => StatsResource::class,
                'sdk_methods' => ['summary'],
                'coverage_test' => ResourceBehaviorTestCatalog::STATS,
                'fixtures' => ['stats_summary.json'],
            ],
            'getTransactionById' => [
                'method' => 'GET',
                'path' => '/v1/transactions',
                'resource' => TransactionsResource::class,
                'sdk_methods' => ['get'],
                'coverage_test' => ResourceBehaviorTestCatalog::TRANSACTIONS,
                'fixtures' => ['transaction.json'],
            ],
            'listCustomers' => [
                'method' => 'GET',
                'path' => '/v1/customers/list',
                'resource' => CustomersResource::class,
                'sdk_methods' => ['list'],
                'coverage_test' => ResourceBehaviorTestCatalog::CUSTOMERS,
                'fixtures' => ['customer_page.json'],
            ],
            'pauseSubscription' => [
                'method' => 'POST',
                'path' => '/v1/subscriptions/{id}/pause',
                'resource' => SubscriptionsResource::class,
                'sdk_methods' => ['pause'],
                'coverage_test' => ResourceBehaviorTestCatalog::SUBSCRIPTIONS,
                'fixtures' => ['subscription.json'],
            ],
            'resumeSubscription' => [
                'method' => 'POST',
                'path' => '/v1/subscriptions/{id}/resume',
                'resource' => SubscriptionsResource::class,
                'sdk_methods' => ['resume'],
                'coverage_test' => ResourceBehaviorTestCatalog::SUBSCRIPTIONS,
                'fixtures' => ['subscription.json'],
            ],
            'retrieveCheckout' => [
                'method' => 'GET',
                'path' => '/v1/checkouts',
                'resource' => CheckoutsResource::class,
                'sdk_methods' => ['get'],
                'coverage_test' => ResourceBehaviorTestCatalog::CHECKOUTS,
                'fixtures' => ['checkout.json'],
            ],
            'retrieveCustomer' => [
                'method' => 'GET',
                'path' => '/v1/customers',
                'resource' => CustomersResource::class,
                'sdk_methods' => ['get', 'findByEmail'],
                'coverage_test' => ResourceBehaviorTestCatalog::CUSTOMERS,
                'fixtures' => ['customer.json'],
            ],
            'retrieveDiscount' => [
                'method' => 'GET',
                'path' => '/v1/discounts',
                'resource' => DiscountsResource::class,
                'sdk_methods' => ['get', 'getByCode'],
                'coverage_test' => ResourceBehaviorTestCatalog::DISCOUNTS,
                'fixtures' => ['discount.json'],
            ],
            'retrieveProduct' => [
                'method' => 'GET',
                'path' => '/v1/products',
                'resource' => ProductsResource::class,
                'sdk_methods' => ['get'],
                'coverage_test' => ResourceBehaviorTestCatalog::PRODUCTS,
                'fixtures' => ['product.json'],
            ],
            'retrieveSubscription' => [
                'method' => 'GET',
                'path' => '/v1/subscriptions',
                'resource' => SubscriptionsResource::class,
                'sdk_methods' => ['get'],
                'coverage_test' => ResourceBehaviorTestCatalog::SUBSCRIPTIONS,
                'fixtures' => ['subscription.json'],
            ],
            'searchProducts' => [
                'method' => 'GET',
                'path' => '/v1/products/search',
                'resource' => ProductsResource::class,
                'sdk_methods' => ['search'],
                'coverage_test' => ResourceBehaviorTestCatalog::PRODUCTS,
                'fixtures' => ['product_page.json'],
            ],
            'searchTransactions' => [
                'method' => 'GET',
                'path' => '/v1/transactions/search',
                'resource' => TransactionsResource::class,
                'sdk_methods' => ['search'],
                'coverage_test' => ResourceBehaviorTestCatalog::TRANSACTIONS,
                'fixtures' => ['transaction_page.json'],
            ],
            'updateSubscription' => [
                'method' => 'POST',
                'path' => '/v1/subscriptions/{id}',
                'resource' => SubscriptionsResource::class,
                'sdk_methods' => ['update'],
                'coverage_test' => ResourceBehaviorTestCatalog::SUBSCRIPTIONS,
                'fixtures' => ['subscription.json'],
            ],
            'upgradeSubscription' => [
                'method' => 'POST',
                'path' => '/v1/subscriptions/{id}/upgrade',
                'resource' => SubscriptionsResource::class,
                'sdk_methods' => ['upgrade'],
                'coverage_test' => ResourceBehaviorTestCatalog::SUBSCRIPTIONS,
                'fixtures' => ['subscription.json'],
            ],
            'validateLicense' => [
                'method' => 'POST',
                'path' => '/v1/licenses/validate',
                'resource' => LicensesResource::class,
                'sdk_methods' => ['validate'],
                'coverage_test' => ResourceBehaviorTestCatalog::LICENSES,
                'fixtures' => ['license.json'],
            ],
        ];

        ksort($coverage);

        return $coverage;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function spec(): array
    {
        $contents = file_get_contents(dirname(__DIR__, 2).'/tests/Fixtures/OpenApi/creem-openapi.json');

        $this->assertNotFalse($contents, 'OpenAPI spec could not be read.');

        /** @var array<string, mixed> $spec */
        $spec = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return $spec;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function value(array $payload, string $key, string $context): mixed
    {
        $this->assertArrayHasKey($key, $payload, sprintf('%s must contain key %s.', $context, $key));

        return $payload[$key];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function objectValue(array $payload, string $key, string $context): array
    {
        $value = $this->value($payload, $key, $context);

        $this->assertIsArray($value, sprintf('%s.%s must be an object.', $context, $key));
        $this->assertFalse(array_is_list($value), sprintf('%s.%s must be an object.', $context, $key));

        /** @var array<string, mixed> $value */
        return $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<mixed>
     */
    public function listValue(array $payload, string $key, string $context): array
    {
        $value = $this->value($payload, $key, $context);

        $this->assertIsArray($value, sprintf('%s.%s must be a list.', $context, $key));
        $this->assertTrue(array_is_list($value), sprintf('%s.%s must be a list.', $context, $key));

        /** @var list<mixed> $value */
        return $value;
    }

    /**
     * @param  list<mixed>  $items
     * @return array<string, mixed>
     */
    public function listObjectAt(array $items, int $index, string $context): array
    {
        $this->assertArrayHasKey($index, $items, sprintf('%s must contain index %d.', $context, $index));

        $value = $items[$index];

        $this->assertIsArray($value, sprintf('%s[%d] must be an object.', $context, $index));
        $this->assertFalse(array_is_list($value), sprintf('%s[%d] must be an object.', $context, $index));

        /** @var array<string, mixed> $value */
        return $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function stringValue(array $payload, string $key, string $context): string
    {
        $value = $this->value($payload, $key, $context);

        $this->assertIsString($value, sprintf('%s.%s must be a string.', $context, $key));

        return $value;
    }
}
