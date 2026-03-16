# Release Workflow

Use this checklist when cutting a public release.

1. Run `composer validate --strict`.
2. Run `composer qa:check`.
3. Update `CHANGELOG.md` with the exact release version and date.
4. Keep release notes and installation guidance aligned with the unofficial `antoniadisio/creem-php` package identity.
5. Create an annotated Git tag for that version, for example `git tag -a v0.3.0 -m "Release v0.3.0"`.
6. Push the tag and publish matching GitHub release notes.
7. Keep the Git tag, GitHub release title, and `CHANGELOG.md` entry identical.
