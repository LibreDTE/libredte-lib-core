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

namespace libredte\lib\Tests\Unit\Package\Billing\Component\Document\Support;

use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\SobreEnvio;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DispatcherException;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentEnvelope;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Receptor;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Test for the data of the document envelope that it requires.
 */
#[CoversClass(DocumentEnvelope::class)]
#[CoversClass(DispatcherException::class)]
#[UsesClass(SobreEnvio::class)]
#[UsesClass(Contribuyente::class)]
#[UsesClass(Emisor::class)]
#[UsesClass(Receptor::class)]
class DocumentEnvelopeRequireTest extends TestCase
{
    public function testTheSobreEnvioIsRequired(): void
    {
        $envelope = new DocumentEnvelope();
        $sobreEnvio = new SobreEnvio(new XmlDocument());

        $envelope->setSobreEnvio($sobreEnvio);

        $this->assertSame($sobreEnvio, $envelope->requireSobreEnvio());
    }

    public function testWithoutSobreEnvioItThrows(): void
    {
        $this->expectException(DispatcherException::class);

        (new DocumentEnvelope())->requireSobreEnvio();
    }

    public function testTheEmisorIsRequired(): void
    {
        $envelope = new DocumentEnvelope();
        $emisor = new Emisor('76192083-9');

        $envelope->setEmisor($emisor);

        $this->assertSame($emisor, $envelope->requireEmisor());
    }

    public function testWithoutEmisorItThrows(): void
    {
        $this->expectException(DispatcherException::class);

        (new DocumentEnvelope())->requireEmisor();
    }

    public function testTheReceptorIsRequired(): void
    {
        $envelope = new DocumentEnvelope();
        $receptor = new Receptor('60803000-K');

        $envelope->setReceptor($receptor);

        $this->assertSame($receptor, $envelope->requireReceptor());
    }

    public function testWithoutReceptorItThrows(): void
    {
        $this->expectException(DispatcherException::class);

        (new DocumentEnvelope())->requireReceptor();
    }

    public function testTheCaratulaIsRequired(): void
    {
        $envelope = new DocumentEnvelope();

        $envelope->setCaratula(['RutEmisor' => '76192083-9']);

        $this->assertSame(['RutEmisor' => '76192083-9'], $envelope->requireCaratula());
    }

    public function testWithoutCaratulaItThrows(): void
    {
        $this->expectException(DispatcherException::class);

        (new DocumentEnvelope())->requireCaratula();
    }
}
