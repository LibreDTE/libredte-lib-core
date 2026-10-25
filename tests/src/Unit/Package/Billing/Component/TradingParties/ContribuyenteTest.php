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

namespace libredte\lib\Tests\Unit\Package\Billing\Component\TradingParties;

use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Functional test for the Contribuyente class.
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
