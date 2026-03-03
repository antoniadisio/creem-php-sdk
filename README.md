# Creem PHP SDK

Handwritten PHP SDK for the `creem.io` API.

This repository is being reset around a curated SDK implementation with Saloon used only as the internal HTTP transport layer. The target public API is a stable `Creem\Client` facade backed by handwritten configuration, exceptions, resources, and DTOs, while the committed OpenAPI contract remains the source of truth.

## Current Status

- baseline package scaffolding is in place
- Saloon is installed as the runtime transport dependency
- the OpenAPI contract will be normalized into `spec/` in the next phase
- the public client, resources, and DTOs are not implemented yet

## Planned Architecture

- `Creem\Client` as the stable SDK entrypoint
- `Creem\Config` and `Creem\Environment` as the public configuration surface
- typed DTOs under `src/Dto`
- typed exceptions under `src/Exception`
- internal HTTP connector and request classes under `src/Internal/Http`

## Tooling

- PHP `^8.1`
- Composer for dependency management and validation
- PHPUnit for tests
- Laravel Pint for code style
- PHPStan for static analysis

## Local Commands

```bash
composer validate --no-check-publish
composer test
composer cs
composer stan
```
