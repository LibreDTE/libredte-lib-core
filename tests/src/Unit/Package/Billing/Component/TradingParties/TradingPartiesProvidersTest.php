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

use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\PersonaNatural;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Receptor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\ContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\EmisorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\ReceptorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\DummyEmisorProvider;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\DummyReceptorProvider;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeEmisorProvider;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeReceptorProvider;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(ContribuyenteFactory::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(ReceptorFactory::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(Receptor::class)]
#[CoversClass(PersonaNatural::class)]
#[CoversClass(DummyEmisorProvider::class)]
#[CoversClass(DummyReceptorProvider::class)]
#[CoversClass(FakeEmisorProvider::class)]
#[CoversClass(FakeReceptorProvider::class)]
class TradingPartiesProvidersTest extends TestCase
{
    public function testFactoryWithoutRutUsesTheRutOfAnUnidentifiedParty(): void
    {
        $contribuyente = (new ContribuyenteFactory())->create(['razon_social' => 'Sin identificar']);

        $this->assertSame('66666666-6', $contribuyente->getRut());
        $this->assertSame(66666666, $contribuyente->getRutAsInt());
    }

    public function testFactoryCompletesTheCheckDigitOfARutGivenAsInteger(): void
    {
        $this->assertSame('76192083-9', (new ContribuyenteFactory())->create(['rut' => 76192083])->getRut());
        $this->assertSame('76192083-9', (new EmisorFactory())->create(['RUTEmisor' => 76192083])->getRut());
        $this->assertSame('60803000-K', (new ReceptorFactory())->create(['RUTRecep' => 60803000])->getRut());
    }

    public function testContribuyenteSerializesToJsonLikeToArray(): void
    {
        $contribuyente = (new ContribuyenteFactory())->create(['rut' => '76192083-9', 'razon_social' => 'SASCO SpA']);

        $this->assertSame($contribuyente->toArray(), $contribuyente->jsonSerialize());
        $this->assertSame((string) json_encode($contribuyente->toArray()), json_encode($contribuyente));
    }

    public function testNaturalPersonSerializesToJsonLikeToArray(): void
    {
        $persona = new PersonaNatural('12345678-5', 'Juan Pérez', 'juan@example.com');

        $this->assertSame($persona->toArray(), $persona->jsonSerialize());
        $this->assertSame('juan@example.com', $persona->jsonSerialize()['email']);
    }

    public function testDummyProvidersCreateThePartyFromARutAndKeepAGivenInstance(): void
    {
        $emisorProvider = new DummyEmisorProvider(new EmisorFactory());
        $receptorProvider = new DummyReceptorProvider(new ReceptorFactory());

        $this->assertSame('76192083-9', $emisorProvider->retrieve('76192083-9')->getRut());
        $this->assertSame('76192083-9', $emisorProvider->retrieve(76192083)->getRut());
        $this->assertSame('60803000-K', $receptorProvider->retrieve('60803000-K')->getRut());
        $this->assertSame('60803000-K', $receptorProvider->retrieve(60803000)->getRut());

        $emisor = new Emisor('12345678-5', 'Propio SpA');
        $receptor = new Receptor('12345678-5', 'Cliente SpA');
        $this->assertSame($emisor, $emisorProvider->retrieve($emisor));
        $this->assertSame($receptor, $receptorProvider->retrieve($receptor));
    }

    public function testFakeProvidersStandardizeTheParties(): void
    {
        $emisor = (new FakeEmisorProvider(new EmisorFactory()))->retrieve('12345678-5');
        $this->assertSame('76192083-9', $emisor->getRut());
        $this->assertSame('SASCO SpA', $emisor->getRazonSocial());
        $this->assertSame('libredte', $emisor->getVendedor());

        $receptor = (new FakeReceptorProvider(new ReceptorFactory()))->retrieve(12345678);
        $this->assertSame('60803000-K', $receptor->getRut());
        $this->assertSame('Servicio de Impuestos Internos', $receptor->getRazonSocial());
    }
}
