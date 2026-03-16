<?php

declare(strict_types=1);

use Playground\Support\Playground;
use Playground\Support\WebhookPlayground;

$bootstrap = require dirname(__DIR__).'/bootstrap.php';
$arguments = array_slice($argv, 1);
$latestOnly = false;
$limit = 10;
$profileName = null;

for ($index = 0; $index < count($arguments); $index++) {
    $argument = $arguments[$index];

    if ($argument === '--latest') {
        $latestOnly = true;

        continue;
    }

    if ($argument === '--limit') {
        $index++;
        $value = $arguments[$index] ?? null;

        if (! is_string($value) || $value === '' || ! ctype_digit($value)) {
            fwrite(STDERR, "--limit requires a positive integer.\n");
            exit(1);
        }

        $limit = (int) $value;

        continue;
    }

    if (str_starts_with($argument, '--limit=')) {
        $value = substr($argument, 8);

        if ($value === '' || ! ctype_digit($value)) {
            fwrite(STDERR, "--limit requires a positive integer.\n");
            exit(1);
        }

        $limit = (int) $value;

        continue;
    }

    if ($argument === '--help' || $argument === '-h') {
        fwrite(STDOUT, "Usage\n");
        fwrite(STDOUT, "php playground/webhooks/inspect.php [--latest] [--limit N] [--profile <name>]\n\n");
        fwrite(STDOUT, "Examples\n");
        fwrite(STDOUT, "php playground/webhooks/inspect.php --latest\n");
        fwrite(STDOUT, "php playground/webhooks/inspect.php --limit 20\n");
        fwrite(STDOUT, "php playground/webhooks/inspect.php --latest --profile cashier\n");

        exit(0);
    }

    if ($argument === '--profile') {
        $index++;
        $profileName = $arguments[$index] ?? null;

        if (! is_string($profileName) || $profileName === '' || str_starts_with($profileName, '--')) {
            fwrite(STDERR, "--profile requires a non-empty profile name.\n");
            exit(1);
        }

        continue;
    }

    if (str_starts_with($argument, '--profile=')) {
        $profileName = substr($argument, 10);

        if ($profileName === '') {
            fwrite(STDERR, "--profile requires a non-empty profile name.\n");
            exit(1);
        }

        continue;
    }

    fwrite(STDERR, sprintf("Unknown option [%s].\n", $argument));
    exit(1);
}

Playground::printJson([
    'ok' => true,
    'captures' => WebhookPlayground::inspect($bootstrap['workspace_path'], $limit, $latestOnly, $profileName),
]);
