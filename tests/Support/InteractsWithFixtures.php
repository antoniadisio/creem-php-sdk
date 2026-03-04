<?php

declare(strict_types=1);

namespace Creem\Tests\Support;

use JsonException;

use function array_replace;
use function dirname;
use function file_get_contents;
use function json_decode;
use function sprintf;

trait InteractsWithFixtures
{
    public function fixturesDirectory(): string
    {
        return dirname(__DIR__).'/Fixtures/Responses';
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function fixture(string $name): array
    {
        $contents = file_get_contents($this->fixturesDirectory().'/'.$name);

        $this->assertNotFalse($contents, sprintf('Fixture %s could not be read.', $name));

        /** @var array<string, mixed> $fixture */
        $fixture = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return $fixture;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function responseFixture(string $fixture, array $overrides = []): array
    {
        return array_replace($this->fixture($fixture), $overrides);
    }
}
