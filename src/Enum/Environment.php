<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Enum;

enum Environment: string
{
    case Production = 'production';
    case Test = 'test';

    public function baseUrl(): string
    {
        return match ($this) {
            self::Production => 'https://api.creem.io',
            self::Test => 'https://test-api.creem.io',
        };
    }
}
