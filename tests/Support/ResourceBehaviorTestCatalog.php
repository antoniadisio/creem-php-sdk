<?php

declare(strict_types=1);

namespace Creem\Tests\Support;

final class ResourceBehaviorTestCatalog
{
    public const string PRODUCTS = 'test_products_resource_builds_requests_and_hydrates_responses';

    public const string CUSTOMERS = 'test_customers_resource_supports_listing_retrieval_and_billing_links';

    public const string SUBSCRIPTIONS = 'test_subscriptions_resource_maps_each_action_endpoint';

    public const string CHECKOUTS = 'test_checkouts_resource_handles_get_and_create';

    public const string LICENSES = 'test_licenses_resource_maps_activation_validation_and_deactivation';

    public const string DISCOUNTS = 'test_discounts_resource_normalizes_lookup_and_delete_operations';

    public const string TRANSACTIONS = 'test_transactions_resource_supports_get_and_search';

    public const string STATS = 'test_stats_resource_returns_typed_summary_data';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::PRODUCTS,
            self::CUSTOMERS,
            self::SUBSCRIPTIONS,
            self::CHECKOUTS,
            self::LICENSES,
            self::DISCOUNTS,
            self::TRANSACTIONS,
            self::STATS,
        ];
    }
}
