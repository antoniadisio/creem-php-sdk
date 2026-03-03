<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Resource\CheckoutsResource;
use Creem\Resource\CustomersResource;
use Creem\Resource\DiscountsResource;
use Creem\Resource\LicensesResource;
use Creem\Resource\ProductsResource;
use Creem\Resource\StatsResource;
use Creem\Resource\SubscriptionsResource;
use Creem\Resource\TransactionsResource;
use JsonException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

use function array_is_list;
use function array_unique;
use function array_values;
use function basename;
use function dirname;
use function file_get_contents;
use function glob;
use function is_array;
use function is_string;
use function json_decode;
use function ksort;
use function sort;

final class OpenApiContractTest extends TestCase
{
    public function test_openapi_spec_operations_match_the_explicit_sdk_coverage_manifest(): void
    {
        $manifest = [];

        foreach ($this->coverageManifest() as $operationId => $coverage) {
            $manifest[$operationId] = [
                'method' => $coverage['method'],
                'path' => $coverage['path'],
            ];
        }

        ksort($manifest);

        self::assertSame($manifest, $this->specOperations());
    }

    public function test_every_spec_operation_maps_to_a_real_resource_method_and_behavior_test(): void
    {
        foreach ($this->coverageManifest() as $operationId => $coverage) {
            foreach ($coverage['sdk_methods'] as $sdkMethod) {
                self::assertTrue(
                    method_exists($coverage['resource'], $sdkMethod),
                    sprintf('Operation %s must map to an existing SDK method.', $operationId),
                );

                $resourceMethod = new ReflectionMethod($coverage['resource'], $sdkMethod);

                self::assertTrue(
                    $resourceMethod->isPublic(),
                    sprintf('Operation %s must map to a public SDK method.', $operationId),
                );
            }

            self::assertTrue(
                method_exists(ResourcesTest::class, $coverage['coverage_test']),
                sprintf('Operation %s must map to a behavior test.', $operationId),
            );
        }
    }

    /**
     * @throws JsonException
     */
    public function test_response_fixtures_are_complete_for_the_coverage_manifest(): void
    {
        $expectedFixtures = [];

        foreach ($this->coverageManifest() as $coverage) {
            $expectedFixtures = [...$expectedFixtures, ...$coverage['fixtures']];
        }

        $expectedFixtures = array_values(array_unique($expectedFixtures));
        $actualFixtures = array_map(
            basename(...),
            glob($this->fixturesDirectory().'/*.json') ?: [],
        );

        sort($expectedFixtures);
        sort($actualFixtures);

        self::assertSame($expectedFixtures, $actualFixtures);

        foreach ($expectedFixtures as $fixture) {
            $contents = file_get_contents($this->fixturesDirectory().'/'.$fixture);

            self::assertNotFalse($contents, sprintf('Fixture %s could not be read.', $fixture));

            $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            self::assertIsArray($payload, sprintf('Fixture %s must decode to an array payload.', $fixture));
        }
    }

    /**
     * @throws JsonException
     */
    public function test_key_response_fixtures_lock_spec_aligned_typed_shapes(): void
    {
        $product = $this->fixture('product.json');
        self::assertSame('USD', $this->stringValue($product, 'currency', 'product fixture'));
        self::assertSame('every-month', $this->stringValue($product, 'billing_period', 'product fixture'));
        self::assertSame(
            'licenseKey',
            $this->stringValue(
                $this->listObjectAt($this->listValue($product, 'features', 'product fixture'), 0, 'product fixture features'),
                'type',
                'product fixture features[0]',
            ),
        );
        self::assertSame('2026-01-01T00:00:00Z', $this->stringValue($product, 'created_at', 'product fixture'));

        $checkout = $this->fixture('checkout.json');
        self::assertSame('pending', $this->stringValue($checkout, 'status', 'checkout fixture'));
        self::assertIsArray($this->value($checkout, 'product', 'checkout fixture'));
        self::assertIsArray($this->value($checkout, 'order', 'checkout fixture'));
        self::assertSame(
            'paid',
            $this->stringValue($this->objectValue($checkout, 'order', 'checkout fixture'), 'status', 'checkout order'),
        );
        self::assertSame(
            'text',
            $this->stringValue(
                $this->listObjectAt($this->listValue($checkout, 'custom_fields', 'checkout fixture'), 0, 'checkout custom fields'),
                'type',
                'checkout custom fields[0]',
            ),
        );
        self::assertSame(
            'file',
            $this->stringValue(
                $this->listObjectAt($this->listValue($checkout, 'feature', 'checkout fixture'), 0, 'checkout features'),
                'type',
                'checkout features[0]',
            ),
        );
        $checkoutMetadata = $this->objectValue($checkout, 'metadata', 'checkout fixture');
        self::assertSame('sdk-test', $this->stringValue($checkoutMetadata, 'source', 'checkout metadata'));
        self::assertIsInt($this->value($checkoutMetadata, 'attempt', 'checkout metadata'));

        $subscription = $this->fixture('subscription.json');
        self::assertIsArray($this->value($subscription, 'product', 'subscription fixture'));
        self::assertSame('cus_123', $this->stringValue($subscription, 'customer', 'subscription fixture'));
        self::assertSame(
            'charge_automatically',
            $this->stringValue($subscription, 'collection_method', 'subscription fixture'),
        );
        self::assertSame(
            'paid',
            $this->stringValue(
                $this->objectValue($subscription, 'last_transaction', 'subscription fixture'),
                'status',
                'subscription last transaction',
            ),
        );
        self::assertSame(
            '2026-01-01T12:00:00Z',
            $this->stringValue($subscription, 'last_transaction_date', 'subscription fixture'),
        );

        $statsSummary = $this->fixture('stats_summary.json');
        self::assertSame(
            12000,
            $this->value($this->objectValue($statsSummary, 'totals', 'stats summary fixture'), 'totalRevenue', 'stats summary totals'),
        );
        $firstStatsPeriod = $this->listObjectAt(
            $this->listValue($statsSummary, 'periods', 'stats summary fixture'),
            0,
            'stats summary periods',
        );
        self::assertIsInt($this->value($firstStatsPeriod, 'timestamp', 'stats summary periods[0]'));
        self::assertSame(11500, $this->value($firstStatsPeriod, 'netRevenue', 'stats summary periods[0]'));
    }

    /**
     * @return array<string, array{method: string, path: string}>
     *
     * @throws JsonException
     */
    private function specOperations(): array
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

                self::assertArrayNotHasKey($operationId, $operations, 'OpenAPI operation IDs must be unique.');

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
    private function coverageManifest(): array
    {
        $coverage = [
            'activateLicense' => [
                'method' => 'POST',
                'path' => '/v1/licenses/activate',
                'resource' => LicensesResource::class,
                'sdk_methods' => ['activate'],
                'coverage_test' => 'test_licenses_resource_maps_activation_validation_and_deactivation',
                'fixtures' => ['license.json'],
            ],
            'cancelSubscription' => [
                'method' => 'POST',
                'path' => '/v1/subscriptions/{id}/cancel',
                'resource' => SubscriptionsResource::class,
                'sdk_methods' => ['cancel'],
                'coverage_test' => 'test_subscriptions_resource_maps_each_action_endpoint',
                'fixtures' => ['subscription.json'],
            ],
            'createCheckout' => [
                'method' => 'POST',
                'path' => '/v1/checkouts',
                'resource' => CheckoutsResource::class,
                'sdk_methods' => ['create'],
                'coverage_test' => 'test_checkouts_resource_handles_get_and_create',
                'fixtures' => ['checkout.json'],
            ],
            'createDiscount' => [
                'method' => 'POST',
                'path' => '/v1/discounts',
                'resource' => DiscountsResource::class,
                'sdk_methods' => ['create'],
                'coverage_test' => 'test_discounts_resource_normalizes_lookup_and_delete_operations',
                'fixtures' => ['discount.json'],
            ],
            'createProduct' => [
                'method' => 'POST',
                'path' => '/v1/products',
                'resource' => ProductsResource::class,
                'sdk_methods' => ['create'],
                'coverage_test' => 'test_products_resource_builds_requests_and_hydrates_responses',
                'fixtures' => ['product.json'],
            ],
            'deactivateLicense' => [
                'method' => 'POST',
                'path' => '/v1/licenses/deactivate',
                'resource' => LicensesResource::class,
                'sdk_methods' => ['deactivate'],
                'coverage_test' => 'test_licenses_resource_maps_activation_validation_and_deactivation',
                'fixtures' => ['license.json'],
            ],
            'deleteDiscount' => [
                'method' => 'DELETE',
                'path' => '/v1/discounts/{id}/delete',
                'resource' => DiscountsResource::class,
                'sdk_methods' => ['delete'],
                'coverage_test' => 'test_discounts_resource_normalizes_lookup_and_delete_operations',
                'fixtures' => ['discount.json'],
            ],
            'generateCustomerLinks' => [
                'method' => 'POST',
                'path' => '/v1/customers/billing',
                'resource' => CustomersResource::class,
                'sdk_methods' => ['createBillingPortalLink'],
                'coverage_test' => 'test_customers_resource_supports_listing_retrieval_and_billing_links',
                'fixtures' => ['customer_links.json'],
            ],
            'getMetricsSummary' => [
                'method' => 'GET',
                'path' => '/v1/stats/summary',
                'resource' => StatsResource::class,
                'sdk_methods' => ['summary'],
                'coverage_test' => 'test_stats_resource_returns_typed_summary_data',
                'fixtures' => ['stats_summary.json'],
            ],
            'getTransactionById' => [
                'method' => 'GET',
                'path' => '/v1/transactions',
                'resource' => TransactionsResource::class,
                'sdk_methods' => ['get'],
                'coverage_test' => 'test_transactions_resource_supports_get_and_search',
                'fixtures' => ['transaction.json'],
            ],
            'listCustomers' => [
                'method' => 'GET',
                'path' => '/v1/customers/list',
                'resource' => CustomersResource::class,
                'sdk_methods' => ['list'],
                'coverage_test' => 'test_customers_resource_supports_listing_retrieval_and_billing_links',
                'fixtures' => ['customer_page.json'],
            ],
            'pauseSubscription' => [
                'method' => 'POST',
                'path' => '/v1/subscriptions/{id}/pause',
                'resource' => SubscriptionsResource::class,
                'sdk_methods' => ['pause'],
                'coverage_test' => 'test_subscriptions_resource_maps_each_action_endpoint',
                'fixtures' => ['subscription.json'],
            ],
            'resumeSubscription' => [
                'method' => 'POST',
                'path' => '/v1/subscriptions/{id}/resume',
                'resource' => SubscriptionsResource::class,
                'sdk_methods' => ['resume'],
                'coverage_test' => 'test_subscriptions_resource_maps_each_action_endpoint',
                'fixtures' => ['subscription.json'],
            ],
            'retrieveCheckout' => [
                'method' => 'GET',
                'path' => '/v1/checkouts',
                'resource' => CheckoutsResource::class,
                'sdk_methods' => ['get'],
                'coverage_test' => 'test_checkouts_resource_handles_get_and_create',
                'fixtures' => ['checkout.json'],
            ],
            'retrieveCustomer' => [
                'method' => 'GET',
                'path' => '/v1/customers',
                'resource' => CustomersResource::class,
                'sdk_methods' => ['get', 'findByEmail'],
                'coverage_test' => 'test_customers_resource_supports_listing_retrieval_and_billing_links',
                'fixtures' => ['customer.json'],
            ],
            'retrieveDiscount' => [
                'method' => 'GET',
                'path' => '/v1/discounts',
                'resource' => DiscountsResource::class,
                'sdk_methods' => ['get', 'getByCode'],
                'coverage_test' => 'test_discounts_resource_normalizes_lookup_and_delete_operations',
                'fixtures' => ['discount.json'],
            ],
            'retrieveProduct' => [
                'method' => 'GET',
                'path' => '/v1/products',
                'resource' => ProductsResource::class,
                'sdk_methods' => ['get'],
                'coverage_test' => 'test_products_resource_builds_requests_and_hydrates_responses',
                'fixtures' => ['product.json'],
            ],
            'retrieveSubscription' => [
                'method' => 'GET',
                'path' => '/v1/subscriptions',
                'resource' => SubscriptionsResource::class,
                'sdk_methods' => ['get'],
                'coverage_test' => 'test_subscriptions_resource_maps_each_action_endpoint',
                'fixtures' => ['subscription.json'],
            ],
            'searchProducts' => [
                'method' => 'GET',
                'path' => '/v1/products/search',
                'resource' => ProductsResource::class,
                'sdk_methods' => ['search'],
                'coverage_test' => 'test_products_resource_builds_requests_and_hydrates_responses',
                'fixtures' => ['product_page.json'],
            ],
            'searchTransactions' => [
                'method' => 'GET',
                'path' => '/v1/transactions/search',
                'resource' => TransactionsResource::class,
                'sdk_methods' => ['search'],
                'coverage_test' => 'test_transactions_resource_supports_get_and_search',
                'fixtures' => ['transaction_page.json'],
            ],
            'updateSubscription' => [
                'method' => 'POST',
                'path' => '/v1/subscriptions/{id}',
                'resource' => SubscriptionsResource::class,
                'sdk_methods' => ['update'],
                'coverage_test' => 'test_subscriptions_resource_maps_each_action_endpoint',
                'fixtures' => ['subscription.json'],
            ],
            'upgradeSubscription' => [
                'method' => 'POST',
                'path' => '/v1/subscriptions/{id}/upgrade',
                'resource' => SubscriptionsResource::class,
                'sdk_methods' => ['upgrade'],
                'coverage_test' => 'test_subscriptions_resource_maps_each_action_endpoint',
                'fixtures' => ['subscription.json'],
            ],
            'validateLicense' => [
                'method' => 'POST',
                'path' => '/v1/licenses/validate',
                'resource' => LicensesResource::class,
                'sdk_methods' => ['validate'],
                'coverage_test' => 'test_licenses_resource_maps_activation_validation_and_deactivation',
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
    private function spec(): array
    {
        $contents = file_get_contents(dirname(__DIR__, 2).'/spec/creem-openapi.json');

        self::assertNotFalse($contents, 'OpenAPI spec could not be read.');

        /** @var array<string, mixed> $spec */
        $spec = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return $spec;
    }

    private function fixturesDirectory(): string
    {
        return dirname(__DIR__).'/Fixtures/Responses';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function value(array $payload, string $key, string $context): mixed
    {
        self::assertArrayHasKey($key, $payload, sprintf('%s must contain key %s.', $context, $key));

        return $payload[$key];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function objectValue(array $payload, string $key, string $context): array
    {
        $value = $this->value($payload, $key, $context);

        self::assertIsArray($value, sprintf('%s.%s must be an object.', $context, $key));
        self::assertFalse(array_is_list($value), sprintf('%s.%s must be an object.', $context, $key));

        /** @var array<string, mixed> $value */
        return $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<mixed>
     */
    private function listValue(array $payload, string $key, string $context): array
    {
        $value = $this->value($payload, $key, $context);

        self::assertIsArray($value, sprintf('%s.%s must be a list.', $context, $key));
        self::assertTrue(array_is_list($value), sprintf('%s.%s must be a list.', $context, $key));

        /** @var list<mixed> $value */
        return $value;
    }

    /**
     * @param  list<mixed>  $items
     * @return array<string, mixed>
     */
    private function listObjectAt(array $items, int $index, string $context): array
    {
        self::assertArrayHasKey($index, $items, sprintf('%s must contain index %d.', $context, $index));

        $value = $items[$index];

        self::assertIsArray($value, sprintf('%s[%d] must be an object.', $context, $index));
        self::assertFalse(array_is_list($value), sprintf('%s[%d] must be an object.', $context, $index));

        /** @var array<string, mixed> $value */
        return $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function stringValue(array $payload, string $key, string $context): string
    {
        $value = $this->value($payload, $key, $context);

        self::assertIsString($value, sprintf('%s.%s must be a string.', $context, $key));

        return $value;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    private function fixture(string $name): array
    {
        $contents = file_get_contents($this->fixturesDirectory().'/'.$name);

        self::assertNotFalse($contents, sprintf('Fixture %s could not be read.', $name));

        /** @var array<string, mixed> $fixture */
        $fixture = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return $fixture;
    }
}
