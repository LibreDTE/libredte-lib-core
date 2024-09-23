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

use libredte\lib\Core\Helper\Date;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Date::class)]
class DateTest extends TestCase
{
    /**
     * Prueba para validateAndConvert() con fechas válidas en formato Y-m-d.
     */
    public function testValidateAndConvertValidDate(): void
    {
        $this->assertEquals('12/03/2023', Date::validateAndConvert('2023-03-12'));
        $this->assertEquals('31/12/2022', Date::validateAndConvert('2022-12-31'));
        $this->assertEquals('01/01/2020', Date::validateAndConvert('2020-01-01'));
    }

    /**
     * Prueba para validateAndConvert() con una fecha en formato Y-m-d pero con
     * un formato de salida personalizado.
     */
    public function testValidateAndConvertWithCustomFormat(): void
    {
        $this->assertEquals('12-03-2023', Date::validateAndConvert('2023-03-12', 'd-m-Y'));
        $this->assertEquals('31.12.2022', Date::validateAndConvert('2022-12-31', 'd.m.Y'));
    }

    /**
     * Prueba para validateAndConvert() con fechas inválidas.
     */
    public function testValidateAndConvertInvalidDate(): void
    {
        // Mes inválido.
        $this->assertNull(Date::validateAndConvert('2023-13-12'));

        // Formato inválido.
        $this->assertNull(Date::validateAndConvert('not-a-date'));

        // Formato incorrecto.
        $this->assertNull(Date::validateAndConvert('2023/03/12'));
    }

    /**
     * Prueba para validateAndConvert() con fechas en otros formatos que
     * deberían fallar.
     */
    public function testValidateAndConvertWithWrongFormat(): void
    {
        // Día primero en lugar de Año.
        $this->assertNull(Date::validateAndConvert('12/03/2023'));

        // Puntos en lugar de guiones.
        $this->assertNull(Date::validateAndConvert('2023.03.12'));
    }
}
