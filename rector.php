<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelLevelSetList;

// Rector proposes automated rewrites; it does not apply anything unless you
// run `vendor/bin/rector process` (default `vendor/bin/rector` is dry-run).
// Always review the diff before committing - especially in this app, which
// moves real money.
return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withSkip([
        __DIR__.'/app/Modules/*/Views',
    ])
    ->withPhpSets(php82: true)
    ->withSets([
        LaravelLevelSetList::UP_TO_LARAVEL_120,
    ])
    ->withImportNames(removeUnusedImports: true)
    ->withCache(cacheDirectory: __DIR__.'/storage/framework/rector');
