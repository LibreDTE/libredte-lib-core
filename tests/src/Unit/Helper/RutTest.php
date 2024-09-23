<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada
 * por la Fundación para el Software Libre, ya sea la versión 3 de la Licencia,
 * o (a su elección) cualquier versión posterior de la misma.
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

use UnexpectedValueException;
use libredte\lib\Core\Helper\Rut;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Rut::class)]
class RutTest extends TestCase
{
    /**
     * Prueba para toArray() con RUTs válidos y con formato.
     *
     * Este test, al igual que muchos otros, se preocupa de entregar los datos
     * como se solicitaron, pero no hace validaciones adicionales. En este caso
     * el RUT 12.345.678-K no es un RUT válido, sin embargo el test pasa de
     * manera correcta porque lo que hace el método Rut::toArray() es solo
     * entregar los valores separados y formateados, pero NO valida. Esto mismo
     * ocurre en otros test. La validación de un RUT real (su DV) se prueba en
     * los tests testValidateValid???() usando el método Rut::validate().
     */
    public function testToArrayValid(): void
    {
        $this->assertEquals([12345678, 'K'], Rut::toArray('12.345.678-K'));
        $this->assertEquals([12345678, 'K'], Rut::toArray('12345678-K'));
        $this->assertEquals([9876543, '5'], Rut::toArray('9876543-5'));
    }

    /**
     * Prueba para format() con un RUT válido como string y como entero.
     */
    public function testFormatValid(): void
    {
        $this->assertEquals('12345678-K', Rut::format('12.345.678-K'));
        $this->assertEquals('12345678-5', Rut::format(12345678));
    }

    /**
     * Prueba para formatFull() con un RUT válido.
     */
    public function testFormatFullValid(): void
    {
        $this->assertEquals('12.345.678-K', Rut::formatFull('12345678-K'));
        $this->assertEquals('9.876.543-3', Rut::formatFull(9876543));
    }

    /**
     * Prueba para calculateDv() con varios RUTs válidos.
     */
    public function testCalculateDv(): void
    {
        $this->assertEquals('5', Rut::calculateDv(12345678));
        $this->assertEquals('3', Rut::calculateDv(9876543));
        $this->assertEquals('1', Rut::calculateDv(11111111));
    }

    /**
     * Prueba para validate() con RUTs válidos.
     */
    public function testValidateValid(): void
    {
        // No deberían lanzar excepción.
        Rut::validate('12.345.678-5');
        Rut::validate('9.876.543-3');

        $this->assertTrue(true);
    }

    /**
     * Prueba para validate() con RUTs incorrectos (DV incorrecto).
     */
    public function testValidateInvalid(): void
    {
        $this->expectException(UnexpectedValueException::class);

        // DV incorrecto, debería lanzar excepción.
        Rut::validate('12.345.678-K');
    }

    /**
     * Prueba para validate() con RUTs menores que el mínimo.
     */
    public function testValidateBelowMin(): void
    {
        $this->expectException(UnexpectedValueException::class);

        // Menor que el mínimo, debería lanzar excepción.
        Rut::validate('999.999-9');
    }

    /**
     * Prueba para validate() con RUTs mayores que el máximo.
     */
    public function testValidateAboveMax(): void
    {
        $this->expectException(UnexpectedValueException::class);

        // Mayor que el máximo, debería lanzar excepción.
        Rut::validate('100.000.000-0');
    }

    /**
     * Prueba para removeDv() con RUTs válidos.
     */
    public function testRemoveDv(): void
    {
        $this->assertEquals(12345678, Rut::removeDv('12.345.678-K'));
        $this->assertEquals(9876543, Rut::removeDv('9.876.543-5'));
    }

    /**
     * Prueba para addDv() con RUTs válidos.
     */
    public function testAddDv(): void
    {
        $this->assertEquals('123456785', Rut::addDv(12345678));
        $this->assertEquals('98765433', Rut::addDv(9876543));
    }
}
