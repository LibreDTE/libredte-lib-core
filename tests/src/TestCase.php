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
 * Base class for all application tests.
 */
abstract class TestCase extends PHPUnitTestCase
{
    /**
     * Base directory for test resources (fixtures) used in tests.
     *
     * @var string
     */
    protected static string $fixturesPath = __DIR__ . '/../fixtures';

    /**
     * Returns the path to the base test resources (fixtures) directory,
     * or a path within that directory when `$path` is provided.
     *
     * @param string|null $path Path within `fixtures`.
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
                'Path does not exist: %s/%s',
                $fixturesPath,
                $path
            ));
        }

        return $fullpath;
    }
}
