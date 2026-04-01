<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

$config = new PhpCsFixer\Config();

return $config
    ->setRules([
        '@PSR12' => true,
        '@PSR12:risky' => true,
        'declare_strict_types' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'no_superfluous_phpdoc_tags' => true,
        'void_return' => true,
        'no_unused_imports' => true,
        'single_quote' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => true,
        'no_trailing_whitespace' => true,
        'no_whitespace_in_blank_line' => true,
        'ordered_imports' => true,
        'modernize_types_casting' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unneeded_control_parentheses' => true,
        'simplified_null_return' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setLineEnding("\n");
