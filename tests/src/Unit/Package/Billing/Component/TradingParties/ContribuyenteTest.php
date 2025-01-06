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

namespace libredte\lib\Tests\Unit\Package\Billing\Component\TradingParties;

use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test funcional para la clase Contribuyente.
 */
#[CoversClass(Contribuyente::class)]
class ContribuyenteTest extends TestCase
{
    public function testContribuyenteData(): void
    {
        $contribuyente = new Contribuyente(
            rut: '12345678-5',
            razon_social: 'Test Razon Social',
            giro: 'Comercio',
            actividad_economica: 123,
            telefono: '+56 9 88775544',
            email: 'test@example.com',
            direccion: '123 Calle Falsa',
            comuna: 'Santiago'
        );

        $this->assertSame('12345678-5', $contribuyente->getRut());
        $this->assertSame('Test Razon Social', $contribuyente->getRazonSocial());
        $this->assertSame('Comercio', $contribuyente->getGiro());
        $this->assertSame(123, $contribuyente->getActividadEconomica());
        $this->assertSame('+56 9 88775544', $contribuyente->getTelefono());
        $this->assertSame('test@example.com', $contribuyente->getEmail());
        $this->assertSame('123 Calle Falsa', $contribuyente->getDireccion());
        $this->assertSame('Santiago', $contribuyente->getComuna());
    }

    public function testContribuyenteSinRazonSocial(): void
    {
        $rut = '76192083-9';
        $contribuyente = new Contribuyente($rut);
        $this->assertSame($rut, $contribuyente->getRazonSocial());
    }
}
