<?php

declare(strict_types=1);

namespace Creem\Internal\Http;

use Composer\InstalledVersions;
use Creem\Config;

use function class_exists;
use function implode;

final class UserAgent
{
    public static function forConfig(Config $config): string
    {
        $segments = [
            'creem-php-sdk/'.self::resolveSdkVersion(),
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
