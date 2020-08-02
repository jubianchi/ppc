<?php

declare(strict_types=1);

$year = date('Y');
$header = <<<HEAD
This file is part of PPC.

Copyright © $year Julien Bianchi <contact@jubianchi.fr>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
HEAD;

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/benchmarks')
    ->in(__DIR__ . '/parsers')
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
;

return (new PhpCsFixer\Config())
    ->setCacheFile(__DIR__.'/tmp/php_cs_cache.json')
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_useless_else' => true,
        'no_useless_return' => true,
        'nullable_type_declaration_for_default_null_value' => ['use_nullable_type_declaration' => true],
        'ordered_class_elements' => true,
        'header_comment' => [
            'comment_type' => 'PHPDoc',
            'header' => $header,
            'location' => 'after_open',
            'separate' => 'bottom',
        ],
        'php_unit_size_class' => ['group' => 'small'],
        'php_unit_internal_class' => ['types' => ['normal', 'final']],
        'php_unit_method_casing' => ['case' => 'camel_case'],
        'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],
    ])
    ->setFinder($finder)
;
