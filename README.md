# Creem PHP SDK

Production-focused PHP SDK for the `creem.io` API.

This repository is being structured around a generated transport and model layer produced with Fern, with a stable handwritten PHP wrapper on top so downstream consumers interact with a predictable public API.

Current status:

- the OpenAPI source spec is present in this repository
- repository scaffolding and local generation tooling are being established
- PHP source generation and handwritten wrapper implementation will follow in subsequent phases

Planned architecture:

- generated SDK output managed with Fern
- handwritten public entrypoints under the `Creem\\` namespace
- package metadata suitable for Git tags and Packagist distribution

## Tooling

- PHP `^8.1`
- Composer for PHP dependency management and validation
- a local `fern-api` CLI install managed through `npm`

## Local Commands

```bash
composer validate
npm run fern -- --version
```
