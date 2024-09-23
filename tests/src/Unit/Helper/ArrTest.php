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

namespace libredte\lib\Tests\Unit\Helper;

use libredte\lib\Core\Helper\Arr;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Arr::class)]
class ArrTest extends TestCase
{
    public function testMergeRecursiveDistinctSimple(): void
    {
        $array1 = ['a' => 1, 'b' => 2];
        $array2 = ['b' => 3, 'c' => 4];

        $expected = ['a' => 1, 'b' => 3, 'c' => 4];
        $this->assertSame($expected, Arr::mergeRecursiveDistinct($array1, $array2));
    }

    public function testMergeRecursiveDistinctNested(): void
    {
        $array1 = [
            'a' => ['a1' => 1, 'a2' => 2],
            'b' => 2
        ];
        $array2 = [
            'a' => ['a2' => 3, 'a3' => 4],
            'c' => 5
        ];

        $expected = [
            'a' => ['a1' => 1, 'a2' => 3, 'a3' => 4],
            'b' => 2,
            'c' => 5
        ];
        $this->assertSame($expected, Arr::mergeRecursiveDistinct($array1, $array2));
    }

    public function testMergeRecursiveDistinctOverwriting(): void
    {
        $array1 = ['a' => 1, 'b' => ['b1' => 2, 'b2' => 3]];
        $array2 = ['b' => ['b2' => 4, 'b3' => 5]];

        $expected = [
            'a' => 1,
            'b' => ['b1' => 2, 'b2' => 4, 'b3' => 5]
        ];
        $this->assertSame($expected, Arr::mergeRecursiveDistinct($array1, $array2));
    }

    public function testMergeRecursiveDistinctEmptyArray(): void
    {
        $array1 = ['a' => 1];
        $array2 = [];

        $expected = ['a' => 1];
        $this->assertSame($expected, Arr::mergeRecursiveDistinct($array1, $array2));
    }

    public function testMergeRecursiveDistinctBothEmpty(): void
    {
        $array1 = [];
        $array2 = [];

        $expected = [];
        $this->assertSame($expected, Arr::mergeRecursiveDistinct($array1, $array2));
    }
}
