# Creem PHP SDK Implementation Plan

## Summary

Build a production-grade PHP SDK for `creem.io` from `creem-openapi.json`, using Fern as the primary generator for both SDK code and API-reference docs, while staying compatible with Fern's free-tier constraints.

This repository currently contains only the OpenAPI spec and is not yet initialized as a local Git repository. The implementation must therefore begin by initializing Git locally, connecting to `https://github.com/antoniadisio/creem-php-sdk`, and then establishing a repeatable generation workflow.

### Scope Boundary

This SDK is responsible for:

- authenticating outbound API calls to Creem
- sending requests to Creem
- deserializing API responses into typed PHP objects
- exposing a stable, framework-agnostic PHP API for downstream package authors
- normalizing transport and API errors into typed exceptions
- providing generated API reference documentation through Fern

This SDK is explicitly not responsible for:

- receiving inbound webhooks from Creem
- verifying webhook signatures
- parsing framework request objects
- implementing Laravel/Symfony/controller middleware concerns

Webhook handling belongs in downstream integration packages (for example, a Laravel package) because that logic is inbound HTTP and framework-adjacent, not part of the core outbound API client. Since the current OpenAPI spec does not include webhooks, Fern will not generate webhook support.

The plan below assumes:

- distribution target: Git tags and Packagist readiness now
- docs mode: Fern local-first only
- minimum PHP version: `8.1`
- SDK license: `MIT`
- webhooks: out of scope for this SDK

The current OpenAPI spec describes:

- OpenAPI `3.0.0`
- 20 paths
- 23 operations
- 61 schemas
- API key auth via `x-api-key`
- two server URLs: production and test

## Public APIs, Interfaces, and Package Defaults

The implementation should standardize on these public package decisions:

- Composer package name: `antoniadisio/creem-php-sdk`
- root PHP namespace: `Creem\\`
- stable handwritten entrypoint: `Creem\Client`
- stable handwritten configuration value object: `Creem\Config`
- stable handwritten environment enum/value object: `Creem\Environment`
- stable handwritten exception hierarchy rooted at `Creem\Exception\CreemException`

The generated Fern SDK should remain the canonical transport/model layer, but consumers should interact through the stable handwritten facade so regeneration does not become a breaking-change surface.

`Creem\Client` should expose:

- constructor accepting `apiKey`, `environment`, and optional transport/config overrides
- named constructors for production and test environments if practical
- accessors or service properties for each API domain:
  - products
  - customers
  - subscriptions
  - checkouts
  - licenses
  - discounts
  - transactions
  - stats

`Creem\Config` should carry:

- API key
- environment (`production` => `https://api.creem.io`, `test` => `https://test-api.creem.io`)
- optional base URL override
- optional timeout/retry knobs if Fern output supports transport customization
- optional user-agent suffix for downstream packages

The stable exception layer should normalize:

- authentication failures
- validation/client errors
- not found errors
- rate-limit/server errors
- transport failures

### DTO Strategy

Response and request DTOs are part of the PHP SDK.

- Preferred path: use Fern-generated model classes as the SDK DTO layer if Fern generates clean, typed, consumer-usable PHP models.
- Fallback path: if Fern does not generate DTOs, or generates weak/awkward models, add handwritten DTOs under `src/Dto/` and map generated or raw responses into those DTOs before returning them from the stable client API.

Do not duplicate every DTO by default. Only add handwritten DTOs when the generated type surface is missing or not acceptable as a stable consumer-facing contract.

## Repository Layout to Create

Use a single-repo layout that keeps Fern config, the source OpenAPI spec, generated code, handwritten wrappers, and docs sources together:

- `fern/`
- `fern/definition/`
- `fern/definition/openapi/`
- `fern/definition/openapi/creem-openapi.json`
- `fern/fern.config.json`
- `fern/generators.yml`
- `src/`
- `src/Client.php`
- `src/Config.php`
- `src/Environment.php`
- `src/Dto/`
- `src/Exception/`
- `generated/`
- `generated/php-sdk/`
- `tests/`
- `tests/Unit/`
- `tests/Integration/`
- `tests/Fixtures/`
- `docs/`
- `docs/README.md`
- `.github/workflows/`
- `IMPLEMENTATION_PLAN.md`

Keep generated Fern PHP output under `generated/php-sdk/` and all handwritten code under `src/`. Root `composer.json` should autoload both `src/` and the generated SDK namespace/path as needed.

## Phase 1 - Initialize the Local Repository and Baseline Tooling

### Goal

Turn the current folder into the real local working copy for the GitHub repository and establish baseline PHP/Node tooling.

### Actions

1. Initialize Git locally in the current directory.
2. Add `origin` pointing to `https://github.com/antoniadisio/creem-php-sdk`.
3. Fetch the remote and align with the default branch if the remote already has commits.
4. If the remote is empty, create the default branch locally (`main`), then make the first commit only after initial scaffolding exists.
5. Add a root `.gitignore` covering:
   - `vendor/`
   - `node_modules/`
   - Fern local build/cache output if generated
   - IDE/system files
6. Create a root `README.md` with a temporary project description and explicit "generated with Fern + handwritten wrapper" positioning.
7. Create `LICENSE` as MIT.
8. Create root `composer.json` with:
   - package name `antoniadisio/creem-php-sdk`
   - `php:^8.1`
   - PSR-4 autoload for `Creem\\`
   - PSR-4 autoload-dev for tests
   - metadata for Packagist readiness
9. Add dev dependencies for:
   - PHPUnit
   - a coding-standard tool
   - a static-analysis tool
10. Add `package.json` only for Fern CLI management and helper scripts.
11. Install Fern CLI locally as a dev dependency instead of relying on a global install, because `fern` is not currently installed.

### Acceptance Criteria

- `git status` works in this directory
- `origin` is configured
- `composer validate` passes
- Fern CLI is runnable via local package scripts
- root metadata is present and publishable

## Phase 2 - Bring the OpenAPI Spec Under Fern Control

### Goal

Convert the existing `creem-openapi.json` into a Fern-managed API definition without losing the original source.

### Actions

1. Move or copy the current root spec into `fern/definition/openapi/creem-openapi.json`.
2. Keep the original root spec only if needed during migration; otherwise make the Fern copy the single source of truth to avoid drift.
3. Initialize Fern config in `fern/`.
4. Configure Fern to import or generate from the OpenAPI file into its definition structure.
5. Commit the generated Fern definition files that are intended to be source-controlled.
6. Document the regeneration command in both `README.md` and package scripts.
7. Add a validation script that fails if the OpenAPI source and derived Fern definition are out of sync.

### Spec Audit Tasks

Before treating generation as stable, explicitly audit the imported definition for:

- `allOf` merges
- `oneOf` unions
- nullable/optional fields
- path parameter naming consistency
- operation IDs that are awkward or misleading
- schema names that would create poor PHP type names
- pagination parameter typing (`number` vs integer-like semantics)
- endpoints that use query IDs where path IDs might have been expected

The current spec already contains `allOf` and `oneOf`, so this audit is mandatory.

### Acceptance Criteria

- Fern can validate the imported API definition locally
- the imported definition is deterministic and committed
- known spec issues are captured in `docs/spec-audit.md` or explicit TODOs

## Phase 3 - Configure Fern PHP Generation for a Repo-Friendly Output

### Goal

Make Fern generate PHP SDK code into a stable location inside this repository, without assuming paid Fern publishing features.

### Actions

1. Add `fern/generators.yml` with a PHP SDK generator using Fern's PHP generator.
2. Configure the output target to a local filesystem path under `generated/php-sdk/`.
3. Avoid any hosted registry or publish settings that require a paid tier.
4. Set package metadata in the Fern generator config only where it does not conflict with the root Composer package.
5. If Fern emits its own `composer.json`, lock one approach and keep it fixed:
   - Preferred: treat the Fern output as a generated subpackage under `generated/php-sdk/` and have the root package wrap it
   - Fallback: if Fern can emit source only, emit source only and let the root `composer.json` be authoritative
6. Add repeatable scripts:
   - `npm run fern:generate`
   - `npm run fern:check`
7. Add a guardrail script/test that detects uncommitted generated diffs after regeneration.
8. Inspect the generated output specifically for request and response model classes:
   - if Fern generates complete, typed, usable PHP models, treat them as the DTO layer
   - if Fern does not generate usable DTOs, mark manual DTO implementation as required in Phase 4

### Important Constraint

Do not make consumers depend directly on the raw generated package layout. The root package must present the stable public API, even if the generated code changes shape.

### Acceptance Criteria

- Running the local Fern generate command produces PHP SDK output under `generated/php-sdk/`
- regeneration is deterministic
- the root package can autoload the generated code
- the project has a documented decision on whether Fern-generated models are sufficient as the DTO layer

## Phase 4 - Add the Stable Handwritten PHP Facade

### Goal

Wrap the generated SDK in a consumer-friendly, production-grade API surface suitable for downstream package authors.

### Actions

1. Implement `Creem\Environment` as a small enum or immutable value object for `production` and `test`.
2. Implement `Creem\Config` as the normalized configuration holder.
3. Implement `Creem\Client` as the stable facade over the generated Fern client.
4. Expose service group accessors for:
   - products
   - customers
   - subscriptions
   - checkouts
   - licenses
   - discounts
   - transactions
   - stats
5. Normalize configuration so consumers do not need to know Fern internals.
6. Add consistent user-agent construction, including:
   - SDK name/version
   - optional consumer suffix
7. Add exception translation from generated/transport exceptions into the stable `Creem\Exception\*` hierarchy.
8. Document how downstream packages can inject their own HTTP client or configuration if the generated layer supports it.
9. Keep all handwritten code regeneration-safe by ensuring it lives outside the generated folder.
10. If Fern-generated DTOs are missing or not acceptable, implement handwritten request/response DTOs in `src/Dto/`.
11. If handwritten DTOs are added, map generated objects or raw decoded payloads into the stable DTOs before returning values from `Creem\Client`.

### Behavioral Rules

- Default environment must be `production`
- `test` must target `https://test-api.creem.io`
- API key must be required for any client instantiation
- errors should fail loudly and predictably with typed exceptions
- no live network calls should be required for unit tests

### Acceptance Criteria

- Consumers can instantiate a single stable client without touching generated classes
- generated internals are hidden behind stable wrappers
- regeneration does not overwrite handwritten code
- consumers receive typed request/response objects either from Fern-generated models or handwritten DTOs

## Phase 5 - Testing Strategy and Test Implementation

### Goal

Provide production-grade confidence without making CI depend on live Creem credentials.

### Test Framework and Standards

Use:

- `PHPUnit` as the test runner
- static analysis at a strict-but-practical baseline
- coding standards in CI
- mutation testing only later if the base suite is stable

### Required Test Layers

1. Unit tests for stable handwritten API:
   - `Config` validation
   - environment resolution
   - API key enforcement
   - base URL selection
   - exception mapping
   - user-agent formatting

2. Wrapper behavior tests:
   - service accessor wiring
   - forwarding of parameters to generated operations
   - translation of generated responses into expected return types
   - translation of generated exceptions into stable exceptions

3. DTO tests:
   - if using Fern-generated models, smoke-test their construction, hydration, and serialization
   - if using handwritten DTOs, verify mapping from generated/raw payloads into DTOs
   - verify optional, nullable, and union-like fields from the OpenAPI spec are represented correctly

4. Generated code smoke tests:
   - generated classes autoload
   - selected model hydration/serialization works
   - representative operations can be instantiated/called through mocks

5. Fixture-driven HTTP contract tests:
   - use stored JSON fixtures under `tests/Fixtures/`
   - mock HTTP responses for key endpoints across all major service groups
   - include success and failure cases
   - cover pagination/search endpoints
   - cover mutation endpoints with representative payloads

6. Optional opt-in live integration tests:
   - disabled by default
   - only run when explicit environment variables are present
   - use test-environment keys against `test-api.creem.io`
   - never required for default CI

### Minimum Endpoint Coverage

At least one happy-path test per domain:

- products
- customers
- subscriptions
- checkouts
- licenses
- discounts
- transactions
- stats

At least one error-path test for:

- 401 unauthorized
- 404 not found
- generic 4xx validation failure
- generic 5xx server failure

### Acceptance Criteria

- Full local test suite passes without external credentials
- CI can run tests deterministically
- live integration tests are clearly isolated and non-blocking by default

## Phase 6 - Fern Docs Setup (Local-First)

### Goal

Generate and maintain docs with Fern while staying within a free-tier-safe workflow.

### Actions

1. Add Fern docs configuration in the `fern/` workspace.
2. Use Fern docs generation for local preview and committed source configuration only.
3. Do not assume paid hosted docs publishing is available.
4. Add local docs scripts:
   - `npm run fern:docs:dev`
   - `npm run fern:docs:check`
5. Create a short landing page in `docs/README.md` that explains:
   - what the SDK is
   - how to install it
   - how to authenticate
   - how to switch environments
   - where generated API reference comes from
6. Ensure docs content points to the stable `Creem\Client` API first, then generated API reference second.
7. If Fern supports example snippets for PHP from the imported definition, include representative examples for:
   - client construction
   - retrieving a product
   - creating a checkout
   - retrieving a subscription
8. Keep docs source in git so future hosted publishing is a config-only change.

### Acceptance Criteria

- Docs can be previewed locally with Fern
- docs source is committed
- no phase depends on paid Fern hosting

## Phase 7 - CI, Release Flow, and Packagist Readiness

### Goal

Make the repo safe to publish and easy to maintain.

### Actions

1. Add GitHub Actions workflows for:
   - dependency install
   - Fern validation
   - Fern generation drift check
   - PHPUnit
   - static analysis
   - coding standards
2. Add a PHP version matrix that includes:
   - 8.1
   - latest supported current version
3. Keep docs preview checks local-only unless Fern offers a free CI-safe validation command that does not require hosted publishing.
4. Define release rules:
   - SemVer tags from GitHub
   - generate code before tagging
   - fail release if generated artifacts are stale
5. Prepare Packagist readiness:
   - valid Composer metadata
   - stable default branch
   - tagged releases
   - no path-based assumptions that break consumer installs
6. If Packagist auto-update is desired later, treat webhook registration with Packagist as a manual post-setup task, not a blocker for code delivery.

### Acceptance Criteria

- CI validates source, generation, and tests on every PR
- a tagged release can be consumed by Composer
- no release step depends on paid Fern infrastructure

## Phase 8 - Documentation for Contributors and Regeneration Workflow

### Goal

Make the repo maintainable for repeated regeneration and future spec updates.

### Actions

1. Expand `README.md` to include:
   - project purpose
   - install instructions
   - quickstart usage
   - environments
   - test commands
   - regeneration commands
2. Add `CONTRIBUTING.md` with:
   - how to update the OpenAPI spec
   - how to re-import/regenerate via Fern
   - where handwritten code must live
   - how to run the test suite
3. Add a "do not edit generated files manually" note.
4. Add a changelog strategy:
   - maintain `CHANGELOG.md` manually or with conventional releases
   - document that spec-driven breaking changes require SemVer major bumps
5. Add a short maintainers' note describing which custom layer is considered the stable public API.

### Acceptance Criteria

- A new contributor can regenerate the SDK without guesswork
- the boundary between generated and handwritten code is explicit
- future spec updates have a documented path

## Test Cases and Scenarios

The implementation must include, at minimum, these scenarios:

- Create client with valid production config
- create client with valid test config
- reject client creation with empty API key
- confirm production/test base URL selection
- confirm `x-api-key` auth is attached
- retrieve a product through the stable client
- search products with pagination parameters
- retrieve a customer
- generate customer billing links
- retrieve and update a subscription
- cancel, pause, resume, and upgrade a subscription
- create and retrieve a checkout
- activate, deactivate, and validate a license
- create, retrieve, and delete a discount
- retrieve a transaction and search transactions
- retrieve stats summary
- map 401/404/4xx/5xx responses into stable exceptions
- verify regeneration drift detection fails when generated code is stale
- verify docs config validates locally
- verify the chosen DTO strategy is covered by tests

## Explicit Assumptions and Defaults

- Use Fern locally via a project dev dependency, not a global install.
- Use Fern for both SDK generation and docs configuration, but keep docs local-first to avoid free-tier limits.
- Treat hosted Fern publishing as out of scope for the first implementation pass.
- Use a stable handwritten facade because the raw generated surface is not a safe long-term public API.
- Keep generated code committed so consumers and CI do not need Fern to install the package.
- Make live API integration tests opt-in only.
- Use `MIT`.
- Use `PHPUnit`.
- If Fern's PHP generator emits a conflicting package scaffold, the root repository package remains authoritative and the generated SDK is treated as an internal implementation dependency.
- Webhooks are intentionally out of scope for this SDK because inbound webhook handling belongs in downstream integration packages, not the core API client.
- DTOs are part of the SDK contract. Prefer Fern-generated models, but implement handwritten DTOs if Fern does not generate usable ones.
