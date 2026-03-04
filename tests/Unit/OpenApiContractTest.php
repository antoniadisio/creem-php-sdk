<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Tests\Support\ResourceBehaviorTestCatalog;
use ReflectionMethod;

use function array_map;
use function array_unique;
use function array_values;
use function basename;
use function file_get_contents;
use function glob;
use function sort;
use function sprintf;

creem_test('openapi spec operations match the explicit sdk coverage manifest', function (): void {
    $manifest = [];

    foreach ($this->coverageManifest() as $operationId => $coverage) {
        $manifest[$operationId] = [
            'method' => $coverage['method'],
            'path' => $coverage['path'],
        ];
    }

    ksort($manifest);

    $this->assertSame($manifest, $this->specOperations());
});

creem_test('every spec operation maps to a real resource method and behavior test', function (): void {
    $availableBehaviorTests = ResourceBehaviorTestCatalog::all();

    foreach ($this->coverageManifest() as $operationId => $coverage) {
        foreach ($coverage['sdk_methods'] as $sdkMethod) {
            $this->assertTrue(method_exists($coverage['resource'], $sdkMethod), sprintf('Operation %s must map to an existing SDK method.', $operationId));

            $resourceMethod = new ReflectionMethod($coverage['resource'], $sdkMethod);

            $this->assertTrue($resourceMethod->isPublic(), sprintf('Operation %s must map to a public SDK method.', $operationId));
        }

        $this->assertContains($coverage['coverage_test'], $availableBehaviorTests, sprintf('Operation %s must map to a behavior test.', $operationId));
    }
});

creem_test('response fixtures are complete for the coverage manifest', function (): void {
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

    $this->assertSame($expectedFixtures, $actualFixtures);

    foreach ($expectedFixtures as $fixture) {
        $contents = file_get_contents($this->fixturesDirectory().'/'.$fixture);

        $this->assertNotFalse($contents, sprintf('Fixture %s could not be read.', $fixture));

        $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($payload, sprintf('Fixture %s must decode to an array payload.', $fixture));
    }
});

creem_test('key response fixtures lock spec aligned typed shapes', function (): void {
    $product = $this->fixture('product.json');
    $this->assertSame('USD', $this->stringValue($product, 'currency', 'product fixture'));
    $this->assertSame('every-month', $this->stringValue($product, 'billing_period', 'product fixture'));
    $this->assertSame('licenseKey', $this->stringValue(
        $this->listObjectAt($this->listValue($product, 'features', 'product fixture'), 0, 'product fixture features'),
        'type',
        'product fixture features[0]',
    ));
    $this->assertSame('2026-01-01T00:00:00Z', $this->stringValue($product, 'created_at', 'product fixture'));

    $checkout = $this->fixture('checkout.json');
    $this->assertSame('pending', $this->stringValue($checkout, 'status', 'checkout fixture'));
    $this->assertIsArray($this->value($checkout, 'product', 'checkout fixture'));
    $this->assertIsArray($this->value($checkout, 'order', 'checkout fixture'));
    $this->assertSame('paid', $this->stringValue($this->objectValue($checkout, 'order', 'checkout fixture'), 'status', 'checkout order'));
    $this->assertSame('text', $this->stringValue(
        $this->listObjectAt($this->listValue($checkout, 'custom_fields', 'checkout fixture'), 0, 'checkout custom fields'),
        'type',
        'checkout custom fields[0]',
    ));
    $this->assertSame('file', $this->stringValue(
        $this->listObjectAt($this->listValue($checkout, 'feature', 'checkout fixture'), 0, 'checkout features'),
        'type',
        'checkout features[0]',
    ));
    $checkoutMetadata = $this->objectValue($checkout, 'metadata', 'checkout fixture');
    $this->assertSame('sdk-test', $this->stringValue($checkoutMetadata, 'source', 'checkout metadata'));
    $this->assertIsInt($this->value($checkoutMetadata, 'attempt', 'checkout metadata'));

    $subscription = $this->fixture('subscription.json');
    $this->assertIsArray($this->value($subscription, 'product', 'subscription fixture'));
    $this->assertSame('cus_123', $this->stringValue($subscription, 'customer', 'subscription fixture'));
    $this->assertSame('charge_automatically', $this->stringValue($subscription, 'collection_method', 'subscription fixture'));
    $this->assertSame('paid', $this->stringValue(
        $this->objectValue($subscription, 'last_transaction', 'subscription fixture'),
        'status',
        'subscription last transaction',
    ));
    $this->assertSame('2026-01-01T12:00:00Z', $this->stringValue($subscription, 'last_transaction_date', 'subscription fixture'));

    $statsSummary = $this->fixture('stats_summary.json');
    $this->assertSame(12000, $this->value($this->objectValue($statsSummary, 'totals', 'stats summary fixture'), 'totalRevenue', 'stats summary totals'));
    $firstStatsPeriod = $this->listObjectAt(
        $this->listValue($statsSummary, 'periods', 'stats summary fixture'),
        0,
        'stats summary periods',
    );
    $this->assertIsInt($this->value($firstStatsPeriod, 'timestamp', 'stats summary periods[0]'));
    $this->assertSame(11500, $this->value($firstStatsPeriod, 'netRevenue', 'stats summary periods[0]'));
});
