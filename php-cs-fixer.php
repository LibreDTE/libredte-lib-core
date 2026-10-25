<?php

declare(strict_types=1);

/**
 * LibreDTE: Library for invoicing in Chile (Core).
 * Copyright (C) 2026 LibreDTE <https://www.libredte.cl>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Configuration file for PHP CS Fixer.
 */

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$dir = __DIR__;

$finder = Finder::create()
    ->in($dir)
    ->exclude('var')
;

return (new Config())
    // Allow risky rules that may change code logic.
    ->setRiskyAllowed(true)
    // Based on PSR-12, the latest style recommendation.
    ->setRules([
        '@PSR12' => true,
        // Add "declare(strict_types=1);" to files.
        // This adds them as <?php declare(strict_types=1); however, it is
        // recommended to edit or add them manually in separate lines.
        'declare_strict_types' => true,
        // Indent with spaces.
        'indentation_type' => true,
        // Sort "use" statements alphabetically.
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
        ],
        // Remove unused imports.
        'no_unused_imports' => true,
        // One import per statement.
        'single_import_per_statement' => true,
        // Convert arrays to short "[]" syntax.
        'array_syntax' => [
            'syntax' => 'short',
        ],
        // Add trailing commas to multiline arrays.
        'trailing_comma_in_multiline' => true,
        // Separate constants and properties.
        'class_attributes_separation' => [
            'elements' => [
                'const' => 'one',
                'property' => 'one',
                'method' => 'one',
            ],
        ],
        // Replace strpos with a boolean return.
        // Example: use str_contains().
        'modernize_strpos' => true,
        // Convert anonymous functions to arrow functions.
        'use_arrow_functions' => true,
        // Use PHPUnit constructors instead of factory methods.
        'php_unit_construct' => true,
        // Use stricter assertions in PHPUnit.
        // Example: use assertSame() instead of assertEquals().
        'php_unit_strict' => true,
    ])
    ->setLineEnding("\n")
    ->setCacheFile($dir . '/var/cache/php-cs-fixer.cache')
    ->setFinder($finder)
;
