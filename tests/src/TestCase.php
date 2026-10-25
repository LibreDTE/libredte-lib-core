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
    protected static function getFixturesPath(?string $path = null): string
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
