# Changelog

## 1.0.4 - 2026-03-17

- Documentation and release-process patch release.
- Adds a manual GitHub Actions release workflow that cuts releases from merged `main` instead of from branch commits.
- Requires the requested version to exist in `CHANGELOG.md` and requires a successful `quality` workflow run on the merged `main` commit before creating the tag and GitHub release.
- Keeps the normal `quality` workflow active on branch and `main` pushes while excluding release tag pushes, and updates the maintainer release runbook to document the new flow and human approval point.
- No runtime API changes are included in this release.
- No code changes are required for existing SDK consumers.

## 1.0.3 - 2026-03-17

- Documentation-only patch release.
- Refactors the contributor and maintainer docs so `README.md`, `CONTRIBUTING.md`, `playground/README.md`, and `docs/maintainers/README.md` each own one clear audience and responsibility.
- Reduces duplicated workflow guidance across the repo and keeps the live playground runbook as the single source of truth for destructive and webhook verification.
- Clarifies contributor expectations around comment discipline, deterministic Pest coverage for new features, and docs-first research for third-party libraries.
- No runtime API changes are included in this release.
- No code changes are required for existing SDK consumers.

## 1.0.2 - 2026-03-17

- Repository hygiene patch release.
- Removes the unused local Git commit template and hook scaffolding now that commits are handled through Codex.
- Simplifies root ignore policy to active repo-wide local state only and leaves playground runtime ignores scoped to `playground/.gitignore`.
- Drops the redundant `tests/Integration/.gitkeep` placeholder and adds LF normalization to `.gitattributes`.
- Aligns contributor guidance with the cleaned Git workflow and ignore policy.
- No runtime API changes are included in this release.
- No code changes are required for existing SDK consumers.

## 1.0.1 - 2026-03-16

- Documentation-only patch release.
- Rewrites `CHANGELOG.md` entries to match the curated flat-bullet style now used for published GitHub release notes.
- Adds the missing `1.0.0` changelog entry and aligns earlier entries with their published release summaries.
- No runtime API changes are included in this release.
- No code changes are required for existing SDK consumers.

## 1.0.0 - 2026-03-16

- First stable release of the unofficial Creem PHP SDK.
- Establishes `antoniadisio/creem-php` and `Antoniadisio\Creem\` as the supported 1.x baseline.
- Keeps the current typed client, resource, DTO, webhook, and playground surface as the stable public contract.
- Requires PHP 8.4+.
- Install with `composer require antoniadisio/creem-php`.
- No migration steps are required from `v0.3.0`.
- Internal changes in this release are limited to maintainability improvements in shared request and hydration helpers; no new public API changes were introduced beyond `v0.3.0`.

## 0.3.0 - 2026-03-15

- Pre-1.0 package rename release for the unofficial personal SDK.
- Renames the package from `antoniadisio/creem-php-sdk` to `antoniadisio/creem-php`.
- Renames the public PHP namespace from `Creem\...` to `Antoniadisio\Creem\...`.
- Adds typed `Product::$customFields` hydration from `custom_fields`.
- Adds explicit webhook parsing coverage for `subscription.scheduled_cancel` while keeping `WebhookEvent::eventType()` forward-compatible.
- Removes the deprecated `Antoniadisio\Creem\Dto\Common\ExpandableValue` artifact and related dead compatibility paths.
- Rewrites consumer, contributor, and maintainer docs around the current `Unit`, `Integration`, and `Smoke` test split and the public playground workflow.
- Reduces smoke coverage to a single authenticated `stats()->summary(...)` canary and moves endpoint-specific live validation into the committed `playground/` harness.
- Install with `composer require antoniadisio/creem-php`.
- Update imports from `Creem\...` to `Antoniadisio\Creem\...` when upgrading to `v0.3.0`.
- Product responses now expose typed `customFields` data when Creem returns `custom_fields`.

## 0.2.0 - 2026-03-05

- Security hardening release for transport, webhook validation, and mutating route handling.
- Disables redirects, enforces TLS certificate verification, pins request, connect, and read timeouts, and requires TLS 1.2 for outbound transport.
- Stops exposing raw sender exceptions in outward SDK exception chains.
- Redacts `sk_*`, `creem_*`, and `whsec_*` tokens in mapped HTTP error messages and context.
- Rejects blank or whitespace-only webhook secrets and adds optional replay-detection callback support in `Webhook::constructEvent(...)`.
- Hardens mutating subscription and discount path identifiers by trimming input, rejecting reserved URI and control characters, allowing only `[A-Za-z0-9._-]`, and blocking `.` and `..` segments.
- Restricts `baseUrl` overrides to trusted official hosts by default; non-official HTTPS hosts now require `allowUnsafeBaseUrlOverride: true`.
- Tightens DTO validation around financially sensitive mutations.
- Expands deterministic security regression coverage and adds `composer audit --locked` to CI.
- This release may reject inputs that earlier versions tolerated.
- Existing integrations should validate identifiers and explicitly opt in when using non-official HTTPS API hosts.

## 0.1.1 - 2026-03-04

- Documentation-only patch release.
- Reorders the README so installation and usage guidance come before contributor workflow details.
- Updates the quick-start example to use `Environment::Test` and clarifies that `Creem\Config` defaults to `Environment::Production`.
- Moves local validation commands into a contributor-focused section.
- No runtime API changes are included in this release.
- No code changes are required for existing SDK consumers.

## 0.1.0 - 2026-03-04

- First public preview release of the unofficial Creem PHP SDK under the personal `antoniadisio/creem-php-sdk` package name.
- Requires PHP 8.4+.
- Adds a typed `Creem\Client` facade, typed resource DTOs, and webhook verification helpers.
- Uses enums and typed date objects in request DTOs for closed-set and temporal fields.
- Returns typed response DTOs with enums, `DateTimeImmutable`, nested DTOs, typed lists, and `ExpandableResource<T>` instead of loose structured containers.
- Fails fast on malformed response payloads with `Creem\Exception\HydrationException`.
- Requires timestamped webhook signatures, rejects replay windows beyond 5 minutes, and enforces a 1 MiB payload cap before decoding.
- Enforces API-key shape, HTTPS-only base URL overrides, default 30-second timeouts, and secret redaction in `Creem\Config`.
- Adds optional idempotency keys on mutating resource methods and parsed `Retry-After` support on `RateLimitException`.
- Public APIs may still evolve between `0.x` releases.
- Existing integrations should replace request string literals such as currency codes, billing types, and stats intervals with the matching `Creem\Enum\*` cases.
