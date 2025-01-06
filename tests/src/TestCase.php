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

namespace libredte\lib\Tests;

use LogicException;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Clase base para todos los tests de la aplicación.
 */
abstract class TestCase extends PHPUnitTestCase
{
    /**
     * Directorio base para los recursos de prueba (fixtures) que se usarán en
     * los tests.
     *
     * @var string
     */
    protected static string $fixturesPath = __DIR__ . '/../fixtures';

    /**
     * Obtiene la ruta hacia el directorio base de recursos de prueba (fixtures)
     * o una ruta dentro de este directorio si se provee `$path`.
     *
     * @param string|null $path Ruta dentro de `fixtures`.
     * @return string
     */
    protected static function getFixturesPath(string $path = null): string
    {
        $fixturesPath = realpath(static::$fixturesPath);

        if ($path === null) {
            return $fixturesPath;
        }

        $fullpath = realpath(sprintf(
            '%s/%s',
            $fixturesPath,
            $path
        ));

        if ($fullpath === false) {
            throw new LogicException(sprintf(
                'No existe la ruta: %s/%s',
                $fixturesPath,
                $path
            ));
        }

        return $fullpath;
    }
}
