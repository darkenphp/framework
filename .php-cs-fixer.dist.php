<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
;

$config = new PhpCsFixer\Config();
return $config->setRules([
    'declare_strict_types' => true,
    '@PSR12' => true,
    'array_syntax' => ['syntax' => 'short'],
    'ordered_imports' => ['sort_algorithm' => 'alpha'],
    'no_unused_imports' => true,
    'ternary_operator_spaces' => true,
    'trailing_comma_in_multiline' => true,
    'trim_array_spaces' => true,
    'unary_operator_spaces' => true,
    'global_namespace_import' => [
        'import_classes' => true,
        'import_functions' => true,
        'import_constants' => true,
    ],
    'return_assignment' => true,
    'semicolon_after_instruction' => true,
    'short_scalar_cast' => true,
    'blank_lines_before_namespace' => true,
    'single_class_element_per_statement' => true,
    'single_line_comment_style' => true,
    'single_quote' => true,
    'space_after_semicolon' => true,
    'standardize_not_equals' => true,
    'dir_constant' => true,
    'is_null' => true,
    'no_homoglyph_names' => true,
    'no_null_property_initialization' => true,
    'no_php4_constructor' => true,
    'no_useless_else' => true,
    'non_printable_character' => true,
    'ordered_class_elements' => true,
    'php_unit_construct' => true,
    'pow_to_exponentiation' => true,
    '@PSR2' => true,
    '@PHP83Migration' => true,
    'class_attributes_separation' => true,
    'random_api_migration' => true,
    'self_accessor' => true,
])
->setFinder($finder)
->setRiskyAllowed(true)
;
