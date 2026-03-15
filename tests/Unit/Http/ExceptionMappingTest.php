<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\Config;
use Antoniadisio\Creem\Exception\AuthenticationException;
use Antoniadisio\Creem\Exception\CreemException;
use Antoniadisio\Creem\Exception\NotFoundException;
use Antoniadisio\Creem\Exception\RateLimitException;
use Antoniadisio\Creem\Exception\ServerException;
use Antoniadisio\Creem\Exception\TransportException;
use Antoniadisio\Creem\Exception\ValidationException;
use Antoniadisio\Creem\Internal\Http\CreemConnector;
use Antoniadisio\Creem\Tests\Support\HttpTestSupport;
use RuntimeException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;

use function array_key_exists;

test('transport failures are wrapped without leaking sender exception internals', function (): void {
    $connector = new CreemConnector(new Config('sk_test_very_secret_123'));
    $mockResponse = MockResponse::make()->throw(
        static fn (PendingRequest $pendingRequest): FatalRequestException => new FatalRequestException(
            new RuntimeException('Socket closed for sk_test_very_secret_123'),
            $pendingRequest,
        ),
    );

    $exception = HttpTestSupport::captureException(
        static fn (): Response => $connector->send(HttpTestSupport::pingRequest(), new MockClient([$mockResponse])),
    );

    expect($exception)->toBeInstanceOf(TransportException::class)
        ->and($exception?->getMessage())->toBe('The Creem API request could not be completed.')
        ->and($exception?->getMessage())->not->toContain('sk_test_very_secret_123')
        ->and($exception?->statusCode())->toBeNull()
        ->and($exception?->context())->toBe([])
        ->and($exception?->getPrevious())->toBeNull();
});

test('plain text error bodies redact sensitive token patterns', function (): void {
    $connector = new CreemConnector(new Config('sk_test_123'));
    $exception = HttpTestSupport::captureException(
        static fn (): Response => $connector->send(
            HttpTestSupport::pingRequest(),
            new MockClient([
                MockResponse::make('Rejected sk_live_secret creem_live_secret whsec_secret', 500),
            ]),
        ),
    );

    expect($exception)->toBeInstanceOf(ServerException::class)
        ->and($exception?->getMessage())->toBe('Rejected [redacted] [redacted] [redacted]')
        ->and($exception?->context())->toBe(['body' => 'Rejected [redacted] [redacted] [redacted]'])
        ->and($exception?->getMessage())->not->toContain('sk_live_secret')
        ->and($exception?->getMessage())->not->toContain('creem_live_secret')
        ->and($exception?->getMessage())->not->toContain('whsec_secret')
        ->and($exception?->getPrevious())->toBeNull();
});

test('json error messages and context redact sensitive token patterns', function (): void {
    $connector = new CreemConnector(new Config('sk_test_123'));
    $exception = HttpTestSupport::captureException(
        static fn (): Response => $connector->send(
            HttpTestSupport::pingRequest(),
            new MockClient([
                MockResponse::make([
                    'message' => 'Invalid key sk_live_secret',
                    'detail' => 'Credential creem_live_secret is invalid',
                    'errors' => [
                        [
                            'detail' => 'Webhook secret whsec_secret is invalid',
                        ],
                    ],
                ], 422),
            ]),
        ),
    );

    expect($exception)->toBeInstanceOf(ValidationException::class)
        ->and($exception?->getMessage())->toBe('Invalid key [redacted]')
        ->and($exception?->context())->toBe([
            'message' => 'Invalid key [redacted]',
            'detail' => 'Credential [redacted] is invalid',
            'errors' => [
                [
                    'detail' => 'Webhook secret [redacted] is invalid',
                ],
            ],
        ])
        ->and($exception?->getPrevious())->toBeNull();
});

foreach (connectorResponseFailureMappings() as $dataset => [$response, $expectedException, $expectedMessage, $expectedStatus, $expectedContext]) {
    test("http failures map to typed exceptions with preserved context ({$dataset})", function () use (
        $response,
        $expectedException,
        $expectedMessage,
        $expectedStatus,
        $expectedContext,
    ): void {
        $connector = new CreemConnector(new Config('sk_test_123'));
        $exception = HttpTestSupport::captureException(
            static fn (): Response => $connector->send(HttpTestSupport::pingRequest(), new MockClient([$response])),
        );

        expect($exception)->toBeInstanceOf(CreemException::class)
            ->and($exception instanceof $expectedException)->toBeTrue()
            ->and($exception?->getMessage())->toBe($expectedMessage)
            ->and($exception?->statusCode())->toBe($expectedStatus)
            ->and($exception?->context())->toBe($expectedContext);

        if ($exception instanceof ValidationException && array_key_exists('errors', $expectedContext)) {
            expect($exception->errors())->toBe($expectedContext['errors']);
        }

        if ($exception instanceof RateLimitException) {
            expect($exception->retryAfterSeconds())->toBe($expectedContext['retry_after_seconds'] ?? null);
        }
    });
}

test('generic client errors map to the base exception type', function (): void {
    $connector = new CreemConnector(new Config('sk_test_123'));
    $exception = HttpTestSupport::captureException(
        static fn (): Response => $connector->send(
            HttpTestSupport::pingRequest(),
            new MockClient([
                MockResponse::make(['detail' => 'Conflict'], 409),
            ]),
        ),
    );

    expect($exception)->toBeInstanceOf(CreemException::class)
        ->and($exception)->not->toBeInstanceOf(AuthenticationException::class)
        ->and($exception)->not->toBeInstanceOf(NotFoundException::class)
        ->and($exception)->not->toBeInstanceOf(ValidationException::class)
        ->and($exception)->not->toBeInstanceOf(RateLimitException::class)
        ->and($exception)->not->toBeInstanceOf(ServerException::class)
        ->and($exception?->getMessage())->toBe('Conflict')
        ->and($exception?->statusCode())->toBe(409)
        ->and($exception?->context())->toBe(['detail' => 'Conflict']);
});

test('array-based top-level messages are preserved and surfaced', function (): void {
    $connector = new CreemConnector(new Config('sk_test_123'));
    $exception = HttpTestSupport::captureException(
        static fn (): Response => $connector->send(
            HttpTestSupport::pingRequest(),
            new MockClient([
                MockResponse::make([
                    'error' => 'Bad Request',
                    'message' => ['description must be a string'],
                ], 400),
            ]),
        ),
    );

    expect($exception)->toBeInstanceOf(CreemException::class)
        ->and($exception?->getMessage())->toBe('description must be a string')
        ->and($exception?->statusCode())->toBe(400)
        ->and($exception?->context())->toBe([
            'message' => ['description must be a string'],
            'error' => 'Bad Request',
        ]);
});

test('nested validation errors resolve useful messages', function (): void {
    $connector = new CreemConnector(new Config('sk_test_123'));
    $errors = [
        [
            'meta' => [
                'detail' => 'Nested error message',
            ],
        ],
    ];
    $exception = HttpTestSupport::captureException(
        static fn (): Response => $connector->send(
            HttpTestSupport::pingRequest(),
            new MockClient([
                MockResponse::make(['errors' => $errors], 400),
            ]),
        ),
    );

    expect($exception)->toBeInstanceOf(ValidationException::class)
        ->and($exception?->getMessage())->toBe('Nested error message')
        ->and($exception?->statusCode())->toBe(400)
        ->and($exception?->context())->toBe(['errors' => $errors]);

    if ($exception instanceof ValidationException) {
        expect($exception->errors())->toBe($errors);
    }
});

test('deeply nested validation errors stop traversing after the recursion guard', function (): void {
    $connector = new CreemConnector(new Config('sk_test_123'));
    $errors = ['level_1' => ['level_2' => ['level_3' => ['level_4' => ['level_5' => ['level_6' => ['detail' => 'Too deep']]]]]]];
    $exception = HttpTestSupport::captureException(
        static fn (): Response => $connector->send(
            HttpTestSupport::pingRequest(),
            new MockClient([
                MockResponse::make(['errors' => $errors], 400),
            ]),
        ),
    );

    expect($exception)->toBeInstanceOf(ValidationException::class)
        ->and($exception?->getMessage())->toBe('The Creem API request failed with status 400.')
        ->and($exception?->context())->toBe([
            'errors' => [
                'level_1' => [
                    'level_2' => [
                        'level_3' => [
                            'level_4' => '[truncated]',
                        ],
                    ],
                ],
            ],
        ]);
});

test('error contexts are sanitized before they are attached to exceptions', function (): void {
    $connector = new CreemConnector(new Config('sk_test_123'));
    $exception = HttpTestSupport::captureException(
        static fn (): Response => $connector->send(
            HttpTestSupport::pingRequest(),
            new MockClient([
                MockResponse::make([
                    'message' => 'Invalid customer data',
                    'email' => 'customer@example.com',
                    'errors' => [
                        'email' => ['Email is invalid.'],
                        'submitted_value' => 'customer@example.com',
                    ],
                ], 422),
            ]),
        ),
    );

    expect($exception)->toBeInstanceOf(ValidationException::class)
        ->and($exception?->context())->toBe([
            'message' => 'Invalid customer data',
            'errors' => [
                'email' => ['Email is invalid.'],
                'submitted_value' => '[redacted]',
            ],
        ]);
});

/**
 * @return array<string, array{
 *     0: MockResponse,
 *     1: class-string<CreemException>,
 *     2: string,
 *     3: int,
 *     4: array<string, mixed>
 * }>
 */
function connectorResponseFailureMappings(): array
{
    return [
        'unauthorized' => [
            MockResponse::make(['message' => 'Unauthorized'], 401),
            AuthenticationException::class,
            'Unauthorized',
            401,
            ['message' => 'Unauthorized'],
        ],
        'forbidden' => [
            MockResponse::make(['message' => 'Forbidden'], 403),
            AuthenticationException::class,
            'Forbidden',
            403,
            ['message' => 'Forbidden'],
        ],
        'not found' => [
            MockResponse::make(['message' => 'Missing'], 404),
            NotFoundException::class,
            'Missing',
            404,
            ['message' => 'Missing'],
        ],
        'validation status' => [
            MockResponse::make(['message' => 'Invalid'], 422),
            ValidationException::class,
            'Invalid',
            422,
            ['message' => 'Invalid'],
        ],
        'validation errors' => [
            MockResponse::make(['errors' => ['name' => ['Name is required.']]], 400),
            ValidationException::class,
            'Name is required.',
            400,
            ['errors' => ['name' => ['Name is required.']]],
        ],
        'rate limit' => [
            MockResponse::make(['message' => 'Slow down'], 429, ['Retry-After' => '7']),
            RateLimitException::class,
            'Slow down',
            429,
            ['message' => 'Slow down', 'retry_after_seconds' => 7],
        ],
        'server error' => [
            MockResponse::make('Internal server error', 500),
            ServerException::class,
            'Internal server error',
            500,
            ['body' => 'Internal server error'],
        ],
    ];
}
