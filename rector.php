<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPreparedSets(deadCode: true, codeQuality: true, codingStyle: true, typeDeclarations: true, privatization: true, naming: true, instanceOf: true, earlyReturn: true, strictBooleans: true)
    ->withPhpSets()
    ->withImportNames(removeUnusedImports: true)
    ->withAttributesSets(phpunit: true)
    ->withPaths([
        __DIR__ . '/src',
    ]);
