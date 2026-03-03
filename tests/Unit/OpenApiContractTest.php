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
            static fn (string $path): string => basename($path),
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
     * @return array<string, array{method: string, path: string}>
     *
     * @throws JsonException
     */
    private function specOperations(): array
    {
        $spec = $this->spec();
        $operations = [];

        foreach ($spec['paths'] ?? [] as $path => $methods) {
            if (! is_array($methods)) {
                continue;
            }

            foreach ($methods as $method => $operation) {
                if (! is_array($operation)) {
                    continue;
                }

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
}
