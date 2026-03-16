# Contributing

## Scope
This repository is a public unofficial PHP SDK. Keep changes focused on package code and contributor-facing documentation. The consumer entrypoint is the stable typed `Antoniadisio\Creem\Client` facade; avoid turning internal Saloon transport classes into part of the public contract.
The public repo intentionally keeps maintainer QA files such as `rector.php`, `phpstan.neon.dist`, `phpunit.xml.dist`, and `composer.lock` committed at the root. Lean package archives for SDK consumers are handled with `.gitattributes export-ignore`, not by removing those repo files from git. The exported consumer archive is intentionally limited to the runtime package surface: `src/`, `composer.json`, `README.md`, and `LICENSE`.

## Local Setup
- Run `composer install` to install PHP dependencies.
- Use PHP 8.4+.
- Read `README.md` before changing the public API surface.

## Development Workflow
- Keep source changes under `src/` and add matching deterministic tests under `tests/Unit/` and `tests/Integration/` as needed.
- Keep contributor inner-loop tests in the default `Unit` flow and keep repository guardrails under the Pest `repo` group. `tests/Unit/Contract/*`, `tests/Unit/Playground/*`, and export-policy coverage are repo guardrails, not default contributor behavior tests.
- Update response fixtures in `tests/Fixtures/Responses/` when payload shapes change.
- Keep committed response fixtures sanitized: use placeholder IDs, reserved-domain URLs, `@example.test` emails, and the canonical timestamp set already used by the fixture corpus.
- Keep OpenAPI contract work aligned with `tests/Fixtures/OpenApi/creem-openapi.json`.
- Keep the committed OpenAPI fixture aligned with the SDK surface the package intentionally supports; when upstream wording or enum values drift from live behavior, normalize the fixture deliberately instead of preserving stale aliases in the public SDK.
- Do not commit local-only planning files or machine-specific files such as `.env`, `.spec/`, `spec/`, `PROJECT_DESCRIPTION.md`, personal local workflow files, `vendor/`, or IDE settings.
- Keep maintainer-only repo files committed only when they support contributor workflows or CI, and mark files that installed SDK consumers do not need with `.gitattributes export-ignore`.
- Keep destructive test-environment verification out of Pest and use the committed [`playground/README.md`](playground/README.md) as the operator guide for manual live calls against `Environment::Test`. Runtime files such as `playground/state.local.json` and `playground/captures/` remain ignored locally.

## Validation
Run these commands locally:

- `composer test` or `composer test:unit` for the fast contributor inner loop. Both run the `Unit` suite excluding the Pest `repo` group.
- `composer test:repo` for repo guardrails such as OpenAPI coverage, fixture policy, playground audit coverage, and export-policy checks.
- `composer test:integration` when you need deterministic mocked transport coverage only.
- `composer test:local` when you need the full deterministic suite: all `Unit` coverage including `repo`, then `Integration`.
- `composer qa` after each completed task, and keep fixing until the full local QA flow passes.
- `composer qa:check` before opening a pull request.
- `composer test:smoke` for the opt-in live smoke canary against `Environment::Test` (requires only `CREEM_TEST_API_KEY`, runs in verbose mode for readable skipped/warning/error lines, skips when the key is absent, and intentionally covers only `stats()->summary(...)`; use the committed `playground/` harness for endpoint-specific retrieval and all mutating live validation).
- `composer cs` to verify formatting.
- `composer cs:fix` to apply formatting fixes.
- `composer stan` to run static analysis on `src` and `tests`.

Keep smoke coverage minimal, keep it as an authenticated connectivity canary only, and keep destructive or endpoint-specific live verification in the committed `playground/` harness and the manual maintainer runbook rather than automated tests or default contributor workflows.

Pull requests should be opened only after `composer qa:check` is green.

## Style
- Use `declare(strict_types=1);`, 4-space indentation, typed properties, and clear immutable DTO-style objects.
- Prefer `final` classes unless extension is part of the design.
- Keep public DTOs, resources, and exceptions in their existing namespaces and naming patterns.

## Typed API Contract Conventions
- Treat `tests/Fixtures/OpenApi/creem-openapi.json` as the source of truth for public DTO field types and contract coverage tests.
- Use enums for closed-set API fields and keep enum-to-string normalization inside the internal request serialization layer.
- Use `DateTimeImmutable` for spec-defined `format: date-time` fields and for millisecond timestamps only when the contract explicitly documents that unit.
- Keep public response DTOs concrete: use nested DTOs, typed lists, and `ExpandableResource<T>` instead of `StructuredObject`, `StructuredList`, `ExpandableValue`, or `int|float` unions.
- Contract violations on required response fields should fail fast with `Antoniadisio\Creem\Exception\HydrationException`; do not silently coerce malformed required values to `null`.

## Commits And Pull Requests
- Write commit subjects in an imperative, outcome-focused style, 72 characters or fewer, with no trailing period.
- Keep the tone concise and direct, focused on what changed for the repository or SDK user.
- Add a `CHANGELOG.md` entry for the next major release when you ship breaking public API changes.
- In pull requests, describe the user-visible impact, list the validation commands you ran, and link the relevant issue when one exists.

## Release Process
- Run `composer validate --strict`.
- Run `composer qa:check`.
- Update `CHANGELOG.md` with the exact release version and date.
- Keep release notes and installation guidance aligned with the unofficial `antoniadisio/creem-php` package identity.
- Create an annotated Git tag for that version (for example `git tag -a v0.3.0 -m "Release v0.3.0"`).
- Push the tag and publish matching GitHub release notes.
- Keep the Git tag, GitHub release title, and `CHANGELOG.md` entry identical.

## Security Reporting
- Do not file public GitHub issues for vulnerabilities.
- Follow `SECURITY.md` and use the repository security reporting path for sensitive disclosures.
