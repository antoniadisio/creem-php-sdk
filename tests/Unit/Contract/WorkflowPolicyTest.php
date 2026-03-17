<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use RuntimeException;

use function dirname;
use function file_exists;
use function file_get_contents;
use function preg_match;

test('quality workflow keeps branch pushes active while excluding release tags', function (): void {
    $workflow = workflowFileContents('.github/workflows/quality.yml');

    expect($workflow)->toContain('push:')
        ->and($workflow)->toContain("branches:\n            - '**'")
        ->and($workflow)->toContain("tags-ignore:\n            - 'v*'")
        ->and($workflow)->toContain('raw.githubusercontent.com/rhysd/actionlint/v1.7.11/scripts/download-actionlint.bash')
        ->and($workflow)->toContain('Lint GitHub Actions workflows');

    expect(checkoutWorkflowVersion($workflow))->toBeGreaterThanOrEqual(5);
});

test('release workflow stays manual and gates releases on merged main quality', function (): void {
    $workflow = workflowFileContents('.github/workflows/release.yml');

    expect($workflow)->toContain('workflow_dispatch:')
        ->and($workflow)->toContain('environment: github-release')
        ->and($workflow)->toContain('if [ "${GITHUB_REF_NAME}" != "main" ]; then')
        ->and($workflow)->toContain('No successful quality workflow run was found')
        ->and($workflow)->toContain('gh release create');

    expect(checkoutWorkflowVersion($workflow))->toBeGreaterThanOrEqual(5);
});

function workflowFileContents(string $relativePath): string
{
    $path = dirname(__DIR__, 3) . '/' . $relativePath;

    if (! file_exists($path)) {
        throw new RuntimeException("Workflow file [{$relativePath}] does not exist.");
    }

    $contents = file_get_contents($path);

    if ($contents === false) {
        throw new RuntimeException("Unable to read workflow file [{$relativePath}].");
    }

    return $contents;
}

function checkoutWorkflowVersion(string $workflow): int
{
    $matches = [];
    $result = preg_match('/uses:\s+actions\/checkout@v(\d+)/', $workflow, $matches);

    if ($result !== 1) {
        throw new RuntimeException('Unable to determine the actions/checkout major version from the workflow.');
    }

    return (int) $matches[1];
}
