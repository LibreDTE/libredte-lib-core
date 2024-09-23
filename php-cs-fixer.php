<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca Estándar en PHP (Núcleo).
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
    ->exclude('vendor');

return (new Config())
    // Permitir reglas riesgosas que cambian la lógica del código.
    ->setRiskyAllowed(true)
    // Basado en PSR-12, la última recomendación de estilo.
    ->setRules([
        '@PSR12' => true,
        // Convertir arrays a la sintaxis corta.
        'array_syntax' => ['syntax' => 'short'],
        // Ordenar los "use" alfabéticamente.
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        // Usar constructores de PHPUnit en lugar de métodos de fábrica.
        'php_unit_construct' => true,
        // Usar aserciones más estrictas en PHPUnit.
        'php_unit_strict' => true,
        // Añadir "declare(strict_types=1);" a los archivos.
        'declare_strict_types' => true,
        // Añadir comas finales en listas multilineales.
        'trailing_comma_in_multiline' => true,
        // Eliminar imports no usados.
        'no_unused_imports' => true,
        // Un import por declaración.
        'single_import_per_statement' => true,
        // Separar constantes y propiedades.
        // 'class_attributes_separation' => [
        //     'elements' => [
        //         'const' => 'one',
        //         'property' => 'one',
        //     ],
        // ],
        // Reemplazar strpos con un return de bool.
        'modernize_strpos' => true,
        // Reemplazar elseif con else if.
        'no_superfluous_elseif' => true,
        // Convertir funciones anónimas a funciones flecha.
        'use_arrow_functions' => true,
        // Indentar con espacios.
        'indentation_type' => true,
    ])
    ->setLineEnding("\n")
    ->setCacheFile($dir . '/var/cache/php-cs-fixer.cache')
    ->setFinder($finder)
;
