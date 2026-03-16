<?php

declare(strict_types=1);

use Playground\Support\Playground;
use Playground\Support\PlaygroundException;

$bootstrap = require __DIR__.'/bootstrap.php';

$operations = Playground::discoverOperations($bootstrap['operations_glob']);
$arguments = array_slice($argv, 1);
$showUsage = false;
$auditMode = false;
$listMode = false;
$describeOperationName = null;
$inputFile = null;
$operationName = null;
$profileName = null;
$operation = null;
$inputs = null;
$requestPayload = null;
$exampleResponse = null;
$liveResponse = null;
$stateChanges = [];
$transport = [];
$idempotencyKey = null;
$commandKind = 'operation_result';

try {
    for ($index = 0; $index < count($arguments); $index++) {
        $argument = $arguments[$index];

        if ($argument === '--help' || $argument === '-h') {
            $showUsage = true;

            continue;
        }

        if ($argument === '--audit') {
            $auditMode = true;

            continue;
        }

        if ($argument === '--list') {
            $listMode = true;

            continue;
        }

        if ($argument === '--describe') {
            $index++;
            $describeOperationName = $arguments[$index] ?? null;

            if (
                ! is_string($describeOperationName)
                || trim($describeOperationName) === ''
                || str_starts_with($describeOperationName, '--')
            ) {
                throw new RuntimeException('The --describe option requires an operation name.');
            }

            continue;
        }

        if (str_starts_with($argument, '--describe=')) {
            $describeOperationName = trim((string) substr($argument, 11));

            if ($describeOperationName === '') {
                throw new RuntimeException('The --describe option requires an operation name.');
            }

            continue;
        }

        if ($argument === '--input-file') {
            $index++;
            $inputFile = $arguments[$index] ?? null;

            if (! is_string($inputFile) || trim($inputFile) === '' || str_starts_with($inputFile, '--')) {
                throw new RuntimeException('The --input-file option requires a path.');
            }

            continue;
        }

        if (str_starts_with($argument, '--input-file=')) {
            $inputFile = trim((string) substr($argument, 13));

            if ($inputFile === '') {
                throw new RuntimeException('The --input-file option requires a path.');
            }

            continue;
        }

        if (str_starts_with($argument, '--')) {
            throw new RuntimeException(sprintf('Unknown playground option [%s].', $argument));
        }

        if ($operationName !== null) {
            throw new RuntimeException('Only one playground operation may be provided.');
        }

        $operationName = $argument;
    }

    $selectedModes = ($auditMode ? 1 : 0) + ($listMode ? 1 : 0) + ($describeOperationName !== null ? 1 : 0);

    if ($selectedModes > 1) {
        throw new RuntimeException('Use only one of --audit, --list, or --describe at a time.');
    }

    if ($inputFile !== null && ($auditMode || $listMode || $describeOperationName !== null)) {
        throw new RuntimeException('The --input-file option may only be used when running an operation.');
    }

    if ($auditMode) {
        $commandKind = 'audit';
    } elseif ($listMode) {
        $commandKind = 'operation_list';
    } elseif ($describeOperationName !== null) {
        $commandKind = 'operation_describe';
    }

    if ($showUsage || ($operationName === null && ! $auditMode && ! $listMode && $describeOperationName === null)) {
        fwrite(STDOUT, "Usage\n");
        fwrite(STDOUT, "php playground/run.php --list\n");
        fwrite(STDOUT, "php playground/run.php --describe <resource>/<action>\n");
        fwrite(STDOUT, "php playground/run.php --audit\n");
        fwrite(STDOUT, "php playground/run.php <resource>/<action> [--input-file <path>]\n\n");
        fwrite(STDOUT, "Notes\n");
        fwrite(STDOUT, "- JSON is the default output contract for list, describe, audit, and run commands.\n");
        fwrite(STDOUT, "- Run input accepts one JSON envelope with profile, allow_write, and values.\n");
        fwrite(STDOUT, "- The same envelope may be provided through --input-file or piped stdin.\n");
        fwrite(STDOUT, "- Write-capable operations require input.allow_write=true.\n\n");
        fwrite(STDOUT, "Available operations\n");

        foreach (Playground::operationNames($operations) as $name) {
            fwrite(STDOUT, $name."\n");
        }

        fwrite(STDOUT, "\n");

        exit(0);
    }

    if ($auditMode) {
        if ($operationName !== null) {
            throw new RuntimeException('Do not provide an operation when using --audit.');
        }

        $audit = Playground::auditOperations($operations, dirname(__DIR__));
        Playground::printJson($audit);

        exit($audit['ok'] ? 0 : 1);
    }

    if ($listMode) {
        if ($operationName !== null) {
            throw new RuntimeException('Do not provide an operation when using --list.');
        }

        Playground::printJson(Playground::listOperationsPayload($operations));

        exit(0);
    }

    if ($describeOperationName !== null) {
        if ($operationName !== null) {
            throw new RuntimeException('Do not provide a positional operation when using --describe.');
        }

        $operation = Playground::resolveOperation($operations, $describeOperationName);
        Playground::printJson(Playground::describeOperationPayload($operation, $bootstrap));

        exit(0);
    }

    $operation = Playground::resolveOperation($operations, (string) $operationName);
    $state = Playground::loadState($bootstrap['state_path'], $bootstrap['state_example_path']);
    $inputEnvelope = Playground::loadInputEnvelope($inputFile, playgroundPipedStdinPayload());
    $values = Playground::buildEffectiveValues(
        $state,
        $operation,
        $inputEnvelope['values'],
        $inputEnvelope['profile'],
    );
    $profileName = Playground::activeProfileName($values);
    $resolvedIdempotency = Playground::resolveGeneratedIdempotencyKey($operation, $values);
    $values = $resolvedIdempotency['values'];
    $idempotencyKey = $resolvedIdempotency['idempotencyKey'];

    /** @var list<string> $requiredValues */
    $requiredValues = $operation['required_values'];

    Playground::validateRequiredValues($values, $requiredValues);
    $inputs = $operation['build_inputs']($values);
    $requestPayload = $operation['build_request_payload']($values);
    $exampleResponse = Playground::loadFixtures($bootstrap['fixtures_path'], $operation['fixtures']);

    if ($operation['operation_mode'] === 'write' && ! $inputEnvelope['allow_write']) {
        throw new PlaygroundException(
            'Write-capable playground operations require input.allow_write=true.',
            context: [
                'operation' => $operation['_name'],
                'operation_mode' => $operation['operation_mode'],
            ],
        );
    }

    $trace = Playground::startTrace($values);

    try {
        $client = Playground::createClient($values);
        $liveResponse = $operation['run']($client, $values);
        $persisted = Playground::persistResponseValues($state, $operation['persist_outputs'], $liveResponse);
        $stateChanges = $persisted['changes'];

        if ($persisted['changes'] !== []) {
            Playground::writeState($bootstrap['state_path'], $persisted['values']);
        }

        $transport = Playground::stopTrace($trace);
    } catch (Throwable $exception) {
        $transport = Playground::stopTrace($trace);

        throw $exception;
    }

    Playground::printJson(Playground::jsonEnvelope(
        $operation,
        $profileName,
        $inputs,
        $requestPayload,
        $exampleResponse,
        $liveResponse,
        $stateChanges,
        $idempotencyKey,
        $transport,
    ));

    exit(0);
} catch (Throwable $exception) {
    if ($commandKind === 'operation_result') {
        Playground::printJson(Playground::jsonEnvelope(
            $operation,
            $profileName,
            $inputs,
            $requestPayload,
            $exampleResponse,
            $liveResponse,
            $stateChanges,
            $idempotencyKey,
            $transport,
            $exception,
        ));
    } elseif ($commandKind === 'operation_describe') {
        Playground::printJson(Playground::commandEnvelope(
            'operation_describe',
            ['operation' => $describeOperationName],
            $exception,
        ));
    } else {
        Playground::printJson(Playground::commandEnvelope($commandKind, [], $exception));
    }

    exit(1);
}

function playgroundPipedStdinPayload(): ?string
{
    if (function_exists('stream_isatty') && stream_isatty(STDIN)) {
        return null;
    }

    $contents = stream_get_contents(STDIN);

    if ($contents === false) {
        throw new RuntimeException('Unable to read playground stdin input.');
    }

    return trim($contents) === '' ? null : $contents;
}
