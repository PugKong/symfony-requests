<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create();
$finder->exclude('vendor');
$finder->in(__DIR__);

$config = new PhpCsFixer\Config();
$config->setRiskyAllowed(true);
$config->setFinder($finder);
$config->setRules([
    '@Symfony' => true,
    '@Symfony:risky' => true,
    '@PHP82Migration' => true,

    'declare_strict_types' => true,
    'global_namespace_import' => ['import_constants' => true, 'import_functions' => true, 'import_classes' => true],
    'trailing_comma_in_multiline' => [
        'after_heredoc' => true,
        'elements' => ['arguments', 'arrays', 'match', 'parameters'],
    ],
    'multiline_whitespace_before_semicolons' => ['strategy' => 'new_line_for_chained_calls'],
    'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],
]);

return $config;
