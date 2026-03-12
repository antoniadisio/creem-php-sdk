# Contributing

## Scope
This repository is a public PHP SDK. Keep changes focused on package code and contributor-facing documentation. The consumer entrypoint is the pre-1.0 `Creem\Client` facade; avoid turning internal Saloon transport classes into part of the public contract.
The public repo intentionally keeps maintainer QA files such as `rector.php`, `phpstan.neon.dist`, `phpunit.xml.dist`, and `composer.lock` committed at the root. Lean package archives for SDK consumers are handled with `.gitattributes export-ignore`, not by removing those repo files from git.

## Local Setup
- Run `composer install` to install PHP dependencies.
- Use PHP 8.4+.
- Read `README.md` before changing the public API surface.

## Development Workflow
- Keep source changes under `src/` and add matching deterministic tests under `tests/Unit/` and `tests/Integration/` as needed.
- Update response fixtures in `tests/Fixtures/Responses/` when payload shapes change.
- Keep OpenAPI contract work aligned with `tests/Fixtures/OpenApi/creem-openapi.json`.
- Do not commit local-only planning files or machine-specific files such as `.env`, `.test-state/`, `plan/`, `PROJECT_DESCRIPTION.md`, personal local workflow files, `vendor/`, or IDE settings.
- Keep maintainer-only repo files committed only when they support contributor workflows or CI, and mark files that installed SDK consumers do not need with `.gitattributes export-ignore`.

## Validation
Run these commands locally:

- `composer qa` after each completed task, and keep fixing until the full local QA flow passes.
- `composer qa:check` before opening a pull request.
- `composer test` when you only need the fast `Unit` suite during iteration.
- `composer test:integration` when you need deterministic mocked transport coverage only.
- `composer cs` to verify formatting.
- `composer cs:fix` to apply formatting fixes.
- `composer stan` to run static analysis on `src` and `tests`.

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
- Contract violations on required response fields should fail fast with `Creem\Exception\HydrationException`; do not silently coerce malformed required values to `null`.

## Commits And Pull Requests
- Write commit subjects in an imperative, outcome-focused style, 72 characters or fewer, with no trailing period.
- Keep the tone concise and direct, focused on what changed for the repository or SDK user.
- Add a `CHANGELOG.md` entry for the next major release when you ship breaking public API changes.
- In pull requests, describe the user-visible impact, list the validation commands you ran, and link the relevant issue when one exists.

## Release Process
- Run `composer validate --strict`.
- Run `composer qa:check`.
- Update `CHANGELOG.md` with the exact release version and date.
- Create an annotated Git tag for that version (for example `git tag -a v0.1.0 -m "Release v0.1.0"`).
- Push the tag and publish matching GitHub release notes.
- Keep the Git tag, GitHub release title, and `CHANGELOG.md` entry identical.

## Security Reporting
- Do not file public GitHub issues for vulnerabilities.
- Follow `SECURITY.md` and use the repository security reporting path for sensitive disclosures.
