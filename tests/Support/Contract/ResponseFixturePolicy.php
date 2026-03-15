<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Support\Contract;

use const FILTER_VALIDATE_URL;
use const PHP_URL_HOST;

use Antoniadisio\Creem\Tests\TestCase;

use function array_any;
use function filter_var;
use function is_array;
use function is_int;
use function is_string;
use function parse_url;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_starts_with;

final class ResponseFixturePolicy
{
    /**
     * @var non-empty-list<string>
     */
    private const array CANONICAL_ISO_TIMESTAMPS = [
        '2026-03-07T06:35:39.943Z',
        '2026-03-07T06:35:41.762Z',
        '2026-03-07T06:49:22.500Z',
        '2026-03-07T06:49:26.257Z',
        '2026-03-07T06:50:38.000Z',
        '2026-03-07T06:50:41.456Z',
        '2026-03-07T06:50:41.467Z',
        '2026-03-07T06:50:46.748Z',
        '2026-03-07T06:51:33.311Z',
        '2026-03-07T06:51:33.586Z',
        '2026-03-10T08:08:03.048Z',
        '2026-03-10T08:43:11.285Z',
        '2026-04-07T06:50:38.000Z',
    ];

    /**
     * @var non-empty-list<int>
     */
    private const array CANONICAL_UNIX_TIMESTAMPS = [
        1763337600000,
        1772866238000,
        1772866243426,
        1775544638000,
    ];

    /**
     * @var non-empty-list<string>
     */
    private const array PLACEHOLDER_IDENTIFIER_PREFIXES = [
        'ch',
        'cust',
        'dis',
        'feat',
        'lk',
        'lki',
        'ord',
        'pprice',
        'prod',
        'sitem',
        'sto',
        'sub',
        'tran',
    ];

    /**
     * @var non-empty-list<string>
     */
    private const array TIMESTAMP_PATH_SUFFIXES = [
        'timestamp',
        'created_at',
        'updated_at',
        'expires_at',
        'expiry_date',
        'last_transaction_date',
        'next_transaction_date',
        'current_period_start_date',
        'current_period_end_date',
        'period_start',
        'period_end',
    ];

    /**
     * @param  array<string, mixed>  $fixturePayload
     */
    public function assertSanitizedFixture(TestCase $testCase, string $fixture, array $fixturePayload): void
    {
        $this->assertSanitizedValue($testCase, $fixture, $fixturePayload, $fixture);
    }

    private function assertSanitizedValue(TestCase $testCase, string $fixture, mixed $value, string $path): void
    {
        if (is_array($value)) {
            foreach ($value as $key => $nestedValue) {
                $this->assertSanitizedValue($testCase, $fixture, $nestedValue, sprintf('%s.%s', $path, (string) $key));
            }

            return;
        }

        if (is_string($value)) {
            if (str_starts_with($value, 'sk_') || str_starts_with($value, 'creem_')) {
                $testCase->fail(sprintf('%s contains a live-looking secret at %s.', $fixture, $path));
            }

            if ($this->looksLikePlaceholderIdentifier($value)) {
                $testCase->assertMatchesRegularExpression(
                    $this->placeholderIdentifierPattern(),
                    $value,
                    sprintf('%s contains a non-placeholder fixture identifier at %s.', $fixture, $path),
                );
            }

            if (str_contains($value, '@')) {
                $testCase->assertMatchesRegularExpression(
                    '/^[a-z0-9._%+-]+@example\.test$/i',
                    $value,
                    sprintf('%s contains a non-sanitized email at %s.', $fixture, $path),
                );
            }

            if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
                $host = parse_url($value, PHP_URL_HOST);

                $testCase->assertIsString($host, sprintf('%s contains an invalid URL at %s.', $fixture, $path));
                $testCase->assertTrue(
                    $host === 'creem.io' || str_ends_with($host, '.example'),
                    sprintf('%s contains a non-placeholder URL host at %s.', $fixture, $path),
                );
            }

            if ($this->isCanonicalTimestampPath($path)) {
                $testCase->assertContains(
                    $value,
                    self::CANONICAL_ISO_TIMESTAMPS,
                    sprintf('%s contains a non-canonical timestamp at %s.', $fixture, $path),
                );
            }

            return;
        }

        if (is_int($value) && $this->isCanonicalTimestampPath($path)) {
            $testCase->assertContains(
                $value,
                self::CANONICAL_UNIX_TIMESTAMPS,
                sprintf('%s contains a non-canonical Unix timestamp at %s.', $fixture, $path),
            );
        }
    }

    private function isCanonicalTimestampPath(string $path): bool
    {
        return array_any(
            self::TIMESTAMP_PATH_SUFFIXES,
            static fn (string $suffix): bool => str_ends_with($path, '.'.$suffix),
        );
    }

    private function looksLikePlaceholderIdentifier(string $value): bool
    {
        return array_any(self::PLACEHOLDER_IDENTIFIER_PREFIXES, fn ($prefix): bool => str_starts_with($value, $prefix.'_'));
    }

    private function placeholderIdentifierPattern(): string
    {
        return '/^('.implode('|', self::PLACEHOLDER_IDENTIFIER_PREFIXES).')_fixture_[a-z0-9_]+$/';
    }
}
