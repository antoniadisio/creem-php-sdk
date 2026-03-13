# Changelog

## Unreleased

### Repository Hardening
- Removed the committed maintainer-only `PROJECT_DESCRIPTION.md` artifact and aligned ignore rules with the local-only `spec/` workflow.
- Rewrote README, contributor guidance, and audit/runbook docs to describe the current `Unit` / `Integration` / `Smoke` suite architecture without preview-era wording.
- Reduced the smoke env contract to `CREEM_TEST_API_KEY` only and collapsed smoke coverage to one authenticated `stats()->summary(...)` canary, moving endpoint-specific retrieval or mutating live verification into the local `.playground/` harness.
- Clarified that destructive `Environment::Test` validation remains a separate manual maintainer workflow outside automated QA and smoke runs.

## 0.2.0 - 2026-03-05

### Security Release
- Hardened outbound transport defaults:
  - disabled redirects (`allow_redirects: false`)
  - enforced TLS certificate verification (`verify: true`)
  - pinned request, connect, and read timeouts
  - enforced TLS 1.2 minimum via `crypto_method`
- Removed raw sender exception leakage from outward SDK exceptions to avoid exposing request internals in exception chains.
- Added centralized sensitive-token redaction in HTTP error handling for `sk_*`, `creem_*`, and `whsec_*` patterns across mapped messages and context.
- Added strict webhook secret validation (blank/whitespace secret rejection).
- Added optional replay-detection callback support in `Webhook::constructEvent(...)` to enable consumer-managed deduplication.
- Hardened mutating path-identifier handling for subscription and discount operations:
  - trim + non-blank enforcement
  - reject reserved URI/control characters (`/`, `\\`, `?`, `#`, `%`, ASCII controls)
  - allow only `[A-Za-z0-9._-]`
  - reject dot segments `.` and `..`
- Hardened base URL override policy:
  - default trusted-host mode for official Creem hosts
  - non-official hosts require explicit `allowUnsafeBaseUrlOverride: true`
- Expanded deterministic security regression coverage across webhook validation, transport/exception redaction, and path-route manipulation rejection scenarios.
- CI quality workflow now includes dependency advisory scanning (`composer audit --locked`).

### Compatibility Notes
- This release tightens validation and may reject inputs previously tolerated:
  - blank webhook secrets now fail fast
  - stricter DTO invariants for financially sensitive mutations
  - mutating path IDs now reject unsafe characters and dot-segment identifiers
  - non-official `baseUrl` overrides now require `allowUnsafeBaseUrlOverride: true`
- Existing integrations should validate and normalize identifiers/inputs before SDK calls, and explicitly opt in when using non-official HTTPS API hosts.

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
