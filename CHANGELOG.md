# Changelog

## Unreleased

## 0.1.1 - 2026-03-04

### Patch Release
- Reordered the README so consumer installation and usage guidance come before contributor workflow details.
- Updated the quick-start example to use `Environment::Test` and explicitly call out that `Creem\Config` defaults to `Environment::Production`.
- Moved development validation commands into a dedicated contributor-focused section to reduce confusion for package consumers.

### Compatibility Notes
- No runtime API changes are included in this release.
- No code changes are required for existing SDK consumers.

## 0.1.0 - 2026-03-04

### Preview Release
- First public preview release of the SDK under the personal `antoniadisio/creem-php-sdk` package name.
- The package targets PHP 8.4+ and ships a typed `Creem\Client` facade, typed resource DTOs, and webhook verification helpers.
- Request DTOs use spec-backed enums and typed date objects for closed-set and temporal fields.
- Response DTOs expose enums, `DateTimeImmutable`, typed nested DTOs, typed lists, and `ExpandableResource<T>` instead of generic structured containers and loose numeric unions.
- Response hydration fails fast with `Creem\Exception\HydrationException` when required payload fields are missing or malformed.
- Webhook verification now requires timestamped signatures, rejects replay windows beyond 5 minutes, and enforces a 1 MiB payload cap before decoding.
- `Creem\Config` now enforces key shape and HTTPS-only base URL overrides, defaults outbound requests to a 30-second timeout, and redacts secrets in debug output and serialization.
- Mutating resource methods now accept an optional idempotency key, and `RateLimitException` exposes parsed `Retry-After` delays.
- Contributor tooling uses Pest 4, split deterministic `Unit` and `Integration` suites, and a default QA gate that runs both local suites.

### Compatibility Notes
- This is a pre-1.0 preview release. Public APIs may still evolve between `0.x` releases.
- Upgrade your runtime to PHP 8.4+ before installing this package.
- Replace request string literals such as currency codes, billing types, and stats intervals with the matching `Creem\Enum\*` cases.
- Expect typed response properties where raw strings or generic containers would normally be returned by looser SDKs.
