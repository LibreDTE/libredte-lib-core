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
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DocumentException;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test for the reader of the XML document of the document bag.
 */
#[CoversClass(DocumentBag::class)]
#[CoversClass(DocumentException::class)]
class DocumentBagXmlReaderTest extends TestCase
{
    public function testWithoutAnXmlDocumentThereIsNoReader(): void
    {
        $bag = new DocumentBag();

        $this->assertNull($bag->getXmlReader());
    }

    public function testRequiringTheReaderWithoutAnXmlDocumentThrows(): void
    {
        $bag = new DocumentBag();

        $this->expectException(DocumentException::class);

        $bag->requireXmlReader();
    }

    public function testTheReaderReadsTheXmlDocument(): void
    {
        $xml = new XmlDocument();
        $xml->loadXml('<DTE><Encabezado><IdDoc><Folio>0021</Folio></IdDoc></Encabezado></DTE>');
        $bag = new DocumentBag(xmlDocument: $xml);

        $this->assertSame('0021', $bag->getXmlReader()?->getString('//Encabezado/IdDoc/Folio'));
        $this->assertSame(21, $bag->requireXmlReader()->getAsInt('//Encabezado/IdDoc/Folio'));
    }
}
