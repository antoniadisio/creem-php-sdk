# Changelog

## 0.1.0 - 2026-03-04

### Preview Release
- First public preview release of the SDK under the personal `antoniadisio/creem-php-sdk` package name.
- The package targets PHP 8.4+ and ships a typed `Creem\Client` facade, typed resource DTOs, and webhook verification helpers.
- Request DTOs use spec-backed enums and typed date objects for closed-set and temporal fields.
- Response DTOs expose enums, `DateTimeImmutable`, typed nested DTOs, typed lists, and `ExpandableResource<T>` instead of generic structured containers and loose numeric unions.
- Response hydration fails fast with `Creem\Exception\HydrationException` when required payload fields are missing or malformed.
- Contributor tooling uses Pest 4, split deterministic `Unit` and `Integration` suites, and a default QA gate that runs both local suites.

### Compatibility Notes
- This is a pre-1.0 preview release. Public APIs may still evolve between `0.x` releases.
- Upgrade your runtime to PHP 8.4+ before installing this package.
- Replace request string literals such as currency codes, billing types, and stats intervals with the matching `Creem\Enum\*` cases.
- Expect typed response properties where raw strings or generic containers would normally be returned by looser SDKs.
