<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Unit;

use Antoniadisio\Creem\Exception\TransportException;
use Antoniadisio\Creem\Internal\Http\ResponseDecoder;
use Antoniadisio\Creem\Tests\Support\HttpTestSupport;
use Saloon\Http\Faking\MockResponse;

foreach (blankResponseBodies() as $dataset => [$body]) {
    test("response decoder returns empty payloads for blank bodies ({$dataset})", function () use ($body): void {
        $response = HttpTestSupport::successResponse(MockResponse::make($body, 200, ['Content-Type' => 'application/json']));

        expect(ResponseDecoder::decode($response))->toBe([]);
    });
}

foreach (nonObjectJsonPayloads() as $dataset => [$body]) {
    test("response decoder rejects non-object json payloads ({$dataset})", function () use ($body): void {
        $response = HttpTestSupport::successResponse(MockResponse::make($body, 200, ['Content-Type' => 'application/json']));

        expect(static fn (): array => ResponseDecoder::decode($response))
            ->toThrow(TransportException::class, 'The Creem API returned an unexpected JSON payload shape.');
    });
}

test('response decoder normalizes invalid json to a transport exception', function (): void {
    $response = HttpTestSupport::successResponse(
        MockResponse::make('{"broken"', 200, ['Content-Type' => 'application/json']),
    );

    expect(static fn (): array => ResponseDecoder::decode($response))
        ->toThrow(TransportException::class, 'The Creem API returned an invalid JSON response.');
});

/**
 * @return array<string, array{0: string}>
 */
function blankResponseBodies(): array
{
    return [
        'empty string' => [''],
        'whitespace only' => [" \n\t "],
    ];
}

/**
 * @return array<string, array{0: string}>
 */
function nonObjectJsonPayloads(): array
{
    return [
        'json list' => ['[]'],
        'json scalar' => ['"ok"'],
    ];
}
