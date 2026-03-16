<?php

declare(strict_types=1);

use Playground\Support\Playground;
use Playground\Support\PlaygroundException;

$bootstrap = require __DIR__.'/bootstrap.php';

$operations = Playground::discoverOperations($bootstrap['operations_glob']);
$arguments = array_slice($argv, 1);
$showUsage = false;
$auditMode = false;
$allowWrite = false;
$operationName = null;
$profileName = null;
$overridesFile = null;
$setAssignments = [];
$operation = null;
$inputs = null;
$requestPayload = null;
$exampleResponse = null;
$liveResponse = null;
$savedState = [];
$transport = [];
$idempotencyKey = null;

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

        if ($argument === '--allow-write') {
            $allowWrite = true;

            continue;
        }

        if ($argument === '--profile') {
            $index++;
            $profileName = $arguments[$index] ?? null;

            if (! is_string($profileName) || trim($profileName) === '' || str_starts_with($profileName, '--')) {
                throw new RuntimeException('The --profile option requires a profile name.');
            }

            continue;
        }

        if (str_starts_with($argument, '--profile=')) {
            $profileName = trim((string) substr($argument, 10));

            if ($profileName === '') {
                throw new RuntimeException('The --profile option requires a profile name.');
            }

            continue;
        }

        if ($argument === '--overrides-file') {
            $index++;
            $overridesFile = $arguments[$index] ?? null;

            if (! is_string($overridesFile) || trim($overridesFile) === '' || str_starts_with($overridesFile, '--')) {
                throw new RuntimeException('The --overrides-file option requires a path.');
            }

            continue;
        }

        if (str_starts_with($argument, '--overrides-file=')) {
            $overridesFile = trim((string) substr($argument, 17));

            if ($overridesFile === '') {
                throw new RuntimeException('The --overrides-file option requires a path.');
            }

            continue;
        }

        if ($argument === '--set') {
            $index++;
            $assignment = $arguments[$index] ?? null;

            if (! is_string($assignment) || trim($assignment) === '' || str_starts_with($assignment, '--')) {
                throw new RuntimeException('The --set option requires a path=value assignment.');
            }

            $setAssignments[] = $assignment;

            continue;
        }

        if (str_starts_with($argument, '--set=')) {
            $assignment = trim((string) substr($argument, 6));

            if ($assignment === '') {
                throw new RuntimeException('The --set option requires a path=value assignment.');
            }

            $setAssignments[] = $assignment;

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

    if ($showUsage || ($operationName === null && ! $auditMode)) {
        fwrite(STDOUT, "Usage\n");
        fwrite(STDOUT, "php playground/run.php <resource>/<action> [--profile <name>] [--allow-write] [--set path=value] [--overrides-file <path>]\n");
        fwrite(STDOUT, "php playground/run.php --audit\n\n");
        fwrite(STDOUT, "Notes\n");
        fwrite(STDOUT, "- JSON is the default output contract.\n");
        fwrite(STDOUT, "- Named profiles resolve API keys and client settings from local state plus env vars.\n");
        fwrite(STDOUT, "- Write-capable operations require --allow-write.\n\n");
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

    $operation = Playground::resolveOperation($operations, (string) $operationName);
    $state = Playground::loadState($bootstrap['state_path']);
    $fileOverrides = is_string($overridesFile)
        ? Playground::loadOverrideValues($overridesFile)
        : [];
    $setOverrides = Playground::parseSetAssignments($operation, $setAssignments);
    $values = Playground::buildEffectiveValues($state, $operation, $fileOverrides, $setOverrides, $profileName);
    $resolvedIdempotency = Playground::resolveGeneratedIdempotencyKey($operation, $values);
    $values = $resolvedIdempotency['values'];
    $idempotencyKey = $resolvedIdempotency['idempotencyKey'];

    /** @var list<string> $requiredValues */
    $requiredValues = $operation['required_values'];

    Playground::validateRequiredValues($values, $requiredValues);
    $inputs = $operation['build_inputs']($values);
    $requestPayload = $operation['build_request_payload']($values);
    $exampleResponse = Playground::loadFixtures($bootstrap['fixtures_path'], $operation['fixtures']);

    if ($operation['operation_mode'] === 'write' && ! $allowWrite) {
        throw new PlaygroundException(
            'Write-capable playground operations require --allow-write.',
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
        $savedState = $persisted['changes'];

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
        $inputs,
        $requestPayload,
        $exampleResponse,
        $liveResponse,
        $savedState,
        $idempotencyKey,
        $transport,
    ));

    exit(0);
} catch (Throwable $exception) {
    Playground::printJson(Playground::jsonEnvelope(
        $operation,
        $inputs,
        $requestPayload,
        $exampleResponse,
        $liveResponse,
        $savedState,
        $idempotencyKey,
        $transport,
        $exception,
    ));

    exit(1);
}
