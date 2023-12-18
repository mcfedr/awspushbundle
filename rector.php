<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\CodeQuality\Rector\ClassMethod\ActionSuffixRemoverRector;
use Rector\Symfony\Set\SensiolabsSetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Symfony\Symfony51\Rector\ClassMethod\CommandConstantReturnCodeRector;
use Rector\Symfony\Symfony53\Rector\Class_\CommandDescriptionToPropertyRector;
use Rector\Symfony\Symfony61\Rector\Class_\CommandPropertyToAttributeRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/src',
    ]);

    $rectorConfig->parallel(120, 1);

    // register a single rule
    $rectorConfig->rules([
        InlineConstructorDefaultToPropertyRector::class,
        ActionSuffixRemoverRector::class,
        CommandConstantReturnCodeRector::class,
        CommandDescriptionToPropertyRector::class,
        CommandPropertyToAttributeRector::class,
    ]);

    //     define sets of rules
    $rectorConfig->sets([
        SetList::DEAD_CODE,
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        DoctrineSetList::GEDMO_ANNOTATIONS_TO_ATTRIBUTES,
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        SensiolabsSetList::ANNOTATIONS_TO_ATTRIBUTES,
        SymfonySetList::SYMFONY_63,
//            SymfonySetList::SYMFONY_CODE_QUALITY,
//            LevelSetList::UP_TO_PHP_82
    ]);
};
