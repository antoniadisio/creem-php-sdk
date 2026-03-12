# Creem PHP SDK

Handwritten PHP SDK for the Creem API.

This is an independently maintained package published under the personal `antoniadisio` namespace; it is not an official Creem package.

The public contract is a pre-1.0 `Creem\Client` facade for outbound API access plus a stateless `Creem\Webhook` helper for inbound webhook verification and parsing. `saloonphp/saloon` is used internally for transport only and is not part of the supported consumer-facing API.

## Installation

```bash
composer require antoniadisio/creem-php-sdk
```

Requires PHP 8.4 or newer.

The current public line is a `0.x` preview release. Follow normal semver expectations for install constraints, but expect the SDK surface to keep evolving until `1.0`.

## Quick Start

```php
<?php

use Creem\Client;
use Creem\Config;
use Creem\Environment;

$client = new Client(new Config(
    apiKey: $_ENV['CREEM_API_KEY'],
    environment: Environment::Test,
));

$product = $client->products()->get('prod_123');
```

`Creem\Config` defaults to `Environment::Production`. If you are using test API keys or test resource IDs, set `Environment::Test` explicitly.

## Configuration

`Creem\Config` is immutable and accepts:

- `apiKey` (required, must start with `sk_` or `creem_`)
- `environment` (`Creem\Environment::Production` by default)
- `baseUrl` (optional override, must be a valid `https://` URL)
- `timeout` (optional request timeout in seconds, defaults to `30`)
- `userAgentSuffix` (optional suffix appended to the SDK user agent)
- `allowUnsafeBaseUrlOverride` (optional, defaults to `false`; required only when `baseUrl` uses a non-Creem host)

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

When `baseUrl` is provided, `Config` now enforces trusted-host mode by default and only allows official Creem hosts (`api.creem.io`, `test-api.creem.io`). To target a non-official host (for example a local proxy), you must opt in explicitly with `allowUnsafeBaseUrlOverride: true`.

```php
<?php

$config = new Config(
    apiKey: $_ENV['CREEM_API_KEY'],
    baseUrl: 'https://proxy.example.test',
    allowUnsafeBaseUrlOverride: true,
);
```

`Config` also redacts the API key in debug output, string casts, and serialization.

Transport defaults are hardened: redirects are disabled, TLS certificate verification is always enabled, TLS 1.2 is enforced as the minimum protocol, and request/connect/read timeouts all use the configured SDK timeout (or the 30-second default).

## Error Handling

All SDK exceptions extend `Creem\Exception\CreemException`.

- `AuthenticationException` for `401` and `403`
- `ValidationException` for `422` and validation-style client payloads
- `NotFoundException` for `404`
- `RateLimitException` for `429` (`retryAfterSeconds()` exposes the parsed `Retry-After` delay when the API sends one)
- `ServerException` for `5xx`
- `TransportException` for transport, timeout, and decode failures
- `WebhookException` for inbound webhook verification and parsing failures
- `InvalidWebhookSignatureException` for invalid or blank `creem-signature` headers
- `InvalidWebhookPayloadException` for malformed webhook payloads

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

## Webhooks

`Creem\Webhook` verifies the incoming `creem-signature` header against the raw request body and parses the JSON payload without requiring a `Client` instance.
The signature header must include a Unix timestamp and signature pair such as `t=1700000000,v1=...`. The SDK signs `timestamp.payload`, rejects blank webhook secrets, and rejects timestamps outside a 5-minute tolerance window.

```php
<?php

use Creem\Webhook;

$payload = file_get_contents('php://input') ?: '';
$signature = $_SERVER['HTTP_CREEM_SIGNATURE'] ?? '';

$event = Webhook::constructEvent(
    $payload,
    $signature,
    $_ENV['CREEM_WEBHOOK_SECRET'],
);

if ($event->eventType() === 'license.created') {
    $licenseId = $event->object()->get('id');
}
```

You can also pass an optional replay callback to `Webhook::constructEvent(...)`. The callback receives the parsed `WebhookEvent`; return `true` to reject already-seen events:

```php
<?php

$event = Webhook::constructEvent(
    $payload,
    $signature,
    $_ENV['CREEM_WEBHOOK_SECRET'],
    static function (\Creem\Dto\Webhook\WebhookEvent $event): bool {
        // Persist event IDs in durable storage (Redis, DB, etc.) with a short TTL.
        return hasSeenWebhookId($event->id());
    },
);
```

Always verify the exact raw request body. Do not `json_decode()`, re-encode, trim, or otherwise mutate the payload before calling `Webhook::verifySignature()` or `Webhook::constructEvent()`, or the HMAC check will fail. The SDK also rejects webhook payloads larger than 1 MiB before decoding.

For Laravel-style controllers, use the raw request content instead of decoded request input:

```php
<?php

namespace App\Http\Controllers;

use Creem\Exception\InvalidWebhookPayloadException;
use Creem\Exception\InvalidWebhookSignatureException;
use Creem\Webhook;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CreemWebhookController
{
    public function __invoke(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('creem-signature', '');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('services.creem.webhook_secret'),
            );
        } catch (InvalidWebhookSignatureException|InvalidWebhookPayloadException) {
            return response(status: 400);
        }

        // Dispatch your application logic here.

        return response(status: 204);
    }
}
```

The returned `WebhookEvent` exposes `id()`, `eventType()`, `createdAt()`, `object()`, `payload()`, and `toArray()`. `object()` returns a `StructuredObject`, so consumers can read nested webhook data without decoding JSON again.

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

All mutating resource methods accept an optional final `?string $idempotencyKey = null` argument. Pass a stable key on retries to prevent duplicate checkout, subscription, discount, or license side effects.

Mutation request DTOs now fail fast with `InvalidArgumentException` when required identifiers are blank after trimming, numeric bounds are invalid (for example `price <= 0` or `units <= 0`), discount payload fields are incoherent (`fixed` vs `percentage`), or list elements are malformed at runtime. Existing integrations that relied on sending empty/invalid payload values should update those call sites before upgrading.

Mutating resource methods that interpolate IDs into path segments (`subscriptions()->cancel/update/upgrade/pause/resume` and `discounts()->delete`) now normalize IDs and reject unsafe input. IDs are trimmed, must be non-blank, may not be dot segments (`.` or `..`), and may only contain `[A-Za-z0-9._-]`; reserved URI/control characters (`/`, `\\`, `?`, `#`, `%`, ASCII controls) are rejected to prevent route manipulation.

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

## Development

If you are contributing to the SDK itself:

```bash
composer qa
```

Before opening a pull request or cutting a release:

```bash
composer qa:check
```

Command guide:

- `composer qa` runs the fix-first local QA flow: Rector, Pint fixes, PHPStan, then the local Pest suites (`Unit` then `Integration`).
- `composer qa:check` runs the same flow without changing files.
- `composer test` runs the fast `Unit` suite only.
- `composer test:integration` runs the deterministic `Integration` suite with Saloon mocks.
- `composer test:local` runs `Unit` then `Integration`.
- `composer test:live` runs the opt-in read-only smoke suite against the Creem test environment and is intentionally excluded from the default QA flow.

Notes:

- The committed Rector config intentionally skips automatic type-declaration inference on `Creem\Client`, `Creem\Config`, and `Creem\Resource\*` so public signatures stay under manual review.
- `composer stan` uses the committed `phpstan.neon.dist` configuration and the repository-defined memory limit.
- `composer install` and `composer update` use the committed Composer platform pin (`php: 8.4.0`) so the lockfile stays aligned with the PHP 8.4 CI target.
- The public repository intentionally keeps maintainer QA files such as `rector.php`, `phpstan.neon.dist`, `phpunit.xml.dist`, and `composer.lock` committed. Installed package archives stay lean through `.gitattributes export-ignore`.
- For `composer test:live`, use `CREEM_LIVE_API_KEY`, and optionally `CREEM_LIVE_BASE_URL` and `CREEM_LIVE_TIMEOUT`.

## Contributing

Contributor workflows, OpenAPI contract fixture maintenance, and release steps live in `CONTRIBUTING.md`.

The package metadata in `composer.json` is suitable for Packagist publication: it includes package name, description, license, keywords, support links, and PSR-4 autoload configuration.
