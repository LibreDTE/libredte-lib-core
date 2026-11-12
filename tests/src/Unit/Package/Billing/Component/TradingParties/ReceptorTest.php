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
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Receptor;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Contribuyente::class)]
#[CoversClass(Receptor::class)]
class ReceptorTest extends TestCase
{
    public function testConstructorAndToDteArray(): void
    {
        $receptor = new Receptor(
            rut: '12345678-5',
            razon_social: 'Cliente SpA',
            giro: 'Comercio',
            actividad_economica: 471100,
            telefono: '+56 9 8877 5544',
            email: 'cliente@example.com',
            direccion: 'Av. Siempre Viva 742',
            comuna: 'Providencia',
            ciudad: 'Santiago',
            codigo_interno: 'C-001',
        );

        $this->assertSame([
            'RUTRecep' => '12345678-5',
            'CdgIntRecep' => 'C-001',
            'RznSocRecep' => 'Cliente SpA',
            'Extranjero' => null,
            'GiroRecep' => 'Comercio',
            'Contacto' => '+56 9 8877 5544',
            'CorreoRecep' => 'cliente@example.com',
            'DirRecep' => 'Av. Siempre Viva 742',
            'CmnaRecep' => 'Providencia',
            'CiudadRecep' => 'Santiago',
        ], $receptor->toDteArray());
    }

    public function testEmptyReceptor(): void
    {
        $dte = (new Receptor('12345678-5'))->toDteArray();

        $this->assertSame('12345678-5', $dte['RUTRecep']);
        $this->assertSame('12345678-5', $dte['RznSocRecep']);
        foreach (['CdgIntRecep', 'Extranjero', 'GiroRecep', 'Contacto', 'CorreoRecep', 'DirRecep', 'CmnaRecep', 'CiudadRecep'] as $tag) {
            $this->assertNull($dte[$tag], $tag);
        }
    }

    public function testExtranjeroWithNationalityAndForeignIdentifier(): void
    {
        $receptor = new Receptor(
            rut: '66666666-6',
            nacionalidad: 'AR',
            identificador_extranjero: 'DNI-123',
        );

        $this->assertSame(
            ['NumId' => 'DNI-123', 'Nacionalidad' => 'AR'],
            $receptor->toDteArray()['Extranjero']
        );
    }

    public function testExtranjeroWithOnlyNationality(): void
    {
        $receptor = new Receptor('66666666-6', nacionalidad: 'AR');

        $this->assertSame(
            ['NumId' => null, 'Nacionalidad' => 'AR'],
            $receptor->toDteArray()['Extranjero']
        );
    }

    public function testExtranjeroWithOnlyForeignIdentifier(): void
    {
        $receptor = new Receptor('66666666-6', identificador_extranjero: 'DNI-123');

        $this->assertSame(
            ['NumId' => 'DNI-123', 'Nacionalidad' => null],
            $receptor->toDteArray()['Extranjero']
        );
    }

    public function testBlankExtranjeroValuesDoNotCreateTheTag(): void
    {
        $receptor = new Receptor(
            rut: '66666666-6',
            nacionalidad: '  ',
            identificador_extranjero: '',
        );

        $this->assertNull($receptor->getNacionalidad());
        $this->assertNull($receptor->getIdentificadorExtranjero());
        $this->assertNull($receptor->toDteArray()['Extranjero']);
    }

    public function testCorreoRecepPrefersEmailAndFallsBackToTheExchangeEmail(): void
    {
        $receptor = new Receptor('12345678-5');
        $this->assertNull($receptor->toDteArray()['CorreoRecep']);

        $receptor->setCorreoIntercambioDte('dte@example.com');
        $this->assertSame('dte@example.com', $receptor->toDteArray()['CorreoRecep']);

        $receptor->setEmail('cliente@example.com');
        $this->assertSame('cliente@example.com', $receptor->toDteArray()['CorreoRecep']);
    }

    public function testContactoIsTheMainPhone(): void
    {
        $receptor = new Receptor('12345678-5', telefono: '111');
        $receptor->setTelefono('222');

        $this->assertSame('222', $receptor->toDteArray()['Contacto']);
    }

    public function testValuesAreTrimmedAndBlankOnesBecomeNull(): void
    {
        $receptor = new Receptor('12345678-5', codigo_interno: '  C-9 ');

        $this->assertSame('C-9', $receptor->getCodigoInterno());

        $receptor->setCodigoInterno('   ');
        $this->assertNull($receptor->getCodigoInterno());
    }

    public function testToArray(): void
    {
        $array = (new Receptor(
            rut: '12345678-5',
            codigo_interno: 'C-1',
            nacionalidad: 'AR',
            identificador_extranjero: 'X-1',
        ))->toArray();

        $this->assertSame('12345678-5', $array['rut']);
        $this->assertSame('C-1', $array['codigo_interno']);
        $this->assertSame('AR', $array['nacionalidad']);
        $this->assertSame('X-1', $array['identificador_extranjero']);
    }
}
