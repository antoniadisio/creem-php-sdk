# Changelog

## Unreleased (Next Major)

### Breaking Changes
- The SDK now requires PHP 8.4 or newer; PHP 8.2 and 8.3 are no longer supported.
- Request DTOs now use spec-backed enums and typed date objects for closed-set and temporal fields instead of raw strings or loose numeric unions.
- Response DTOs now expose enums, `DateTimeImmutable`, typed nested DTOs, typed lists, and `ExpandableResource<T>` instead of `StructuredObject`, `StructuredList`, `ExpandableValue`, and `int|float` unions.
- Response hydration now fails fast with `Creem\Exception\HydrationException` when required payload fields are missing or malformed.
- `CreateProductRequest` and `CreateCheckoutRequest` no longer expose the deprecated `customField` alias; use `customFields`.
- Contributor tooling now runs Pest 4 instead of direct PHPUnit commands.

### Migration Notes
- Upgrade your runtime to PHP 8.4+ before installing this major version.
- Replace request string literals such as currency codes, billing types, and stats intervals with the matching `Creem\Enum\*` cases.
- Expect typed response properties where raw strings or generic containers were previously returned.
- Update error handling if you relied on malformed response payloads being silently coerced to `null`.
