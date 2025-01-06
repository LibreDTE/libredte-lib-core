<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada por
 * la Fundación para el Software Libre, ya sea la versión 3 de la Licencia, o
 * (a su elección) cualquier versión posterior de la misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero SIN
 * GARANTÍA ALGUNA; ni siquiera la garantía implícita MERCANTIL o de APTITUD
 * PARA UN PROPÓSITO DETERMINADO. Consulte los detalles de la Licencia Pública
 * General Affero de GNU para obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de
 * GNU junto a este programa.
 *
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

/**
 * Archivo de configuración para PHP CS Fixer.
 */

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$dir = __DIR__;

$finder = Finder::create()
    ->in($dir)
    ->name('*.php')
    ->exclude('vendor')
;

return (new Config())
    // Permitir reglas riesgosas que cambian la lógica del código.
    ->setRiskyAllowed(true)
    // Basado en PSR-12, la última recomendación de estilo.
    ->setRules([
        '@PSR12' => true,
        // Añadir "declare(strict_types=1);" a los archivos.
        // Esto los añade así <?php declare(strict_types=1); sin embargo se
        // recomienda editarlos o añadirlos manualmente en líneas separadas.
        'declare_strict_types' => true,
        // Indentar con espacios.
        'indentation_type' => true,
        // Ordenar los "use" alfabéticamente.
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
        ],
        // Eliminar imports no usados.
        'no_unused_imports' => true,
        // Un import por declaración.
        'single_import_per_statement' => true,
        // Convertir arreglos a la sintaxis corta "[]".
        'array_syntax' => [
            'syntax' => 'short',
        ],
        // Añadir comas finales en arreglos de varias líneas.
        'trailing_comma_in_multiline' => true,
        // Separar constantes y propiedades.
        'class_attributes_separation' => [
            'elements' => [
                'const' => 'one',
                'property' => 'one',
                'method' => 'one',
            ],
        ],
        // Reemplazar strpos con un return de bool.
        // Ejemplo usar: str_contains().
        'modernize_strpos' => true,
        // Convertir funciones anónimas a funciones flecha.
        'use_arrow_functions' => true,
        // Usar constructores de PHPUnit en lugar de métodos de fábrica.
        'php_unit_construct' => true,
        // Usar aserciones más estrictas en PHPUnit.
        // Ejemplo: usar assertSame() en vez de assertEquals().
        'php_unit_strict' => true,
    ])
    ->setLineEnding("\n")
    ->setCacheFile($dir . '/var/cache/php-cs-fixer.cache')
    ->setFinder($finder)
;
