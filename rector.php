<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Symfony\Symfony34\Rector\ClassMethod\MergeMethodAnnotationToRouteAnnotationRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/app',
        __DIR__ . '/archive',
        __DIR__ . '/bundles',
        __DIR__ . '/data',
        __DIR__ . '/doc',
        __DIR__ . '/docker',
        __DIR__ . '/engine',
        __DIR__ . '/node_modules',
        __DIR__ . '/tests',
        __DIR__ . '/util',
        __DIR__ . '/web',
    ]);

    // register a single rule
    $rectorConfig->rule(MergeMethodAnnotationToRouteAnnotationRector::class);

    // define sets of rules
    /*$rectorConfig->sets([
        LevelSetList::UP_TO_PHP_74
    ]);*/
};
