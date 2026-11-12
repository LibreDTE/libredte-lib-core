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

use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiEnvironment;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\AutorizacionDte;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Contribuyente::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(AutorizacionDte::class)]
#[CoversClass(SiiEnvironment::class)]
class EmisorTest extends TestCase
{
    public function testConstructorWithScalarsAndToDteArray(): void
    {
        $emisor = new Emisor(
            rut: '76192083-9',
            razon_social: 'Empresa de Prueba SpA',
            giro: 'Servicios de informatica',
            actividad_economica: 620200,
            telefono: '+56 2 2345 6789',
            email: 'contacto@example.com',
            direccion: 'Calle 123',
            comuna: 'Santiago',
            ciudad: 'Santiago',
            sucursal: 'Casa Matriz',
            codigo_sucursal: 123,
            vendedor: 'V-01',
        );

        $this->assertSame([
            'RUTEmisor' => '76192083-9',
            'RznSoc' => 'Empresa de Prueba SpA',
            'GiroEmis' => 'Servicios de informatica',
            'Telefono' => '+56 2 2345 6789',
            'CorreoEmisor' => 'contacto@example.com',
            'Acteco' => 620200,
            'Sucursal' => 'Casa Matriz',
            'CdgSIISucur' => 123,
            'DirOrigen' => 'Calle 123',
            'CmnaOrigen' => 'Santiago',
            'CiudadOrigen' => 'Santiago',
            'CdgVendedor' => 'V-01',
        ], $emisor->toDteArray());
    }

    public function testConstructorWithListsOfActividadesAndTelefonos(): void
    {
        $emisor = new Emisor(
            rut: '76192083-9',
            actividad_economica: [620200, 620300],
            telefono: ['+56 2 1111 1111', '+56 2 2222 2222'],
        );

        $this->assertSame([620200, 620300], $emisor->getActividadesEconomicas());
        $this->assertSame(['+56 2 1111 1111', '+56 2 2222 2222'], $emisor->getTelefonos());
        $this->assertSame([620200, 620300], $emisor->toDteArray()['Acteco']);
        $this->assertSame(
            ['+56 2 1111 1111', '+56 2 2222 2222'],
            $emisor->toDteArray()['Telefono']
        );
    }

    public function testEmptyEmisorHasNullsInDteArray(): void
    {
        $dte = (new Emisor('76192083-9'))->toDteArray();

        $this->assertSame('76192083-9', $dte['RUTEmisor']);
        $this->assertSame('76192083-9', $dte['RznSoc']);
        foreach (['GiroEmis', 'Telefono', 'CorreoEmisor', 'Acteco', 'Sucursal', 'CdgSIISucur', 'DirOrigen', 'CmnaOrigen', 'CiudadOrigen', 'CdgVendedor'] as $tag) {
            $this->assertNull($dte[$tag], $tag);
        }
    }

    public function testAddActividadEconomicaDoesNotDuplicate(): void
    {
        $emisor = new Emisor('76192083-9');
        $emisor->addActividadEconomica(100)->addActividadEconomica(200)->addActividadEconomica(100);

        $this->assertSame([100, 200], $emisor->getActividadesEconomicas());
    }

    public function testSetActividadesEconomicasReplacesThePreviousOnes(): void
    {
        $emisor = new Emisor('76192083-9', actividad_economica: 100);
        $emisor->setActividadesEconomicas([300, 400]);

        $this->assertSame([300, 400], $emisor->getActividadesEconomicas());
    }

    public function testAddTelefonoDoesNotDuplicate(): void
    {
        $emisor = new Emisor('76192083-9');
        $emisor->addTelefono('111')->addTelefono('222')->addTelefono('111');

        $this->assertSame(['111', '222'], $emisor->getTelefonos());
    }

    public function testSetTelefonosReplacesThePreviousOnes(): void
    {
        $emisor = new Emisor('76192083-9', telefono: '111');
        $emisor->setTelefonos(['333', '444']);

        $this->assertSame(['333', '444'], $emisor->getTelefonos());
    }

    /**
     * @return array<string, array{list<int>, int|list<int>|null}>
     */
    public static function provideActeco(): array
    {
        return [
            'none' => [[], null],
            'one' => [[100], 100],
            'two' => [[100, 200], [100, 200]],
            'four' => [[1, 2, 3, 4], [1, 2, 3, 4]],
            'more than four' => [[1, 2, 3, 4, 5, 6], [1, 2, 3, 4]],
        ];
    }

    /**
     * @param list<int> $actividades
     * @param int|list<int>|null $expected
     */
    #[DataProvider('provideActeco')]
    public function testActecoTagOfTheDteIsLimitedToFourActividades(array $actividades, int|array|null $expected): void
    {
        $emisor = new Emisor('76192083-9');
        $emisor->setActividadesEconomicas($actividades);

        $this->assertSame($expected, $emisor->toDteArray()['Acteco']);
    }

    /**
     * @return array<string, array{list<string>, string|list<string>|null}>
     */
    public static function provideTelefono(): array
    {
        return [
            'none' => [[], null],
            'one' => [['111'], '111'],
            'two' => [['111', '222'], ['111', '222']],
            'more than two' => [['111', '222', '333'], ['111', '222']],
        ];
    }

    /**
     * @param list<string> $telefonos
     * @param string|list<string>|null $expected
     */
    #[DataProvider('provideTelefono')]
    public function testTelefonoTagOfTheDteIsLimitedToTwoNumbers(array $telefonos, string|array|null $expected): void
    {
        $emisor = new Emisor('76192083-9');
        $emisor->setTelefonos($telefonos);

        $this->assertSame($expected, $emisor->toDteArray()['Telefono']);
    }

    public function testActividadesKeepAllTheValuesWhenARepeatedOneIsSetAgain(): void
    {
        $emisor = new Emisor('76192083-9', actividad_economica: [100, 200]);

        // 100 ya estaba: pasa a ser la principal, sin perder la 200.
        $emisor->setActividadEconomica(100);

        $this->assertSame([100, 200], $emisor->getActividadesEconomicas());
        $this->assertSame([100, 200], $emisor->toDteArray()['Acteco']);
    }

    public function testTelefonosKeepAllTheValuesWhenARepeatedOneIsSetAgain(): void
    {
        $emisor = new Emisor('76192083-9', telefono: ['111', '222']);

        $emisor->setTelefono('111');

        $this->assertSame(['111', '222'], $emisor->getTelefonos());
        $this->assertSame(['111', '222'], $emisor->toDteArray()['Telefono']);
    }

    public function testSetActividadEconomicaMakesItThePrimaryOne(): void
    {
        $emisor = new Emisor('76192083-9', actividad_economica: 100);
        $emisor->setActividadEconomica(200);

        $this->assertSame(200, $emisor->getActividadEconomica());
        $this->assertSame([200, 100], $emisor->getActividadesEconomicas());
    }

    public function testCorreoEmisorPrefersEmailAndFallsBackToTheExchangeEmail(): void
    {
        $emisor = new Emisor('76192083-9');
        $this->assertNull($emisor->toDteArray()['CorreoEmisor']);

        $emisor->setCorreoIntercambioDte('dte@example.com');
        $this->assertSame('dte@example.com', $emisor->toDteArray()['CorreoEmisor']);

        $emisor->setEmail('contacto@example.com');
        $this->assertSame('contacto@example.com', $emisor->toDteArray()['CorreoEmisor']);
    }

    public function testBlankValuesBecomeNull(): void
    {
        $emisor = new Emisor(
            rut: '76192083-9',
            sucursal: '   ',
            codigo_sucursal: 0,
            vendedor: "  \t",
        );
        $emisor->setCorreoIntercambioDte('  ');

        $this->assertNull($emisor->getSucursal());
        $this->assertNull($emisor->getCodigoSucursal());
        $this->assertNull($emisor->getVendedor());
        $this->assertNull($emisor->getCorreoIntercambioDte());
    }

    public function testValuesAreTrimmed(): void
    {
        $emisor = new Emisor('76192083-9', sucursal: '  Sucursal 1 ', vendedor: ' V-9 ');

        $this->assertSame('Sucursal 1', $emisor->getSucursal());
        $this->assertSame('V-9', $emisor->getVendedor());
    }

    public function testToArrayWithoutAutorizacionNorLogo(): void
    {
        $array = (new Emisor('76192083-9', sucursal: 'S', codigo_sucursal: 5, vendedor: 'V'))->toArray();

        $this->assertSame('76192083-9', $array['rut']);
        $this->assertSame('S', $array['sucursal']);
        $this->assertSame(5, $array['codigo_sucursal']);
        $this->assertSame('V', $array['vendedor']);
        $this->assertNull($array['autorizacion_dte']);
        $this->assertNull($array['logo']);
    }

    public function testToArrayWithAutorizacionAndLogo(): void
    {
        $emisor = new Emisor(
            rut: '76192083-9',
            autorizacion_dte: new AutorizacionDte('2020-01-15', 80),
            logo: 'logo.png',
        );

        $array = $emisor->toArray();

        $this->assertSame(
            ['fecha_resolucion' => '2020-01-15', 'numero_resolucion' => 80],
            $array['autorizacion_dte']
        );
        $this->assertSame('logo.png', $array['logo']);
        $this->assertSame(SiiEnvironment::STAGING, $emisor->getAutorizacionDte()?->getEnvironment());
    }

    public function testAutorizacionWithResolutionNumberZeroIsProduction(): void
    {
        $emisor = new Emisor('76192083-9', autorizacion_dte: new AutorizacionDte('2020-01-15'));

        $this->assertSame(SiiEnvironment::PRODUCTION, $emisor->getAutorizacionDte()?->getEnvironment());

        $emisor->setAutorizacionDte(null);
        $this->assertNull($emisor->getAutorizacionDte());
    }
}
