# Creem PHP SDK Implementation Plan

## Summary

Restart the SDK implementation as a handwritten, Saloon-based PHP package and remove the current Fern-first generation workflow before more code is built on top of it.

This restart keeps the OpenAPI contract as the source of truth, but the SDK itself becomes a curated PHP codebase with:

- a stable public `Creem\Client` facade
- typed handwritten DTOs
- typed handwritten exceptions
- Saloon used internally for HTTP transport only

The current repository already has the baseline package scaffold in place, but the actual SDK has not been implemented yet. That makes this the right time to pivot without carrying forward a generator-first architecture.

## Scope Boundary

This SDK is responsible for:

- authenticating outbound API calls to Creem
- sending requests to Creem
- deserializing API responses into typed PHP objects
- exposing a stable, framework-agnostic PHP API for downstream package authors
- normalizing API and transport failures into typed exceptions
- keeping the committed OpenAPI document aligned with the implemented SDK surface

This SDK is explicitly not responsible for:

- receiving inbound webhooks
- verifying webhook signatures
- parsing framework request objects
- shipping framework-specific middleware
- generating hosted API docs as part of the first implementation pass

## Current State

These tasks are already complete and should remain complete after the restart:

- [x] Local Git repository is initialized
- [x] `origin` points to `https://github.com/antoniadisio/creem-php-sdk`
- [x] Root package metadata exists in `composer.json`
- [x] `composer validate --no-check-publish` passes
- [x] Baseline tooling includes PHPUnit, Pint, and PHPStan
- [x] `README.md`, `LICENSE`, and `phpunit.xml.dist` are present

These items exist now but are part of the old direction and must be removed or replaced:

- [x] Fern workspace and derived definition files
- [x] Node-based Fern scripts and package manifests
- [x] Fern-specific smoke tests
- [x] Fern-first documentation and planning language

## Public API And Package Defaults

The SDK should standardize on these public package decisions:

- Composer package: `antoniadisio/creem-php-sdk`
- Minimum PHP version: `^8.1`
- Root namespace: `Creem\\`
- Stable entrypoint: `Creem\Client`
- Stable configuration object: `Creem\Config`
- Stable environment enum: `Creem\Environment`
- Stable exception root: `Creem\Exception\CreemException`

### Public API Rules

- Consumers use `Creem\Client` as the only documented SDK entrypoint.
- Consumers should not instantiate Saloon requests directly.
- Saloon classes are internal implementation details and are not part of the public contract.
- Public methods return typed handwritten DTOs or typed page DTOs, not raw arrays.

### Resource Accessors

`Creem\Client` should expose:

- `products()`
- `customers()`
- `subscriptions()`
- `checkouts()`
- `licenses()`
- `discounts()`
- `transactions()`
- `stats()`

### Method Naming Rules

- Retrieval by ID uses `get(string $id)`, even if the upstream API uses query parameters.
- Collection endpoints use `list(...)`.
- Filtered collection endpoints use `search(...)`.
- Mutations use `create(...)`, `update(...)`, and `delete(...)`.
- Delete methods return `void` unless the API returns meaningful structured data that must be preserved.

## Internal Architecture

### Saloon Usage

Use `saloonphp/saloon` as the internal HTTP foundation.

- One internal connector at `src/Internal/Http/CreemConnector.php`
- Internal Saloon request classes under `src/Internal/Http/Requests/`
- Public resource classes under `src/Resource/`
- DTO hydration under `src/Internal/Hydration/`

### Configuration Rules

`Creem\Config` should carry:

- API key
- environment (`production` or `test`)
- optional base URL override
- optional request timeout
- optional user-agent suffix

Base URLs:

- production: `https://api.creem.io`
- test: `https://test-api.creem.io`

### Exception Rules

Implement this exception hierarchy:

- `Creem\Exception\CreemException`
- `Creem\Exception\AuthenticationException`
- `Creem\Exception\ValidationException`
- `Creem\Exception\NotFoundException`
- `Creem\Exception\RateLimitException`
- `Creem\Exception\ServerException`
- `Creem\Exception\TransportException`

Map failures as follows:

- `401` and `403` -> `AuthenticationException`
- `404` -> `NotFoundException`
- `422` and validation-style client payloads -> `ValidationException`
- `429` -> `RateLimitException`
- `5xx` -> `ServerException`
- network failures, timeouts, and decode failures -> `TransportException`

## Repository Layout

Target layout after the restart:

- `spec/creem-openapi.json`
- `docs/openapi-audit.md`
- `src/Client.php`
- `src/Config.php`
- `src/Environment.php`
- `src/Resource/`
- `src/Dto/`
- `src/Exception/`
- `src/Internal/Http/CreemConnector.php`
- `src/Internal/Http/Requests/`
- `src/Internal/Hydration/`
- `tests/Unit/`
- `tests/Contract/`
- `tests/Fixtures/Responses/`

## Model Guidance By Phase

Use these model settings per phase so you can switch sessions cleanly:

- Phase 1: `GPT-5.3-Codex` with `xhigh`
- Phase 2: `GPT-5.3-Codex` with `xhigh`
- Phase 3: `GPT-5.3-Codex` with `xhigh`
- Phase 4: `GPT-5.3-Codex` with `high`
- Phase 5: `GPT-5.3-Codex` with `high`
- Phase 6: `gpt-5` or `GPT-5.3-Codex` with `medium`

Reasoning defaults:

- use `xhigh` for architecture, repo resets, cross-cutting abstractions, and spec-to-code mapping
- use `high` for steady implementation across many request/resource/DTO files
- use `medium` for documentation cleanup and low-risk polish

## Phase 1 - Replace The Fern-First Baseline

**Recommended model:** `GPT-5.3-Codex` with `xhigh`

### Goal

Replace the existing plan and tooling assumptions so the repository is clearly Saloon-first.

### Tasks

- [x] Confirm the repo baseline still relevant to the restart (`git`, `origin`, Composer package metadata)
- [x] Confirm `composer validate --no-check-publish` passes before the reset
- [x] Reassess the existing architecture and decide to pivot before PHP SDK code exists
- [x] Replace `IMPLEMENTATION_PLAN.md` with a Saloon-first implementation plan
- [x] Rewrite `README.md` so it no longer describes a Fern-generated architecture
- [x] Remove Fern-specific language from package descriptions and project docs
- [x] Add `saloonphp/saloon` to the implementation dependency plan in `composer.json`

### Acceptance Criteria

- [x] The implementation plan reflects a Saloon-first restart
- [x] The repository no longer documents Fern as the target architecture
- [x] The package metadata clearly aligns with the Saloon-first direction

## Phase 2 - Preserve The OpenAPI Contract And Remove Fern

**Recommended model:** `GPT-5.3-Codex` with `xhigh`

### Goal

Keep the OpenAPI file as the source of truth, but remove Fern and Node from the active workflow.

### Tasks

- [x] Move `fern/definition/openapi/creem-openapi.json` to `spec/creem-openapi.json`
- [x] Create the `spec/` directory as the canonical contract location
- [x] Rename or rewrite `docs/spec-audit.md` to `docs/openapi-audit.md`
- [x] Update the audit document to point at `spec/creem-openapi.json`
- [x] Remove `.fern/`
- [x] Remove `fern/`
- [x] Remove `package.json`
- [x] Remove `package-lock.json`
- [x] Remove `scripts/run-fern.mjs`
- [x] Remove `scripts/check-fern-definition-sync.mjs`
- [x] Replace the Fern-specific smoke test with a contract-layout smoke test for the new source of truth
- [x] Remove any remaining Fern references from `README.md`, tests, and docs

### Acceptance Criteria

- [x] The OpenAPI source of truth lives at `spec/creem-openapi.json`
- [x] No active build or test workflow depends on Fern
- [x] No active build or test workflow depends on Node
- [x] The audit document still captures the spec risks that affect the PHP SDK

## Phase 3 - Build The Core SDK Foundation

**Recommended model:** `GPT-5.3-Codex` with `xhigh`

### Goal

Implement the reusable cross-cutting SDK foundation before building endpoint coverage.

### Tasks

- [x] Add `saloonphp/saloon` as a runtime dependency
- [x] Implement `Creem\Environment` as a backed enum
- [x] Implement `Creem\Config` as an immutable configuration object
- [x] Implement `Creem\Exception\CreemException`
- [x] Implement the full public exception hierarchy
- [x] Implement `src/Internal/Http/CreemConnector.php`
- [x] Configure default JSON headers
- [x] Configure `x-api-key` authentication
- [x] Configure environment-specific base URLs with override support
- [x] Add stable SDK user-agent construction
- [x] Implement a shared response decoder
- [x] Implement a shared exception mapper
- [x] Implement `Creem\Client` as the stable public facade
- [x] Add public resource accessor methods on `Creem\Client`

### Acceptance Criteria

- [x] Consumers can instantiate `Creem\Client`
- [x] The connector uses the correct base URL and authentication header
- [x] HTTP failures map to typed exceptions
- [x] Saloon remains internal to the implementation

## Phase 4 - Implement Resources, Requests, And DTOs

**Recommended model:** `GPT-5.3-Codex` with `high`

### Goal

Implement each API domain with a stable public surface and full typed mapping.

### Resource Order

1. Products
2. Customers
3. Checkouts
4. Subscriptions
5. Licenses
6. Discounts
7. Transactions
8. Stats

### Tasks Per Resource

- [x] Add internal Saloon request classes for every operation in the domain
- [x] Add public resource methods with clean SDK naming
- [x] Add public request DTOs for create, update, and search flows
- [x] Add public response DTOs for single-entity responses
- [x] Add public page DTOs for list and search responses
- [x] Add hydration logic from decoded payloads into DTOs
- [x] Normalize awkward upstream paths into consistent SDK methods

### Global Acceptance Criteria

- [x] All 23 current API operations are represented by internal request classes
- [x] All public methods return typed DTOs or typed page DTOs
- [x] Query-parameter retrieval endpoints still present clean `get($id)` methods
- [x] Action-style delete endpoints still present clean `delete($id)` methods
- [x] Nullable and union-like payloads are handled without exposing raw arrays

## Phase 5 - Add Contract Tests And Guardrails

**Recommended model:** `GPT-5.3-Codex` with `high`

### Goal

Replace the temporary Phase 2 layout smoke test with explicit spec coverage and behavioral tests.

Some low-risk foundation tests were pulled into Phase 3 so the core SDK pieces could be validated as they landed.

### Tasks

- [ ] Replace the temporary contract-layout smoke test with behavior-focused coverage
- [x] Add unit tests for `Environment`
- [x] Add unit tests for `Config`
- [x] Add unit tests for exception mapping
- [x] Add tests for user-agent and auth header behavior
- [x] Add request/response tests for each resource method
- [ ] Add response fixtures under `tests/Fixtures/Responses/`
- [ ] Add contract tests that parse `spec/creem-openapi.json`
- [ ] Add contract tests that verify every OpenAPI operation is mapped to SDK coverage
- [ ] Fail tests when the spec changes without corresponding SDK coverage updates

### Acceptance Criteria

- [ ] Test coverage protects the public contract, not just file existence
- [ ] The spec is validated by PHP-side tests
- [ ] SDK coverage gaps are caught automatically when the spec changes

## Phase 6 - Final Documentation And Release Readiness

**Recommended model:** `gpt-5` or `GPT-5.3-Codex` with `medium`

### Goal

Document the finished SDK and prepare it for normal package release flow.

### Tasks

- [ ] Rewrite `README.md` with Saloon-based installation and usage examples
- [ ] Document configuration, environments, and error handling
- [ ] Document one representative example per major resource group
- [ ] Document how the OpenAPI contract is maintained in `spec/`
- [ ] Remove stale references to Fern, generated docs, and Node scripts
- [ ] Verify the package is Packagist-ready
- [ ] Add release notes guidance for future spec-driven SDK updates

### Acceptance Criteria

- [ ] The README describes the actual SDK architecture
- [ ] The docs match the final public API
- [ ] No release step depends on Fern or Node tooling

## Test Scenarios

These scenarios must be covered before the restart is considered complete:

- [x] `Environment` resolves production and test URLs correctly
- [x] `Config` applies overrides correctly
- [x] The connector sends the `x-api-key` header
- [x] The connector sends the SDK user agent
- [x] Each resource method builds the correct path, method, query string, and body
- [x] `401`, `403`, `404`, `422`, `429`, and `5xx` responses map to the correct exception types
- [x] Network and timeout failures map to `TransportException`
- [x] Nullable payload fields hydrate correctly
- [x] Pagination payloads hydrate into shared page and pagination DTOs
- [x] Union-like nested payloads are normalized into stable DTO representations
- [x] Every documented OpenAPI operation has both internal request coverage and public method coverage

## Explicit Assumptions

These defaults are fixed for the current implementation pass:

- OpenAPI remains authoritative and committed
- The current OpenAPI file should be moved, not re-imported
- Saloon is an internal implementation detail
- The public SDK surface is `Creem\Client` plus typed DTOs and exceptions
- The first implementation pass is synchronous only
- Automatic retry behavior is out of scope for v1
- Automatic paginator iterators are out of scope for v1
- Webhook handling is out of scope for this package
- Hosted/generated API docs are out of scope for the restart
