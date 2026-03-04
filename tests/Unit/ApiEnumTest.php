<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use BackedEnum;
use Creem\Enum\ApiMode;
use Creem\Enum\BillingPeriod;
use Creem\Enum\BillingType;
use Creem\Enum\CheckoutStatus;
use Creem\Enum\CurrencyCode;
use Creem\Enum\CustomFieldType;
use Creem\Enum\DiscountDuration;
use Creem\Enum\DiscountStatus;
use Creem\Enum\DiscountType;
use Creem\Enum\LicenseInstanceStatus;
use Creem\Enum\LicenseStatus;
use Creem\Enum\OrderStatus;
use Creem\Enum\OrderType;
use Creem\Enum\ProductFeatureType;
use Creem\Enum\ProductStatus;
use Creem\Enum\StatsInterval;
use Creem\Enum\SubscriptionCancellationAction;
use Creem\Enum\SubscriptionCancellationMode;
use Creem\Enum\SubscriptionCollectionMethod;
use Creem\Enum\SubscriptionStatus;
use Creem\Enum\SubscriptionUpdateBehavior;
use Creem\Enum\TaxCategory;
use Creem\Enum\TaxMode;
use Creem\Enum\TransactionStatus;
use Creem\Enum\TransactionType;

use function array_map;
use function sort;
use function sprintf;

creem_test('spec enums used by the sdk have matching php enums', function (): void {
    foreach (apiEnumSpecMap() as $path => $enumClass) {
        $expected = $this->specEnumValuesAtPath($path);
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
