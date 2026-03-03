# Repository Guidelines

## Project Structure & Module Organization
The SDK is a PHP 8.1 library with PSR-4 autoloading under `src/` (`Creem\\`). Keep the public API stable through `src/Client.php`, `src/Config.php`, resource wrappers in `src/Resource/`, shared API enums in `src/Enum/`, and typed DTOs in `src/Dto/`. Transport and hydration details belong in `src/Internal/`; typed failures live in `src/Exception/`. Tests are in `tests/Unit/`, with reusable JSON fixtures in `tests/Fixtures/Responses/`. `tests/Integration/` exists for future end-to-end coverage. Contract references live in `spec/creem-openapi.json` and `docs/openapi-audit.md`. Human contributor guidance belongs in `CONTRIBUTING.md`.

## Public Repo Hygiene
Commit only package and contributor-facing assets. Keep maintainer-only planning files local and ignored: `IMPLEMENTATION_PLAN.md`, `PROJECT_DESCRIPTION.md`, `plan/`, `.env`, IDE files, and tool caches.

## Build, Test, and Development Commands
Install dependencies with `composer install`. Use the Composer scripts already defined in `composer.json`:

- `composer test` runs the PHPUnit unit suite from `tests/Unit/`.
- `composer cs` checks formatting with Laravel Pint.
- `composer cs:fix` applies Pint formatting fixes.
- `composer stan` runs PHPStan against `src` and `tests` through the committed `phpstan.neon.dist` configuration.

Run `composer test` and `composer stan` after each completed task and keep fixing until both pass cleanly. Before opening a pull request, also run `composer cs`.

## Coding Style & Naming Conventions
Follow the existing code style: `declare(strict_types=1);`, 4-space indentation, typed properties, and constructor property promotion where it improves clarity. Use Laravel Pint as the formatting authority. Keep classes `final` unless extension is required. Match the current naming patterns: DTOs and request payloads use PascalCase (`CreateProductRequest`), resource classes end in `Resource`, shared API enums use PascalCase class names with backed cases, and custom exceptions end in `Exception`. Keep public APIs in `src/` consumer-focused; hide transport-specific details under `src/Internal/`.

## Testing Guidelines
This project uses PHPUnit 10 (`phpunit.xml.dist` runs only `tests/Unit`). Name test files `*Test.php` and keep test methods descriptive, usually `test_*`. Add or update unit tests for every public API change, request mapper change, or exception mapping change. When response shapes change, update the matching fixture JSON under `tests/Fixtures/Responses/`. No numeric coverage threshold is enforced, so treat regression coverage as part of the change.

## Agent Maintenance
After each completed task, review this file and update it when repository workflows, conventions, or contributor expectations have changed. After each completed task or phase, also review `README.md` and update it when the public API usage, workflows, or release guidance changed.

## Commit & Pull Request Guidelines
Commits follow the repo template in `.gitmessage.txt`: use an imperative, outcome-focused subject line, keep it at 72 characters or fewer, and do not end it with a period (for example, `Add typed resource operations`). Write subjects in a senior-engineer voice: concise, direct, calm, and focused on the outcome rather than the implementation chatter. Optional commit bodies should explain why the change matters and any tradeoffs. Pull requests should describe user-visible API changes, list validation steps (`composer test`, `composer cs`, `composer stan`), and link the relevant issue when one exists.
For breaking public API changes targeted at a major release, add a matching `CHANGELOG.md` entry that summarizes the surface change and the required migration steps.
