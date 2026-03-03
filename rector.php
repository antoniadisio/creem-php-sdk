<?php

declare(strict_types=1);

use Rector\Config\Level\TypeDeclarationLevel;
use Rector\Config\RectorConfig;

$publicApiTypeDeclarationSkips = array_fill_keys(TypeDeclarationLevel::RULES, [
    __DIR__.'/src/Client.php',
    __DIR__.'/src/Config.php',
    __DIR__.'/src/Resource/*',
]);

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withoutParallel()
    ->withPhpSets(php82: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        instanceOf: true,
        phpunitCodeQuality: true,
    )
    ->withSkip($publicApiTypeDeclarationSkips)
    ->withComposerBased(phpunit: true);
