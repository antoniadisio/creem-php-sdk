# Creem PHP SDK

Handwritten PHP SDK for the Creem API.

The public contract is a stable `Creem\Client` facade with typed DTOs, typed exceptions, and resource classes for the currently supported Creem endpoints. `saloonphp/saloon` is used internally for transport only and is not part of the supported consumer-facing API.

## Installation

```bash
composer require antoniadisio/creem-php-sdk
```

Requires PHP 8.2 or newer.

## Validation

For local development, run the repository checks before shipping changes:

```bash
composer qa
```

`composer qa` runs the fix-first local QA flow in this order: Rector, Pint fixes, PHPStan, then PHPUnit. When you want a non-mutating verification pass (for example before opening a pull request), run `composer qa:check`.
`composer stan` uses the committed `phpstan.neon.dist` project configuration and the repository-defined memory limit, so local analysis and CI run the same PHPStan setup.
`composer install` and `composer update` also use the committed Composer platform pin (`php: 8.2.0`), which keeps the lockfile aligned with the PHP 8.2 CI target even when dependency updates are run on newer local PHP versions.

## Quick Start

```php
<?php

use Creem\Client;
use Creem\Config;

$client = new Client(new Config(
    apiKey: $_ENV['CREEM_API_KEY'],
));

$product = $client->products()->get('prod_123');
```

## Configuration

`Creem\Config` is immutable and accepts:

- `apiKey` (required)
- `environment` (`Creem\Environment::Production` by default)
- `baseUrl` (optional override)
- `timeout` (optional request timeout in seconds)
- `userAgentSuffix` (optional suffix appended to the SDK user agent)

```php
<?php

use Creem\Client;
use Creem\Config;
use Creem\Environment;

$client = new Client(new Config(
    apiKey: $_ENV['CREEM_API_KEY'],
    environment: Environment::Test,
    timeout: 10,
    userAgentSuffix: 'my-app/1.2.0',
));
```

Environments resolve to:

- `Environment::Production` -> `https://api.creem.io`
- `Environment::Test` -> `https://test-api.creem.io`

Use `baseUrl` only when you need to override the default API host for local proxying or similar non-standard setups.

## Error Handling

All SDK exceptions extend `Creem\Exception\CreemException`.

- `AuthenticationException` for `401` and `403`
- `ValidationException` for `422` and validation-style client payloads
- `NotFoundException` for `404`
- `RateLimitException` for `429`
- `ServerException` for `5xx`
- `TransportException` for transport, timeout, and decode failures

```php
<?php

use Creem\Dto\Checkout\CreateCheckoutRequest;
use Creem\Exception\TransportException;
use Creem\Exception\ValidationException;

try {
    $checkout = $client->checkouts()->create(new CreateCheckoutRequest(
        productId: 'prod_123',
    ));
} catch (ValidationException $exception) {
    $errors = $exception->errors();
} catch (TransportException $exception) {
    error_log($exception->getMessage());
}
```

## Resources

`Creem\Client` exposes these resource accessors:

- `products()`
- `customers()`
- `subscriptions()`
- `checkouts()`
- `licenses()`
- `discounts()`
- `transactions()`
- `stats()`

### Products

```php
<?php

use Creem\Dto\Product\CreateProductRequest;
use Creem\Dto\Product\SearchProductsRequest;
use Creem\Enum\BillingPeriod;
use Creem\Enum\BillingType;
use Creem\Enum\CurrencyCode;

$product = $client->products()->create(new CreateProductRequest(
    name: 'Pro Plan',
    price: 4900,
    currency: CurrencyCode::USD,
    billingType: BillingType::Recurring,
    billingPeriod: BillingPeriod::EveryMonth,
));

$page = $client->products()->search(new SearchProductsRequest(
    pageNumber: 1,
    pageSize: 25,
));
```

Supported methods:

- `get(string $id)`
- `create(CreateProductRequest $request)`
- `search(?SearchProductsRequest $request = null)`

### Customers

```php
<?php

use Creem\Dto\Customer\CreateCustomerBillingPortalLinkRequest;
use Creem\Dto\Customer\ListCustomersRequest;

$page = $client->customers()->list(new ListCustomersRequest(
    pageNumber: 1,
    pageSize: 25,
));

$customer = $client->customers()->findByEmail('customer@example.com');

$links = $client->customers()->createBillingPortalLink(
    new CreateCustomerBillingPortalLinkRequest(customerId: 'cus_123')
);
```

Supported methods:

- `list(?ListCustomersRequest $request = null)`
- `get(string $id)`
- `findByEmail(string $email)`
- `createBillingPortalLink(CreateCustomerBillingPortalLinkRequest $request)`

### Subscriptions

```php
<?php

use Creem\Dto\Subscription\CancelSubscriptionRequest;
use Creem\Dto\Subscription\UpdateSubscriptionRequest;
use Creem\Dto\Subscription\UpgradeSubscriptionRequest;
use Creem\Dto\Subscription\UpsertSubscriptionItem;
use Creem\Enum\SubscriptionCancellationMode;
use Creem\Enum\SubscriptionUpdateBehavior;

$subscription = $client->subscriptions()->update(
    'sub_123',
    new UpdateSubscriptionRequest(
        items: [
            new UpsertSubscriptionItem(productId: 'prod_pro', units: 2),
        ],
        updateBehavior: SubscriptionUpdateBehavior::ProrationCharge,
    ),
);

$subscription = $client->subscriptions()->upgrade(
    'sub_123',
    new UpgradeSubscriptionRequest(
        productId: 'prod_enterprise',
        updateBehavior: SubscriptionUpdateBehavior::ProrationChargeImmediately,
    ),
);

$subscription = $client->subscriptions()->cancel(
    'sub_123',
    new CancelSubscriptionRequest(mode: SubscriptionCancellationMode::Immediate),
);
```

Supported methods:

- `get(string $id)`
- `cancel(string $id, ?CancelSubscriptionRequest $request = null)`
- `update(string $id, UpdateSubscriptionRequest $request)`
- `upgrade(string $id, UpgradeSubscriptionRequest $request)`
- `pause(string $id)`
- `resume(string $id)`

### Checkouts

```php
<?php

use Creem\Dto\Checkout\CreateCheckoutRequest;
use Creem\Dto\Checkout\CheckoutCustomerInput;

$checkout = $client->checkouts()->create(new CreateCheckoutRequest(
    productId: 'prod_123',
    successUrl: 'https://example.com/billing/success',
    customer: new CheckoutCustomerInput(email: 'customer@example.com'),
));
```

Supported methods:

- `get(string $id)`
- `create(CreateCheckoutRequest $request)`

### Licenses

```php
<?php

use Creem\Dto\License\ActivateLicenseRequest;
use Creem\Dto\License\DeactivateLicenseRequest;
use Creem\Dto\License\ValidateLicenseRequest;

$license = $client->licenses()->activate(new ActivateLicenseRequest(
    key: 'license_key',
    instanceName: 'production-web-1',
));

$license = $client->licenses()->validate(new ValidateLicenseRequest(
    key: 'license_key',
    instanceId: 'instance_123',
));

$license = $client->licenses()->deactivate(new DeactivateLicenseRequest(
    key: 'license_key',
    instanceId: 'instance_123',
));
```

Supported methods:

- `activate(ActivateLicenseRequest $request)`
- `deactivate(DeactivateLicenseRequest $request)`
- `validate(ValidateLicenseRequest $request)`

### Discounts

```php
<?php

use Creem\Dto\Discount\CreateDiscountRequest;
use Creem\Enum\DiscountDuration;
use Creem\Enum\DiscountType;

$discount = $client->discounts()->create(new CreateDiscountRequest(
    name: 'Spring Sale',
    type: DiscountType::Percentage,
    duration: DiscountDuration::Once,
    appliesToProducts: ['prod_123'],
    code: 'SPRING25',
    percentage: 25,
));

$discount = $client->discounts()->getByCode('SPRING25');
```

Supported methods:

- `get(string $id)`
- `getByCode(string $code)`
- `create(CreateDiscountRequest $request)`
- `delete(string $id)`

### Transactions

```php
<?php

use Creem\Dto\Transaction\SearchTransactionsRequest;

$page = $client->transactions()->search(new SearchTransactionsRequest(
    customerId: 'cus_123',
    pageNumber: 1,
    pageSize: 50,
));
```

Supported methods:

- `get(string $id)`
- `search(?SearchTransactionsRequest $request = null)`

### Stats

```php
<?php

use Creem\Dto\Stats\GetStatsSummaryRequest;
use Creem\Enum\CurrencyCode;
use Creem\Enum\StatsInterval;

$summary = $client->stats()->summary(new GetStatsSummaryRequest(
    currency: CurrencyCode::USD,
    interval: StatsInterval::Day,
));
```

Supported methods:

- `summary(GetStatsSummaryRequest $request)`

## Response Shapes

Collection-style endpoints return `Creem\Dto\Common\Page`, with pagination metadata in `Creem\Dto\Common\Pagination`. Resource items are exposed through typed DTO payloads instead of raw decoded arrays.

Closed-set response fields are hydrated to `Creem\Enum\*` cases, spec-defined date-time fields are hydrated to `DateTimeImmutable`, and malformed required payloads now raise `Creem\Exception\HydrationException` instead of being silently coerced.

## OpenAPI Contract Maintenance

The committed OpenAPI file at `spec/creem-openapi.json` remains the source of truth for endpoint coverage.

When the Creem API changes:

1. Update `spec/creem-openapi.json`.
2. Update request classes, resources, DTOs, fixtures, and tests to match the new contract.
3. Run `composer test` to confirm the contract checks still pass.

`tests/Unit/OpenApiContractTest.php` fails when the spec changes without corresponding SDK coverage updates.

## Release Notes Guidance

For future releases driven by spec updates, call out:

- which OpenAPI operations were added, changed, or removed
- any public SDK surface changes (new methods, renamed DTO fields, behavior changes)
- new exception behavior or validation changes
- required consumer migration steps, if any

Document those notes in `CHANGELOG.md` for the next release, especially when the change is intentionally breaking.

## Local Development

```bash
composer validate --no-check-publish
composer test
composer cs
composer stan
```

The package metadata in `composer.json` is suitable for Packagist publication: it includes package name, description, license, keywords, support links, and PSR-4 autoload configuration.
