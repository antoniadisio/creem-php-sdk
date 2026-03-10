<?php

declare(strict_types=1);

namespace Creem\Tests;

use Creem\Client;
use Creem\Config;
use Creem\Dto\Common\Page;
use Creem\Dto\Common\Pagination;
use Creem\Enum\Environment;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;

use function expect;
use function getenv;
use function is_numeric;
use function is_string;
use function trim;

abstract class SmokeTestCase extends TestCase
{
    public function smokeClient(#[\SensitiveParameter] ?string $apiKey = null): Client
    {
        return new Client(new Config(
            $apiKey ?? $this->requireSmokeApiKey(),
            Environment::Test,
            $this->smokeBaseUrl(),
            $this->smokeTimeout(),
            'pest-smoke',
        ));
    }

    public function requireSmokeApiKey(): string
    {
        $apiKey = $this->smokeApiKey();

        if ($apiKey === null) {
            $this->markTestSkipped('Set CREEM_TEST_API_KEY to run authenticated smoke tests against Environment::Test.');
        }

        return $apiKey;
    }

    public function requireOptionalSmokeValue(string $name, string $resourceCall): string
    {
        $value = $this->env($name);

        if ($value === null) {
            $this->markTestSkipped("Set {$name} to run the optional {$resourceCall} smoke check against Environment::Test.");
        }

        return $value;
    }

    /**
     * @template TItem of object
     *
     * @param  Page<TItem>  $page
     * @param  class-string<TItem>  $itemClass
     */
    public function assertTypedSmokePage(Page $page, string $itemClass): void
    {
        expect($page)->toBeInstanceOf(Page::class)
            ->and($page->pagination)->toBeInstanceOf(Pagination::class);

        $item = $page->get(0);

        if ($item !== null) {
            expect($item)->toBeInstanceOf($itemClass);
        }
    }

    /**
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
     */
    public function smokeWindow(): array
    {
        $end = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return [
            $end->sub(new DateInterval('P1D')),
            $end,
        ];
    }

    private function smokeApiKey(): ?string
    {
        return $this->env('CREEM_TEST_API_KEY');
    }

    private function smokeBaseUrl(): ?string
    {
        return $this->env('CREEM_TEST_BASE_URL');
    }

    private function smokeTimeout(): float
    {
        $timeout = $this->env('CREEM_TEST_TIMEOUT');

        if ($timeout === null || ! is_numeric($timeout)) {
            return 10.0;
        }

        return (float) $timeout;
    }

    private function env(string $name): ?string
    {
        $value = getenv($name);

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
