<?php

declare(strict_types=1);

namespace Antoniadisio\Creem\Internal\Http;

use Antoniadisio\Creem\Config;
use Composer\InstalledVersions;

use function class_exists;
use function implode;

final class UserAgent
{
    public static function forConfig(Config $config): string
    {
        $segments = [
            'creem-php/'.self::resolveSdkVersion(),
            'php/'.PHP_VERSION,
        ];

        if ($config->userAgentSuffix() !== null) {
            $segments[] = $config->userAgentSuffix();
        }

        return implode(' ', $segments);
    }

    private static function resolveSdkVersion(): string
    {
        if (! class_exists(InstalledVersions::class)) {
            return 'unknown';
        }

        $version = InstalledVersions::getRootPackage()['pretty_version'];

        return $version !== '' ? $version : 'unknown';
    }
}
