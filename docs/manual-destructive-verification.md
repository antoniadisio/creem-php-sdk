# Manual Destructive Verification

This runbook is for maintainers validating Creem `Environment::Test` flows that intentionally mutate remote state. These checks are not part of `composer qa`, `composer qa:check`, or `composer test:smoke`.

The automated smoke suite now covers only one authenticated connectivity canary with `CREEM_TEST_API_KEY`. Use this runbook or the committed `playground/` harness for any specific endpoint retrieval, create, mutate, cancel, activate, deactivate, or other live-behavior validation.

## Guardrails

- Use `Environment::Test` only.
- Use a dedicated test account and API key.
- Prefer unique names and idempotency keys for each run so created resources are easy to trace.
- Capture request and response data when a contract change depends on live behavior:
  - HTTP method
  - endpoint path
  - query parameters
  - request JSON body
  - response status code
  - response JSON body
  - relevant response headers
- Sanitize any captured values before committing fixture updates. Replace real IDs, URLs, emails, timestamps, and secrets with the canonical placeholder style used in `tests/Fixtures/Responses/`.

## Suggested Harness

- Prefer the committed `playground/` harness when you need live SDK request/response evidence.
- Export `CREEM_TEST_API_KEY` before running the SDK manually.
- Record created product, checkout, subscription, discount, customer, and license identifiers in a local scratchpad so cleanup is straightforward.

## Product Creation

- Create one recurring product and one one-time product with unique names.
- Verify the returned `Product` DTO fields, especially pricing, billing type, billing period, and tax settings.
- Confirm the products appear in the Creem test dashboard with the expected metadata.
- Capture the response if product payload fields changed relative to `product.json`.

## Billing Portal Links

- Use an existing test customer created from a prior checkout, or create one through a manual test checkout first.
- Call `customers()->createBillingPortalLink(...)` for that customer.
- Verify the SDK returns a link string and that the URL opens the billing portal for the expected customer in the test environment.
- Capture the response if the billing-link payload changed relative to `customer_links.json`.

## One-Time Checkout Creation

- Use the one-time product from the product-creation step.
- Call `checkouts()->create(...)` with a unique `requestId` and idempotency key.
- Verify the returned checkout URL loads in the test environment and completes successfully with a test payment method.
- Confirm the resulting customer, order, and transaction appear in the dashboard.
- Capture request and response payloads if checkout, order, or embedded product fields changed relative to `checkout.json`.

## Discount Flows

- Create a discount scoped to the target one-time or recurring product.
- Verify `discounts()->get(...)` and `discounts()->getByCode(...)` return the created discount with the expected type, amount, duration, and product scoping.
- Redeem the discount in a manual checkout and confirm the resulting order or subscription reflects the discount.
- Call `discounts()->delete(...)` after verification and confirm the status transition returned by the API.
- Capture payloads if the create, retrieve, or delete responses changed relative to `discount.json`.

## Subscription Update, Upgrade, And Cancel

- Create two recurring products: a baseline plan and a target upgrade plan.
- Complete a recurring checkout to create a fresh test subscription.
- Call `subscriptions()->update(...)` and verify item quantities and update behavior in the returned DTO and dashboard state.
- For seat or quantity updates on an existing subscription item, prefer `price_id` as the item reference and include the current subscription item `id` when adjusting an existing line item. Creem's troubleshooting guidance recommends `price_id` as the most specific reference for validation.
- When validating immediate seat changes, record the chosen `update_behavior` explicitly. `proration-charge-immediately` is the clearest setting when you expect an immediate proration charge and a new transaction.
- Call `subscriptions()->upgrade(...)` and verify the product switch, proration behavior, and next billing state.
- Call `subscriptions()->cancel(...)` with the intended mode/action and verify the returned status plus dashboard state.
- Capture payloads if subscription, embedded transaction, or date fields changed relative to `subscription.json`.

## License Activate, Validate, And Deactivate

- Use a license-key product and complete a checkout that yields a fresh license key.
- Call `licenses()->activate(...)` with a new instance name and verify activation count plus instance details.
- Call `licenses()->validate(...)` for the activated instance and confirm the API reports the expected active state.
- Call `licenses()->deactivate(...)` and confirm the instance status and activation counts update as expected.
- Capture payloads if license or embedded instance fields changed relative to `license.json`.

## Cleanup

- Expire or delete discounts created for the run.
- Cancel subscriptions that should not remain active in the shared test account.
- Archive or otherwise mark temporary products so later runs can ignore them.
- Remove any local scratch scripts or captured raw payload files that should not be committed.
