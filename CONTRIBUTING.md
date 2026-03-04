# Contributing

## Scope
This repository is a public PHP SDK. Keep changes focused on package code and contributor-facing documentation. The stable consumer entrypoint is `Creem\Client`; avoid turning internal Saloon transport classes into part of the public contract.

## Local Setup
- Run `composer install` to install PHP dependencies.
- Use PHP 8.4+.
- Read `README.md` before changing the public API surface.

## Development Workflow
- Keep source changes under `src/` and matching tests under `tests/Unit/`.
- Update response fixtures in `tests/Fixtures/Responses/` when payload shapes change.
- Keep OpenAPI contract work aligned with `spec/creem-openapi.json` and `docs/openapi-audit.md`.
- Do not commit local-only planning files or machine-specific files such as `.env`, `plan/`, `PROJECT_DESCRIPTION.md`, `vendor/`, or IDE settings.

## Validation
Run these commands locally:

- `composer test` after each completed task, and keep fixing until the Pest suite passes.
- `composer cs` to verify formatting.
- `composer cs:fix` to apply formatting fixes.
- `composer stan` to run static analysis on `src` and `tests`.

Pull requests should be opened only after `composer test`, `composer cs`, and `composer stan` are green.

## Style
- Use `declare(strict_types=1);`, 4-space indentation, typed properties, and clear immutable DTO-style objects.
- Prefer `final` classes unless extension is part of the design.
- Keep public DTOs, resources, and exceptions in their existing namespaces and naming patterns.

## Typed API Contract Conventions
- Treat `spec/creem-openapi.json` as the source of truth for public DTO field types.
- Use enums for closed-set API fields and keep enum-to-string normalization inside the internal request serialization layer.
- Use `DateTimeImmutable` for spec-defined `format: date-time` fields and for millisecond timestamps only when the contract explicitly documents that unit.
- Keep public response DTOs concrete: use nested DTOs, typed lists, and `ExpandableResource<T>` instead of `StructuredObject`, `StructuredList`, `ExpandableValue`, or `int|float` unions.
- Contract violations on required response fields should fail fast with `Creem\Exception\HydrationException`; do not silently coerce malformed required values to `null`.

## Commits And Pull Requests
- Write commit subjects in an imperative, outcome-focused style, 72 characters or fewer, with no trailing period.
- Keep the tone concise and direct, focused on what changed for the repository or SDK user.
- Add a `CHANGELOG.md` entry for the next major release when you ship breaking public API changes.
- In pull requests, describe the user-visible impact, list the validation commands you ran, and link the relevant issue when one exists.
