<?php

$finder = PhpCsFixer\Finder::create()
    ->in(['src', 'tests']);

$config = new PhpCsFixer\Config();

return $config
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => true,
        'yoda_style' => false,
        'declare_strict_types' => true,
        'native_constant_invocation' => false,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
