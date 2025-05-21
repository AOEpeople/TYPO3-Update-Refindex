<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withAutoloadPaths([__DIR__ . '/../Classes'])
    ->withCache('.cache/rector/upgrade/')
    ->withImportNames(false)
    ->withPhpSets(true)
    ->withPaths([
        __DIR__ . '/../Classes',
        __DIR__ . '/../Configuration',
        __DIR__ . '/../Resources',
        __DIR__ . '/../Tests',
        __DIR__ . '/../code-quality',
    ])
    ->withSets([
        SetList::PHP_84,

        // add custom sets here
    ])
    ->withRules([
        // add custom rules here
    ])
    ->withSkip([
        // add custom skips here
    ]);
