<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\Enum\ApiMode;
use Antoniadisio\Creem\Enum\BillingPeriod;
use Antoniadisio\Creem\Enum\BillingType;
use Antoniadisio\Creem\Enum\CheckoutStatus;
use Antoniadisio\Creem\Enum\CurrencyCode;
use Antoniadisio\Creem\Enum\CustomFieldType;
use Antoniadisio\Creem\Enum\DiscountDuration;
use Antoniadisio\Creem\Enum\DiscountStatus;
use Antoniadisio\Creem\Enum\DiscountType;
use Antoniadisio\Creem\Enum\LicenseInstanceStatus;
use Antoniadisio\Creem\Enum\LicenseStatus;
use Antoniadisio\Creem\Enum\OrderStatus;
use Antoniadisio\Creem\Enum\OrderType;
use Antoniadisio\Creem\Enum\ProductFeatureType;
use Antoniadisio\Creem\Enum\ProductStatus;
use Antoniadisio\Creem\Enum\StatsInterval;
use Antoniadisio\Creem\Enum\SubscriptionCancellationAction;
use Antoniadisio\Creem\Enum\SubscriptionCancellationMode;
use Antoniadisio\Creem\Enum\SubscriptionCollectionMethod;
use Antoniadisio\Creem\Enum\SubscriptionStatus;
use Antoniadisio\Creem\Enum\SubscriptionUpdateBehavior;
use Antoniadisio\Creem\Enum\TaxCategory;
use Antoniadisio\Creem\Enum\TaxMode;
use Antoniadisio\Creem\Enum\TransactionStatus;
use Antoniadisio\Creem\Enum\TransactionType;
use Antoniadisio\Creem\Tests\TestCase;
use BackedEnum;

use function array_map;
use function sort;
use function sprintf;

test('spec enums used by the sdk have matching php enums', function (): void {
    /** @var TestCase $testCase */
    $testCase = $this;

    foreach (apiEnumSpecMap() as $path => $enumClass) {
        $expected = $testCase->openApiSpec()->enumValuesAtPath($path);
        $actual = apiEnumCasesFor($enumClass);

        sort($expected);
        sort($actual);

        $this->assertSame($expected, $actual, sprintf('Enum %s must match the spec values at %s.', $enumClass, $path));
    }
});

/**
 * @return array<string, class-string<BackedEnum>>
 */
function apiEnumSpecMap(): array
{
    return [
        'paths./v1/stats/summary.get.parameters.2.schema' => StatsInterval::class,
        'paths./v1/stats/summary.get.parameters.3.schema' => CurrencyCode::class,
        'components.schemas.EnvironmentMode' => ApiMode::class,
        'components.schemas.ProductFeatureType' => ProductFeatureType::class,
        'components.schemas.ProductBillingType' => BillingType::class,
        'components.schemas.ProductBillingPeriod' => BillingPeriod::class,
        'components.schemas.ProductStatus' => ProductStatus::class,
        'components.schemas.TaxMode' => TaxMode::class,
        'components.schemas.TaxCategory' => TaxCategory::class,
        'components.schemas.ProductCurrency' => CurrencyCode::class,
        'components.schemas.ProductRequestBillingType' => BillingType::class,
        'components.schemas.ProductRequestBillingPeriod' => BillingPeriod::class,
        'components.schemas.CustomFieldRequestType' => CustomFieldType::class,
        'components.schemas.SubscriptionCollectionMethod' => SubscriptionCollectionMethod::class,
        'components.schemas.SubscriptionStatus' => SubscriptionStatus::class,
        'components.schemas.TransactionType' => TransactionType::class,
        'components.schemas.TransactionStatus' => TransactionStatus::class,
        'components.schemas.CancelSubscriptionRequestEntity.properties.mode' => SubscriptionCancellationMode::class,
        'components.schemas.CancelSubscriptionRequestEntity.properties.onExecute' => SubscriptionCancellationAction::class,
        'components.schemas.UpdateSubscriptionRequestEntity.properties.update_behavior' => SubscriptionUpdateBehavior::class,
        'components.schemas.UpgradeSubscriptionRequestEntity.properties.update_behavior' => SubscriptionUpdateBehavior::class,
        'components.schemas.OrderStatus' => OrderStatus::class,
        'components.schemas.OrderType' => OrderType::class,
        'components.schemas.CustomFieldType' => CustomFieldType::class,
        'components.schemas.LicenseStatus' => LicenseStatus::class,
        'components.schemas.LicenseInstanceEntity.properties.status' => LicenseInstanceStatus::class,
        'components.schemas.CheckoutEntity.properties.status' => CheckoutStatus::class,
        'components.schemas.DiscountEntity.properties.status' => DiscountStatus::class,
        'components.schemas.DiscountEntity.properties.type' => DiscountType::class,
        'components.schemas.DiscountEntity.properties.duration' => DiscountDuration::class,
        'components.schemas.DiscountType' => DiscountType::class,
        'components.schemas.CouponDurationType' => DiscountDuration::class,
    ];
}

/**
 * @param  class-string<BackedEnum>  $enumClass
 * @return list<int|string>
 */
function apiEnumCasesFor(string $enumClass): array
{
    /** @var list<int|string> */
    return array_map(
        static fn (BackedEnum $case): int|string => $case->value,
        $enumClass::cases(),
    );
}
