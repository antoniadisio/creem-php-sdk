<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Tests\Support\Contract;

use function array_map;
use function basename;
use function dirname;
use function glob;
use function sort;

final class ResponseFixtureCatalog
{
    public function directory(): string
    {
        return dirname(__DIR__, 2).'/Fixtures/Responses';
    }

    public function path(string $fixture): string
    {
        return $this->directory().'/'.$fixture;
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        $fixtures = array_map(
            basename(...),
            $this->paths(),
        );

        sort($fixtures);

        return $fixtures;
    }

    /**
     * @return list<string>
     */
    public function paths(): array
    {
        $paths = glob($this->directory().'/*.json');

        if ($paths === false) {
            return [];
        }

        sort($paths);

        return $paths;
    }
}
