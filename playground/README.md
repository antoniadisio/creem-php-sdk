# Live Harness

`playground/` is a committed contributor harness for real requests against `Antoniadisio\Creem\Enum\Environment::Test` and for local webhook capture/inspection during live test-environment verification.

It is agent-first. The CLI remains because agents need a deterministic executable boundary with stable stdout, exit codes, and repeatable inputs.

The harness still exercises the real SDK path:

1. instantiate `Antoniadisio\Creem\Client`
2. call the public resource method
3. let the SDK build the Saloon request
4. send the real HTTP call
5. capture both raw transport data and normalized SDK output

It does not bypass the SDK with handcrafted HTTP requests.

## Before You Run Anything

1. Install dependencies with `composer install`.
2. Export the env vars referenced by your active profile in `playground/state.json`. The default profile uses `CREEM_TEST_API_KEY` and `CREEM_TEST_WEBHOOK_SECRET`.
3. Seed or review non-sensitive runtime values in `playground/state.json`.
4. Use `--set` for one-off overrides and `--overrides-file` only for complex nested overrides.
5. Add `--allow-write` only when the user explicitly wants a mutating live request.
6. Add named profiles and `webhookRoutes` in `playground/state.json` when you need more than one API key or webhook secret.

## Commands

Audit SDK-to-harness parity:

```bash
php playground/run.php --audit
```

Run a read operation:

```bash
php playground/run.php stats/summary
```

Run a read operation with an explicit named profile:

```bash
php playground/run.php stats/summary --profile cashier
```

Run a read operation with an ephemeral ID override:

```bash
php playground/run.php checkouts/get --set shared.checkoutId=ch_XJCn5WrHjMpUqFDCgvJFl
```

Run a write operation:

```bash
php playground/run.php products/create --allow-write --set products.create.name="SDK Harness Product"
```

Run with a nested override file:

```bash
php playground/run.php subscriptions/update --allow-write --overrides-file /tmp/playground-update.json
```

Show usage:

```bash
php playground/run.php --help
```

JSON output is the default. There is no separate human-mode runner.

## Webhook Commands

Start the local webhook receiver with PHP's built-in server:

```bash
php -S 127.0.0.1:8765 playground/webhooks/receive.php
```

Check the receiver health:

```bash
curl http://127.0.0.1:8765/health
```

Inspect the latest captured webhook:

```bash
php playground/webhooks/inspect.php --latest
```

Inspect a recent capture window:

```bash
php playground/webhooks/inspect.php --limit 20
```

Inspect a capture with an explicit profile override:

```bash
php playground/webhooks/inspect.php --latest --profile cashier
```

The webhook receiver is intentionally separate from `php playground/run.php`. Inbound webhook capture is not part of the outbound SDK method audit surface.

## State And Overrides

`playground/state.json` is the machine-managed local state store.

Rules:

- Credentials are profile-driven. `shared.apiKey`, `shared.baseUrl`, `shared.timeout`, and `shared.userAgentSuffix` are derived from the selected profile.
- The default synthesized profile reads `CREEM_TEST_API_KEY` and `CREEM_TEST_WEBHOOK_SECRET`.
- Additional profiles should store env var names, not raw secrets, under `profiles.<name>.apiKeyEnv` and `profiles.<name>.webhookSecretEnv`.
- `shared.activeProfile` selects the outbound profile when `--profile` is not provided.
- `webhookRoutes` maps inbound request paths to profile names; exact path match wins, then the receiver falls back to `shared.activeProfile`.
- `state.json` stores reusable runtime values such as IDs, codes, and license instance state.
- Action-local request defaults live in the action definition files, not in `state.json`.
- `--set path=value` never persists.
- `--overrides-file` never persists.
- Successful runs may persist declared `saved_state` values back into `state.json`.

Example profile state:

```json
{
  "shared": {
    "activeProfile": "default"
  },
  "profiles": {
    "default": {
      "environment": "test",
      "apiKeyEnv": "CREEM_TEST_API_KEY",
      "webhookSecretEnv": "CREEM_TEST_WEBHOOK_SECRET",
      "timeout": 30,
      "userAgentSuffix": "playground/agent"
    },
    "cashier": {
      "environment": "test",
      "apiKeyEnv": "CREEM_CASHIER_API_KEY",
      "webhookSecretEnv": "CREEM_CASHIER_WEBHOOK_SECRET",
      "timeout": 30,
      "userAgentSuffix": "playground/cashier"
    }
  },
  "webhookRoutes": {
    "/": "default",
    "/creem/webhook": "cashier"
  }
}
```

Merge order:

1. base runtime values from the environment
2. action-local `defaults`
3. `playground/state.json`
4. `--overrides-file`
5. `--set`

## Output Contract

Successful runs print one JSON object to stdout with:

- `ok`
- `operation`
- `operation_mode`
- `sdk_call`
- `method`
- `path`
- `idempotency_key`
- `inputs`
- `request_payload`
- `example_response`
- `transport`
- `live_response`
- `saved_state`
- `error`

`transport` contains redacted raw request/response evidence captured from Saloon middleware after the SDK has built the request:

- `transport.request.url`
- `transport.request.headers`
- `transport.request.body`
- `transport.response.status_code`
- `transport.response.headers`
- `transport.response.body`

Failures keep the same JSON envelope and exit non-zero.

Webhook capture files are stored locally under `playground/captures/webhooks/`. They are ignored and must never be copied into committed fixtures.

## Action Contract

Each `playground/<resource>/<action>.php` file returns one associative array with:

- `resource`
- `action`
- `operation_mode`
- `sdk_call`
- `http_method`
- `path`
- `fixtures`
- `required_values`
- `defaults`
- `inputs`
- `idempotency_key_path`
- `persist_outputs`
- `build_inputs`
- `build_request_payload`
- `run`

Write-capable operations must:

- declare `operation_mode` as `write`
- expose an idempotency key input
- declare `idempotency_key_path`
- require `--allow-write`

If a write action does not receive an idempotency key, the harness generates one and returns it in `idempotency_key`.

## Webhook Workflow

Use the webhook receiver when you need live inbound evidence:

1. Start `php -S 127.0.0.1:8765 playground/webhooks/receive.php`.
2. Expose that local port with your tunnel of choice.
3. Register the public URL in Creem test webhooks and map each path to the intended profile in `webhookRoutes`.
4. Trigger a live test event with the outbound harness.
5. Run `php playground/webhooks/inspect.php --latest` to inspect the resolved profile, verification status, event type, and extracted `mode` paths.

The receiver always stores the raw headers and payload first. It resolves the intended profile from `webhookRoutes` or `shared.activeProfile`, then attempts verification through the real `Antoniadisio\Creem\Webhook::constructEventForProfile(...)` helper when that profile's webhook secret env var is configured.

## Working Rules

- Any add, remove, or behavior/signature change to an outbound SDK endpoint must be mirrored in the matching `playground/` action definition in the same task.
- Use `php playground/run.php --audit` after endpoint work to catch missing or orphaned actions.
- Use `--profile <name>` when you need to drive a non-default outbound credential set.
- Prefer `--set` for agent-driven one-off checks.
- Use `--overrides-file` only when a scalar `--set` override is not enough.
- Keep runtime artifacts local-only through the ignored `playground/` runtime files.
