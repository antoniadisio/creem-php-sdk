# Release Workflow

Public releases go through a pull request first, then a manual GitHub Actions release run from merged `main`.

## Normal Flow

1. Make the release changes on a topic branch such as `fix/...` or `feat/...`.
2. Run `composer validate --strict`.
3. Run `composer qa:check`.
4. Update `CHANGELOG.md` with the exact release version and date.
5. Keep release notes and installation guidance aligned with the unofficial `antoniadisio/creem-php` package identity.
6. Open and merge the pull request into `main`.
7. Wait for the `quality` workflow on the merged `main` commit to pass.
8. In GitHub Actions, run the `release` workflow on `main` and provide the version as `1.0.3` or `v1.0.3`.
9. Let the workflow create the annotated tag, publish the GitHub release, and keep the tag, release title, and `CHANGELOG.md` entry aligned.

The release workflow validates that:

- it is running from the latest `main` commit
- the requested tag does not already exist
- `CHANGELOG.md` contains the matching version heading
- the merged `main` commit already has a successful `quality` workflow run

## Human Approval

There is always one human step in this flow: you manually trigger the `release` workflow from GitHub after the PR is merged and checks are green.

If you want a second explicit approval gate before tag and release creation, configure required reviewers for the `github-release` environment in the repository settings. The workflow already targets that environment.
