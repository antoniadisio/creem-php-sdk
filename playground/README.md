# Live Harness

`playground/` is the committed contributor harness for real requests against `Antoniadisio\Creem\Enum\Environment::Test` and for local webhook capture during live verification.
Deterministic repo guardrails such as contract, fixture, playground audit, and export-policy checks stay in `composer test:repo`; this document is only for live and destructive verification.

It stays on the real SDK path:

1. instantiate `Antoniadisio\Creem\Client`
2. call the public resource method
3. let the SDK build the Saloon request
4. send the real HTTP call
5. capture redacted transport evidence plus normalized SDK output

It does not bypass the SDK with handcrafted HTTP requests.

## First Run

1. Install dependencies with `composer install`.
2. Export the env vars referenced by the profile you plan to use. The committed template defaults to `CREEM_TEST_API_KEY` and `CREEM_TEST_WEBHOOK_SECRET`.
3. Inspect the available operations with `php playground/run.php --list`.
4. Inspect one operation contract with `php playground/run.php --describe <resource>/<action>`.
5. Run an operation. The first run auto-creates ignored `playground/state.local.json` from committed `playground/state.example.json`.

## Discovery Commands

List every supported outbound operation:

```bash
php playground/run.php --list
```

Describe one operation, including inputs, defaults, required values, persisted outputs, and schema paths:

```bash
php playground/run.php --describe products/create
```

Audit the committed action definitions against the current public SDK surface:

```bash
php playground/run.php --audit
```

All three commands emit JSON to stdout.

## Run Contract

Outbound runs accept one JSON envelope with three top-level keys:

- `profile`
- `allow_write`
- `values`

Provide that envelope through `--input-file`:

```bash
php playground/run.php stats/summary --input-file /tmp/stats-summary.json
```

Example read envelope:

```json
{
  "profile": "default",
  "values": {
    "stats": {
      "summary": {
        "currency": "USD",
        "startDate": "2026-03-01T00:00:00+00:00",
        "endDate": "2026-03-31T23:59:59+00:00",
        "interval": "month"
      }
    }
  }
}
```

When `stats.summary.interval` is set, the live API also requires `startDate` and `endDate`.

Or pipe the same envelope through stdin:

```bash
cat /tmp/products-create.json | php playground/run.php products/create
```

Example write envelope:

```json
{
  "profile": "cashier",
  "allow_write": true,
  "values": {
    "products": {
      "create": {
        "name": "SDK Harness Product"
      }
    }
  }
}
```

Write-capable operations are blocked unless `allow_write` is explicitly `true`.

If you omit input entirely, the runner uses an empty envelope and falls back to operation defaults plus local state.

## State

`playground/state.example.json` is the committed sanitized template.

`playground/state.local.json` is ignored, auto-created on first run, and safe to edit locally for reusable non-sensitive IDs and profile metadata.

Rules:

- Secrets stay env-driven only. Store env var names under `profiles.<name>.apiKeyEnv` and `profiles.<name>.webhookSecretEnv`, never raw secrets.
- `shared.activeProfile` is the fallback outbound profile when the input envelope omits `profile`.
- `webhookRoutes` maps inbound webhook paths to profile names.
- Successful runs persist only declared `persist_outputs` mappings back into `state.local.json`.
- Action-local request defaults stay in the action definition files, not in local state.

Run-time merge order is:

1. base playground values
2. action-local `defaults`
3. `playground/state.local.json`
4. input envelope `values`

After the merge, profile-derived shared credentials such as `shared.apiKey` are resolved from env vars.

## Output Contract

Operation runs print one JSON object to stdout with:

- `ok`
- `kind`
- `operation`
- `operation_mode`
- `profile`
- `sdk_call`
- `request`
- `example_response`
- `live_response`
- `transport`
- `state_changes`
- `error`

Success and failure use the same envelope. Failures exit non-zero.

`request` contains:

- `method`
- `path`
- `idempotency_key`
- `inputs`
- `payload`

## Schemas

Committed machine-readable schemas live under `playground/schemas/`:

- `playground/schemas/run-input.schema.json`
- `playground/schemas/run-output.schema.json`
- `playground/schemas/operation-describe.schema.json`

Use `--describe` first when an agent needs the exact input field list, defaults, write mode, or persisted output mappings for one operation.

## Webhook Commands

Inbound webhook tooling remains separate from `php playground/run.php`.

Start the local receiver:

```bash
php -S 127.0.0.1:8765 playground/webhooks/receive.php
```

Check health:

```bash
curl http://127.0.0.1:8765/health
```

Inspect the latest capture:

```bash
php playground/webhooks/inspect.php --latest
```

Inspect a recent window:

```bash
php playground/webhooks/inspect.php --limit 20
```

Inspect using an explicit webhook profile override:

```bash
php playground/webhooks/inspect.php --latest --profile cashier
```

Captured webhook payloads stay ignored under `playground/captures/webhooks/`.

## Destructive Verification

Destructive verification against `Antoniadisio\Creem\Enum\Environment::Test` stays manual and should run through the committed playground harness whenever you need live SDK request and response evidence.

Guardrails:

- Use `Environment::Test` only.
- Use a dedicated test account and API key.
- Prefer unique names and idempotency keys so created resources are easy to trace.
- Capture request and response data when a contract change depends on live behavior.
- Sanitize any captured values before committing fixture updates.

Suggested flow:

- Product creation:
  Create one recurring product and one one-time product with unique names. For raw manual requests in Try It or another HTTP client, omit `billing_period` on one-time product creation. The live API currently rejects `billing_period` for one-time creates even though product responses still include `billing_period: "once"`.
- Billing portal links:
  Use a customer created from a prior checkout, call `customers()->createBillingPortalLink(...)`, and verify the returned URL opens the expected test customer portal.
- One-time checkout creation:
  Use the one-time product, call `checkouts()->create(...)` with a unique `requestId` and idempotency key, complete the checkout, and confirm the resulting customer, order, and transaction.
- Discount flows:
  Create a discount, verify `get(...)` and `getByCode(...)`, redeem it in a checkout, then call `delete(...)` and confirm the returned deleted state.
- Subscription update, upgrade, and cancel:
  Create a baseline recurring product and an upgrade target, complete a recurring checkout, then verify `update(...)`, `upgrade(...)`, and `cancel(...)`. For seat updates, prefer `price_id` and include the current subscription item `id`. Set `update_behavior` explicitly during live verification: `proration-charge-immediately` applies the unit change immediately, while `proration-charge` and `proration-none` leave the current units unchanged until the next billing boundary.
- License activate, validate, and deactivate:
  Use a license-key product, complete a checkout that yields a fresh license key, then verify `activate(...)`, `validate(...)`, and `deactivate(...)`.

Cleanup:

- Expire or delete discounts created for the run.
- Cancel subscriptions that should not remain active.
- Archive or otherwise mark temporary products so later runs can ignore them.
- Remove any local scratch scripts or raw captures that should not be committed.

## Working Rules

- Any add, remove, or behavior/signature change to an outbound SDK endpoint must be mirrored in the matching `playground/<resource>/<action>.php` definition in the same task.
- Keep `php playground/run.php --audit` clean after endpoint work.
- Keep the outbound harness on the real `Antoniadisio\Creem\Client` resource-method path.
- Keep webhook capture and inspection separate from the outbound audit surface.
