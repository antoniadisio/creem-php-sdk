# Contributing

## Scope
This repository is a public unofficial PHP SDK. This document is the source of truth for contributor workflow, deterministic validation, and repository hygiene.

- Consumer-facing usage belongs in `README.md`.
- Live and destructive verification belongs in `playground/README.md`.
- Maintainer release workflow belongs in `docs/maintainers/README.md`.

Keep changes focused on package code and contributor-facing documentation. The stable public SDK surface is `Antoniadisio\Creem\Client`, `Config`, `Webhook`, `Resource\*`, `Dto\*`, `Enum\*`, and `Exception\*`. Do not turn internal Saloon transport classes into part of the public contract.

## Local Setup
- Run `composer install`.
- Use PHP 8.4 or newer.
- Read `README.md` before changing the public API surface.

## Development Workflow
- Keep source changes under `src/`.
- Keep transport, hydration, and request serialization internals under `src/Internal/`.
- Add matching deterministic tests under `tests/Unit/` and `tests/Integration/` when behavior changes.
- Keep `tests/Integration/` resource-focused, usually one resource per file.
- Keep contributor inner-loop tests in the default `Unit` flow and keep repository guardrails under the Pest `repo` group.
- Treat `tests/Unit/Contract/*`, `tests/Unit/Playground/*`, and export-policy coverage as repo guardrails rather than default contributor behavior tests.
- Update response fixtures in `tests/Fixtures/Responses/` when payload shapes change.
- Keep committed response fixtures sanitized with placeholder IDs, reserved-domain URLs, `@example.test` emails, and the canonical timestamp set already used by the fixture corpus.
- Keep OpenAPI contract work aligned with `tests/Fixtures/OpenApi/creem-openapi.json`.
- When upstream wording or enum values drift from live behavior, normalize the committed OpenAPI fixture deliberately instead of preserving stale aliases in the public SDK.
- Public signature changes in `src/Client.php`, `src/Config.php`, and `src/Resource/*` require manual review because Rector intentionally skips automatic type-declaration inference there.
- Any add, remove, behavior change, or signature change to an outbound SDK endpoint must update the matching playground action definitions, audit coverage, schemas, and `playground/README.md` in the same task.

## Style
- Use `declare(strict_types=1);`, 4-space indentation, typed properties, and clear immutable DTO-style objects.
- Prefer `final` classes unless extension is part of the design.
- Keep public DTOs, resources, and exceptions in their existing namespaces and naming patterns.
- Do not add obvious comments above methods or code blocks.
- Do not add variable docblocks unless a specific type annotation is needed.
- Add comments only when they explain non-obvious reasoning or constraints.

## Testing
This project uses Pest 4 with `Unit`, `Integration`, and `Smoke` suites.

- Put pure logic and branch coverage in `tests/Unit/`.
- Put deterministic mocked transport coverage in `tests/Integration/`.
- Keep opt-in read-only network checks in `tests/Smoke/`.
- Do not add a `Feature` suite.
- Name test files `*Test.php`.
- Keep Pest descriptions as direct behavior statements with `test('...')`.
- Prefer shared helpers in `tests/TestCase.php`, `tests/IntegrationTestCase.php`, `tests/SmokeTestCase.php`, and `tests/Support/`.
- Add or update deterministic tests for every public API change, request mapper change, exception mapping change, and new feature.
- For new features, add Pest automated tests covering happy paths, failure scenarios, invalid input, boundary conditions, and unauthorized access when that behavior exists in scope.
- Keep smoke coverage minimal and limited to the authenticated `stats()->summary(...)` canary. Keep endpoint-specific retrieval and all mutating live verification in the committed `playground/` harness instead.

## Validation
Run these commands locally as needed:

- `composer test` or `composer test:unit` for the fast contributor inner loop. Both run the `Unit` suite excluding the Pest `repo` group.
- `composer test:repo` for repository guardrails such as OpenAPI coverage, fixture policy, playground audit coverage, and export-policy checks.
- `composer test:integration` for deterministic mocked transport coverage only.
- `composer test:local` for the full deterministic suite: all `Unit` coverage including `repo`, then `Integration`.
- `composer test:smoke` for the opt-in live smoke canary against `Environment::Test`. It requires only `CREEM_TEST_API_KEY`, runs in verbose mode, skips when the key is absent, and intentionally covers only `stats()->summary(...)`.
- `composer cs` to verify formatting.
- `composer cs:fix` to apply formatting fixes.
- `composer stan` to run static analysis on `src` and `tests`.
- `composer qa` after each completed task, and keep fixing until the full local QA flow passes.
- `composer qa:check` before opening a pull request.

Keep destructive or endpoint-specific live verification out of Pest. Use `playground/README.md` for manual live calls against `Environment::Test`.

## Repository Hygiene
- Do not commit local-only planning files or machine-specific files such as `.env`, `.spec/`, `.codex/`, `vendor/`, `node_modules/`, or IDE settings.
- Keep playground runtime artifacts such as `playground/state.local.json`, `playground/captures/`, and `playground/*.local.json` local via `playground/.gitignore`.
- Keep maintainer QA files such as `rector.php`, `phpstan.neon.dist`, `phpunit.xml.dist`, and `composer.lock` committed when they support contributor workflow or CI.
- Keep `.gitattributes export-ignore` aligned with the installed package boundary. The minimal consumer archive is `src/`, `composer.json`, `README.md`, and `LICENSE`.

## Commits and Pull Requests
- Write commit subjects in an imperative, outcome-focused style, 72 characters or fewer, with no trailing period.
- Keep the tone concise and direct, focused on what changed for the repository or SDK user.
- Add a `CHANGELOG.md` entry for the next major release when you ship breaking public API changes.
- In pull requests, describe the user-visible impact, list the validation commands you ran, and link the relevant issue when one exists.
- Open pull requests only after `composer qa:check` is green.

## Security Reporting
- Do not file public GitHub issues for vulnerabilities.
- Follow `SECURITY.md` and use the repository security reporting path for sensitive disclosures.
