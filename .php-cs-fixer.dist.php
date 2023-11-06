<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/test',
    ]);

$config = new PhpCsFixer\Config();
$config
    ->setFinder($finder)
    ->setUsingCache(false)
    ->setRules([
        '@PSR12' => true,
        '@PHP80Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
        'trailing_comma_in_multiline' => ['elements' => ['arguments', 'arrays', 'parameters']],
        'binary_operator_spaces' => true,
        'whitespace_after_comma_in_array' => ['ensure_single_space' => true],
        'yoda_style' => true,
    ]);

return $config;
