<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use RuntimeException;

use function array_map;
use function array_unique;
use function array_values;
use function dirname;
use function explode;
use function fclose;
use function fgets;
use function is_resource;
use function sort;
use function trim;

test('git archive exports only the runtime package surface', function (): void {
    $entries = exportedArchiveEntries();
    $topLevelEntries = topLevelArchiveEntries($entries);

    expect($topLevelEntries)->toBe([
        'LICENSE',
        'README.md',
        'composer.json',
        'src/',
    ]);

    expect($entries)->toContain('src/Client.php')
        ->and($entries)->toContain('src/Webhook.php')
        ->and($entries)->not->toContain('AGENTS.md')
        ->and($entries)->not->toContain('CONTRIBUTING.md')
        ->and($entries)->not->toContain('pint.json')
        ->and($entries)->not->toContain('phpstan.neon.dist')
        ->and($entries)->not->toContain('phpunit.xml.dist')
        ->and($entries)->not->toContain('playground/')
        ->and($entries)->not->toContain('rector.php')
        ->and($entries)->not->toContain('tests/');
});

/**
 * @return list<string>
 */
function exportedArchiveEntries(): array
{
    $repoRoot = dirname(__DIR__, 3);
    $process = proc_open(
        ['sh', '-lc', 'git archive --format=tar --worktree-attributes HEAD | tar -tf -'],
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        $repoRoot,
    );

    if (! is_resource($process)) {
        throw new RuntimeException('Unable to inspect the exported archive.');
    }

    fclose($pipes[0]);

    $entries = [];

    while (($line = fgets($pipes[1])) !== false) {
        $entry = trim($line);

        if ($entry !== '') {
            $entries[] = $entry;
        }
    }

    $stderr = trim(stream_get_contents($pipes[2]));

    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        throw new RuntimeException($stderr !== '' ? $stderr : 'Unable to inspect the exported archive.');
    }

    return $entries;
}

/**
 * @param  list<string>  $entries
 * @return list<string>
 */
function topLevelArchiveEntries(array $entries): array
{
    $topLevelEntries = array_map(static function (string $entry): string {
        $segments = explode('/', $entry, 2);

        if (isset($segments[1])) {
            return $segments[0] . '/';
        }

        return $entry;
    }, $entries);

    $topLevelEntries = array_values(array_unique($topLevelEntries));

    sort($topLevelEntries);

    return $topLevelEntries;
}
