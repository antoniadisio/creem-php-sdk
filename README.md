<p align="center">
    <img src="https://banners.beyondco.de/creem-php.png?theme=dark&packageManager=composer+require&packageName=antoniadisio%2Fcreem-php&pattern=architect&style=style_1&description=PHP+SDK+to+easily+integrate+a+PHP%2FLaravel+application+with+Creem.io&md=1&showWatermark=0&fontSize=125px" alt="creem-php">
</p>

<p align="center">
    <a href="https://packagist.org/packages/antoniadisio/creem-php"><img src="https://img.shields.io/packagist/v/antoniadisio/creem-php?label=Packagist&color=%23f28d1a" alt="Packagist Version"></a>
    <a href="https://github.com/antoniadisio/creem-php/actions/workflows/quality.yml"><img src="https://img.shields.io/github/actions/workflow/status/antoniadisio/creem-php/quality.yml?branch=main&label=Tests" alt="GitHub Tests Action Status"></a>
    <a href="https://github.com/antoniadisio/creem-php/actions/workflows/quality.yml"><img src="https://img.shields.io/github/actions/workflow/status/antoniadisio/creem-php/quality.yml?branch=main&label=Code%20Style" alt="GitHub Code Style Action Status"></a>
    <a href="https://packagist.org/packages/antoniadisio/creem-php"><img src="https://img.shields.io/packagist/dt/antoniadisio/creem-php?label=Downloads" alt="Packagist Downloads"></a>
</p>

# Creem PHP SDK

Unofficial but passionate PHP SDK for Creem, with a typed client facade, resource DTOs, credential profiles, and webhook verification helpers.

This is an independently maintained personal package published under the `antoniadisio` namespace. It is not an official Creem package and is not distributed by Creem.

The public contract centers on a typed `Antoniadisio\Creem\Client` facade for outbound API access, named credential profile helpers for multi-account integrations, and a stateless `Antoniadisio\Creem\Webhook` helper for inbound webhook verification and parsing. `saloonphp/saloon` is used internally for transport only and is not part of the supported consumer-facing API.

## Installation

```bash
composer require antoniadisio/creem-php
```

Requires PHP 8.4 or newer.
The runtime namespace is `Antoniadisio\Creem\`.

Release history and migration notes live in [the GitHub changelog](https://github.com/antoniadisio/creem-php/blob/main/CHANGELOG.md).

## Audience

- Integrators: use this README and the supported public API in `Antoniadisio\Creem\Client`, `Config`, `Webhook`, `Resource\*`, `Dto\*`, `Enum\*`, and `Exception\*`.
- Contributors: use [the GitHub contributor guide](https://github.com/antoniadisio/creem-php/blob/main/CONTRIBUTING.md).
- Repository workflow and releases: use [the GitHub contributor guide](https://github.com/antoniadisio/creem-php/blob/main/CONTRIBUTING.md), [the release guide](https://github.com/antoniadisio/creem-php/blob/main/RELEASING.md), and the [live playground runbook](https://github.com/antoniadisio/creem-php/blob/main/playground/README.md).

This README stays consumer-focused. Repository workflow, releases, and live verification procedures live in the linked repo docs.

Everything under `Antoniadisio\Creem\Internal\*` is shipped for runtime support only and is not part of the supported consumer-facing API.

## Quick Start

```php
<?php

use Antoniadisio\Creem\Client;
use Antoniadisio\Creem\Config;
use Antoniadisio\Creem\Enum\Environment;

$client = new Client(new Config(
    apiKey: $_ENV['CREEM_API_KEY'],
    environment: Environment::Test,
));

$product = $client->products()->get('prod_123');
```

Product responses expose `custom_fields` as typed `Antoniadisio\Creem\Dto\Common\CustomField` objects via `$product->customFields`.

`Antoniadisio\Creem\Config` defaults to `Environment::Production`. If you are using test API keys or test resource IDs, set `Environment::Test` explicitly. Creem's marketing/docs may also call the test environment "sandbox", but the SDK does not expose a separate sandbox environment.

## Configuration

`Antoniadisio\Creem\Config` is immutable and accepts:

- `apiKey` (required; current Creem keys use `creem_test_...` for `Environment::Test` and `creem_...` for `Environment::Production`)
- `environment` (`Antoniadisio\Creem\Enum\Environment::Production` by default)
- `baseUrl` (optional override, must be a valid `https://` URL)
- `timeout` (optional request timeout in seconds, defaults to `30`)
- `userAgentSuffix` (optional suffix appended to the SDK user agent)
- `allowUnsafeBaseUrlOverride` (optional, defaults to `false`; required only when `baseUrl` uses a non-Creem host)

```php
<?php

use Antoniadisio\Creem\Client;
use Antoniadisio\Creem\Config;
use Antoniadisio\Creem\Enum\Environment;

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

The SDK validates Creem-prefixed keys against the configured environment. `creem_test_...` keys are only accepted with `Environment::Test`, and `creem_...` production keys are only accepted with `Environment::Production`.

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

## Credential Profiles

For first-class multi-account integrations, use named credential profiles instead of trying to overload one `Config` with multiple API keys or webhook secrets.

`Antoniadisio\Creem\CredentialProfile` mirrors the `Config` inputs for one concrete credential set and adds an optional `webhookSecret`. `Antoniadisio\Creem\CredentialProfiles` stores named profiles, and `Antoniadisio\Creem\ClientFactory` lazily builds one `Client` per profile.

```php
<?php

use Antoniadisio\Creem\ClientFactory;
use Antoniadisio\Creem\CredentialProfile;
use Antoniadisio\Creem\CredentialProfiles;
use Antoniadisio\Creem\Enum\Environment;

$profiles = new CredentialProfiles([
    'default' => new CredentialProfile(
        apiKey: $_ENV['CREEM_API_KEY'],
        environment: Environment::Test,
        webhookSecret: $_ENV['CREEM_WEBHOOK_SECRET'],
    ),
    'playground' => new CredentialProfile(
        apiKey: $_ENV['CREEM_PLAYGROUND_API_KEY'],
        environment: Environment::Test,
        webhookSecret: $_ENV['CREEM_PLAYGROUND_WEBHOOK_SECRET'],
    ),
]);

$factory = new ClientFactory($profiles);

$merchantClient = $factory->forProfile('playground');
$product = $merchantClient->products()->get('prod_123');
```

The low-level single-key API remains available when you only need one credential set. The multi-profile layer is the recommended SDK path when you need to route requests or webhooks across multiple Creem accounts or app surfaces.

## Error Handling

All SDK exceptions extend `Antoniadisio\Creem\Exception\CreemException`.

- `AuthenticationException` for `401`
- `ForbiddenException` for `403`
- `ConflictException` for `409`
- `GoneException` for `410`
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

use Antoniadisio\Creem\Dto\Checkout\CreateCheckoutRequest;
use Antoniadisio\Creem\Exception\TransportException;
use Antoniadisio\Creem\Exception\ValidationException;

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

`Antoniadisio\Creem\Webhook` verifies the incoming `creem-signature` header against the raw request body and parses the JSON payload without requiring a `Client` instance.
Creem currently sends `creem-signature` as the raw HMAC digest of the payload, for example `63dcbb00f44e82ac158edfb75fd745286f99e9bcebed04dbc0133bb20d15d09c`.

```php
<?php

use Antoniadisio\Creem\Enum\WebhookEventType;
use Antoniadisio\Creem\Webhook;

$payload = file_get_contents('php://input') ?: '';
$signature = $_SERVER['HTTP_CREEM_SIGNATURE'] ?? '';

$event = Webhook::constructEvent(
    $payload,
    $signature,
    $_ENV['CREEM_WEBHOOK_SECRET'],
);

if ($event->eventTypeEnum() === WebhookEventType::SubscriptionActive) {
    $subscriptionId = $event->object()->get('id');
}
```

You can also pass an optional replay callback to `Webhook::constructEvent(...)`. The callback receives the parsed `WebhookEvent`; return `true` to reject already-seen events:

```php
<?php

$event = Webhook::constructEvent(
    $payload,
    $signature,
    $_ENV['CREEM_WEBHOOK_SECRET'],
    static function (\Antoniadisio\Creem\Dto\Webhook\WebhookEvent $event): bool {
        // Persist event IDs in durable storage (Redis, DB, etc.) with a short TTL.
        return hasSeenWebhookId($event->id());
    },
);
```

Always verify the exact raw request body. Do not `json_decode()`, re-encode, trim, or otherwise mutate the payload before calling `Webhook::verifySignature()` or `Webhook::constructEvent()`, or the HMAC check will fail. The SDK also rejects webhook payloads larger than 1 MiB before decoding.

When multiple webhook secrets exist, resolve the intended named profile first and use the profile-aware helpers instead of trying every secret blindly:

```php
<?php

use Antoniadisio\Creem\CredentialProfile;
use Antoniadisio\Creem\CredentialProfiles;
use Antoniadisio\Creem\Enum\Environment;
use Antoniadisio\Creem\Webhook;

$profiles = new CredentialProfiles([
    'default' => new CredentialProfile(
        apiKey: $_ENV['CREEM_API_KEY'],
        environment: Environment::Test,
        webhookSecret: $_ENV['CREEM_WEBHOOK_SECRET'],
    ),
    'playground' => new CredentialProfile(
        apiKey: $_ENV['CREEM_PLAYGROUND_API_KEY'],
        environment: Environment::Test,
        webhookSecret: $_ENV['CREEM_PLAYGROUND_WEBHOOK_SECRET'],
    ),
]);

$profile = $request->is('creem/webhook') ? 'playground' : 'default';

$event = Webhook::constructEventForProfile(
    $payload,
    $signature,
    $profile,
    $profiles,
);
```

`Webhook::verifySignatureForProfile(...)` and `Webhook::constructEventForProfile(...)` resolve exactly one secret from the named profile. The SDK does not iterate across every configured secret for you.

For local webhook development and manual dashboard test sends, the maintainer runbook in [the GitHub playground guide](https://github.com/antoniadisio/creem-php/blob/main/playground/README.md) documents the `php -S` plus `ngrok` flow and the per-route/profile verification checklist.

For Laravel-style controllers, use the raw request content instead of decoded request input:

```php
<?php

namespace App\Http\Controllers;

use Antoniadisio\Creem\Exception\InvalidWebhookPayloadException;
use Antoniadisio\Creem\Exception\InvalidWebhookSignatureException;
use Antoniadisio\Creem\Webhook;
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

The returned `WebhookEvent` exposes `id()`, `eventType()`, `eventTypeEnum()`, `createdAt()`, `object()`, `payload()`, and `toArray()`. `eventType()` remains the raw string from Creem for forward compatibility, while `eventTypeEnum()` returns a `WebhookEventType` for currently documented events or `null` for unknown future values. `object()` returns a `StructuredObject`, so consumers can read nested webhook data without decoding JSON again. Live Creem deliveries currently send `created_at` as a Unix epoch timestamp; the SDK normalizes that to `DateTimeImmutable` for you.

## Resources

`Antoniadisio\Creem\Client` exposes these resource accessors:

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

use Antoniadisio\Creem\Dto\Product\CreateProductRequest;
use Antoniadisio\Creem\Dto\Product\SearchProductsRequest;
use Antoniadisio\Creem\Enum\BillingPeriod;
use Antoniadisio\Creem\Enum\BillingType;
use Antoniadisio\Creem\Enum\CurrencyCode;

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

use Antoniadisio\Creem\Dto\Customer\CreateCustomerBillingPortalLinkRequest;
use Antoniadisio\Creem\Dto\Customer\ListCustomersRequest;

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

use Antoniadisio\Creem\Dto\Subscription\CancelSubscriptionRequest;
use Antoniadisio\Creem\Dto\Subscription\UpdateSubscriptionRequest;
use Antoniadisio\Creem\Dto\Subscription\UpgradeSubscriptionRequest;
use Antoniadisio\Creem\Dto\Subscription\UpsertSubscriptionItem;
use Antoniadisio\Creem\Enum\SubscriptionCancellationMode;
use Antoniadisio\Creem\Enum\SubscriptionUpdateBehavior;

$subscription = $client->subscriptions()->update(
    'sub_123',
    new UpdateSubscriptionRequest(
        items: [
            new UpsertSubscriptionItem(
                id: 'sitem_123',
                priceId: 'pprice_pro_monthly',
                units: 2,
            ),
        ],
        updateBehavior: SubscriptionUpdateBehavior::ProrationChargeImmediately,
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

For live seat updates, prefer `priceId` on `UpsertSubscriptionItem` and pass the current subscription item `id` when adjusting an existing line item. Creem's API troubleshooting guidance recommends `price_id` as the most specific reference for validation.
When setting `updateBehavior` on `subscriptions()->update(...)`, `SubscriptionUpdateBehavior::ProrationChargeImmediately` applies the unit change immediately and may generate a proration charge right away. In live testing, `SubscriptionUpdateBehavior::ProrationCharge` and `SubscriptionUpdateBehavior::ProrationNone` left the current item units unchanged until the next billing boundary.

### Checkouts

```php
<?php

use Antoniadisio\Creem\Dto\Checkout\CreateCheckoutRequest;
use Antoniadisio\Creem\Dto\Checkout\CheckoutCustomerInput;

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

use Antoniadisio\Creem\Dto\License\ActivateLicenseRequest;
use Antoniadisio\Creem\Dto\License\DeactivateLicenseRequest;
use Antoniadisio\Creem\Dto\License\ValidateLicenseRequest;

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

use Antoniadisio\Creem\Dto\Discount\CreateDiscountRequest;
use Antoniadisio\Creem\Enum\DiscountDuration;
use Antoniadisio\Creem\Enum\DiscountType;

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

use Antoniadisio\Creem\Dto\Transaction\SearchTransactionsRequest;

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

use Antoniadisio\Creem\Dto\Stats\GetStatsSummaryRequest;
use Antoniadisio\Creem\Enum\CurrencyCode;
use Antoniadisio\Creem\Enum\StatsInterval;
use DateTimeImmutable;

$startDate = new DateTimeImmutable('-7 days');
$endDate = new DateTimeImmutable('now');

$summary = $client->stats()->summary(new GetStatsSummaryRequest(
    currency: CurrencyCode::USD,
    startDate: $startDate,
    endDate: $endDate,
    interval: StatsInterval::Day,
));
```

Supported methods:

- `summary(GetStatsSummaryRequest $request)`

## Response Shapes

Collection-style endpoints return `Antoniadisio\Creem\Dto\Common\Page`, with pagination metadata in `Antoniadisio\Creem\Dto\Common\Pagination`. Resource items are exposed through typed DTO payloads instead of raw decoded arrays.

Closed-set response fields are hydrated to `Antoniadisio\Creem\Enum\*` cases, spec-defined date-time fields are hydrated to `DateTimeImmutable`, and malformed required payloads now raise `Antoniadisio\Creem\Exception\HydrationException` instead of being silently coerced.
