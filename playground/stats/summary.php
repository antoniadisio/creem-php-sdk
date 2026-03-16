<?php

declare(strict_types=1);

use Antoniadisio\Creem\Client;
use Antoniadisio\Creem\Dto\Stats\GetStatsSummaryRequest;
use Antoniadisio\Creem\Enum\CurrencyCode;
use Antoniadisio\Creem\Enum\StatsInterval;
use Playground\Support\Playground;

$request = static function (array $values): GetStatsSummaryRequest {
    $currency = Playground::enumValue(
        CurrencyCode::class,
        Playground::value($values, 'stats.summary.currency'),
        'stats.summary.currency',
    );
    $intervalValue = Playground::value($values, 'stats.summary.interval');

    return new GetStatsSummaryRequest(
        currency: $currency,
        startDate: Playground::nullableDateTime(
            Playground::value($values, 'stats.summary.startDate'),
            'stats.summary.startDate',
        ),
        endDate: Playground::nullableDateTime(
            Playground::value($values, 'stats.summary.endDate'),
            'stats.summary.endDate',
        ),
        interval: $intervalValue === null
            ? null
            : Playground::enumValue(StatsInterval::class, $intervalValue, 'stats.summary.interval'),
    );
};

return [
    'resource' => 'stats',
    'action' => 'summary',
    'operation_mode' => 'read',
    'sdk_call' => '$client->stats()->summary(new GetStatsSummaryRequest(...))',
    'http_method' => 'GET',
    'path' => '/v1/stats/summary',
    'fixtures' => 'stats_summary.json',
    'required_values' => [
        'shared.apiKey',
        'stats.summary.currency',
    ],
    'defaults' => [
        'stats' => [
            'summary' => [
                'currency' => 'USD',
                'startDate' => null,
                'endDate' => null,
                'interval' => null,
            ],
        ],
    ],
    'inputs' => [
        Playground::field('stats.summary.currency', 'Currency', 'enum', enum: CurrencyCode::class),
        Playground::field('stats.summary.startDate', 'Start date', 'nullable-string', nullable: true),
        Playground::field('stats.summary.endDate', 'End date', 'nullable-string', nullable: true),
        Playground::field('stats.summary.interval', 'Interval', 'enum', nullable: true, enum: StatsInterval::class),
    ],
    'idempotency_key_path' => null,
    'persist_outputs' => [],
    'build_inputs' => static fn (array $values): array => [
        'currency' => Playground::value($values, 'stats.summary.currency'),
        'startDate' => Playground::value($values, 'stats.summary.startDate'),
        'endDate' => Playground::value($values, 'stats.summary.endDate'),
        'interval' => Playground::value($values, 'stats.summary.interval'),
    ],
    'build_request_payload' => static fn (array $values): array => $request($values)->toQuery(),
    'run' => static fn (Client $client, array $values) => $client->stats()->summary($request($values)),
];
