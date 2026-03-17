<?php

declare(strict_types=1);

namespace Playground\Support;

use Antoniadisio\Creem\Client;
use Antoniadisio\Creem\ClientFactory;
use Antoniadisio\Creem\CredentialProfile;
use Antoniadisio\Creem\CredentialProfiles;
use Antoniadisio\Creem\Dto\Common\ExpandableResource;
use Antoniadisio\Creem\Dto\Common\Page;
use Antoniadisio\Creem\Dto\Common\StructuredList;
use Antoniadisio\Creem\Dto\Common\StructuredObject;
use Antoniadisio\Creem\Enum\Environment;
use Antoniadisio\Creem\Exception\CreemException;
use BackedEnum;
use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use JsonException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Saloon\Config as SaloonConfig;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response as SaloonResponse;
use Throwable;
use UnitEnum;
use ValueError;

use function array_is_list;
use function array_keys;
use function basename;
use function count;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function get_debug_type;
use function get_object_vars;
use function glob;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_scalar;
use function is_string;
use function json_decode;
use function json_encode;
use function ksort;
use function method_exists;
use function pathinfo;
use function rtrim;
use function sort;
use function sprintf;
use function str_starts_with;
use function strtolower;
use function trim;

final class Playground
{
    /**
     * @return array{
     *     workspace_path: string,
     *     readme_path: string,
     *     state_path: string,
     *     state_example_path: string,
     *     support_path: string,
     *     schemas_path: string,
     *     fixtures_path: string,
     *     operations_glob: string
     * }
     */
    public static function workspace(string $workspacePath): array
    {
        return [
            'workspace_path' => $workspacePath,
            'readme_path' => $workspacePath . '/README.md',
            'state_path' => self::workspacePathOverride(
                'CREEM_PLAYGROUND_STATE_PATH',
                $workspacePath . '/state.local.json',
            ),
            'state_example_path' => self::workspacePathOverride(
                'CREEM_PLAYGROUND_STATE_EXAMPLE_PATH',
                $workspacePath . '/state.example.json',
            ),
            'support_path' => $workspacePath . '/Support',
            'schemas_path' => $workspacePath . '/schemas',
            'fixtures_path' => dirname($workspacePath) . '/tests/Fixtures/Responses',
            'operations_glob' => $workspacePath . '/*/*.php',
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function discoverOperations(string $pattern): array
    {
        $paths = glob($pattern) ?: [];
        sort($paths);

        $operations = [];

        foreach ($paths as $path) {
            if (in_array(basename(dirname($path)), ['Support', 'webhooks'], true)) {
                continue;
            }

            $operation = require $path;

            if (! is_array($operation)) {
                throw new PlaygroundException(sprintf(
                    'Playground operation file [%s] must return an array, %s returned.',
                    $path,
                    get_debug_type($operation),
                ));
            }

            self::assertOperationDefinition($path, $operation);

            $name = $operation['resource'] . '/' . $operation['action'];
            $operation['_path'] = $path;
            $operation['_name'] = $name;
            $operations[$name] = $operation;
        }

        ksort($operations);

        return $operations;
    }

    /**
     * @param  array<string, array<string, mixed>>  $operations
     * @return array<string, mixed>
     */
    public static function resolveOperation(array $operations, string $requested): array
    {
        $requested = trim($requested);

        if ($requested === '') {
            throw new PlaygroundException('Operation name cannot be blank.');
        }

        if (isset($operations[$requested])) {
            return $operations[$requested];
        }

        throw new PlaygroundException(
            sprintf('Unknown playground operation [%s].', $requested),
            context: ['availableOperations' => self::operationNames($operations)],
        );
    }

    /**
     * @param  array<string, array<string, mixed>>  $operations
     * @return list<string>
     */
    public static function operationNames(array $operations): array
    {
        /** @var list<string> $names */
        $names = array_keys($operations);

        return $names;
    }

    /**
     * @param  array<string, array<string, mixed>>  $operations
     * @return array<string, mixed>
     */
    public static function listOperationsPayload(array $operations): array
    {
        return self::commandEnvelope('operation_list', [
            'operations' => array_values(array_map(
                static fn(array $operation): array => self::operationSummary($operation),
                $operations,
            )),
            'schemas' => self::schemaCatalog(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $operation
     * @param  array<string, mixed>  $workspace
     * @return array<string, mixed>
     */
    public static function describeOperationPayload(array $operation, array $workspace): array
    {
        $defaults = self::mergeValues(self::baseValues(), $operation['defaults']);
        $requiredValues = $operation['required_values'];
        $inputFields = [];

        foreach ($operation['inputs'] as $field) {
            self::assertInputField($field);

            $path = $field['path'];

            if (! is_string($path)) {
                throw new PlaygroundException('Input field paths must be strings.');
            }

            $inputFields[] = [
                'path' => $path,
                'label' => $field['label'],
                'type' => $field['type'],
                'nullable' => $field['nullable'],
                'required' => in_array($path, $requiredValues, true),
                'choices' => $field['choices'],
                'enum' => $field['enum'],
                'default' => self::normalize(self::value($defaults, $path)),
            ];
        }

        return self::commandEnvelope('operation_describe', [
            'operation' => $operation['_name'],
            'resource' => $operation['resource'],
            'action' => $operation['action'],
            'operation_mode' => $operation['operation_mode'],
            'sdk_call' => $operation['sdk_call'],
            'method' => $operation['http_method'],
            'path' => $operation['path'],
            'write_requires_allow_write' => $operation['operation_mode'] === 'write',
            'required_values' => self::normalize($requiredValues),
            'idempotency_key_path' => $operation['idempotency_key_path'],
            'persisted_outputs' => self::normalize($operation['persist_outputs']),
            'defaults' => self::normalize($operation['defaults']),
            'inputs' => $inputFields,
            'input_envelope' => self::defaultInputEnvelope(),
            'state' => [
                'local_path' => $workspace['state_path'],
                'example_path' => $workspace['state_example_path'],
                'auto_bootstrap' => true,
            ],
            'schemas' => self::schemaCatalog(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function commandEnvelope(string $kind, array $payload = [], ?Throwable $exception = null): array
    {
        return array_merge(
            [
                'ok' => $exception === null,
                'kind' => $kind,
            ],
            $payload,
            [
                'error' => $exception === null ? null : [
                    'class' => $exception::class,
                    'message' => $exception->getMessage(),
                    'status_code' => self::statusCode($exception),
                    'context' => self::context($exception),
                ],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return array<string, mixed>
     */
    public static function operationSummary(array $operation): array
    {
        return [
            'operation' => $operation['_name'],
            'resource' => $operation['resource'],
            'action' => $operation['action'],
            'operation_mode' => $operation['operation_mode'],
            'sdk_call' => $operation['sdk_call'],
            'method' => $operation['http_method'],
            'path' => $operation['path'],
            'write_requires_allow_write' => $operation['operation_mode'] === 'write',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function schemaCatalog(): array
    {
        return [
            'run_input' => 'playground/schemas/run-input.schema.json',
            'run_output' => 'playground/schemas/run-output.schema.json',
            'operation_describe' => 'playground/schemas/operation-describe.schema.json',
        ];
    }

    /**
     * @param  array<string>  $choices
     * @return array{
     *     path: string,
     *     label: string,
     *     type: string,
     *     nullable: bool,
     *     choices: array<string>,
     *     enum: class-string<UnitEnum>|null
     * }
     */
    public static function field(
        string $path,
        string $label,
        string $type,
        bool $nullable = false,
        array $choices = [],
        ?string $enum = null,
    ): array {
        return [
            'path' => $path,
            'label' => $label,
            'type' => $type,
            'nullable' => $nullable,
            'choices' => $choices,
            'enum' => $enum,
        ];
    }

    /**
     * @return array{path: string, source: string}
     */
    public static function persist(string $path, string $source): array
    {
        return [
            'path' => $path,
            'source' => $source,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultInputEnvelope(): array
    {
        return [
            'profile' => null,
            'allow_write' => false,
            'values' => [],
        ];
    }

    /**
     * @return array{
     *     profile: string|null,
     *     allow_write: bool,
     *     values: array<string, mixed>
     * }
     */
    public static function loadInputEnvelope(?string $inputFile = null, ?string $stdinPayload = null): array
    {
        if ($inputFile !== null && $stdinPayload !== null) {
            throw new PlaygroundException('Provide playground JSON input through --input-file or stdin, not both.');
        }

        if ($inputFile === null && $stdinPayload === null) {
            return self::defaultInputEnvelope();
        }

        $decoded = $inputFile !== null
            ? self::loadJsonObjectFile($inputFile, 'Input file')
            : self::decodeJsonObject($stdinPayload, 'STDIN JSON input');

        $allowedKeys = ['profile', 'allow_write', 'values'];

        foreach (array_keys($decoded) as $key) {
            if (! in_array($key, $allowedKeys, true)) {
                throw new PlaygroundException(sprintf(
                    'Unknown playground input envelope key [%s].',
                    $key,
                ));
            }
        }

        $profile = $decoded['profile'] ?? null;

        if ($profile !== null) {
            $profile = self::stringValue($profile, 'profile');
        }

        $allowWrite = $decoded['allow_write'] ?? false;

        if (! is_bool($allowWrite)) {
            throw new PlaygroundException(sprintf(
                'Expected [allow_write] to be a bool, %s given.',
                get_debug_type($allowWrite),
            ));
        }

        $values = $decoded['values'] ?? [];

        if (! is_array($values) || array_is_list($values)) {
            throw new PlaygroundException('Playground input envelope [values] must be a JSON object.');
        }

        return [
            'profile' => $profile,
            'allow_write' => $allowWrite,
            'values' => $values,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function loadState(string $path, ?string $templatePath = null): array
    {
        if (! file_exists($path)) {
            $state = self::baseValues();

            if ($templatePath !== null && file_exists($templatePath)) {
                $state = self::mergeValues(
                    $state,
                    self::loadJsonObjectFile($templatePath, 'State template file'),
                );
            }

            self::writeState($path, $state);

            return $state;
        }

        return self::loadJsonObjectFile($path, 'State file');
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public static function writeState(string $path, array $state): void
    {
        try {
            $contents = (string) json_encode(
                self::sortMapKeys($state),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            throw new PlaygroundException('Unable to encode state JSON.', previous: $exception);
        }

        self::ensureDirectory(dirname($path));

        if (file_put_contents($path, $contents . "\n") === false) {
            throw new PlaygroundException(sprintf('Unable to write state file [%s].', $path));
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function baseValues(): array
    {
        return [
            'shared' => [
                'activeProfile' => 'default',
                'apiKey' => null,
                'baseUrl' => null,
                'environment' => Environment::Test->value,
                'timeout' => 30,
                'userAgentSuffix' => 'playground/agent',
            ],
            'profiles' => [
                'default' => [
                    'environment' => Environment::Test->value,
                    'baseUrl' => null,
                    'timeout' => 30,
                    'userAgentSuffix' => 'playground/agent',
                    'apiKeyEnv' => 'CREEM_TEST_API_KEY',
                    'webhookSecretEnv' => 'CREEM_TEST_WEBHOOK_SECRET',
                ],
            ],
            'webhookRoutes' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, mixed>  $operation
     * @param  array<string, mixed>  $inputValues
     * @return array<string, mixed>
     */
    public static function buildEffectiveValues(
        array $state,
        array $operation,
        array $inputValues = [],
        ?string $activeProfile = null,
    ): array {
        $defaults = $operation['defaults'] ?? [];

        if (! is_array($defaults)) {
            throw new PlaygroundException('Operation defaults must be an array.');
        }

        $values = self::mergeValues(self::baseValues(), $defaults);
        $values = self::mergeValues($values, $state);

        if ($inputValues !== []) {
            $values = self::mergeValues($values, $inputValues);
        }

        if ($activeProfile !== null) {
            $values = self::withValue($values, 'shared.activeProfile', trim($activeProfile));
        }

        return self::applyActiveProfileConfiguration($values);
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public static function buildProfileValues(
        array $state,
        ?string $profileName = null,
        ?string $webhookPath = null,
        bool $allowPlaceholderApiKeys = false,
    ): array {
        $values = self::mergeValues(self::baseValues(), $state);

        if ($profileName !== null) {
            $values = self::withValue($values, 'shared.activeProfile', trim($profileName));
        } elseif ($webhookPath !== null) {
            $values = self::withValue(
                $values,
                'shared.activeProfile',
                self::resolveWebhookProfileName($values, $webhookPath),
            );
        }

        return self::applyActiveProfileConfiguration($values, $allowPlaceholderApiKeys);
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return array{
     *     values: array<string, mixed>,
     *     idempotencyKey: string|null
     * }
     */
    public static function resolveGeneratedIdempotencyKey(array $operation, array $values): array
    {
        $path = $operation['idempotency_key_path'] ?? null;

        if ($path === null) {
            return [
                'values' => $values,
                'idempotencyKey' => null,
            ];
        }

        if (! is_string($path) || trim($path) === '') {
            throw new PlaygroundException('Operation idempotency_key_path must be null or a non-empty string.');
        }

        $current = self::value($values, $path);

        if (is_string($current)) {
            $current = trim($current);

            if ($current !== '' && ! str_starts_with($current, 'REPLACE_WITH_')) {
                return [
                    'values' => $values,
                    'idempotencyKey' => $current,
                ];
            }
        } elseif ($current !== null) {
            throw new PlaygroundException(sprintf(
                'Expected [%s] to be a string or null, %s given.',
                $path,
                get_debug_type($current),
            ));
        }

        $generated = self::generateIdempotencyKey($operation['_name'] ?? 'operation');

        return [
            'values' => self::withValue($values, $path, $generated),
            'idempotencyKey' => $generated,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function mergeValues(array $values, array $overrides): array
    {
        foreach ($overrides as $key => $override) {
            if (
                isset($values[$key])
                && is_array($values[$key])
                && is_array($override)
                && ! array_is_list($values[$key])
                && ! array_is_list($override)
            ) {
                /** @var array<string, mixed> $nestedValues */
                $nestedValues = $values[$key];
                /** @var array<string, mixed> $nestedOverrides */
                $nestedOverrides = $override;
                $values[$key] = self::mergeValues($nestedValues, $nestedOverrides);

                continue;
            }

            $values[$key] = $override;
        }

        return $values;
    }

    public static function createClient(array $values): Client
    {
        $profileName = self::activeProfileName($values);
        $factory = new ClientFactory(self::credentialProfiles($values, $profileName));

        return $factory->forProfile($profileName);
    }

    public static function activeProfileName(array $values): string
    {
        return self::stringValue(self::value($values, 'shared.activeProfile'), 'shared.activeProfile');
    }

    public static function profileHasWebhookSecret(array $values, string $profileName): bool
    {
        $profile = self::profileDefinition($values, $profileName);
        $secretEnv = self::nullableString($profile['webhookSecretEnv'] ?? null);

        if ($secretEnv === null) {
            return false;
        }

        $secret = getenv($secretEnv);

        return is_string($secret) && trim($secret) !== '';
    }

    public static function resolveWebhookProfileName(array $values, string $webhookPath): string
    {
        $normalizedPath = self::normalizeWebhookPath($webhookPath);
        $routes = self::value($values, 'webhookRoutes');

        if (is_array($routes) && ! array_is_list($routes)) {
            $resolvedProfile = $routes[$normalizedPath] ?? null;

            if ($resolvedProfile !== null) {
                return self::stringValue($resolvedProfile, 'webhookRoutes.' . $normalizedPath);
            }
        }

        return self::activeProfileName($values);
    }

    public static function credentialProfiles(
        array $values,
        ?string $onlyProfile = null,
        bool $allowPlaceholderApiKeys = false,
    ): CredentialProfiles {
        $profileNames = $onlyProfile === null
            ? self::profileNames($values)
            : [trim($onlyProfile)];
        $profiles = [];

        foreach ($profileNames as $profileName) {
            $profile = self::profileDefinition($values, $profileName);
            $environmentValue = self::stringValue(
                $profile['environment'] ?? null,
                'profiles.' . $profileName . '.environment',
            );

            try {
                $environment = Environment::from($environmentValue);
            } catch (ValueError $exception) {
                throw new PlaygroundException(
                    sprintf('Invalid environment value for playground credential profile [%s].', $profileName),
                    context: [
                        'profile' => $profileName,
                        'environment' => $environmentValue,
                    ],
                    previous: $exception,
                );
            }

            $apiKeyEnv = self::stringValue(
                $profile['apiKeyEnv'] ?? null,
                'profiles.' . $profileName . '.apiKeyEnv',
            );
            $apiKey = self::environmentVariableValue(
                $apiKeyEnv,
                $profileName,
                allowPlaceholder: $allowPlaceholderApiKeys,
            );
            $webhookSecretEnv = self::nullableString($profile['webhookSecretEnv'] ?? null);
            $webhookSecret = $webhookSecretEnv === null
                ? null
                : self::nullableEnvironmentVariableValue($webhookSecretEnv);

            $profiles[$profileName] = new CredentialProfile(
                apiKey: $apiKey,
                environment: $environment,
                baseUrl: self::nullableString($profile['baseUrl'] ?? null),
                timeout: self::nullableFloat($profile['timeout'] ?? null),
                userAgentSuffix: self::nullableString($profile['userAgentSuffix'] ?? null),
                allowUnsafeBaseUrlOverride: false,
                webhookSecret: $webhookSecret,
            );
        }

        return new CredentialProfiles($profiles);
    }

    /**
     * @param  list<array<string, mixed>>  $mappings
     * @return array{
     *     values: array<string, mixed>,
     *     changes: array<string, mixed>
     * }
     */
    public static function persistResponseValues(array $values, array $mappings, mixed $response): array
    {
        $normalized = self::normalize($response);
        $changes = [];

        foreach ($mappings as $mapping) {
            self::assertPersistMapping($mapping);

            $path = $mapping['path'];
            $source = $mapping['source'];

            if (! is_string($path) || ! is_string($source)) {
                throw new PlaygroundException('Persist mappings must use string path/source values.');
            }

            $resolved = self::responseValue($normalized, $source);

            if ($resolved === null) {
                continue;
            }

            $currentValue = self::value($values, $path);

            if ($currentValue === $resolved) {
                continue;
            }

            $values = self::withValue($values, $path, $resolved);
            $changes[$path] = $resolved;
        }

        return [
            'values' => $values,
            'changes' => $changes,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $operations
     * @return array{
     *     ok: bool,
     *     kind: string,
     *     summary: array<string, int>,
     *     findings: list<array<string, mixed>>
     * }
     */
    public static function auditOperations(array $operations, string $repoRoot): array
    {
        $resourceFiles = glob($repoRoot . '/src/Resource/*Resource.php') ?: [];
        sort($resourceFiles);

        $expected = [];

        foreach ($resourceFiles as $resourceFile) {
            $class = 'Antoniadisio\\Creem\\Resource\\' . pathinfo($resourceFile, PATHINFO_FILENAME);

            if ($class === 'Antoniadisio\\Creem\\Resource\\Resource' || ! class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            $resource = strtolower(substr($reflection->getShortName(), 0, -strlen('Resource')));

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $class || str_starts_with($method->getName(), '__')) {
                    continue;
                }

                $expected[] = $resource . '/' . self::snakeCase($method->getName());
            }
        }

        sort($expected);

        /** @var list<string> $actual */
        $actual = array_keys($operations);
        sort($actual);

        $expectedSet = array_fill_keys($expected, true);
        $actualSet = array_fill_keys($actual, true);
        $findings = [];

        foreach ($expected as $name) {
            if (! isset($actualSet[$name])) {
                $findings[] = [
                    'code' => 'missing_operation',
                    'message' => sprintf('Missing playground action for SDK method [%s].', $name),
                    'context' => ['operation' => $name],
                ];
            }
        }

        foreach ($actual as $name) {
            if (! isset($expectedSet[$name])) {
                $findings[] = [
                    'code' => 'orphan_operation',
                    'message' => sprintf('Orphan playground action [%s] has no matching SDK method.', $name),
                    'context' => ['operation' => $name],
                ];
            }
        }

        foreach ($operations as $name => $operation) {
            if (! is_array($operation)) {
                continue;
            }

            if (! is_array($operation['defaults'] ?? null)) {
                $findings[] = [
                    'code' => 'invalid_defaults',
                    'message' => sprintf('Operation [%s] must declare defaults as an array.', $name),
                    'context' => ['operation' => $name],
                ];
            }

            if (! is_array($operation['inputs'] ?? null) || ! array_is_list($operation['inputs'])) {
                $findings[] = [
                    'code' => 'invalid_inputs',
                    'message' => sprintf('Operation [%s] must declare inputs as a list.', $name),
                    'context' => ['operation' => $name],
                ];
            }

            if (($operation['operation_mode'] ?? null) === 'write') {
                $idempotencyPath = $operation['idempotency_key_path'] ?? null;

                if (! is_string($idempotencyPath) || trim($idempotencyPath) === '') {
                    $findings[] = [
                        'code' => 'missing_idempotency_path',
                        'message' => sprintf('Write operation [%s] must declare idempotency_key_path.', $name),
                        'context' => ['operation' => $name],
                    ];

                    continue;
                }

                $inputPaths = array_keys(self::fieldsByPath($operation['inputs'] ?? []));

                if (! in_array($idempotencyPath, $inputPaths, true)) {
                    $findings[] = [
                        'code' => 'missing_idempotency_input',
                        'message' => sprintf('Write operation [%s] must expose its idempotency_key_path as an input.', $name),
                        'context' => [
                            'operation' => $name,
                            'idempotency_key_path' => $idempotencyPath,
                        ],
                    ];
                }
            } elseif (($operation['idempotency_key_path'] ?? null) !== null) {
                $findings[] = [
                    'code' => 'unexpected_idempotency_path',
                    'message' => sprintf('Read operation [%s] must not declare idempotency_key_path.', $name),
                    'context' => ['operation' => $name],
                ];
            }
        }

        return [
            'ok' => $findings === [],
            'kind' => 'audit',
            'summary' => [
                'sdk_methods' => count($expected),
                'playground_operations' => count($actual),
                'findings' => count($findings),
            ],
            'findings' => $findings,
        ];
    }

    public static function startTrace(array $values): PlaygroundTrace
    {
        $trace = new PlaygroundTrace(
            secrets: [
                self::nullableString(self::value($values, 'shared.apiKey')),
            ],
        );

        SaloonConfig::clearGlobalMiddleware();
        SaloonConfig::globalMiddleware()
            ->onRequest(static function (PendingRequest $pendingRequest) use ($trace): void {
                $trace->captureRequest($pendingRequest);
            }, 'playgroundTraceRequest')
            ->onResponse(static function (SaloonResponse $response) use ($trace): void {
                $trace->captureResponse($response);
            }, 'playgroundTraceResponse')
            ->onFatalException(static function (FatalRequestException $exception) use ($trace): void {
                $trace->captureFatal($exception);
            }, 'playgroundTraceFatal');

        return $trace;
    }

    /**
     * @return array<string, mixed>
     */
    public static function stopTrace(PlaygroundTrace $trace): array
    {
        SaloonConfig::clearGlobalMiddleware();

        return $trace->snapshot();
    }

    /**
     * @param  list<string>  $requiredPaths
     */
    public static function validateRequiredValues(array $values, array $requiredPaths): void
    {
        $unresolved = [];

        foreach ($requiredPaths as $path) {
            if (! is_string($path) || trim($path) === '') {
                throw new PlaygroundException('Required value paths must be non-empty strings.');
            }

            if (! self::isResolved($values, $path)) {
                $unresolved[] = $path;
            }
        }

        if ($unresolved !== []) {
            throw new PlaygroundException(
                'One or more required playground values are unresolved.',
                context: ['unresolved' => $unresolved],
            );
        }
    }

    public static function value(array $values, string $path): mixed
    {
        $segments = explode('.', $path);
        $current = $values;

        foreach ($segments as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * @param  string|list<string>  $fixtures
     */
    public static function loadFixtures(string $fixturesPath, string|array $fixtures): mixed
    {
        if (is_string($fixtures)) {
            return self::loadFixture($fixturesPath, $fixtures);
        }

        if (! array_is_list($fixtures)) {
            throw new PlaygroundException('Operation fixtures must be a string or a list of strings.');
        }

        if (count($fixtures) === 1) {
            return self::loadFixture($fixturesPath, $fixtures[0]);
        }

        $loaded = [];

        foreach ($fixtures as $fixture) {
            if (! is_string($fixture) || trim($fixture) === '') {
                throw new PlaygroundException('Fixture paths must be non-empty strings.');
            }

            $loaded[$fixture] = self::loadFixture($fixturesPath, $fixture);
        }

        return $loaded;
    }

    public static function enumValue(string $enumClass, mixed $value, string $path): UnitEnum
    {
        if (! enum_exists($enumClass)) {
            throw new PlaygroundException(sprintf('Enum class [%s] does not exist.', $enumClass));
        }

        $value = self::stringValue($value, $path);

        try {
            if (is_a($enumClass, BackedEnum::class, true)) {
                /** @var class-string<BackedEnum> $enumClass */
                return $enumClass::from($value);
            }

            foreach ($enumClass::cases() as $case) {
                if ($case->name === $value) {
                    return $case;
                }
            }
        } catch (Throwable $exception) {
            throw new PlaygroundException(
                sprintf('Invalid enum value for [%s].', $path),
                context: ['path' => $path, 'value' => $value, 'enum' => $enumClass],
                previous: $exception,
            );
        }

        throw new PlaygroundException(
            sprintf('Invalid enum value for [%s].', $path),
            context: ['path' => $path, 'value' => $value, 'enum' => $enumClass],
        );
    }

    public static function nullableDateTime(mixed $value, string $path): ?DateTimeImmutable
    {
        $value = self::nullableString($value);

        if ($value === null) {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Throwable $exception) {
            throw new PlaygroundException(
                sprintf('Invalid datetime value for [%s].', $path),
                context: ['path' => $path, 'value' => $value],
                previous: $exception,
            );
        }
    }

    public static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new PlaygroundException(sprintf(
                'Expected string or null, %s given.',
                get_debug_type($value),
            ));
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    public static function stringValue(mixed $value, string $path): string
    {
        if (! is_string($value)) {
            throw new PlaygroundException(sprintf(
                'Expected [%s] to be a string, %s given.',
                $path,
                get_debug_type($value),
            ));
        }

        $value = trim($value);

        if ($value === '') {
            throw new PlaygroundException(sprintf('Expected [%s] to be a non-empty string.', $path));
        }

        return $value;
    }

    public static function intValue(mixed $value, string $path): int
    {
        if (! is_int($value)) {
            throw new PlaygroundException(sprintf(
                'Expected [%s] to be an int, %s given.',
                $path,
                get_debug_type($value),
            ));
        }

        return $value;
    }

    public static function boolValue(mixed $value, string $path): bool
    {
        if (! is_bool($value)) {
            throw new PlaygroundException(sprintf(
                'Expected [%s] to be a bool, %s given.',
                $path,
                get_debug_type($value),
            ));
        }

        return $value;
    }

    public static function nullableFloat(mixed $value): int|float|null
    {
        if ($value === null) {
            return null;
        }

        if (! is_int($value) && ! is_float($value)) {
            throw new PlaygroundException(sprintf(
                'Expected int, float, or null, %s given.',
                get_debug_type($value),
            ));
        }

        return $value;
    }

    public static function printJson(mixed $value, string $stream = 'stdout'): void
    {
        try {
            $output = (string) json_encode(
                self::normalize($value),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            throw new PlaygroundException('Unable to encode playground JSON output.', previous: $exception);
        }

        $target = $stream === 'stderr' ? 'php://stderr' : 'php://output';

        if (file_put_contents($target, $output . "\n") === false) {
            throw new PlaygroundException(sprintf('Unable to write playground JSON output to [%s].', $stream));
        }
    }

    /**
     * @param  array<string, mixed>|null  $operation
     * @param  array<string, mixed>  $stateChanges
     * @return array<string, mixed>
     */
    public static function jsonEnvelope(
        ?array $operation,
        ?string $profile,
        mixed $inputs,
        mixed $requestPayload,
        mixed $exampleResponse,
        mixed $liveResponse,
        array $stateChanges = [],
        ?string $idempotencyKey = null,
        array $transport = [],
        ?Throwable $exception = null,
    ): array {
        return [
            'ok' => $exception === null,
            'kind' => 'operation_result',
            'operation' => is_array($operation) && is_string($operation['_name'] ?? null) ? $operation['_name'] : null,
            'operation_mode' => is_array($operation) && is_string($operation['operation_mode'] ?? null) ? $operation['operation_mode'] : null,
            'profile' => $profile,
            'sdk_call' => is_array($operation) && is_string($operation['sdk_call'] ?? null) ? $operation['sdk_call'] : null,
            'request' => [
                'method' => is_array($operation) && is_string($operation['http_method'] ?? null) ? $operation['http_method'] : null,
                'path' => is_array($operation) && is_string($operation['path'] ?? null) ? $operation['path'] : null,
                'idempotency_key' => $idempotencyKey,
                'inputs' => self::normalize($inputs),
                'payload' => self::normalize($requestPayload),
            ],
            'example_response' => self::normalize($exampleResponse),
            'live_response' => self::normalize($liveResponse),
            'transport' => self::normalize($transport),
            'state_changes' => self::normalize($stateChanges),
            'error' => $exception === null ? null : [
                'class' => $exception::class,
                'message' => $exception->getMessage(),
                'status_code' => self::statusCode($exception),
                'context' => self::context($exception),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|list<mixed>|string|int|float|bool|null
     */
    public static function normalize(mixed $value): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::RFC3339_EXTENDED);
        }

        if ($value instanceof Page) {
            return [
                'items' => self::normalize($value->items()),
                'pagination' => self::normalize($value->pagination),
            ];
        }

        if ($value instanceof StructuredObject) {
            return self::normalize($value->all());
        }

        if ($value instanceof StructuredList) {
            return self::normalize($value->all());
        }

        if ($value instanceof ExpandableResource) {
            return [
                'id' => $value->id(),
                'isExpanded' => $value->isExpanded(),
                'resource' => self::normalize($value->resource()),
            ];
        }

        if (is_array($value)) {
            $normalized = [];

            foreach ($value as $key => $item) {
                $normalized[$key] = self::normalize($item);
            }

            return $normalized;
        }

        if (method_exists($value, 'toArray')) {
            /** @var mixed $arrayValue */
            $arrayValue = $value->toArray();

            return self::normalize($arrayValue);
        }

        $publicValues = get_object_vars($value);

        if ($publicValues !== []) {
            return self::normalize($publicValues);
        }

        return ['class' => $value::class];
    }

    public static function statusCode(Throwable $exception): int|string|null
    {
        if ($exception instanceof PlaygroundException) {
            return $exception->statusCode();
        }

        if ($exception instanceof CreemException) {
            return $exception->statusCode();
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function context(Throwable $exception): array
    {
        if ($exception instanceof PlaygroundException) {
            return $exception->context();
        }

        if ($exception instanceof CreemException) {
            return $exception->context();
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return list<string>
     */
    private static function profileNames(array $values): array
    {
        $profiles = self::value($values, 'profiles');

        if (! is_array($profiles) || array_is_list($profiles) || $profiles === []) {
            throw new PlaygroundException('Playground credential profiles must be declared as a non-empty object.');
        }

        /** @var list<string> $names */
        $names = array_keys($profiles);

        return $names;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private static function applyActiveProfileConfiguration(array $values, bool $allowPlaceholderApiKeys = false): array
    {
        $profileName = self::activeProfileName($values);
        $profile = self::profileDefinition($values, $profileName);
        $apiKeyEnv = self::stringValue(
            $profile['apiKeyEnv'] ?? null,
            'profiles.' . $profileName . '.apiKeyEnv',
        );

        $values = self::withValue($values, 'shared.activeProfile', $profileName);
        $values = self::withValue(
            $values,
            'shared.apiKey',
            self::environmentVariableValue(
                $apiKeyEnv,
                $profileName,
                allowPlaceholder: $allowPlaceholderApiKeys,
            ),
        );
        $values = self::withValue(
            $values,
            'shared.environment',
            self::stringValue($profile['environment'] ?? null, 'profiles.' . $profileName . '.environment'),
        );
        $values = self::withValue($values, 'shared.baseUrl', self::nullableString($profile['baseUrl'] ?? null));
        $values = self::withValue($values, 'shared.timeout', self::nullableFloat($profile['timeout'] ?? null));
        $values = self::withValue(
            $values,
            'shared.userAgentSuffix',
            self::nullableString($profile['userAgentSuffix'] ?? null),
        );

        return $values;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private static function profileDefinition(array $values, string $profileName): array
    {
        $profiles = self::value($values, 'profiles');

        if (! is_array($profiles) || array_is_list($profiles)) {
            throw new PlaygroundException('Playground credential profiles must be declared as an object.');
        }

        $profile = $profiles[$profileName] ?? null;

        if (! is_array($profile) || array_is_list($profile)) {
            throw new PlaygroundException(
                sprintf('Playground credential profile [%s] is not defined.', $profileName),
                context: ['profile' => $profileName],
            );
        }

        return $profile;
    }

    private static function environmentVariableValue(
        string $envName,
        string $profileName,
        bool $allowPlaceholder = false,
    ): string {
        $value = self::nullableEnvironmentVariableValue($envName);

        if ($value !== null) {
            return $value;
        }

        if ($allowPlaceholder) {
            return 'creem_test_profile_' . $profileName;
        }

        throw new PlaygroundException(
            sprintf(
                'The environment variable [%s] for playground credential profile [%s] is missing or blank.',
                $envName,
                $profileName,
            ),
            context: [
                'profile' => $profileName,
                'env' => $envName,
            ],
        );
    }

    private static function nullableEnvironmentVariableValue(string $envName): ?string
    {
        $value = getenv($envName);

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private static function workspacePathOverride(string $envName, string $default): string
    {
        $value = getenv($envName);

        if (! is_string($value)) {
            return $default;
        }

        $value = trim($value);

        return $value === '' ? $default : $value;
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadJsonObjectFile(string $path, string $label): array
    {
        if (! file_exists($path)) {
            throw new PlaygroundException(sprintf('%s [%s] does not exist.', $label, $path));
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new PlaygroundException(sprintf('Unable to read %s [%s].', strtolower($label), $path));
        }

        return self::decodeJsonObject($contents, $label . ' [' . $path . ']');
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeJsonObject(?string $contents, string $label): array
    {
        if ($contents === null) {
            throw new PlaygroundException(sprintf('%s is missing.', $label));
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PlaygroundException(
                sprintf('%s contains invalid JSON.', $label),
                previous: $exception,
            );
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new PlaygroundException(sprintf('%s must contain a JSON object.', $label));
        }

        return $decoded;
    }

    private static function ensureDirectory(string $path): void
    {
        if ($path === '' || is_dir($path)) {
            return;
        }

        if (! mkdir($path, 0777, true) && ! is_dir($path)) {
            throw new PlaygroundException(sprintf('Unable to create directory [%s].', $path));
        }
    }

    private static function normalizeWebhookPath(string $path): string
    {
        $path = trim($path);

        if ($path === '' || $path === '/') {
            return '/';
        }

        if (! str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        $path = rtrim($path, '/');

        return $path === '' ? '/' : $path;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private static function withValue(array $values, string $path, mixed $value): array
    {
        $segments = explode('.', $path);
        $cursor = &$values;

        foreach ($segments as $index => $segment) {
            if ($index === array_key_last($segments)) {
                $cursor[$segment] = $value;

                return $values;
            }

            if (! isset($cursor[$segment]) || ! is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }

            $cursor = &$cursor[$segment];
        }

        return $values;
    }

    private static function responseValue(mixed $value, string $path): mixed
    {
        $segments = explode('.', $path);
        $current = $value;

        foreach ($segments as $segment) {
            if (! is_array($current)) {
                return null;
            }

            if (ctype_digit($segment)) {
                $index = (int) $segment;

                if (! array_key_exists($index, $current)) {
                    return null;
                }

                $current = $current[$index];

                continue;
            }

            if (! array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @return array<string, array<string, mixed>>
     */
    private static function fieldsByPath(array $fields): array
    {
        $indexed = [];

        foreach ($fields as $field) {
            self::assertInputField($field);

            $path = $field['path'];

            if (! is_string($path)) {
                throw new PlaygroundException('Input field paths must be strings.');
            }

            $indexed[$path] = $field;
        }

        return $indexed;
    }

    private static function snakeCase(string $value): string
    {
        $value = preg_replace('/(?<!^)[A-Z]/', '_$0', $value);

        if (! is_string($value)) {
            throw new PlaygroundException('Unable to convert value to snake_case.');
        }

        return strtolower($value);
    }

    private static function generateIdempotencyKey(string $operationName): string
    {
        $suffix = bin2hex(random_bytes(4));

        return self::snakeCase(str_replace('/', '-', $operationName)) . '-' . gmdate('Ymd\THis\Z') . '-' . $suffix;
    }

    private static function sortMapKeys(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            foreach ($value as $index => $item) {
                $value[$index] = self::sortMapKeys($item);
            }

            return $value;
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = self::sortMapKeys($item);
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $operation
     */
    private static function assertOperationDefinition(string $path, array $operation): void
    {
        $requiredKeys = [
            'resource',
            'action',
            'operation_mode',
            'sdk_call',
            'http_method',
            'path',
            'fixtures',
            'required_values',
            'defaults',
            'inputs',
            'idempotency_key_path',
            'persist_outputs',
            'build_inputs',
            'build_request_payload',
            'run',
        ];

        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $operation)) {
                throw new PlaygroundException(sprintf(
                    'Operation file [%s] is missing required key [%s].',
                    $path,
                    $key,
                ));
            }
        }

        $resource = basename(dirname($path));
        $action = pathinfo($path, PATHINFO_FILENAME);

        if ($operation['resource'] !== $resource || $operation['action'] !== $action) {
            throw new PlaygroundException(sprintf(
                'Operation file [%s] must declare resource/action as [%s/%s].',
                $path,
                $resource,
                $action,
            ));
        }

        if (
            ! is_string($operation['operation_mode'])
            || ! in_array($operation['operation_mode'], ['read', 'write'], true)
        ) {
            throw new PlaygroundException(sprintf(
                'Operation file [%s] must declare operation_mode as read or write.',
                $path,
            ));
        }

        if (! is_array($operation['required_values']) || ! array_is_list($operation['required_values'])) {
            throw new PlaygroundException(sprintf(
                'Operation file [%s] must declare required_values as a list.',
                $path,
            ));
        }

        if (! is_array($operation['defaults'])) {
            throw new PlaygroundException(sprintf(
                'Operation file [%s] must declare [defaults] as an array.',
                $path,
            ));
        }

        foreach (['inputs', 'persist_outputs'] as $key) {
            if (! is_array($operation[$key]) || ! array_is_list($operation[$key])) {
                throw new PlaygroundException(sprintf(
                    'Operation file [%s] must declare [%s] as a list.',
                    $path,
                    $key,
                ));
            }
        }

        if ($operation['idempotency_key_path'] !== null && ! is_string($operation['idempotency_key_path'])) {
            throw new PlaygroundException(sprintf(
                'Operation file [%s] must declare [idempotency_key_path] as a string or null.',
                $path,
            ));
        }

        foreach (['build_inputs', 'build_request_payload', 'run'] as $key) {
            if (! $operation[$key] instanceof Closure) {
                throw new PlaygroundException(sprintf(
                    'Operation file [%s] key [%s] must be a closure.',
                    $path,
                    $key,
                ));
            }
        }
    }

    private static function isResolved(array $values, string $path): bool
    {
        $segments = explode('.', $path);
        $current = $values;

        foreach ($segments as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return false;
            }

            $current = $current[$segment];
        }

        if ($current === null) {
            return false;
        }

        if (! is_string($current)) {
            return true;
        }

        $current = trim($current);

        return $current !== '' && ! str_starts_with($current, 'REPLACE_WITH_');
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private static function assertInputField(array $field): void
    {
        foreach (['path', 'label', 'type', 'nullable', 'choices', 'enum'] as $key) {
            if (! array_key_exists($key, $field)) {
                throw new PlaygroundException(sprintf('Input fields must declare [%s].', $key));
            }
        }
    }

    /**
     * @param  array<string, mixed>  $mapping
     */
    private static function assertPersistMapping(array $mapping): void
    {
        foreach (['path', 'source'] as $key) {
            if (! array_key_exists($key, $mapping)) {
                throw new PlaygroundException(sprintf('Persist output mappings must declare [%s].', $key));
            }
        }
    }

    private static function loadFixture(string $fixturesPath, string $fixture): mixed
    {
        $fixture = trim($fixture);

        if ($fixture === '') {
            throw new PlaygroundException('Fixture paths cannot be blank.');
        }

        $path = $fixturesPath . '/' . $fixture;

        if (! file_exists($path)) {
            throw new PlaygroundException(sprintf('Fixture [%s] does not exist.', $path));
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new PlaygroundException(sprintf('Unable to read fixture [%s].', $path));
        }

        try {
            return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PlaygroundException(
                sprintf('Fixture [%s] contains invalid JSON.', $path),
                previous: $exception,
            );
        }
    }
}

final class PlaygroundException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message,
        private readonly array $context = [],
        private readonly ?int $statusCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    public function statusCode(): ?int
    {
        return $this->statusCode;
    }
}

final class PlaygroundTrace
{
    /**
     * @var list<string>
     */
    private array $secrets;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $request = null;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $response = null;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $fatal = null;

    /**
     * @param  list<string|null>  $secrets
     */
    public function __construct(array $secrets)
    {
        $this->secrets = array_values(array_filter($secrets, static fn(?string $secret): bool => is_string($secret) && $secret !== ''));
    }

    public function captureRequest(PendingRequest $pendingRequest): void
    {
        $request = $pendingRequest->createPsrRequest();

        $this->request = [
            'method' => $request->getMethod(),
            'url' => (string) $request->getUri(),
            'headers' => $this->redactHeaders($request->getHeaders()),
            'body' => $this->redactString($this->readBody($request)),
        ];
    }

    public function captureResponse(SaloonResponse $response): void
    {
        $psrResponse = $response->getPsrResponse();

        if ($this->request === null) {
            $this->capturePsrRequest($response->getPsrRequest());
        }

        $this->response = [
            'status_code' => $psrResponse->getStatusCode(),
            'headers' => $this->redactHeaders($psrResponse->getHeaders()),
            'body' => $this->redactString($this->readBody($psrResponse)),
        ];
    }

    public function captureFatal(FatalRequestException $exception): void
    {
        if ($this->request === null) {
            $this->captureRequest($exception->getPendingRequest());
        }

        $this->fatal = [
            'class' => $exception::class,
            'message' => $this->redactString($exception->getMessage()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return [
            'request' => $this->request,
            'response' => $this->response,
            'fatal' => $this->fatal,
        ];
    }

    private function capturePsrRequest(RequestInterface $request): void
    {
        $this->request = [
            'method' => $request->getMethod(),
            'url' => (string) $request->getUri(),
            'headers' => $this->redactHeaders($request->getHeaders()),
            'body' => $this->redactString($this->readBody($request)),
        ];
    }

    /**
     * @param  array<string, array<int, string>>  $headers
     * @return array<string, string|list<string>>
     */
    private function redactHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $name => $values) {
            $lowerName = strtolower($name);

            if (in_array($lowerName, ['authorization', 'x-api-key', 'api-key', 'cookie', 'set-cookie'], true)) {
                $normalized[$name] = count($values) === 1 ? '[REDACTED]' : array_fill(0, count($values), '[REDACTED]');

                continue;
            }

            $redactedValues = array_map(fn(string $value): string => $this->redactString($value), $values);
            $normalized[$name] = count($redactedValues) === 1 ? $redactedValues[0] : $redactedValues;
        }

        return $normalized;
    }

    private function readBody(MessageInterface $message): string
    {
        $stream = $message->getBody();
        $contents = $stream->getContents();

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        return $contents;
    }

    private function redactString(string $value): string
    {
        foreach ($this->secrets as $secret) {
            if ($secret !== '') {
                $value = str_replace($secret, '[REDACTED]', $value);
            }
        }

        $value = preg_replace('/Bearer\s+[A-Za-z0-9._\-]+/i', 'Bearer [REDACTED]', $value);

        if (! is_string($value)) {
            return '[REDACTED]';
        }

        return $value;
    }
}
